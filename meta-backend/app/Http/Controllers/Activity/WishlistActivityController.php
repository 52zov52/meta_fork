<?php

namespace App\Http\Controllers\Activity;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Models\WishlistActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Activities (Student)
 */
class WishlistActivityController extends Controller
{
    /**
     * Получить список избранного (мероприятий)
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     */
    public function index(int $user_id): JsonResponse
    {
        $userLevelId = \App\Models\Member::where('user_id', $user_id)->value('education_level_id') ?? 0;

        $activities = WishlistActivity::join('activities', 'wishlist_activity.activity_id', '=', 'activities.id')
            ->join('planets', 'activities.planet_id', '=', 'planets.id')
            ->leftJoin('activity_scores', function ($join) use ($userLevelId) {
                $join->on('activity_scores.activity_id', '=', 'activities.id')
                    ->where('activity_scores.education_level_id', '=', $userLevelId)
                    ->where('activity_scores.place', '=', 'participation');
            })
            ->where('wishlist_activity.user_id', '=', $user_id)
            ->select(
                'activities.id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                'activities.is_dynamic_score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activities.photo_path',
                'activity_scores.final_score as user_personal_score',
                DB::raw("IF(EXISTS (
                    SELECT 1
                    FROM activity_user
                    WHERE activity_user.activity_id = activities.id
                    AND activity_user.user_id = $user_id
                ), 1, 0) AS is_registered")
            )
            ->orderBy('activities.date_start', 'desc')
            ->orderBy('activities.id', 'desc')
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Добавить мероприятие в избранное
     *
     * Создаёт запись в списке избранного для пары пользователь + мероприятие.
     *
     * @bodyParam user_id integer required ID пользователя. Example: 2
     * @bodyParam activity_id integer required ID мероприятия. Example: 1
     *
     * @response 200 {
     *   "message": "Мероприятие успешно добавлено в избранное!"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        WishlistActivity::create($request->only(['user_id', 'activity_id']));

        return response()->json(['message' => 'Мероприятие успешно добавлено в избранное!']);
    }

    /**
     * Удалить мероприятие из избранного
     *
     * Удаляет запись избранного по паре user_id и activity_id.
     *
     * @bodyParam user_id integer required ID пользователя. Example: 2
     * @bodyParam activity_id integer required ID мероприятия. Example: 1
     *
     * @response 200 {
     *   "message": "Вы успешно удалили мероприятие из избранного!"
     * }
     */
    public function delete(Request $request): JsonResponse
    {
        $wishlistActivity = WishlistActivity::where('user_id', '=', $request->user_id)
            ->where('activity_id', '=', $request->activity_id);
        $wishlistActivity->delete();

        return response()->json(['message' => 'Вы успешно удалили мероприятие из избранного!']);
    }
}
