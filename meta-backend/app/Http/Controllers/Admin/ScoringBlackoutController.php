<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScoringBlackoutUpdateRequest;
use App\Models\ScoringBlackoutSetting;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;

/**
 * Периоды запрета подтверждения баллов и привязки портфолио к мероприятиям.
 */
class ScoringBlackoutController extends Controller
{
    /**
     * Получить границы периодов запрета
     *
     * Возвращает зимний и летний интервалы в формате MM-DD.
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     */
    public function show(): JsonResponse
    {
        $setting = ScoringBlackoutSetting::query()->firstOrFail();

        return response()->json([
            'data' => [
                'winter_start' => $setting->winter_start,
                'winter_end' => $setting->winter_end,
                'summer_start' => $setting->summer_start,
                'summer_end' => $setting->summer_end,
            ],
        ]);
    }

    /**
     * Обновить периоды запрета
     *
     * Сохраняет границы зимнего и летнего blackout; проверяется формат MM-DD и валидность даты.
     *
     * @group Settings (Admin)
     *
     * @bodyParam winter_start string required Начало зимнего периода (MM-DD). Example: 12-26
     * @bodyParam winter_end string required Конец зимнего периода (MM-DD). Example: 01-31
     * @bodyParam summer_start string required Начало летнего периода (MM-DD). Example: 06-26
     * @bodyParam summer_end string required Конец летнего периода (MM-DD). Example: 08-31
     *
     * @response 200 {
     *   "message": "Периоды запрета успешно обновлены.",
     *   "data": {
     *     "winter_start": "12-26",
     *     "winter_end": "01-31",
     *     "summer_start": "06-26",
     *     "summer_end": "08-31"
     *   }
     * }
     * @response 422 {
     *   "message": "Ошибка валидации",
     *   "errors": {}
     * }
     */
    public function update(ScoringBlackoutUpdateRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $setting = ScoringBlackoutSetting::query()->first();
        if (! $setting) {
            $setting = new ScoringBlackoutSetting();
        }

        $setting->fill($payload);
        $setting->save();

        SemesterService::clearCache();

        return response()->json([
            'message' => 'Периоды запрета успешно обновлены.',
            'data' => [
                'winter_start' => $setting->winter_start,
                'winter_end' => $setting->winter_end,
                'summer_start' => $setting->summer_start,
                'summer_end' => $setting->summer_end,
            ],
        ]);
    }
}
