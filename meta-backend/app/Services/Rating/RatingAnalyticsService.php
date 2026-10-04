<?php

namespace App\Services\Rating;

use App\Models\Academy;
use App\Models\ActivityCategory;
use App\Models\Institution;
use App\Models\Planet;
use App\Services\ScoreService;
use App\Services\ScoreCalculationService;
use Illuminate\Support\Facades\DB;

/**
 * Агрегаты для вкладки «Аналитика» рейтинга (JSON для графиков).
 */
class RatingAnalyticsService
{
    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string, type?: string}  $period
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return array<string, mixed>
     */
    public function buildAnalyticsPayload(array $period, array $filters, array $scope = ['type' => 'all', 'value' => null]): array
    {
        $cappedScoresQuery = $this->cappedScoresSubquery($period);
        return [
            'period' => $this->periodPayload($period),
            'score' => [
                'byInstitution' => $this->scoreByInstitution($cappedScoresQuery, $filters, $scope),
                'byPlanet' => $this->scoreByPlanet($cappedScoresQuery, $filters, $scope),
                'byCategory' => $this->scoreByCategory($cappedScoresQuery, $filters, $scope),
                'byAcademy' => $this->scoreByAcademy($cappedScoresQuery, $filters, $scope),
                'byAcademyAndCategory' => $this->scoreByAcademyAndCategory($cappedScoresQuery, $filters, $scope),
            ],
            'engagement' => [
                'byAcademy' => $this->engagementByAcademy($period, $filters, $scope),
            ],
            'events' => [
                'byCategory' => $this->eventsByCategory($period, $filters, $scope),
                'byAcademy' => $this->eventsByAcademy($period, $filters, $scope),
            ],
        ];
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string, type?: string}  $period
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return array{period: array{type: string|null, label: string, start: string, end: string}, participantsByPlanet: list<array{id:int,label:string,value:int}>, participantsByCategory: list<array{id:int,label:string,value:int}>}
     */
    public function buildStatisticsPayload(array $period, array $filters, array $scope = ['type' => 'all', 'value' => null]): array
    {
        $cappedScoresQuery = $this->cappedScoresSubquery($period);

        return [
            'period' => $this->periodPayload($period),
            'participantsByPlanet' => $this->scoreByPlanet($cappedScoresQuery, $filters, $scope),
            'participantsByCategory' => $this->scoreByCategory($cappedScoresQuery, $filters, $scope),
        ];
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $period
     */
    private function cappedScoresSubquery(array $period)
    {
        return DB::table('activity_user as au')
            ->join('activities as a', 'au.activity_id', '=', 'a.id')
            ->where('au.is_confirmed', 1)
            ->whereBetween('a.date_end', [$period['start'], $period['end']])
            ->select(
                'au.user_id',
                'a.category_id',
                DB::raw('SUM(' . ScoreCalculationService::earnedScoreExpression('au', 'a') . ') as category_capped')
            )
            ->groupBy('au.user_id', 'a.category_id');
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string, type?: string}  $period
     * @return array{type: string|null, label: string, start: string, end: string}
     */
    private function periodPayload(array $period): array
    {
        return [
            'type' => $period['type'] ?? null,
            'label' => (string) $period['label'],
            'start' => $period['start']->toDateString(),
            'end' => $period['end']->toDateString(),
        ];
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $cappedScoresQuery
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     */
    private function baseMembersQuery($cappedScoresQuery, array $filters, array $scope)
    {
        $q = DB::table('members')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->joinSub($cappedScoresQuery, 'cat_scores', function ($join) {
                $join->on('members.user_id', '=', 'cat_scores.user_id');
            })
            ->where('users.is_activated', 1)
            ->where('users.role', 'student');

        $this->applyMemberConstraints($q, $filters, $scope);

        return $q;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     */
    public function applyMemberFilters($query, array $filters, string $membersAlias = 'members'): void
    {
        if (! empty($filters['academy_ids'])) {
            $query->whereIn($membersAlias.'.academy_id', $filters['academy_ids']);
        }
        if ($filters['education_level_id'] !== null) {
            $query->where($membersAlias.'.education_level_id', $filters['education_level_id']);
        }
        if ($filters['course'] !== null) {
            $query->where($membersAlias.'.course', $filters['course']);
        }
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  array{type: string, value: int|null}  $scope
     */
    public function applyMemberScope($query, array $scope, string $membersAlias = 'members'): void
    {
        if (($scope['type'] ?? 'all') === 'academy') {
            $query->where($membersAlias.'.academy_id', $scope['value']);
        }

        if (($scope['type'] ?? 'all') === 'institution') {
            $query->where($membersAlias.'.institution_id', $scope['value']);
        }
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     */
    private function applyMemberConstraints($query, array $filters, array $scope, string $membersAlias = 'members'): void
    {
        $this->applyMemberFilters($query, $filters, $membersAlias);
        $this->applyMemberScope($query, $scope, $membersAlias);
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $cappedScoresQuery
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return list<int>
     */
    private function scoreByInstitution($cappedScoresQuery, array $filters, array $scope): array
    {
        $institutions = Institution::query()->orderBy('id')->get();

        $out = [];
        foreach ($institutions as $institution) {
            $value = $this->baseMembersQuery($cappedScoresQuery, $filters, $scope)
                ->where('members.institution_id', $institution->id)
                ->selectRaw('COALESCE(ROUND(SUM(cat_scores.category_capped)), 0) as value')
                ->value('value');

            $out[] = [
                'id' => (int) $institution->id,
                'label' => (string) $institution->name,
                'value' => (int) $value,
            ];
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $cappedScoresQuery
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     */
    private function scoreByPlanet($cappedScoresQuery, array $filters, array $scope): array
    {
        $planets = Planet::query()->orderBy('id')->get();
        $out = [];

        foreach ($planets as $planet) {
            $value = DB::table('planets')
                ->leftJoin('members', 'planets.id', '=', 'members.planet_id')
                ->leftJoin('users', function ($join) {
                    $join->on('members.user_id', '=', 'users.id')
                        ->where('users.role', 'student')
                        ->where('users.is_activated', 1);
                })
                ->leftJoinSub($cappedScoresQuery, 'cat_scores', function ($join) {
                    $join->on('users.id', '=', 'cat_scores.user_id');
                })
                ->whereNotNull('users.id')
                ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope))
                ->where('planets.id', $planet->id)
                ->selectRaw('COALESCE(ROUND(SUM(cat_scores.category_capped)), 0) as value')
                ->value('value');

            $out[] = [
                'id' => (int) $planet->id,
                'label' => (string) $planet->name,
                'value' => (int) $value,
            ];
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $cappedScoresQuery
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     */
    private function scoreByCategory($cappedScoresQuery, array $filters, array $scope): array
    {
        $categories = ActivityCategory::query()->get();
        $out = [];
        foreach ($categories as $category) {
            $value = DB::table('activity_categories')
                ->leftJoinSub($cappedScoresQuery, 'cat_scores', function ($join) use ($category) {
                    $join->on('activity_categories.id', '=', 'cat_scores.category_id')
                        ->where('activity_categories.id', $category->id);
                })
                ->leftJoin('users', function ($join) {
                    $join->on('cat_scores.user_id', '=', 'users.id')
                        ->where('users.role', 'student')
                        ->where('users.is_activated', 1);
                })
                ->leftJoin('members', 'users.id', '=', 'members.user_id')
                ->whereNotNull('users.id')
                ->whereNotNull('members.user_id')
                ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope))
                ->where('activity_categories.id', $category->id)
                ->selectRaw('COALESCE(ROUND(SUM(cat_scores.category_capped)), 0) as value')
                ->value('value');

            $out[] = [
                'id' => (int) $category->id,
                'label' => (string) $category->name,
                'value' => (int) $value,
            ];
        }

        usort($out, fn ($a, $b) => $b['value'] <=> $a['value']);

        return $out;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $cappedScoresQuery
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return list<array{academy_id: int|null, academy_name: string, total_score: float, percent: float}>
     */
    private function scoreByAcademy($cappedScoresQuery, array $filters, array $scope): array
    {
        $rows = DB::table('members')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->joinSub($cappedScoresQuery, 'cat_scores', function ($join) {
                $join->on('members.user_id', '=', 'cat_scores.user_id');
            })
            ->leftJoin('academies', 'members.academy_id', '=', 'academies.id')
            ->where('users.is_activated', 1)
            ->where('users.role', 'student')
            ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope))
            ->whereNotNull('members.academy_id')
            ->select(
                'members.academy_id',
                DB::raw('COALESCE(academies.short_name, academies.name, \'\') as academy_name'),
                DB::raw('ROUND(SUM(cat_scores.category_capped)) as total_score')
            )
            ->groupBy('members.academy_id', 'academies.short_name', 'academies.name')
            ->orderByDesc('total_score')
            ->get();

        $total = (float) $rows->sum('total_score');
        $out = [];
        foreach ($rows as $r) {
            $score = (float) $r->total_score;
            $out[] = [
                'id' => (int) $r->academy_id,
                'label' => (string) $r->academy_name,
                'value' => (int) $score,
                'percent' => $total > 0 ? round(($score / $total) * 100, 2) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return list<array{academy_id: int|null, academy_name: string, total_students: int, active_students: int, activity_percent: float}>
     */
    private function engagementByAcademy(array $period, array $filters, array $scope): array
    {
        $totalQuery = DB::table('members')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->where('users.is_activated', 1)
            ->whereNotNull('members.academy_id')
            ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope))
            ->select(
                'members.academy_id',
                DB::raw('COUNT(DISTINCT members.user_id) as total_students')
            )
            ->groupBy('members.academy_id');

        $activePartScores = DB::table('activity_user as au')
            ->join('activities as a', 'au.activity_id', '=', 'a.id')
            ->join('members as m', 'au.user_id', '=', 'm.user_id')
            ->join('users as u', 'm.user_id', '=', 'u.id')
            ->where('u.role', 'student')
            ->where('u.is_activated', 1)
            ->where('au.is_confirmed', 1)
            ->whereBetween('a.date_end', [$period['start'], $period['end']])
            ->whereNotNull('m.academy_id')
            ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope, 'm'))
            ->select('m.academy_id', 'au.user_id as uid');

        $activePartPortfolio = DB::table('activity_portfolio as ap')
            ->join('portfolio as p', 'ap.portfolio_id', '=', 'p.id')
            ->join('members as m', 'p.user_id', '=', 'm.user_id')
            ->join('users as u', 'm.user_id', '=', 'u.id')
            ->where('u.role', 'student')
            ->where('u.is_activated', 1)
            ->whereBetween('ap.created_at', [$period['start'], $period['end']])
            ->whereNotNull('m.academy_id')
            ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope, 'm'))
            ->select('m.academy_id', 'p.user_id as uid');

        $activeRows = DB::query()
            ->fromSub($activePartScores->union($activePartPortfolio), 'combined')
            ->select('combined.academy_id', DB::raw('COUNT(DISTINCT combined.uid) as active_students'))
            ->groupBy('combined.academy_id')
            ->get()
            ->keyBy(fn ($r) => (int) $r->academy_id);

        $totals = $totalQuery->get()->keyBy('academy_id');
        $academies = Academy::all()->keyBy('id');

        $out = [];
        foreach ($totals as $academyId => $t) {
            $aid = (int) $academyId;
            $total = (int) $t->total_students;
            $active = (int) (($activeRows[$aid] ?? null)->active_students ?? 0);
            $academy = $academies->get($aid);
            $name = $academy ? ($academy->short_name ?: $academy->name) : '';
            $out[] = [
                'academy_id' => $aid,
                'academy_name' => $name,
                'total_students' => $total,
                'active_students' => $active,
                'activity_percent' => $total > 0 ? round(($active / $total) * 100, 2) : 0.0,
            ];
        }

        usort($out, function ($a, $b) use ($academies) {
            $academyA = $academies->get($a['academy_id']);
            $academyB = $academies->get($b['academy_id']);

            $instA = $academyA ? $academyA->institution_id : 999;
            $instB = $academyB ? $academyB->institution_id : 999;

            $isAcadA = ($instA === 1) ? 0 : 1;
            $isAcadB = ($instB === 1) ? 0 : 1;

            if ($isAcadA !== $isAcadB) {
                return $isAcadA <=> $isAcadB;
            }

            $nameA = $academyA ? ($academyA->name ?? '') : '';
            $nameB = $academyB ? ($academyB->name ?? '') : '';

            return strcasecmp($nameA, $nameB);
        });

        return $out;
    }

    /**
     * Мероприятия в пересечении с семестром по календарю дат мероприятия.
     *
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return list<array{category_id: int, category_name: string, count: int, percent: float}>
     */
    private function eventsByCategory(array $period, array $filters, array $scope): array
    {
        $semStart = $period['start']->toDateString();
        $semEnd = $period['end']->toDateString();

        $q = DB::table('activities as a')
            ->join('activity_categories as ac', 'a.category_id', '=', 'ac.id')
            ->where('a.date_start', '<=', $semEnd)
            ->where('a.date_end', '>=', $semStart)
            ->select('ac.id as category_id', 'ac.name as category_name', DB::raw('COUNT(a.id) as cnt'))
            ->groupBy('ac.id', 'ac.name');

        $q->whereExists(function ($sub) use ($filters, $scope) {
            $sub->select(DB::raw(1))
                ->from('activity_user as au')
                ->join('members as m', 'au.user_id', '=', 'm.user_id')
                ->join('users as u', 'm.user_id', '=', 'u.id')
                ->whereColumn('au.activity_id', 'a.id')
                ->where('au.is_confirmed', 1)
                ->where('u.role', 'student')
                ->where('u.is_activated', 1);
            $this->applyMemberConstraints($sub, $filters, $scope, 'm');
        });

        $rows = $q->get();
        $total = (int) $rows->sum('cnt');
        $out = [];
        foreach ($rows as $r) {
            $c = (int) $r->cnt;
            $out[] = [
                'category_id' => (int) $r->category_id,
                'category_name' => (string) $r->category_name,
                'count' => $c,
                'percent' => $total > 0 ? round(($c / $total) * 100, 2) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * @param  array{start: \Carbon\Carbon, end: \Carbon\Carbon}  $semester
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return array{total: int, common: int, common_percent: float, distributed_total: int, items: list<array{academy_id: int, academy_name: string, count: int, percent: float}>}
     */
    private function eventsByAcademy(array $period, array $filters, array $scope): array
    {
        $semStart = $period['start']->toDateString();
        $semEnd = $period['end']->toDateString();
        $resolvedAcademySql = "CASE
            WHEN a.academy_id IS NOT NULL THEN a.academy_id
            WHEN creator.role = 'mentor' THEN creator_member.academy_id
            ELSE NULL
        END";

        $eventRows = DB::table('activities as a')
            ->leftJoin('users as creator', 'a.creator_id', '=', 'creator.id')
            ->leftJoin('members as creator_member', 'creator.id', '=', 'creator_member.user_id')
            ->where('a.date_start', '<=', $semEnd)
            ->where('a.date_end', '>=', $semStart)
            ->select('a.id')
            ->selectRaw("{$resolvedAcademySql} as resolved_academy_id");

        $eventRows->whereExists(function ($sub) use ($filters) {
            $sub->select(DB::raw(1))
                ->from('activity_user as au')
                ->join('members as m', 'au.user_id', '=', 'm.user_id')
                ->join('users as u', 'm.user_id', '=', 'u.id')
                ->whereColumn('au.activity_id', 'a.id')
                ->where('au.is_confirmed', 1)
                ->where('u.role', 'student')
                ->where('u.is_activated', 1);

            if (! empty($filters['academy_ids'])) {
                $sub->whereIn('m.academy_id', $filters['academy_ids']);
            }

            if ($filters['education_level_id'] !== null) {
                $sub->where('m.education_level_id', $filters['education_level_id']);
            }

            if ($filters['course'] !== null) {
                $sub->where('m.course', $filters['course']);
            }
        });

        if ($filters['education_level_id'] !== null || $filters['course'] !== null) {
            $eventRows->whereExists(function ($sub) use ($filters) {
                $memberFilters = $filters;
                $memberFilters['academy_ids'] = null;

                $sub->select(DB::raw(1))
                    ->from('activity_user as au')
                    ->join('members as m', 'au.user_id', '=', 'm.user_id')
                    ->join('users as u', 'm.user_id', '=', 'u.id')
                    ->whereColumn('au.activity_id', 'a.id')
                    ->where('au.is_confirmed', 1)
                    ->where('u.role', 'student')
                    ->where('u.is_activated', 1);

                $this->applyMemberFilters($sub, $memberFilters, 'm');
            });
        }

        $q = DB::query()
            ->fromSub($eventRows, 'event_rows')
            ->leftJoin('academies', 'event_rows.resolved_academy_id', '=', 'academies.id');

        if (! empty($filters['academy_ids'])) {
            $q->whereIn('event_rows.resolved_academy_id', $filters['academy_ids']);
        }

        if (($scope['type'] ?? 'all') === 'academy') {
            $q->where(function ($query) use ($scope): void {
                $query->where('event_rows.resolved_academy_id', $scope['value'])
                    ->orWhereNull('event_rows.resolved_academy_id');
            });
        }

        if (($scope['type'] ?? 'all') === 'institution') {
            $q->where(function ($query) use ($scope): void {
                $query->where('academies.institution_id', $scope['value'])
                    ->orWhereNull('event_rows.resolved_academy_id');
            });
        }

        $rows = $q->select(
            'event_rows.resolved_academy_id as academy_id',
            DB::raw('COALESCE(academies.short_name, academies.name, \'\') as academy_name'),
            DB::raw('COUNT(DISTINCT event_rows.id) as cnt')
        )
            ->groupBy('event_rows.resolved_academy_id', 'academies.short_name', 'academies.name')
            ->get();

        $total = (int) $rows->sum('cnt');
        $common = (int) ($rows->firstWhere('academy_id', null)->cnt ?? 0);
        $distributedTotal = $total - $common;
        $out = [];
        foreach ($rows as $r) {
            if ($r->academy_id === null) {
                continue;
            }

            $c = (int) $r->cnt;
            $out[] = [
                'academy_id' => (int) $r->academy_id,
                'academy_name' => (string) $r->academy_name,
                'count' => $c,
                'percent' => $distributedTotal > 0 ? round(($c / $distributedTotal) * 100, 2) : 0.0,
            ];
        }

        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return [
            'total' => $total,
            'common' => $common,
            'common_percent' => $total > 0 ? round(($common / $total) * 100, 2) : 0.0,
            'distributed_total' => $distributedTotal,
            'items' => $out,
        ];
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $cappedScoresQuery
     * @param  array{academy_ids: list<int>|null, education_level_id: int|null, course: int|null}  $filters
     * @param  array{type: string, value: int|null}  $scope
     * @return list<array{academy_id: int, academy_name: string, categories: list<array{name: string, score: float, percent: float}>}>
     */
    private function scoreByAcademyAndCategory($cappedScoresQuery, array $filters, array $scope): array
    {
        $rows = DB::table('members')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->joinSub($cappedScoresQuery, 'cat_scores', function ($join) {
                $join->on('members.user_id', '=', 'cat_scores.user_id');
            })
            ->join('activity_categories as ac', 'cat_scores.category_id', '=', 'ac.id')
            ->leftJoin('academies', 'members.academy_id', '=', 'academies.id')
            ->where('users.is_activated', 1)
            ->where('users.role', 'student')
            ->whereNotNull('members.academy_id')
            ->tap(fn ($q) => $this->applyMemberConstraints($q, $filters, $scope))
            ->select(
                'members.academy_id',
                DB::raw('COALESCE(academies.short_name, academies.name, \'\') as academy_name'),
                'ac.id as category_id',
                'ac.name as category_name',
                DB::raw('ROUND(SUM(cat_scores.category_capped)) as score')
            )
            ->groupBy('members.academy_id', 'academies.short_name', 'academies.name', 'ac.id', 'ac.name')
            ->orderBy('members.academy_id')
            ->get();

        $grouped = [];
        $categoryTotals = [];
        foreach ($rows as $r) {
            $aid = (int) $r->academy_id;
            if (! isset($grouped[$aid])) {
                $grouped[$aid] = [
                    'academy_id' => $aid,
                    'academy_name' => (string) $r->academy_name,
                    'categories' => [],
                ];
            }
            $categoryId = (int) $r->category_id;
            $score = (float) $r->score;
            $grouped[$aid]['categories'][] = [
                'id' => $categoryId,
                'name' => (string) $r->category_name,
                'score' => $score,
            ];
            $categoryTotals[$categoryId] = ($categoryTotals[$categoryId] ?? 0.0) + $score;
        }

        $out = [];
        foreach ($grouped as $g) {
            $cats = [];
            foreach ($g['categories'] as $c) {
                $categoryTotal = $categoryTotals[$c['id']] ?? 0.0;
                $cats[] = [
                    'label' => $c['name'],
                    'value' => (int) round($c['score'], 2),
                    'percent' => $categoryTotal > 0 ? round(($c['score'] / $categoryTotal) * 100, 2) : 0.0,
                ];
            }
            $out[] = [
                'id' => (int) $g['academy_id'],
                'label' => (string) $g['academy_name'],
                'categories' => $cats,
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $out;
    }
}
