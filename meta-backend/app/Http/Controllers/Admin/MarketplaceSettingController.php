<?php

namespace App\Http\Controllers\Admin;

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
     * Возвращает текст подсказки о том, где забрать товар.
     *
     * @group Marketplace (Admin)
     */
    public function show(): JsonResponse
    {
        $setting = MarketplaceSetting::first();

        return response()->json([
            'data' => [
                'pickup_hint' => $setting?->pickup_hint ?? '',
            ],
        ]);
    }

    /**
     * Обновить настройки маркетплейса
     *
     * Сохраняет текст подсказки, который показывается студентам при обмене баллов на товары.
     *
     * @group Marketplace (Admin)
     *
     * @bodyParam pickup_hint string nullable Текст подсказки. Example: Чтобы получить товар, пройдите в 101 кабинет.
     *
     * @response 200 scenario="Success" {"message":"Настройки успешно сохранены."}
     * @response 422 scenario="Validation error" {"message":"The given data was invalid."}
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_hint' => 'nullable|string',
        ]);

        $setting = MarketplaceSetting::first() ?? new MarketplaceSetting;
        $setting->pickup_hint = $validated['pickup_hint'] ?? null;
        $setting->save();

        return response()->json(['message' => 'Настройки успешно сохранены.']);
    }
}
