<?php

use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityFileController;
use App\Http\Controllers\Activity\ActivityUserController;
use App\Http\Controllers\Stats\DashboardController;
use App\Http\Controllers\User\MemberController;
use App\Http\Controllers\Mentor\MentorController;
use App\Http\Controllers\Mentor\StudentsController;
use Illuminate\Support\Facades\Route;

// Роль ментор
Route::group(['middleware' => ['auth:sanctum', 'activated', 'role:mentor'], 'prefix' => 'mentor'], function () {
    Route::get('/statistics', [DashboardController::class, 'index']);

    Route::group(['prefix' => 'students'], function () {
        Route::get('/', [StudentsController::class, 'students']);
        Route::get('/groups', [StudentsController::class, 'groups']);
        Route::post('/', [StudentsController::class, 'store']);
        Route::get('/{user_id}', [StudentsController::class, 'show']);
        Route::post('/{user_id}', [StudentsController::class, 'update']);
        Route::post('/{user_id}/reset-password', [StudentsController::class, 'resetPassword']);
        Route::post('/{user_id}/toggle-block', [StudentsController::class, 'toggleBlock']);
    });

    // Информация о текущем пользователе
    Route::get('/users/{user_id}', [MentorController::class, 'getMy']);

    // Получить студентов наставника (фильтр по академии)
    Route::get('/students', [StudentsController::class, 'students']);

    // Просмотр всех мероприятий
    Route::get('/activities-mentor', [ActivityController::class, 'index']);

    Route::group(['prefix' => 'activity'], function () {
        // Создание мероприятия
        Route::post('/store-mentor', [ActivityController::class, 'store']);
        // Получить список всех мероприятий конкретного ментора
        Route::get('mentor/activities/{user_id}', [ActivityController::class, 'mentorActivities']);

        // Просмотр мероприятия (для просмотра участников)
        Route::get('/show-mentor/{id}', [ActivityController::class, 'show']);

        // Редактирование мероприятия
        Route::get('/edit-mentor/{id}', [ActivityController::class, 'mentorEdit']);
        Route::post('/update-mentor/{id}', [ActivityController::class, 'mentorUpdate']);

        // Удаление мероприятия
        Route::delete('/destroy-mentor/{id}', [ActivityController::class, 'mentorDestroy']);

        // Поиск мероприятия по наименованию
        Route::get('/search-mentor/{sortMethod}/{searchTxt}', [ActivityController::class, 'search']);
    });

    // Получение списка участников мероприятия
    Route::get('activities/{activity_id}/participants', [ActivityUserController::class, 'index']);

    // Файлы мероприятий
    Route::group(['prefix' => 'activities'], function () {
        // Загрузка файла к мероприятию (только к своим мероприятиям)
        Route::post('/{activity_id}/files', [ActivityFileController::class, 'upload']);
        // Удаление файла мероприятия (только у своих мероприятий)
        Route::delete('/files/{file_id}', [ActivityFileController::class, 'destroy']);
    });

    // Подтверждение участия (выставление баллов)
    Route::post('user-confirmation-mentor', [ActivityUserController::class, 'confirmation']);
    // Подтверждение участия с динамическими баллами (КСУ)
    Route::post('user-confirmation-ksu-mentor', [ActivityUserController::class, 'confirmationKsu']);

    // Просмотр рейтинга пользователей
    Route::get('/members/{sortMethod}/{role?}', [MemberController::class, 'index']);
    Route::get('/member/show-mentor/{user_id}', [MemberController::class, 'show']);
    Route::get('/member/search-mentor/{sortMethod}/{searchTxt}', [MemberController::class, 'search']);
});
