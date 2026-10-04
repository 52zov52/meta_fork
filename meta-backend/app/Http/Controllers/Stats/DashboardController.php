<?php

namespace App\Http\Controllers\Stats;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Member;
use App\Models\Planet;
use App\Services\Rating\RatingAnalyticsService;
use App\Services\ScoreCalculationService;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @group Reporting (Admin)
 */
class DashboardController extends Controller
{
    private const DASHBOARD_COURSES = [1, 2, 3, 4];

    public function __construct(
        private readonly RatingAnalyticsService $ratingAnalytics,
    ) {}

    /**
     * Получить статистику для панели пользователя.
     *
     * Структура ответа организована по временным периодам для ясности:
     * - periods.all_time: регистрация пользователей (всё время)
     * - periods.semester: мероприятия в текущем семестре
     * - periods.academic_year: баллы за учебный год
     * - demographics: распределение зарегистрированных участников
     */
    public function index(Request $request): JsonResponse
    {
        $semester = SemesterService::getCurrentSemester();
        $academicYear = SemesterService::getCurrentAcademicYear();

        $semStart = $semester['start']->toDateString();
        $semEnd = $semester['end']->toDateString();

        $studentBaseQuery = Member::query()
            ->join('users', 'members.user_id', '=', 'users.id')
            ->where('users.role', 'student');
        $activitiesInSemesterQuery = Activity::query()
            ->whereBetween('date_end', [$semester['start'], $semester['end']])
            ->whereNot('status', ActivityStatus::DRAFT->value);

        $countActivitiesInCurrentSemester = (clone $activitiesInSemesterQuery)->count();

        $countActiveActivitiesInCurrentSemester = (clone $activitiesInSemesterQuery)
            ->whereIn('status', ActivityStatus::activeValues())
            ->count();

        $countUsers = (clone $studentBaseQuery)->count();
        $countActiveUsers = (clone $studentBaseQuery)->where('users.is_activated', true)->count();
        $countActiveStudentsInSemester = $this->countActiveStudentsInSemester($semester);

        // Группируем мероприятия по году и месяцу начала для вычисления точных учебных периодов
        $activitiesYearMonthCounts = Activity::select(
            DB::raw("YEAR(date_start) as y"),
            DB::raw("MONTH(date_start) as m"),
            DB::raw('count(*) as count')
        )
            ->groupBy('y', 'm')
            ->get();

        $activitiesByYearMap = [];
        foreach ($activitiesYearMonthCounts as $row) {
            if ($row->y === null || $row->m === null) {
                $label = 'Без даты';
            } else {
                $date = \Carbon\Carbon::create((int) $row->y, (int) $row->m, 15);
                $acadYear = SemesterService::getAcademicYearForDate($date);
                $label = $acadYear['label'];
            }
            $activitiesByYearMap[$label] = ($activitiesByYearMap[$label] ?? 0) + (int) $row->count;
        }

        // Сортируем учебные года по возрастанию, а "Без даты" выносим в конец
        $noDateCount = $activitiesByYearMap['Без даты'] ?? null;
        unset($activitiesByYearMap['Без даты']);
        ksort($activitiesByYearMap);

        $activitiesByYear = [];
        foreach ($activitiesByYearMap as $label => $count) {
            $activitiesByYear[] = [
                'year' => $label,
                'count' => $count,
            ];
        }
        if ($noDateCount !== null) {
            $activitiesByYear[] = [
                'year' => 'Без даты',
                'count' => $noDateCount,
            ];
        }

        $demographicsByPlanet = $this->buildDemographicsByPlanet();
        $scoreByCategories = $this->buildScoreByCategories($semester);
        $activitiesByCategories = $this->buildActivitiesByCategories($semester);
        $analyticsPeriod = $semester;
        $analyticsPeriod['type'] = 'semester';
        $analyticsPeriod['label'] = $this->formatSemesterLabel($semester);
        $analytics = $this->ratingAnalytics->buildAnalyticsPayload(
            $analyticsPeriod,
            $this->parseAnalyticsFilters($request),
            ['type' => 'all', 'value' => null]
        );

        $s1 = SemesterService::getSemester(1, $academicYear['start']->year);
        $s2 = SemesterService::getSemester(2, $academicYear['end']->year);

        $countActivitiesInSemester1 = Activity::query()
            ->whereBetween('date_end', [$s1['start'], $s1['end']])
            ->whereNot('status', ActivityStatus::DRAFT->value)
            ->count();

        $countActivitiesInSemester2 = Activity::query()
            ->whereBetween('date_end', [$s2['start'], $s2['end']])
            ->whereNot('status', ActivityStatus::DRAFT->value)
            ->count();

        $activitiesBySemester = [
            [
                'semester' => 1,
                'label' => $this->formatSemesterLabel($s1),
                'count' => $countActivitiesInSemester1,
            ],
            [
                'semester' => 2,
                'label' => $this->formatSemesterLabel($s2),
                'count' => $countActivitiesInSemester2,
            ],
        ];

        return response()->json([
            'activities_by_semester' => $activitiesBySemester,
            'periods' => [
                'all_time' => [
                    'users' => [
                        'total' => $countUsers,
                        'active' => $countActiveUsers,
                    ],
                ],
                'semester' => [
                    'type' => 'semester',
                    'label' => $this->formatSemesterLabel($semester),
                    'start' => $semStart,
                    'end' => $semEnd,
                    'activities' => [
                        'total' => $countActivitiesInCurrentSemester,
                        'active' => $countActiveActivitiesInCurrentSemester,
                    ],
                    'total_score_sum' => UserYearlyScoresController::semester($semester),
                ],
                'academic_year' => [
                    'type' => 'academic_year',
                    'label' => $academicYear['label'],
                    'start' => $academicYear['start']->toDateString(),
                    'end' => $academicYear['end']->toDateString(),
                    'total_score_sum' => UserYearlyScoresController::index(),
                ],
            ],
            'demographics' => [
                'by_planet' => $demographicsByPlanet,
            ],
            'activities_by_year' => $activitiesByYear,
            'general' => [
                'activated_students_count' => $countActiveUsers,
                'active_students_in_semester_count' => $countActiveStudentsInSemester,
                'score_by_categories' => $scoreByCategories,
                'activities_by_categories' => $activitiesByCategories,
            ],
            'analytics' => $analytics,
            'by_employers' => [
                'placeholder' => true,
            ],
        ]);
    }

    /**
     * Format semester label for display (e.g., "Весна 2026" or "Осень 2025")
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
     * @return array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}
     */
    private function parseAnalyticsFilters(Request $request): array
    {
        $validated = $request->validate([
            'academy_id' => ['sometimes', 'nullable', 'integer'],
            'academy_ids' => ['sometimes', 'nullable', 'array'],
            'academy_ids.*' => ['integer'],
            'education_level_id' => ['sometimes', 'nullable', 'integer', 'exists:education_levels,id'],
            'course' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:4'],
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
        ];
    }

    /**
     * @return list<array{id: int, label: string, count: int}>
     */
    private function buildDemographicsByPlanet(): array
    {
        $planets = Planet::all();
        $planetCounts = Member::query()
            ->join('users', 'members.user_id', '=', 'users.id')
            ->tap(fn ($query) => $this->applyActiveStudentScope($query))
            ->select('members.planet_id', DB::raw('count(*) as count'))
            ->groupBy('members.planet_id')
            ->pluck('count', 'planet_id');

        return $planets->map(fn ($planet) => [
            'id' => $planet->id,
            'label' => $planet->name,
            'count' => (int) ($planetCounts[$planet->id] ?? 0),
        ])->values()->all();
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     */
    private function countActiveStudentsInSemester(array $semester): int
    {
        $scoreUsersQuery = DB::table('activity_user')
            ->join('activities', 'activity_user.activity_id', '=', 'activities.id')
            ->join('members', 'activity_user.user_id', '=', 'members.user_id')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->tap(fn ($query) => $this->applyActiveStudentScope($query, 'members', 'users'))
            ->where('activity_user.is_confirmed', 1)
            ->whereBetween('activities.date_end', [$semester['start'], $semester['end']])
            ->select('activity_user.user_id')
            ->distinct();

        $certificateUsersQuery = DB::table('activity_portfolio')
            ->join('portfolio', 'activity_portfolio.portfolio_id', '=', 'portfolio.id')
            ->join('members', 'portfolio.user_id', '=', 'members.user_id')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->tap(fn ($query) => $this->applyActiveStudentScope($query, 'members', 'users'))
            ->whereBetween('activity_portfolio.created_at', [$semester['start'], $semester['end']])
            ->select('portfolio.user_id')
            ->distinct();

        return DB::query()
            ->fromSub($scoreUsersQuery->union($certificateUsersQuery), 'active_users')
            ->count();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyActiveStudentScope($query, string $membersAlias = 'members', string $usersAlias = 'users'): void
    {
        $query
            ->where($usersAlias.'.role', 'student')
            ->where($usersAlias.'.is_activated', 1);
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     * @return array<string, mixed>
     */
    private function buildScoreByCategories(array $semester): array
    {
        return $this->buildCategoryChartByCourses($semester, function ($query): void {
            $earnedScore = ScoreCalculationService::earnedScoreExpression();

            $query->selectRaw("a.category_id as category_id, SUM({$earnedScore}) as aggregate_value")
                ->groupBy('a.category_id');
        });
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     * @return array<string, mixed>
     */
    private function buildActivitiesByCategories(array $semester): array
    {
        $categories = DB::table('activity_categories')
            ->orderBy('id')
            ->get(['id', 'name']);

        $dataByCourse = [];
        foreach (['all', ...self::DASHBOARD_COURSES] as $course) {
            $query = DB::table('activities as a')
                ->whereBetween('a.date_end', [$semester['start'], $semester['end']])
                ->selectRaw('a.category_id as category_id, COUNT(DISTINCT a.id) as aggregate_value')
                ->groupBy('a.category_id');

            if ($course !== 'all') {
                $query->whereExists(function ($subquery) use ($course): void {
                    $subquery->select(DB::raw(1))
                        ->from('activity_user as au')
                        ->join('members as m', 'au.user_id', '=', 'm.user_id')
                        ->join('users as u', 'm.user_id', '=', 'u.id')
                        ->whereColumn('au.activity_id', 'a.id')
                        ->where('u.role', 'student')
                        ->where('u.is_activated', 1)
                        ->where('m.course', $course);
                });
            }

            $dataByCourse[$course === 'all' ? 'all' : 'course_'.$course] = $this->formatCategoryChartRows(
                $categories,
                $query->get()
            );
        }

        return $dataByCourse;
    }

    private function formatCategoryChartRows(Collection $categories, Collection $rows): array
    {
        $indexedRows = $rows->keyBy(fn ($row) => (int) $row->category_id);

        $items = [];
        $total = 0.0;
        foreach ($categories as $category) {
            $value = (float) ($indexedRows[(int) $category->id]->aggregate_value ?? 0);
            $total += $value;
            $items[] = [
                'category_id' => (int) $category->id,
                'category_name' => $category->name,
                'value' => round($value, 2),
            ];
        }

        foreach ($items as &$item) {
            $item['percent'] = $total > 0 ? round(($item['value'] / $total) * 100, 2) : 0.0;
        }
        unset($item);

        return [
            'total' => round($total, 2),
            'items' => $items,
        ];
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     * @return array<string, mixed>
     */
    private function buildCategoryChartByCourses(array $semester, callable $aggregateBuilder, bool $requireConfirmed = true): array
    {
        $categories = DB::table('activity_categories')
            ->orderBy('id')
            ->get(['id', 'name']);

        $courses = ['all', ...self::DASHBOARD_COURSES];
        $dataByCourse = [];

        foreach ($courses as $course) {
            $baseQuery = DB::table('activity_user as au')
                ->join('activities as a', 'au.activity_id', '=', 'a.id')
                ->join('members as m', 'au.user_id', '=', 'm.user_id')
                ->join('users as u', 'm.user_id', '=', 'u.id')
                ->where('u.role', 'student')
                ->where('u.is_activated', 1)
                ->when($requireConfirmed, fn ($q) => $q->where('au.is_confirmed', 1))
                ->whereBetween('a.date_end', [$semester['start'], $semester['end']]);

            if ($course !== 'all') {
                $baseQuery->where('m.course', $course);
            }

            $aggregateBuilder($baseQuery);
            $rows = $baseQuery->get();
            $courseKey = $course === 'all' ? 'all' : 'course_'.$course;
            $dataByCourse[$courseKey] = $this->formatCategoryChartRows($categories, $rows);
        }

        return $dataByCourse;
    }

}
