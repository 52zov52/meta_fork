<?php

use App\Http\Controllers\Activity\ActivityCategoryController;
use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityFileController;
use App\Http\Controllers\Activity\DynamicScoreController;
use App\Http\Controllers\Common\PublicDataController;
use App\Http\Controllers\History\HistoryController;
use App\Http\Controllers\Portfolio\PortfolioController;
use App\Http\Controllers\Rating\RatingController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\ScoringBlackoutController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/api-login', [AuthenticatedSessionController::class, 'apiLogin'])
        ->middleware('throttle:10,1');

    Route::get('regions', [PublicDataController::class, 'getRegions']);
    Route::get('planets', [PublicDataController::class, 'getPlanets']);
    Route::get('academies', [PublicDataController::class, 'getAcademies']);
    Route::get('education-levels', [PublicDataController::class, 'getEducationLevels']);
    Route::get('institutions', [PublicDataController::class, 'getInstitutions']);
    Route::get('scoring-blackout', [ScoringBlackoutController::class, 'show']);
    Route::get('categories', [ActivityCategoryController::class, 'index']);

    Route::group(['prefix' => 'rating'], function () {
        Route::get('/users', [RatingController::class, 'getUsersRating']);
        Route::get('/statistics', [RatingController::class, 'getStatistics']);
        Route::get('/analytics', [RatingController::class, 'getAnalytics']);
    });

    Route::group(['prefix' => 'public/student'], function () {
        // Портфолио
        Route::get('/portfolio-public/{user_id}', [PortfolioController::class, 'publicIndex']);
        // Профиль
        Route::get('/users/{user_id}/short', [UserController::class, 'noFullInfo']);
        // История начисления баллов
        Route::get('/history-score/{user_id}', [HistoryController::class, 'getMyHistoryScore']);
        // Мероприятия
        Route::get('/activities/{filter}/{sortMethod}', [ActivityController::class, 'getPublicActivities']);
        Route::get('/activity/{id}', [ActivityController::class, 'show']);
    });

    // Файлы мероприятий (скачивание)
    Route::get('/activities/files/{file_id}/download', [ActivityFileController::class, 'download']);
    // Получение списка файлов мероприятия
    Route::get('/activities/{activity_id}/files', [ActivityFileController::class, 'index']);
    // Предварительный расчет баллов КСУ
    Route::post('/activities/calculate-preview', [DynamicScoreController::class, 'calculatePreview']);
    // Получение баллов мероприятия (для студента)
    Route::get('/activities/{id}/scores', [DynamicScoreController::class, 'showScores']);

    require __DIR__.'/api/student.php';
    require __DIR__.'/api/admin.php';
    require __DIR__.'/api/mentor.php';
    require __DIR__.'/api/moderator.php';
});
