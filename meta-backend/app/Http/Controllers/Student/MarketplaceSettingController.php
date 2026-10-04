<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Настройки маркетплейса
 *
 * @group Marketplace
 */
class MarketplaceSettingController extends Controller
{
    /**
     * Получить настройки маркетплейса
     *
     * Возвращает текст подсказки о том, где забрать товар. Для пользователей с типом учреждения "Другое" всегда возвращает пустую строку.
     *
     * @group Marketplace (Student)
     */
    public function show(Request $request): JsonResponse
    {
        if ((int) $request->user()->member?->institution_id === 4) {
            return response()->json([
                'data' => [
                    'pickup_hint' => '',
                ],
            ]);
        }

        $setting = MarketplaceSetting::first();

        return response()->json([
            'data' => [
                'pickup_hint' => $setting?->pickup_hint ?? '',
            ],
        ]);
    }
}
