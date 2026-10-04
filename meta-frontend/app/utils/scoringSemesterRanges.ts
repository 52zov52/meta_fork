import type { AdminScoringBlackoutData } from '~/types/admin-dashboard'

const MM_DD = /^((?:0[1-9]|1[0-2]))-((?:0[1-9]|[12][0-9]|3[01]))$/

function parseMmDd(value: string): { month: number; day: number } | null {
    const s = String(value ?? '').trim()
    const match = MM_DD.exec(s)
    if (!match) return null
    const m = Number(match[1])
    const d = Number(match[2])
    const date = new Date(2000, m - 1, d)
    if (date.getMonth() !== m - 1 || date.getDate() !== d) return null
    return { month: m, day: d }
}

/** Календарный год, в котором начинается учебный год (осенью), для даты `ref`. */
export function academicYearStartCalendarYear(ref: Date): number {
    const y = ref.getFullYear()
    const m = ref.getMonth() + 1
    return m >= 9 ? y : y - 1
}

/** Дата MM-DD внутри учебного года, начинающегося осенью `academicStartYear`. */
function mmddInAcademicYear(mmdd: string, academicStartYear: number): Date | null {
    const parsed = parseMmDd(mmdd)
    if (!parsed) return null
    const { month, day } = parsed
    const year = month >= 9 ? academicStartYear : academicStartYear + 1
    const d = new Date(year, month - 1, day)
    if (d.getMonth() !== month - 1 || d.getDate() !== day) return null
    return d
}

function addDays(date: Date, days: number): Date {
    const d = new Date(date.getTime())
    d.setDate(d.getDate() + days)
    return d
}

function academicYearStartDate(academicStartYear: number): Date {
    return new Date(academicStartYear, 8, 1)
}

function academicYearEndDate(academicStartYear: number): Date {
    return new Date(academicStartYear + 1, 7, 31)
}

const ruDayMonth = new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'long',
})

export function formatRuDayMonth(date: Date): string {
    return ruDayMonth.format(date)
}

export type ScoringSemesterRange = { start: Date; end: Date }

/**
 * Два интервала приёма вне blackout внутри учебного года.
 */
/** Поля `data` из `GET /api/v1/scoring-blackout` (все опциональны до парсинга). */
export type ScoringBlackoutInput = Partial<
    Pick<AdminScoringBlackoutData, 'winter_start' | 'winter_end' | 'summer_start' | 'summer_end'>
>

export function computeScoringSemestersFromBlackout(
    data: ScoringBlackoutInput,
    refDate: Date = new Date(),
): { first: ScoringSemesterRange; second: ScoringSemesterRange } | null {
    const y0 = academicYearStartCalendarYear(refDate)
    const ayStart = academicYearStartDate(y0)
    const ayEnd = academicYearEndDate(y0)

    const winterStart = mmddInAcademicYear(data.winter_start ?? '', y0)
    const winterEnd = mmddInAcademicYear(data.winter_end ?? '', y0)
    const summerStart = mmddInAcademicYear(data.summer_start ?? '', y0)

    if (!winterStart || !winterEnd || !summerStart) return null

    const firstStart = ayStart
    const firstEnd = addDays(winterStart, -1)
    const secondStart = addDays(winterEnd, 1)
    const secondEnd = addDays(summerStart, -1)

    const ordered =
        firstStart.getTime() <= firstEnd.getTime()
        && firstEnd.getTime() < secondStart.getTime()
        && secondStart.getTime() <= secondEnd.getTime()
        && ayStart.getTime() <= firstStart.getTime()
        && secondEnd.getTime() <= ayEnd.getTime()

    if (!ordered) return null

    return {
        first: { start: firstStart, end: firstEnd },
        second: { start: secondStart, end: secondEnd },
    }
}

export function formatScoringSemesterSummaryLines(
    ranges: { first: ScoringSemesterRange; second: ScoringSemesterRange },
): { firstLine: string; secondLine: string } {
    return {
        firstLine: `с ${formatRuDayMonth(ranges.first.start)} по ${formatRuDayMonth(ranges.first.end)}`,
        secondLine: `с ${formatRuDayMonth(ranges.second.start)} по ${formatRuDayMonth(ranges.second.end)}`,
    }
}
