<?php

namespace App\Http\Controllers\Rating;

use App\Http\Controllers\Controller;
use App\Services\Rating\RatingAnalyticsService;
use App\Services\ScoreService;
use App\Services\SemesterService;
use App\Services\ScoreCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Public Endpoints
 *
 * @unauthenticated
 */
class RatingController extends Controller
{
    public function __construct(
        private readonly RatingAnalyticsService $ratingAnalytics,
    ) {}

    /**
     * Получение рейтинга
     *
     * @queryParam academy_id integer Фильтр по одной академии. Example: 1
     * @queryParam academy_ids array Фильтр по нескольким академиям (ID). Example: [1,2]
     * @queryParam education_level_id integer ID уровня образования. Example: 1
     * @queryParam course integer Курс 1–4. Example: 2
     * @queryParam per_page integer Размер страницы (1–100). Example: 20
     */
    public function getUsersRating(Request $request): JsonResponse
    {
        $filters = $this->parseRatingFilters($request);
        $semester = SemesterService::getCurrentSemester();

        $cappedScoresQuery = DB::table('activity_user as au')
            ->join('activities as a', 'au.activity_id', '=', 'a.id')
            ->where('au.is_confirmed', 1)
            ->whereBetween('a.date_end', [$semester['start'], $semester['end']])
            ->select(
                'au.user_id',
                'a.category_id',
                DB::raw('LEAST(SUM(' . ScoreCalculationService::earnedScoreExpression('au', 'a') . '), '.ScoreService::CAP_PER_CATEGORY.') as category_capped')
            )
            ->groupBy('au.user_id', 'a.category_id');

        $aggregated = DB::table('members')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->joinSub($cappedScoresQuery, 'cat_scores', function ($join) {
                $join->on('members.user_id', '=', 'cat_scores.user_id');
            })
            ->leftJoin('academies', 'members.academy_id', '=', 'academies.id')
            ->leftJoin('education_levels', 'members.education_level_id', '=', 'education_levels.id')
            ->where('users.role', 'student')
            ->where('users.is_activated', 1)
            ->tap(fn ($q) => $this->ratingAnalytics->applyMemberFilters($q, $filters))
            ->select(
                'members.user_id',
                'members.academy_id',
                'academies.name as academy_name',
                'academies.short_name as academy_short_name',
                'education_levels.name as education_level',
                DB::raw("CONCAT(
                    members.last_name,
                    ' ',
                    SUBSTRING(members.first_name, 1, 1),
                    '.',
                    CASE
                        WHEN members.middle_name IS NOT NULL
                        AND TRIM(members.middle_name) != ''
                        THEN CONCAT(SUBSTRING(members.middle_name, 1, 1), '.')
                        ELSE ''
                    END
                ) as fio"),
                DB::raw('ROUND(SUM(cat_scores.category_capped)) as score')
            )
            ->groupBy('members.user_id', 'members.academy_id', 'academies.name', 'academies.short_name', 'education_levels.name', 'fio');

        $scoreLevels = DB::query()
            ->fromSub(clone $aggregated, 'score_rows')
            ->select('score')
            ->distinct();

        $place = DB::query()
            ->fromSub($scoreLevels, 'score_levels')
            ->selectRaw('COUNT(*) + 1')
            ->whereColumn('score_levels.score', '>', 'r.score');

        $ranked = DB::query()
            ->fromSub($aggregated, 'r')
            ->select('r.*')
            // MySQL 5.7-compatible dense rank: 1 + number of distinct higher scores.
            ->selectSub($place, 'place')
            ->orderBy('place')
            ->orderBy('user_id');

        $perPage = max(1, min(100, $filters['per_page'] ?? 20));

        return response()->json($ranked->paginate($perPage));
    }

    /**
     * Получение статистики
     *
     * @queryParam academy_id integer
     * @queryParam academy_ids array
     * @queryParam education_level_id integer
     * @queryParam course integer
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $filters = $this->parseRatingFilters($request);
        $period = SemesterService::getCurrentSemester();
        $period['type'] = 'semester';
        $period['label'] = $this->formatSemesterLabel($period);

        return response()->json(
            $this->ratingAnalytics->buildStatisticsPayload($period, $filters)
        );
    }

    /**
     * Полные данные аналитики рейтинга для графиков
     *
     * @queryParam academy_id integer
     * @queryParam academy_ids array
     * @queryParam education_level_id integer
     * @queryParam course integer
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $filters = $this->parseRatingFilters($request);
        $period = SemesterService::getCurrentSemester();
        $period['type'] = 'semester';
        $period['label'] = $this->formatSemesterLabel($period);

        return response()->json(
            $this->ratingAnalytics->buildAnalyticsPayload($period, $filters)
        );
    }

    /**
     * Format semester label for display (e.g., "Весна 2026" or "Осень 2025").
     *
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     */
    private function formatSemesterLabel(array $semester): string
    {
        $startMonth = (int) $semester['start']->month;
        $year = $semester['start']->year;

        if ($startMonth < 9) {
            return "Весна {$year}";
        }

        return "Осень {$year}";
    }

    /**
     * @return array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null, per_page: int}
     */
    private function parseRatingFilters(Request $request): array
    {
        $validated = $request->validate([
            'academy_id' => ['sometimes', 'nullable', 'integer'],
            'academy_ids' => ['sometimes', 'nullable', 'array'],
            'academy_ids.*' => ['integer'],
            'education_level_id' => ['sometimes', 'nullable', 'integer', 'exists:education_levels,id'],
            'course' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:4'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $academyIds = $validated['academy_ids'] ?? null;
        if (($academyIds === null || $academyIds === []) && array_key_exists('academy_id', $validated) && $validated['academy_id'] !== null) {
            $academyIds = [(int) $validated['academy_id']];
        }
        if (is_array($academyIds) && $academyIds !== []) {
            $academyIds = array_values(array_unique(array_map('intval', $academyIds)));
        } else {
            $academyIds = null;
        }

        return [
            'academy_ids' => $academyIds,
            'education_level_id' => isset($validated['education_level_id']) ? (int) $validated['education_level_id'] : null,
            'course' => isset($validated['course']) ? (int) $validated['course'] : null,
            'per_page' => isset($validated['per_page']) ? (int) $validated['per_page'] : 20,
        ];
    }
}
