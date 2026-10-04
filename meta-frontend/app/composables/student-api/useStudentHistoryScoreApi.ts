import type { paths } from '~/types/api'

type GetCategoriesResponse =
    paths['/api/v1/categories']['get']['responses'][200]['content']['application/json']

type HistoryScoreResponse =
    paths['/api/v1/public/student/history-score/{user_id}']['get']['responses'][200]['content']['application/json']

type PublicStudentActivityResponse =
    paths['/api/v1/public/student/activity/{id}']['get']['responses'][200]['content']['application/json']

export type StudentHistoryScoreItem = {
    title?: string
    score?: number
    category?: string
    score_assignment_date?: string
    activity_id?: number | string
    portfolio_id?: number | string | null
}

export type StudentCategoryOption = { name?: string }

export function useStudentHistoryScoreApi() {
    const { get } = useApi()

    async function getCategories(): Promise<StudentCategoryOption[]> {
        const data = await get<GetCategoriesResponse>('/api/v1/categories')
        return data
    }

    async function getHistoryScore(userId: string | number, params: { page: number; semester?: string }): Promise<{
        items: StudentHistoryScoreItem[]
        currentPage: number | null
        lastPage: number | null
        perPage: number | null
        totalItems: number
        prevPageUrl: string | null
        nextPageUrl: string | null
    }> {
        const data = await get<HistoryScoreResponse>(`/api/v1/public/student/history-score/${userId}`, { query: params })

        return {
            items: data.data ?? [],
            currentPage: data.current_page ?? null,
            lastPage: data.last_page ?? null,
            perPage: data.per_page ?? null,
            totalItems: data.total ?? 0,
            prevPageUrl: data.prev_page_url ?? null,
            nextPageUrl: data.next_page_url ?? null,
        }
    }

    async function getPublicActivity(activityId: number | string): Promise<PublicStudentActivityResponse> {
        return await get<PublicStudentActivityResponse>(`/api/v1/public/student/activity/${activityId}`)
    }

    return { getCategories, getHistoryScore, getPublicActivity }
}

