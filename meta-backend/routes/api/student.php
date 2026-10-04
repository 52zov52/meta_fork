<?php

use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityUserController;
use App\Http\Controllers\Activity\WishlistActivityController;
use App\Http\Controllers\Marketplace\MarketplaceController;
use App\Http\Controllers\Marketplace\StudentMarketplaceOrderController;
use App\Http\Controllers\Student\MarketplaceSettingController as StudentMarketplaceSettingController;
use App\Http\Controllers\Portfolio\PortfolioController;
use App\Http\Controllers\User\MemberController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

// Роль обучающийся
Route::group(['middleware' => ['auth:sanctum', 'activated', 'role:student'], 'prefix' => 'student'], function () {
    Route::get('/users/{user_id}', [UserController::class, 'info']);

    // Мероприятия
    Route::group(['prefix' => 'activity'], function () {
        // Получение списка мероприятий с фильтрами (status, search, category, sort, academy)
        Route::get('/', [ActivityController::class, 'getActivities']);
        // Получение списка мероприятий пользователя
        Route::get('/users/{user_id}/activities', [ActivityUserController::class, 'getMyActivities']);
        // Регистрация на мероприятие
        Route::post('/register', [ActivityUserController::class, 'register']);
        // Отмена регичтрации на мероприятие
        Route::post('/undo', [ActivityUserController::class, 'undo']);
        // Список избранного
        Route::group(['prefix' => 'wishlist'], function () {
            // Получение списка избранного (мероприятий)
            Route::get('/{user_id}', [WishlistActivityController::class, 'index']);
            // Добавление в списко избранного
            Route::post('/store', [WishlistActivityController::class, 'store']);
            // Удаление из списка избранного
            Route::post('/delete', [WishlistActivityController::class, 'delete']);
        });
    });

    // Портфолио
    Route::group(['prefix' => 'portfolio'], function () {
        // Загрузка нового файла в портфолио
        Route::post('/store', [PortfolioController::class, 'store']);
        // Удаление документа из портфолио
        Route::delete('/destroy/{id}', [PortfolioController::class, 'destroy']);
        // Привязка портфолио к мероприятию
        Route::post('/attach-to-activity', [PortfolioController::class, 'attachPortfolioToActivity']);
        // Отвязка портфолио от мероприятия
        Route::post('/detach-from-activity', [PortfolioController::class, 'detachPortfolioFromActivity']);
    });

    // Профиль пользователя
    Route::group(['prefix' => 'profile'], function () {
        // Отображение информацию пользователя
        Route::get('/show/{user_id}', [MemberController::class, 'show']);
        // Получение информацию пользователя для редактирования
        Route::get('/edit/{user_id}', [MemberController::class, 'edit']);
        // Обновление
        Route::group(['prefix' => 'update'], function () {
            // Обновление персональной информациии
            Route::post('personal/{user_id}', [MemberController::class, 'updatePersonalData']);
            // Обновление информации об учреждении
            Route::post('institution/{user_id}', [MemberController::class, 'updateInstitutionData']);
            // Обновление информации об аккаунте
            Route::post('account/{user_id}', [MemberController::class, 'updateAccountData']);
            // Обновление пароля
            Route::post('password/{user_id}', [MemberController::class, 'updatePassword']);
        });
    });

    // Маркетплейс
    Route::get('/my/tradable-scores', [UserController::class, 'getTradableScores']);
    Route::group(['prefix' => 'marketplace'], function () {
        Route::get('/settings', [StudentMarketplaceSettingController::class, 'show']);
        Route::get('/products', [MarketplaceController::class, 'studentIndex']);
        Route::get('/orders', [StudentMarketplaceOrderController::class, 'index']);
        Route::post('/orders', [StudentMarketplaceOrderController::class, 'store']);
    });
});
