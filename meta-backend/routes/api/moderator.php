<?php

use App\Http\Controllers\Moderator\ModeratorActivityController;
use App\Http\Controllers\Moderator\ModeratorStudentController;
use App\Http\Controllers\Stats\DashboardController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum', 'activated', 'role:moderator'], 'prefix' => 'moderator'], function () {
    Route::get('/statistics', [DashboardController::class, 'index']);

    // Мероприятия
    Route::group(['prefix' => 'activities'], function () {
        Route::get('/', [ModeratorActivityController::class, 'index']);
        Route::post('/', [ModeratorActivityController::class, 'store']);
        Route::post('/{id}', [ModeratorActivityController::class, 'update']);
        Route::delete('/{id}', [ModeratorActivityController::class, 'destroy']);
        Route::patch('/{id}/publish', [ModeratorActivityController::class, 'publish']);
    });

    // Вкладка Пользователи
    Route::group(['prefix' => 'students'], function () {
        // Список с фильтрами
        Route::get('/', [ModeratorStudentController::class, 'index']);
        // Профиль студента
        Route::get('/{id}', [ModeratorStudentController::class, 'show']);
        // Блокировка/разблокировка
        Route::patch('/{id}/block', [ModeratorStudentController::class, 'toggleBlock']);
        // Сброс пароля
        Route::post('/{id}/reset-password', [ModeratorStudentController::class, 'resetPassword']);
    });
});
