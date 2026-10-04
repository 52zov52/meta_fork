<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @group Marketplace
 */
class StudentMarketplaceOrderController extends Controller
{
    private const SUCCESS_MESSAGE = 'Покупка успешна.';

    /**
     * Получить свои покупки
     *
     * Возвращает историю покупок текущего студента. (ResponseCalls broken)
     *
     * @group Marketplace (Student)
     *
     * @queryParam status string optional Фильтр по статусу: pending, issued, cancelled. Example: pending
     * @queryParam sort string optional Поле сортировки: created_at, processed_at, item_score_snapshot. Example: created_at
     * @queryParam direction string optional Направление сортировки: asc или desc. Example: desc
     * @queryParam per_page integer optional Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page":1,"data":[{"id":10,"user_id":2,"marketplace_id":1,"item_name_snapshot":"Пенал","item_score_snapshot":20,"status":"pending","issued_at":null,"issued_by":null,"cancelled_at":null,"cancelled_by":null,"created_at":"2026-05-04T10:00:00.000000Z","updated_at":"2026-05-04T10:00:00.000000Z"}],"last_page":1,"per_page":20,"total":1}
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in([
                MarketplaceOrder::STATUS_PENDING,
                MarketplaceOrder::STATUS_ISSUED,
                MarketplaceOrder::STATUS_CANCELLED,
            ])],
            'sort' => ['sometimes', 'string', Rule::in(['created_at', 'processed_at', 'item_score_snapshot'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = MarketplaceOrder::query()
            ->select('marketplace_orders.*')
            ->selectRaw('COALESCE(issued_at, cancelled_at) as processed_at')
            ->where('user_id', $request->user()->id);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $sortMap = [
            'created_at' => 'marketplace_orders.created_at',
            'processed_at' => DB::raw('COALESCE(marketplace_orders.issued_at, marketplace_orders.cancelled_at)'),
            'item_score_snapshot' => 'marketplace_orders.item_score_snapshot',
        ];

        $orders = $query
            ->orderBy($sortMap[$validated['sort'] ?? 'created_at'], $validated['direction'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($orders);
    }

    /**
     * Создать покупку
     *
     * Списывает баллы текущего студента и создаёт заказ со статусом pending.
     *
     * @group Marketplace (Student)
     *
     * @bodyParam marketplace_id integer required ID товара. Example: 1
     *
     * @response 201 scenario="Created" {"message":"Покупка успешна.","data":{"id":10,"status":"pending","previous_semester_score":55}}
     * @response 404 scenario="Not found" {"message":"Товар не найден."}
     * @response 422 scenario="Archived product" {"message":"Товар недоступен для покупки."}
     * @response 422 scenario="Not enough score" {"message":"Недостаточно баллов для покупки этого товара."}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace_id' => ['required', 'integer'],
        ]);

        $result = DB::transaction(function () use ($request, $validated): array {
            $user = User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Marketplace::query()
                ->whereKey($validated['marketplace_id'])
                ->lockForUpdate()
                ->first();

            if (! $product) {
                return ['status' => 404, 'message' => 'Товар не найден.'];
            }

            if ((bool) $product->is_archive) {
                return ['status' => 422, 'message' => 'Товар недоступен для покупки.'];
            }

            if ($user->previous_semester_score < $product->score) {
                return ['status' => 422, 'message' => 'Недостаточно баллов для покупки этого товара.'];
            }

            $user->previous_semester_score -= $product->score;
            $user->save();

            $order = MarketplaceOrder::create([
                'user_id' => $user->id,
                'marketplace_id' => $product->id,
                'item_name_snapshot' => $product->name,
                'item_score_snapshot' => $product->score,
                'status' => MarketplaceOrder::STATUS_PENDING,
            ]);

            return [
                'status' => 201,
                'order' => $order,
                'previous_semester_score' => $user->previous_semester_score,
            ];
        });

        if (($result['status'] ?? 201) !== 201) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => self::SUCCESS_MESSAGE,
            'data' => [
                'id' => $result['order']->id,
                'status' => $result['order']->status,
                'previous_semester_score' => $result['previous_semester_score'],
            ],
        ], 201);
    }
}
