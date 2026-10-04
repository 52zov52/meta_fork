<?php

namespace App\Services;

use App\Models\ScoringBlackoutSetting;
use Carbon\Carbon;

/**
 * Календарь семестров и запреты на подтверждение баллов / привязку портфолио.
 * 
 * Семестры определяются динамически на основе настроек периодов блокировки (blackout).
 * Обычно:
 * Семестр 1: Сентябрь — Декабрь.
 * Семестр 2: Февраль — Июнь.
 * Учебная пара: осень Y → С1 [Y-09-01, Y-12-31]; весна/лето после неё → С2 [(Y+1)-01-01, (Y+1)-08-31].
 *
 * Запреты (дата без TZ, инклюзивно): зима 26.12—31.01; лето 26.06—31.08.
 */
class SemesterService
{
    private static ?array $blackoutCache = null;

    /**
     * Текущий семестр относительно «сейчас».
     */
    public static function getCurrentSemester(): array
    {
        $now = Carbon::now();
        $year = $now->year;

        // Определяем границы 2-го семестра для текущего календарного года
        $s2 = self::getSemester(2, $year);

        // Если сейчас ДО начала 2-го семестра (например, в январе), 
        // значит мы всё еще в 1-м семестре предыдущего учебного года.
        if ($now->lt($s2['start'])) {
            return self::getSemester(1, $year - 1);
        }

        // Если сейчас ПОСЛЕ конца 2-го семестра (например, в сентябре),
        // значит мы уже в 1-м семестре нового учебного года.
        $s1_current = self::getSemester(1, $year);
        if ($now->gte($s1_current['start'])) {
            return $s1_current;
        }

        return $s2;
    }

    /**
     * Get previous semester boundaries
     */
    public static function getPreviousSemester(): array
    {
        $current = self::getCurrentSemester();
        $now = Carbon::now();

        // Если сейчас 1 семестр, предыдущим был 2 семестр
        if ($current['start']->month === 9) {
            return self::getSemester(2, $current['start']->year);
        }

        // Если сейчас 2 семестр, предыдущим был 1 семестр (прошлого года)
        return self::getSemester(1, $current['start']->year - 1);
    }

    /**
     * Учебный год: два семестра вместе (осень + весна).
     */
    public static function getCurrentAcademicYear(): array
    {
        $current = self::getCurrentSemester();

        // Если сейчас Семестр 1 (начинается в сентябре)
        if ($current['start']->month === 9) {
            $s2 = self::getSemester(2, $current['start']->year + 1);
            return [
                'start' => $current['start'],
                'end' => $s2['end'],
                'label' => $current['start']->year . '-' . ($current['start']->year + 1),
            ];
        }

        // Если сейчас Семестр 2 (начинается в начале года)
        $s1 = self::getSemester(1, $current['start']->year - 1);
        return [
            'start' => $s1['start'],
            'end' => $current['end'],
            'label' => ($current['start']->year - 1) . '-' . $current['start']->year,
        ];
    }

    /**
     * Get semester boundaries by number and year
     * 
     * Семестр 1: Начинается после окончания летнего blackout, 
     *           заканчивается перед началом зимнего blackout.
     * Семестр 2: Начинается после окончания зимнего blackout, 
     *           заканчивается перед началом летнего blackout.
     */
    public static function getSemester(int $number, int $year): array
    {
        $config = self::getScoringBlackoutConfig();

        if ($number === 1) {
            $start = Carbon::createFromFormat('Y-m-d', $year . '-' . $config['summer_end'])->addDay()->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $year . '-' . $config['winter_start'])->subDay()->endOfDay();

            return [
                'start' => $start,
                'end' => $end,
            ];
        }

        // Семестр 2 начинается в указанном году (январь-февраль)
        $start = Carbon::createFromFormat('Y-m-d', $year . '-' . $config['winter_end'])->addDay()->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $year . '-' . $config['summer_start'])->subDay()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Запрет подтверждения баллов и привязки портфолио к мероприятию.
     */
    public static function isScoringBlackout(Carbon $date): bool
    {
        $config = self::getScoringBlackoutConfig();
        $target = $date->format('m-d');

        return self::isWithinRange($target, $config['winter_start'], $config['winter_end'])
            || self::isWithinRange($target, $config['summer_start'], $config['summer_end']);
    }

    public static function getScoringBlackoutConfig(): array
    {
        if (self::$blackoutCache !== null) {
            return self::$blackoutCache;
        }

        $setting = ScoringBlackoutSetting::query()->first();

        // Fallback если настроек нет в базе (хотя они должны быть)
        if (!$setting) {
            return [
                'winter_start' => '12-26',
                'winter_end' => '01-31',
                'summer_start' => '06-26',
                'summer_end' => '08-31',
            ];
        }

        self::$blackoutCache = [
            'winter_start' => self::normalizeMmDd($setting->winter_start),
            'winter_end' => self::normalizeMmDd($setting->winter_end),
            'summer_start' => self::normalizeMmDd($setting->summer_start),
            'summer_end' => self::normalizeMmDd($setting->summer_end),
        ];

        return self::$blackoutCache;
    }

    /**
     * Вычислить границы и лейбл учебного года для произвольной даты на основе периодов blackout.
     * 
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    public static function getAcademicYearForDate(Carbon $date): array
    {
        $year = $date->year;
        $s2 = self::getSemester(2, $year);

        if ($date->lt($s2['start'])) {
            $current = self::getSemester(1, $year - 1);
        } else {
            $s1_current = self::getSemester(1, $year);
            if ($date->gte($s1_current['start'])) {
                $current = $s1_current;
            } else {
                $current = $s2;
            }
        }

        if ($current['start']->month === 9) {
            $s2_full = self::getSemester(2, $current['start']->year + 1);
            return [
                'start' => $current['start'],
                'end' => $s2_full['end'],
                'label' => $current['start']->year . '-' . ($current['start']->year + 1),
            ];
        }

        $s1_full = self::getSemester(1, $current['start']->year - 1);
        return [
            'start' => $s1_full['start'],
            'end' => $current['end'],
            'label' => ($current['start']->year - 1) . '-' . $current['start']->year,
        ];
    }

    /**
     * Сбросить кеш настроек (для тестов)
     */
    public static function clearCache(): void
    {
        self::$blackoutCache = null;
    }

    private static function isWithinRange(string $target, string $start, string $end): bool
    {
        if ($start <= $end) {
            return $target >= $start && $target <= $end;
        }

        // Range crosses year boundary, e.g. 12-26..01-31.
        return $target >= $start || $target <= $end;
    }

    private static function normalizeMmDd(?string $value): string
    {
        if (! is_string($value)) {
            throw new \RuntimeException('Значение периода запрета должно быть строкой в формате MM-DD.');
        }

        if (! preg_match('/^(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/', $value)) {
            throw new \RuntimeException('Значение периода запрета имеет неверный формат MM-DD.');
        }

        [$month, $day] = array_map('intval', explode('-', $value));
        if (! checkdate($month, $day, 2000)) {
            throw new \RuntimeException('Значение периода запрета не является корректной календарной датой.');
        }

        return $value;
    }
}
