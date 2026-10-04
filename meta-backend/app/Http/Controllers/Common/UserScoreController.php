<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\ActivityUser;
use App\Services\ScoreCalculationService;
use Illuminate\Support\Facades\DB;

class UserScoreController extends Controller
{
    public static function getUserScoreByAcademicYear($userId)
    {
        return ActivityUser::join('activities', 'activity_user.activity_id', '=', 'activities.id')
            ->where('activity_user.user_id', '=', $userId)
            ->where('is_confirmed', '=', 1)
            ->select(
                DB::raw('SUM('.ScoreCalculationService::earnedScoreExpression('activity_user', 'activities').') as score')
            )
            ->groupBy('activity_user.user_id')
            ->first()?->score;
    }
}
