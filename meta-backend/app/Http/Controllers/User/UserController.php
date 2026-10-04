<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Users
 */
class UserController extends Controller
{
    /**
     * Получить информацию о пользователе
     *
     * @group Users (Student)
     *
     * @urlParam user_id int required ID пользователя. Example: 2
     */
    public function info(int $user_id)
    {
        $user = User::with(['member.academy', 'member.institution'])->findOrFail($user_id);

        $categoryScores = ScoreService::getCappedScores($user->id);
        $cappedTotal = ScoreService::getCappedTotalForUser($user->id);

        return response()->json([
            'shortName' => $user->member->shortName,
            'fullName' => $user->member->fullName,
            'region' => $user->member->region->name,
            'institution' => $user->member->institutionDisplay,
            'academy' => $user->member->academy ? [
                'id' => $user->member->academy->id,
                'name' => $user->member->academy->name,
                'short_name' => $user->member->academy->short_name,
            ] : null,
            'education_level' => $user->member->educationLevel?->name,
            'course' => $user->member->course,
            'total_score' => $cappedTotal,
            'category_scores' => $categoryScores,
            'profile_photo_path' => $user->member->profile_photo_path,
        ]);
    }

    /**
     * Публичная информация
     *
     * @group Users (Student)
     *
     * @unauthenticated
     *
     * @urlParam user_id int required ID пользователя. Example: 2
     */
    public function noFullInfo(int $user_id)
    {
        $user = User::with(['member.academy', 'member.institution'])->findOrFail($user_id);

        $categoryScores = ScoreService::getCappedScores($user->id);
        $cappedTotal = ScoreService::getCappedTotalForUser($user->id);

        return response()->json([
            'shortName' => $user->member->shortName,
            'institution' => $user->member->institutionDisplay,
            'academy' => $user->member->academy ? [
                'id' => $user->member->academy->id,
                'name' => $user->member->academy->name,
                'short_name' => $user->member->academy->short_name,
            ] : null,
            'education_level' => $user->member->educationLevel?->name,
            'course' => $user->member->course,
            'total_score' => $cappedTotal,
            'category_scores' => $categoryScores,
            'profile_photo_path' => $user->member->profile_photo_path,
        ]);
    }

    /**
     * Активировать пользователя
     *
     * @group Users (Admin)
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     */
    public function activate(int $user_id)
    {
        $user = User::findOrFail($user_id);
        $user->is_activated = true;
        $user->save();

        return response()->json(['message' => 'Пользователь успешно активирован!']);
    }

    /**
     * Массовая активация
     *
     * This endpoint allows admin to activate multiple users at once by their IDs.
     *
     * @group Users (Admin)
     *
     * @bodyParam user_ids array required Array of user IDs to activate. Example: [1, 2, 3]
     * @bodyParam user_ids.* integer required User ID. Example: 1
     *
     * @response 200 {
     *   "message": "Пользователи успешно активированы!",
     *   "count": 3
     * }
     * @response 400 {
     *   "message": "Не выбраны пользователи"
     * }
     * @response 500 {
     *   "message": "Произошла ошибка при массовой активации"
     * }
     */
    public function bulkActivate(Request $request)
    {
        $userIds = $request->input('user_ids', []);

        if (empty($userIds)) {
            return response()->json(['message' => 'Не выбраны пользователи'], 400);
        }

        User::whereIn('id', $userIds)->update(['is_activated' => true]);

        return response()->json([
            'message' => 'Пользователи успешно активированы!',
            'count' => count($userIds),
        ]);
    }

    /**
     * Деактивировать пользователя
     *
     * @group Users (Admin)
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     */
    public function deactivate(int $user_id)
    {
        $user = User::findOrFail($user_id);
        $user->is_activated = false;
        $user->save();

        return response()->json(['message' => 'Пользователь успешно деактивирован!']);
    }

    /**
     * Массовая деактивация
     *
     * This endpoint allows admin to deactivate multiple users at once by their IDs.
     *
     * @group Users (Admin)
     *
     * @bodyParam user_ids array required Array of user IDs to deactivate. Example: [1, 2, 3]
     * @bodyParam user_ids.* integer required User ID. Example: 1
     *
     * @response 200 {
     *   "message": "Пользователи успешно деактивированы!",
     *   "count": 3
     * }
     * @response 400 {
     *   "message": "Не выбраны пользователи"
     * }
     * @response 500 {
     *   "message": "Произошла ошибка при массовой деактивации"
     * }
     */
    public function bulkDeactivate(Request $request)
    {
        $userIds = $request->input('user_ids', []);

        if (empty($userIds)) {
            return response()->json(['message' => 'Не выбраны пользователи'], 400);
        }

        User::whereIn('id', $userIds)->update(['is_activated' => false]);

        return response()->json([
            'message' => 'Пользователи успешно деактивированы!',
            'count' => count($userIds),
        ]);
    }

    /**
     * Получить очки для обмена на маркетлейсе
     *
     * @group Users (Student)
     */
    public function getTradableScores(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'previous_semester_score' => $user->previous_semester_score,
        ]);
    }
}
