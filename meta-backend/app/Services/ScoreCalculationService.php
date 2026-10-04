<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityScore;
use App\Models\BaseScore;
use App\Models\EducationLevel;
use App\Models\KsuMatrix;

class ScoreCalculationService
{
    /**
     * SQL-выражение для единой формулы начисления баллов.
     * Используется во всех агрегатных запросах (рейтинг, история, аналитика).
     */
    public static function earnedScoreExpression(string $auAlias = 'au', string $aAlias = 'a'): string
    {
        return "COALESCE({$auAlias}.earned_score, {$aAlias}.score * {$auAlias}.level)";
    }

    /**
     * Чистая функция расчета балла с КСУ (для unit-тестов).
     * UTA здесь — NUO целевого уровня обучения мероприятия.
     */
    public static function calculateKsuScore(int $baseScore, int $nuo, int $uta): int
    {
        $delta = $uta - $nuo;
        $coefficient = KsuMatrix::where('delta', $delta)->where('is_active', true)->first()?->coefficient ?? 1.00;

        return (int) round($baseScore * $coefficient);
    }

    /**
     * Найти подходящую запись base_scores по категории и параметрам.
     */
    public static function resolveBaseScore(int $categoryId, array $params): ?BaseScore
    {
        return BaseScore::where('category_id', $categoryId)
            ->where('activity_type', $params['activity_type'] ?? null)
            ->where('has_places', $params['has_places'] ?? false)
            ->when(array_key_exists('has_implementation', $params), function ($query) use ($params) {
                $query->where('has_implementation', $params['has_implementation']);
            })
            ->when(array_key_exists('is_leader', $params), function ($query) use ($params) {
                $query->where('is_leader', $params['is_leader']);
            })
            ->where('is_active', true)
            ->first();
    }

    /**
     * Нормализует список target_education_level_ids → массив уникальных int.
     */
    private static function normalizeTargetIds(mixed $targetIds): array
    {
        if ($targetIds === null) {
            return [];
        }
        if (! is_array($targetIds)) {
            $targetIds = [$targetIds];
        }
        return array_values(array_unique(array_map('intval', array_filter($targetIds, fn ($v) => $v !== null && $v !== ''))));
    }

    /**
     * NUO для каждого выбранного target. Сортируется по возрастанию.
     */
    private static function targetNuos(array $targetIds): array
    {
        if ($targetIds === []) {
            return [];
        }
        $nuos = EducationLevel::whereIn('id', $targetIds)->pluck('nuo_value')->map(fn ($v) => (int) $v)->all();
        sort($nuos);
        return $nuos;
    }

    /**
     * Возвращает [delta, ksu] для данного NUO студента и набора NUO target-ов.
     * - Если target пуст → delta=0, KSU=1.0
     * - Если NUO студента совпадает с любым target → delta=0, KSU=1.0
     * - Иначе берётся ближайший target и delta = target_NUO − student_NUO
     */
    private static function deltaAndKsu(int $studentNuo, array $targetNuos): array
    {
        if ($targetNuos === [] || in_array($studentNuo, $targetNuos, true)) {
            return [0, (float) 1.0];
        }

        $nearest = $targetNuos[0];
        $minDist = abs($studentNuo - $nearest);
        foreach ($targetNuos as $nuo) {
            $dist = abs($studentNuo - $nuo);
            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = $nuo;
            }
        }

        $delta = $nearest - $studentNuo;
        $ksu = (float) (KsuMatrix::where('delta', $delta)->where('is_active', true)->first()?->coefficient ?? 1.00);

        return [$delta, $ksu];
    }

    /**
     * Рассчитать превью баллов без сохранения. Возвращает строку на каждый уровень обучения.
     * $targetEducationLevelIds — null/пустой массив = равные баллы (KSU=1.00).
     */
    public static function calculatePreview(int $categoryId, array $params, ?array $targetEducationLevelIds = null): array
    {
        $baseScore = self::resolveBaseScore($categoryId, $params);

        if (! $baseScore) {
            return [];
        }

        $targetIds = self::normalizeTargetIds($targetEducationLevelIds);
        $targetNuos = self::targetNuos($targetIds);
        $educationLevels = EducationLevel::orderBy('nuo_value')->get();
        $preview = [];

        $places = [
            '1st_place' => 'first_place',
            '2nd_place' => 'second_place',
            '3rd_place' => 'third_place',
            'participation' => 'participation',
        ];

        foreach ($educationLevels as $level) {
            $nuo = (int) $level->nuo_value;
            [$delta, $ksu] = self::deltaAndKsu($nuo, $targetNuos);

            $scores = [];
            foreach ($places as $placeCode => $field) {
                $value = $baseScore->$field;
                if ($value !== null) {
                    $scores[$placeCode] = (int) round($value * $ksu);
                }
            }

            $preview[] = [
                'education_level_id' => $level->id,
                'education_level_name' => $level->name,
                'nuo' => $nuo,
                'delta' => $delta,
                'ksu' => round((float) $ksu, 2),
                'is_target' => in_array($level->id, $targetIds, true),
                'scores' => $scores,
            ];
        }

        return $preview;
    }

    /**
     * Рассчитать и сохранить баллы для мероприятия в activity_scores.
     * Записи создаются для ВСЕХ уровней обучения.
     */
    public static function calculateAndStore(Activity $activity, array $params, ?array $targetEducationLevelIds = null): void
    {
        $baseScore = self::resolveBaseScore((int) $activity->category_id, $params);

        ActivityScore::where('activity_id', $activity->id)->delete();

        if (! $baseScore) {
            return;
        }

        $targetIds = self::normalizeTargetIds($targetEducationLevelIds);
        $targetNuos = self::targetNuos($targetIds);
        $educationLevels = EducationLevel::orderBy('nuo_value')->get();

        $places = [
            '1st' => 'first_place',
            '2nd' => 'second_place',
            '3rd' => 'third_place',
            'participation' => 'participation',
        ];

        foreach ($educationLevels as $level) {
            $nuo = (int) $level->nuo_value;
            [$delta, $ksu] = self::deltaAndKsu($nuo, $targetNuos);

            foreach ($places as $placeCode => $field) {
                $baseValue = $baseScore->$field;
                if ($baseValue === null) {
                    continue;
                }

                ActivityScore::create([
                    'activity_id' => $activity->id,
                    'education_level_id' => $level->id,
                    'place' => $placeCode,
                    'base_score' => $baseValue,
                    // uta_level в записи теперь несёт смысл "эффективный target NUO для данного студента"
                    'uta_level' => $delta === 0 ? $nuo : $nuo + $delta,
                    'nuo_value' => $nuo,
                    'delta' => $delta,
                    'ksu_coefficient' => $ksu,
                    'final_score' => (int) round($baseValue * $ksu),
                ]);
            }
        }
    }
}
