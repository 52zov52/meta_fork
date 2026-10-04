<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ScoreService
{
    const CAP_PER_CATEGORY = 50;

    /**
     * Выражение для подзапроса с GROUP BY user_id: сумма по категориям после
     * LEAST(сумма_в_категории, CAP) — как в getCappedTotalForUser.
     *
     * @param  list<int|string>  $categoryIds
     * @return array{0: string, 1: list<int>}
     */
    public static function totalCappedScoreSelectSql(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return ['0', []];
        }

        $cap = self::CAP_PER_CATEGORY;
        $parts = [];
        $bindings = [];
        foreach ($categoryIds as $catId) {
            $parts[] = 'ROUND(LEAST(COALESCE(SUM(CASE WHEN a.category_id = ? THEN ' . ScoreCalculationService::earnedScoreExpression('au', 'a') . ' END), 0), '.$cap.'))';
            $bindings[] = (int) $catId;
        }

        return ['COALESCE('.implode(' + ', $parts).', 0)', $bindings];
    }

    /**
     * То же выражение total capped для встраивания в сырой SQL (только целые id категорий из БД).
     *
     * @param  list<int>  $categoryIds
     */
    public static function totalCappedScoreSqlLiteral(array $categoryIds): string
    {
        if ($categoryIds === []) {
            return '0';
        }

        $cap = self::CAP_PER_CATEGORY;
        $parts = [];
        foreach ($categoryIds as $catId) {
            $id = (int) $catId;
            $parts[] = "ROUND(LEAST(COALESCE(SUM(CASE WHEN a.category_id = {$id} THEN " . ScoreCalculationService::earnedScoreExpression('au', 'a') . " END), 0), {$cap}))";
        }

        return 'COALESCE('.implode(' + ', $parts).', 0)';
    }

    /**
     * Ограниченные баллы по одной категории (после суммы по мероприятиям), для GROUP BY user_id.
     * Добавить алиас: `... as cat_{id}_capped`.
     */
    public static function categoryCappedSumExpression(): string
    {
        return 'ROUND(LEAST(COALESCE(SUM(CASE WHEN a.category_id = ? THEN ' . ScoreCalculationService::earnedScoreExpression('au', 'a') . ' END), 0), '.self::CAP_PER_CATEGORY.'))';
    }

    public static function getCappedScores(int $userId): array
    {
        $semester = SemesterService::getCurrentSemester();

        /* Привязка к семестру идет по дате окончания мероприятия (activities.date_end). */
        $sql = '
            SELECT
                ac.name as category,
                ac.description,
                ROUND(COALESCE(SUM(' . ScoreCalculationService::earnedScoreExpression('au', 'a') . '), 0)) as real_score,
                ROUND(LEAST(COALESCE(SUM(' . ScoreCalculationService::earnedScoreExpression('au', 'a') . '), 0), '.self::CAP_PER_CATEGORY.')) as capped_score
            FROM activity_categories ac
            LEFT JOIN activities a ON ac.id = a.category_id
            LEFT JOIN activity_user au ON a.id = au.activity_id
                AND au.user_id = ?
                AND au.is_confirmed = 1
                AND a.date_end BETWEEN ? AND ?
            GROUP BY ac.id, ac.name, ac.description
            ORDER BY real_score DESC
        ';

        $results = DB::select($sql, [
            $userId,
            $semester['start'],
            $semester['end'],
        ]);

        return array_map(fn ($row) => [
            'category' => $row->category,
            'description' => $row->description,
            'score' => (int) $row->capped_score,
            'real_score' => (int) $row->real_score,
        ], $results);
    }

    public static function getCappedTotalForUser(int $userId): int
    {
        $semester = SemesterService::getCurrentSemester();

        /* Привязка к семестру идет по дате окончания мероприятия (activities.date_end). */
        $sql = '
            SELECT SUM(category_capped) as total
            FROM (
                SELECT
                    a.category_id,
                    ROUND(LEAST(SUM(' . ScoreCalculationService::earnedScoreExpression('au', 'a') . '), '.self::CAP_PER_CATEGORY.')) as category_capped
                FROM activity_user au
                JOIN activities a ON au.activity_id = a.id
                WHERE au.user_id = ?
                  AND au.is_confirmed = 1
                  AND a.date_end BETWEEN ? AND ?
                GROUP BY a.category_id
            ) as category_totals
        ';

        $result = DB::selectOne($sql, [
            $userId,
            $semester['start'],
            $semester['end'],
        ]);

        return (int) $result->total;
    }
}
