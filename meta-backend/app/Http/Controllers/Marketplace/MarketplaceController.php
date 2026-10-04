<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketplaceRequest;
use App\Models\Marketplace;
use App\Support\PublicUploadPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Marketplace
 */
class MarketplaceController extends Controller
{
    /**
     * Получить товары для администратора
     *
     * Возвращает каталог товаров с поиском, сортировкой и пагинацией. (ResponseCalls broken)
     *
     * @group Marketplace (Admin)
     *
     * @queryParam search string optional Поиск по названию товара. Example: книга
     * @queryParam sort string optional Поле сортировки: id, name, score, created_at. Example: created_at
     * @queryParam direction string optional Направление сортировки: asc или desc. Example: desc
     * @queryParam per_page integer optional Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page":1,"data":[{"id":1,"name":"Блокнот","score":15,"photo_path":"marketplace/2026-05-04/blocknote.jpg","is_archive":false,"creator_id":1,"created_at":"2026-05-04T10:00:00.000000Z","updated_at":"2026-05-04T10:00:00.000000Z"}],"last_page":1,"per_page":20,"total":1}
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', Rule::in(['id', 'name', 'score', 'created_at'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Marketplace::query();

        if (! empty($validated['search'])) {
            $query->where('name', 'like', '%'.$validated['search'].'%');
        }

        $marketplace = $query
            ->orderBy($validated['sort'] ?? 'id', $validated['direction'] ?? 'asc')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($marketplace);
    }

    /**
     * Получить товары для студента
     *
     * Возвращает только активные товары, доступные для покупки студентом. (ResponseCalls broken)
     *
     * @group Marketplace (Student)
     *
     * @queryParam page integer optional Номер страницы. Example: 1
     * @queryParam per_page integer optional Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page":1,"data":[{"id":1,"name":"Блокнот","score":15,"photo_path":"marketplace/2026-05-04/blocknote.jpg","is_archive":false,"creator_id":1,"created_at":"2026-05-04T10:00:00.000000Z","updated_at":"2026-05-04T10:00:00.000000Z"}],"last_page":1,"per_page":20,"total":1}
     */
    public function studentIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $marketplace = Marketplace::query()
            ->where('is_archive', false)
            ->orderBy('id', 'desc')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($marketplace);
    }

    /**
     * Создать товар
     *
     * Создаёт товар маркетплейса от имени текущего администратора.
     *
     * @group Marketplace (Admin)
     *
     * @bodyParam name string required Название товара. Example: Новая книга
     * @bodyParam score integer required Стоимость товара в баллах. Example: 20
     * @bodyParam is_archive boolean optional Архивный товар. Example: false
     * @bodyParam photo file optional Фото товара (jpg, jpeg или png, максимум 2 МБ).
     *
     * @response 201 scenario="Created" {"message":"Запись успешно создана!","data":{"id":1,"name":"Новая книга","score":20,"photo_path":"marketplace/2026-05-04/book.jpg","is_archive":false}}
     * @response 422 scenario="Validation error" {"message":"Ошибка валидации","errors":{"name":["Поле name обязательно для заполнения."]}}
     */
    public function store(MarketplaceRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store(PublicUploadPath::marketplaceProduct(), 'public');
        }

        $marketplace = Marketplace::create([
            'name' => $data['name'],
            'score' => $data['score'],
            'photo_path' => $data['photo_path'] ?? null,
            'is_archive' => $data['is_archive'] ?? false,
            'creator_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Запись успешно создана!',
            'data' => $marketplace,
        ], 201);
    }

    /**
     * Получить товар
     *
     * Возвращает данные одного товара маркетплейса.
     *
     * @group Marketplace (Admin)
     *
     * @urlParam product integer required ID товара. Example: 1
     */
    public function show(Marketplace $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Обновить товар
     *
     * Обновляет поля товара маркетплейса.
     *
     * @group Marketplace (Admin)
     *
     * @urlParam product integer required ID товара. Example: 1
     *
     * @bodyParam name string optional Название товара. Example: Обновленное название
     * @bodyParam score integer optional Стоимость товара в баллах. Example: 25
     * @bodyParam is_archive boolean optional Архивный товар. Example: false
     * @bodyParam photo file optional Новое фото товара (jpg, jpeg или png, максимум 2 МБ).
     *
     * @response 200 scenario="Success" {"message":"Запись успешно обновлена!","data":{"id":1,"name":"Обновленное название","score":25,"photo_path":"marketplace/2026-05-04/book.jpg","is_archive":false}}
     * @response 422 scenario="Validation error" {"message":"Ошибка валидации","errors":{"score":["Поле score должно быть целым числом."]}}
     */
    public function update(MarketplaceRequest $request, Marketplace $product): JsonResponse
    {
        $data = $request->validated();
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store(PublicUploadPath::marketplaceProduct(), 'public');
        }

        $product->fill($data);
        $product->save();

        return response()->json([
            'message' => 'Запись успешно обновлена!',
            'data' => $product,
        ]);
    }

    /**
     * Удалить товар
     *
     * Удаляет товар маркетплейса. История покупок сохраняет снимок товара.
     *
     * @group Marketplace (Admin)
     *
     * @urlParam product integer required ID товара. Example: 1
     *
     * @response 200 scenario="Success" {"message":"Запись успешно удалена!"}
     * @response 404 scenario="Not found" {"error":"Запись не найдена"}
     */
    public function destroy(Marketplace $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Запись успешно удалена!']);
    }
}
