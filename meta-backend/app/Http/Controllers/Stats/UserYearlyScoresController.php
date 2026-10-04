<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\Models\ActivityUser;
use App\Services\ScoreCalculationService;
use App\Services\SemesterService;

class UserYearlyScoresController extends Controller
{
    /**
     * Получает сумму баллов подтверждённых активностей за уч. год.
     *
     * @return int Возвращает int с суммой баллов.
     */
    public static function index(): int
    {
        $academicYear = SemesterService::getCurrentAcademicYear();

        return self::sumScoreBetween($academicYear['start'], $academicYear['end']);
    }

    /**
     * Сумма баллов подтверждённых активностей за текущий семестр.
     *
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     */
    public static function semester(array $semester): int
    {
        return self::sumScoreBetween($semester['start'], $semester['end']);
    }

    private static function sumScoreBetween($start, $end): int
    {
        $scoreExpr = ScoreCalculationService::earnedScoreExpression('activity_user', 'activities');
        $sumScore = ActivityUser::query()
            ->join('activities', 'activity_user.activity_id', '=', 'activities.id')
            ->join('users', 'activity_user.user_id', '=', 'users.id')
            ->where('activity_user.is_confirmed', '=', 1)
            ->where('users.role', 'student')
            ->where('users.is_activated', 1)
            ->whereBetween('activities.date_end', [$start, $end])
            ->selectRaw("SUM({$scoreExpr}) as total")
            ->value('total');

        return (int) round((float) $sumScore);
    }
}
