<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use Illuminate\Http\JsonResponse;

/**
 * @group Activities
 */
class ActivityCategoryController extends Controller
{
    /**
     * Получить все категории мероприятий
     *
     * Возвращает список всех доступных категорий.
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $categories = ActivityCategory::select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }
}
