<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Settings (Admin)
 */
class EducationLevelController extends Controller
{
    /**
     * Получить уровни обучения
     *
     * @group Settings (Admin)
     *
     * @response 200 [{"id": 1, "name": "Колледж", "nuo_value": 1}, {"id": 2, "name": "Бакалавриат", "nuo_value": 2}]
     */
    public function index(): JsonResponse
    {
        return response()->json(EducationLevel::all());
    }

    /**
     * Обновить уровень обучения
     *
     * @group Settings (Admin)
     *
     * @urlParam id integer required ID уровня обучения. Example: 1
     *
     * @bodyParam name string Наименование. Example: "Колледж"
     * @bodyParam nuo_value integer Значение НУО. Example: 1
     *
     * @response 200 {"id": 1, "name": "Колледж", "nuo_value": 1}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $level = EducationLevel::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'nuo_value' => ['sometimes', 'integer', 'min:0'],
        ]);

        $level->update($validated);

        return response()->json($level);
    }
}
