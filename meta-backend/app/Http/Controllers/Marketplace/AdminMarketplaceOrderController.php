<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @group Marketplace
 */
class AdminMarketplaceOrderController extends Controller
{
    /**
     * Получить покупки маркетплейса
     *
     * Возвращает покупки студентов для выдачи и историю выданных товаров. (ResponseCalls broken)
     *
     * @group Marketplace (Admin)
     *
     * @queryParam status string optional Фильтр по статусу: pending, issued, cancelled. Example: pending
     * @queryParam search string optional Поиск по ФИО студента, группе, email или названию товара. Example: Иванов
     * @queryParam sort string optional Поле сортировки: created_at, issued_at, item_score_snapshot. Example: created_at
     * @queryParam direction string optional Направление сортировки: asc или desc. Example: desc
     * @queryParam per_page integer optional Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page":1,"data":[{"id":1,"user_id":2,"marketplace_id":5,"item_name_snapshot":"Блокнот","item_score_snapshot":15,"status":"pending","issued_at":null,"issued_by":null,"cancelled_at":null,"cancelled_by":null,"created_at":"2026-05-04T10:00:00.000000Z","updated_at":"2026-05-04T10:00:00.000000Z","student_last_name":"Иванов","student_first_name":"Иван","student_middle_name":"Иванович","student_full_name":"Иванов Иван Иванович","student_email":"student@i.ru","student_group":"К2Т","issuer_full_name":null,"canceller_full_name":null}],"last_page":1,"per_page":20,"total":1}
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in([
                MarketplaceOrder::STATUS_PENDING,
                MarketplaceOrder::STATUS_ISSUED,
                MarketplaceOrder::STATUS_CANCELLED,
            ])],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', Rule::in(['created_at', 'issued_at', 'item_score_snapshot'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = MarketplaceOrder::query()
            ->leftJoin('users as students', 'marketplace_orders.user_id', '=', 'students.id')
            ->leftJoin('members as student_members', 'student_members.user_id', '=', 'students.id')
            ->leftJoin('users as issuers', 'marketplace_orders.issued_by', '=', 'issuers.id')
            ->leftJoin('members as issuer_members', 'issuer_members.user_id', '=', 'issuers.id')
            ->leftJoin('users as cancellers', 'marketplace_orders.cancelled_by', '=', 'cancellers.id')
            ->leftJoin('members as canceller_members', 'canceller_members.user_id', '=', 'cancellers.id')
            ->select('marketplace_orders.*')
            ->selectRaw('student_members.last_name as student_last_name')
            ->selectRaw('student_members.first_name as student_first_name')
            ->selectRaw('student_members.middle_name as student_middle_name')
            ->selectRaw("TRIM(CONCAT_WS(' ', student_members.last_name, student_members.first_name, student_members.middle_name)) as student_full_name")
            ->selectRaw('students.email as student_email')
            ->selectRaw('student_members.student_group as student_group')
            ->selectRaw("TRIM(CONCAT_WS(' ', issuer_members.last_name, issuer_members.first_name, issuer_members.middle_name)) as issuer_full_name")
            ->selectRaw("TRIM(CONCAT_WS(' ', canceller_members.last_name, canceller_members.first_name, canceller_members.middle_name)) as canceller_full_name");

        if (! empty($validated['status'])) {
            $query->where('marketplace_orders.status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $search = '%'.$validated['search'].'%';
            $query->where(function ($query) use ($search): void {
                $query->where('marketplace_orders.item_name_snapshot', 'like', $search)
                    ->orWhere('students.email', 'like', $search)
                    ->orWhere('student_members.last_name', 'like', $search)
                    ->orWhere('student_members.first_name', 'like', $search)
                    ->orWhere('student_members.middle_name', 'like', $search)
                    ->orWhere('student_members.student_group', 'like', $search);
            });
        }

        $sortMap = [
            'created_at' => 'marketplace_orders.created_at',
            'issued_at' => 'marketplace_orders.issued_at',
            'item_score_snapshot' => 'marketplace_orders.item_score_snapshot',
        ];

        $orders = $query
            ->orderBy($sortMap[$validated['sort'] ?? 'created_at'], $validated['direction'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($orders);
    }

    /**
     * Обновить статус покупки
     *
     * Разрешает только переход pending в issued или cancelled.
     *
     * @group Marketplace (Admin)
     *
     * @urlParam order integer required ID покупки. Example: 1
     *
     * @bodyParam status string required Новый статус: issued или cancelled. Example: issued
     *
     * @response 200 scenario="Success" {"message":"Покупка отмечена как выданная."}
     * @response 200 scenario="Cancelled" {"message":"Покупка отменена, баллы возвращены."}
     * @response 404 scenario="Not found" {"error":"Запись не найдена"}
     * @response 422 scenario="Unavailable transition" {"message":"Изменение статуса недоступно."}
     */
    public function update(Request $request, MarketplaceOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                MarketplaceOrder::STATUS_PENDING,
                MarketplaceOrder::STATUS_ISSUED,
                MarketplaceOrder::STATUS_CANCELLED,
            ])],
        ]);

        $result = DB::transaction(function () use ($request, $order, $validated): array {
            $lockedOrder = MarketplaceOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== MarketplaceOrder::STATUS_PENDING) {
                return ['status' => 422, 'message' => 'Изменение статуса недоступно.'];
            }

            if ($validated['status'] === MarketplaceOrder::STATUS_ISSUED) {
                $lockedOrder->forceFill([
                    'status' => MarketplaceOrder::STATUS_ISSUED,
                    'issued_at' => now(),
                    'issued_by' => $request->user()->id,
                ])->save();

                return ['status' => 200, 'message' => 'Покупка отмечена как выданная.'];
            }

            if ($validated['status'] === MarketplaceOrder::STATUS_CANCELLED) {
                $user = User::query()
                    ->whereKey($lockedOrder->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $user->previous_semester_score += $lockedOrder->item_score_snapshot;
                $user->save();

                $lockedOrder->forceFill([
                    'status' => MarketplaceOrder::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_by' => $request->user()->id,
                ])->save();

                return ['status' => 200, 'message' => 'Покупка отменена, баллы возвращены.'];
            }

            return ['status' => 422, 'message' => 'Изменение статуса недоступно.'];
        });

        if ($result['status'] !== 200) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json(['message' => $result['message']]);
    }
}
