<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BaseScoreController;
use App\Http\Controllers\Admin\EducationLevelController;
use App\Http\Controllers\Admin\KsuMatrixController;
use App\Http\Controllers\Admin\ScoringBlackoutController;
use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityFileController;
use App\Http\Controllers\Activity\ActivityUserController;
use App\Http\Controllers\Common\PublicDataController;
use App\Http\Controllers\Marketplace\AdminMarketplaceOrderController;
use App\Http\Controllers\Marketplace\MarketplaceController;
use App\Http\Controllers\Admin\MarketplaceSettingController as AdminMarketplaceSettingController;
use App\Http\Controllers\Notification\MailController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Portfolio\PortfolioController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Reports\AcademyDistributionController;
use App\Http\Controllers\Reports\DirectionDistributionController;
use App\Http\Controllers\Stats\DashboardController;
use App\Http\Controllers\User\ImportUsersController;
use App\Http\Controllers\User\MemberController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

// Роль админ
Route::group(['middleware' => ['auth:sanctum', 'activated', 'role:admin'], 'prefix' => 'admin'], function () {
    // Главная (статистика)
    Route::get('/statistics', [DashboardController::class, 'index']);
    // Информация о текущем пользователе
    Route::get('/users/{user_id}', [AdminController::class, 'getMy']);
    // Получение списка планет
    Route::get('/planets', [PublicDataController::class, 'getPlanets']);
    // Получение списка всех пользователей
    Route::get('/members', [MemberController::class, 'index']);
    // Mail
    Route::post('/reset-passwords-by-role', [MailController::class, 'resetGroupPasswords']);

    // Пользователи
    Route::group(['prefix' => 'member'], function () {
        // Поиск пользователя по ФИО
        Route::get('/search/{sortMethod}/{searchTxt}', [MemberController::class, 'search']);
        // Просмотр профиля пользователя
        Route::get('/show/{user_id}', [MemberController::class, 'show']);
        // Добаваление нового пользователя
        Route::post('/store', [MemberController::class, 'store']);
        // Изменение пароля пользователя администратором
        Route::patch('/update-password/{user_id}', [MemberController::class, 'adminUpdatePassword']);
        // Удаление пользователя
        Route::delete('/destroy/{user_id}', [MemberController::class, 'destroy']);
        Route::delete('/bulk-destroy', [MemberController::class, 'bulkDestroy']);
    });
    // Импорт пользователей
    Route::group(['prefix' => 'import-users'], function () {
        Route::get('/template', [ImportUsersController::class, 'downloadTemplate']);
        Route::get('/institution-types', [ImportUsersController::class, 'getInstitutionTypes']);
        Route::post('/', [ImportUsersController::class, 'import']);
    });
    // Активация и деактивация пользователей
    Route::group(['prefix' => 'user'], function () {
        Route::get('/activate/{user_id}', [UserController::class, 'activate']);
        Route::post('/bulk-activate', [UserController::class, 'bulkActivate']);
        Route::get('/deactivate/{user_id}', [UserController::class, 'deactivate']);
        Route::post('/bulk-deactivate', [UserController::class, 'bulkDeactivate']);
    });

    // Получение списка всех мероприятий
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::group(['prefix' => 'activity'], function () {
        // Поиск мероприятия по наименованию
        Route::get('/search/{sortMethod}/{searchTxt}', [ActivityController::class, 'search']);
        // Просмотр мероприятия
        Route::get('/show/{id}', [ActivityController::class, 'show']);
        // Добавление нового мероприятия
        Route::post('/store', [ActivityController::class, 'store']);
        // Получение данных о мероприятии для редактирования
        Route::get('/edit/{id}', [ActivityController::class, 'edit']);
        // Обновление данных мероприятия
        Route::post('/update/{id}', [ActivityController::class, 'update']);
        // Удаление мероприятия
        Route::delete('/destroy/{id}', [ActivityController::class, 'destroy']);
    });
    // Поиск и добавление участников мероприятия
    Route::get('activities/participants-import-template', [ActivityUserController::class, 'participantsImportTemplate']);
    Route::get('activities/{activity_id}/participant-candidates', [ActivityUserController::class, 'participantCandidates']);
    Route::post('activities/{activity_id}/participants', [ActivityUserController::class, 'storeParticipant']);
    Route::post('activities/{activity_id}/participants-import/preview', [ActivityUserController::class, 'previewParticipantsImport']);
    Route::post('activities/{activity_id}/participants-import', [ActivityUserController::class, 'importParticipants']);
    // Получение списка мероприятий конкретного пользователя
    Route::get('activities/{activity_id}/participants', [ActivityUserController::class, 'index']);
    // Подтверждение участия в мероприятии
    Route::post('user-confirmation', [ActivityUserController::class, 'confirmation']);
    // Массовое подтверждение участия в мероприятии
    Route::post('activities/bulk-confirmation', [ActivityUserController::class, 'bulkConfirmation']);
    // Подтверждение участия с динамическими баллами (КСУ)
    Route::post('user-confirmation-ksu', [ActivityUserController::class, 'confirmationKsu']);
    // Настройки периодов запрета (чтение — GET /api/v1/scoring-blackout)
    Route::put('scoring-blackout', [ScoringBlackoutController::class, 'update']);
    // Маркетплейс
    Route::group(['prefix' => 'marketplace'], function () {
        // Настройки маркетплейса
        Route::get('/settings', [AdminMarketplaceSettingController::class, 'show']);
        Route::put('/settings', [AdminMarketplaceSettingController::class, 'update']);
        // Покупки студентов
        Route::get('/orders', [AdminMarketplaceOrderController::class, 'index']);
        Route::patch('/orders/{order}', [AdminMarketplaceOrderController::class, 'update']);
        // Товары
        Route::apiResource('products', MarketplaceController::class)->parameters(['products' => 'product']);
    });

    // Получение списка всех уведомлений
    Route::get('/notifications/order/{sortMethod}', [NotificationController::class, 'index']);
    // Уведомления
    Route::group(['prefix' => 'notification'], function () {
        // Добавление нового уведомления
        Route::post('/store', [NotificationController::class, 'store']);
        // Поучение данных об уведомлении для редактирования
        Route::get('/edit/{id}', [NotificationController::class, 'edit']);
        // Обновлении данных уведомления
        Route::post('/update/{id}', [NotificationController::class, 'update']);
        // Удаление уведомления
        Route::delete('/destroy/{id}', [NotificationController::class, 'destroy']);
    });

    // Портфолио
    Route::group(['prefix' => 'portfolio'], function () {
        // Получение всех портфолио с фильтрацией
        Route::post('/query', [PortfolioController::class, 'adminIndex']);
        // Просмотр конкретного портфолио
        Route::get('/show/{id}', [PortfolioController::class, 'adminShow']);
        // Удаление портфолио администратором
        Route::delete('/destroy/{id}', [PortfolioController::class, 'adminDestroy']);
    });

    // Отчет по всем пользователям
    Route::get('/generate-users-report', [ReportController::class, 'generateUsersReport']);
    // Отчет по академиям
    Route::get('/generate-academy-distribution-report', [AcademyDistributionController::class, 'index']);
    // Отчет по направлениям
    Route::get('/generate-direction-distribution-report', [DirectionDistributionController::class, 'index']);

    // Файлы мероприятий
    Route::group(['prefix' => 'activities'], function () {
        // Загрузка файла к мероприятию
        Route::post('/{activity_id}/files', [ActivityFileController::class, 'upload']);
        // Удаление файла мероприятия
        Route::delete('/files/{file_id}', [ActivityFileController::class, 'destroy']);
    });

    // Справочники КСУ
    Route::group(['prefix' => 'ksu-matrix'], function () {
        Route::get('/', [KsuMatrixController::class, 'index']);
        Route::post('/', [KsuMatrixController::class, 'store']);
        Route::put('/{id}', [KsuMatrixController::class, 'update']);
        Route::delete('/{id}', [KsuMatrixController::class, 'destroy']);
    });

    // Справочники базовых баллов
    Route::group(['prefix' => 'base-scores'], function () {
        Route::get('/', [BaseScoreController::class, 'index']);
        Route::post('/', [BaseScoreController::class, 'store']);
        Route::put('/{id}', [BaseScoreController::class, 'update']);
        Route::delete('/{id}', [BaseScoreController::class, 'destroy']);
    });

    // Уровни обучения (управление НУО)
    Route::group(['prefix' => 'education-levels'], function () {
        Route::get('/', [EducationLevelController::class, 'index']);
        Route::put('/{id}', [EducationLevelController::class, 'update']);
    });
});
