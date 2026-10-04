<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KsuMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Settings (Admin)
 */
class KsuMatrixController extends Controller
{
    /**
     * Получить матрицу КСУ
     *
     * @group Settings (Admin)
     *
     * @response 200 [{"id": 1, "delta": -2, "coefficient": 0.8, "description": "УТА значительно ниже НУО", "is_active": true}]
     */
    public function index(): JsonResponse
    {
        return response()->json(KsuMatrix::all());
    }

    /**
     * Создать запись матрицы КСУ
     *
     * @group Settings (Admin)
     *
     * @bodyParam delta integer required Разница УТА − НУО. Example: 1
     * @bodyParam coefficient number required Коэффициент КСУ. Example: 1.1
     * @bodyParam description string nullable Описание. Example: "УТА выше НУО"
     * @bodyParam is_active boolean Активность. Example: true
     *
     * @response 201 {"id": 1, "delta": 1, "coefficient": 1.1, "description": "УТА выше НУО", "is_active": true}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delta' => ['required', 'integer', 'between:-5,5'],
            'coefficient' => ['required', 'decimal:0,2', 'between:0.01,9.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item = KsuMatrix::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Обновить запись матрицы КСУ
     *
     * @group Settings (Admin)
     *
     * @urlParam id integer required ID записи. Example: 1
     *
     * @bodyParam delta integer Разница УТА − НУО. Example: 2
     * @bodyParam coefficient number Коэффициент КСУ. Example: 1.2
     * @bodyParam description string nullable Описание. Example: "УТА значительно выше НУО"
     * @bodyParam is_active boolean Активность. Example: true
     *
     * @response 200 {"id": 1, "delta": 2, "coefficient": 1.2, "description": "УТА значительно выше НУО", "is_active": true}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = KsuMatrix::findOrFail($id);

        $validated = $request->validate([
            'delta' => ['sometimes', 'integer', 'between:-5,5'],
            'coefficient' => ['sometimes', 'decimal:0,2', 'between:0.01,9.99'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * Удалить запись матрицы КСУ
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
        KsuMatrix::findOrFail($id)->delete();

        return response()->json(['message' => 'Запись удалена.']);
    }
}
