<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaseScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Settings (Admin)
 */
class BaseScoreController extends Controller
{
    /**
     * Получить базовые баллы
     *
     * @group Settings (Admin)
     *
     * @response 200 [{"id": 1, "category_id": 1, "activity_type": "external", "has_places": true, "has_implementation": null, "is_leader": false, "first_place": 25, "second_place": 20, "third_place": 15, "participation": 10, "uta_level": 2, "is_active": true, "category": {"id": 1, "name": "Проектная"}}]
     */
    public function index(): JsonResponse
    {
        return response()->json(BaseScore::with('category')->get());
    }

    /**
     * Создать базовые баллы
     *
     * @group Settings (Admin)
     *
     * @bodyParam category_id integer required ID категории. Example: 1
     * @bodyParam activity_type string nullable Тип активности. Example: "external"
     * @bodyParam has_places boolean Призовые места. Example: true
     * @bodyParam has_implementation boolean nullable Внедрение. Example: true
     * @bodyParam is_leader boolean Лидер студ. лаборатории. Example: false
     * @bodyParam first_place integer nullable Баллы за 1 место. Example: 25
     * @bodyParam second_place integer nullable Баллы за 2 место. Example: 20
     * @bodyParam third_place integer nullable Баллы за 3 место. Example: 15
     * @bodyParam participation integer required Баллы за участие. Example: 10
     * @bodyParam uta_level integer required Уровень требований активности (1–5). Example: 2
     * @bodyParam is_active boolean Активность. Example: true
     *
     * @response 201 {"id": 1, "category_id": 1, "activity_type": "external", "has_places": true, "has_implementation": null, "is_leader": false, "first_place": 25, "second_place": 20, "third_place": 15, "participation": 10, "uta_level": 2, "is_active": true}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:activity_categories,id'],
            'activity_type' => ['nullable', 'string', 'max:50'],
            'has_places' => ['sometimes', 'boolean'],
            'has_implementation' => ['sometimes', 'boolean', 'nullable'],
            'is_leader' => ['sometimes', 'boolean'],
            'first_place' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'second_place' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'third_place' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'participation' => ['required', 'integer', 'min:0'],
            'uta_level' => ['required', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item = BaseScore::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Обновить базовые баллы
     *
     * @group Settings (Admin)
     *
     * @urlParam id integer required ID записи. Example: 1
     *
     * @bodyParam category_id integer ID категории. Example: 1
     * @bodyParam activity_type string nullable Тип активности. Example: "external"
     * @bodyParam has_places boolean Призовые места. Example: true
     * @bodyParam has_implementation boolean nullable Внедрение. Example: true
     * @bodyParam is_leader boolean Лидер студ. лаборатории. Example: false
     * @bodyParam first_place integer nullable Баллы за 1 место. Example: 25
     * @bodyParam second_place integer nullable Баллы за 2 место. Example: 20
     * @bodyParam third_place integer nullable Баллы за 3 место. Example: 15
     * @bodyParam participation integer Баллы за участие. Example: 10
     * @bodyParam uta_level integer Уровень требований активности (1–5). Example: 2
     * @bodyParam is_active boolean Активность. Example: true
     *
     * @response 200 {"id": 1, "category_id": 1, "activity_type": "external", "has_places": true, "has_implementation": null, "is_leader": false, "first_place": 25, "second_place": 20, "third_place": 15, "participation": 10, "uta_level": 2, "is_active": true}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = BaseScore::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:activity_categories,id'],
            'activity_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'has_places' => ['sometimes', 'boolean'],
            'has_implementation' => ['sometimes', 'boolean', 'nullable'],
            'is_leader' => ['sometimes', 'boolean'],
            'first_place' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'second_place' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'third_place' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'participation' => ['sometimes', 'integer', 'min:0'],
            'uta_level' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * Удалить базовые баллы
     *
     * @group Settings (Admin)
     *
     * @urlParam id integer required ID записи. Example: 1
     *
     * @response 200 {"message": "Запись удалена."}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     */
    public function destroy(int $id): JsonResponse
    {
        BaseScore::findOrFail($id)->delete();

        return response()->json(['message' => 'Запись удалена.']);
    }
}
