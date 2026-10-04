<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\ActivityUser;
use App\Services\SemesterService;
use App\Services\ScoreCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Users
 */
class HistoryController extends Controller
{
    /**
     * Получить историю начисления баллов
     *
     * @group Users (Student)
     *
     * @unauthenticated
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     *
     * @queryParam semester string Фильтр по семестру. Допустимые значения: `current`, `previous`, `all`. By default: `all`. Example: current
     */
    public function getMyHistoryScore(Request $request, int $user_id): JsonResponse
    {
        $semesterFilter = $request->input('semester'); // 'current', 'previous', or 'all'

        /* Assumes: для is_confirmed=1 поле updated_at совпадает с моментом подтверждения. */
        $query = ActivityUser::join('activities', 'activity_user.activity_id', '=', 'activities.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->where('activity_user.user_id', $user_id)
            ->where('activity_user.is_confirmed', 1)
            ->select(
                'activity_user.id',
                'activities.id as activity_id',
                'activities.title',
                'activity_categories.name as category',
                DB::raw('ROUND(' . ScoreCalculationService::earnedScoreExpression('activity_user', 'activities') . ') as score'),
                DB::raw('activity_user.updated_at as score_assignment_date')
            );

        // Apply semester filter
        if ($semesterFilter === 'current') {
            $semesterRange = SemesterService::getCurrentSemester();
            $query->whereBetween('activities.date_end', [$semesterRange['start'], $semesterRange['end']]);
        } elseif ($semesterFilter === 'previous') {
            $semesterRange = SemesterService::getPreviousSemester();
            $query->whereBetween('activities.date_end', [$semesterRange['start'], $semesterRange['end']]);
        }
        // 'all' or no parameter shows everything

        $historyScore = $query->orderBy('score_assignment_date', 'desc')
            ->paginate(20);

        return response()->json($historyScore);
    }
}
