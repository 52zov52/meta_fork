import type { paths } from '~/types/api'

interface DemographicsItem {
    id: number
    label: string
    count: number
}

interface ActivitiesByYearItem {
    year: string | number
    count: number
}

/** Тело ответа `GET /api/v1/admin/statistics` (новая структура после рефакторинга бэка). */
export interface AdminDashboardResponse {
    periods: {
        all_time: {
            users: {
                total: number
                active: number
            }
        }
        semester: {
            type: string
            label: string
            start: string
            end: string
            activities: {
                total: number
                active: number
            }
        }
        academic_year: {
            type: string
            label: string
            start: string
            end: string
            total_score_sum: number
        }
    }
    demographics: {
        by_planet: DemographicsItem[]
        by_institution: DemographicsItem[]
    }
    activities_by_year: ActivitiesByYearItem[]
}

/** Ответ публичного `GET /api/v1/scoring-blackout` (чтение периодов). */
export type AdminScoringBlackoutGetJson =
    paths['/api/v1/scoring-blackout']['get']['responses'][200]['content']['application/json']

/** Текущие эффективные значения blackout-периодов (`data` в ответе). */
export type AdminScoringBlackoutData = NonNullable<AdminScoringBlackoutGetJson['data']>

/** Тело запроса `PUT /api/v1/admin/scoring-blackout`. */
export type AdminScoringBlackoutPutBody =
    paths['/api/v1/admin/scoring-blackout']['put']['requestBody']['content']['application/json']
