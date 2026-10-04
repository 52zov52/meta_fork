import type { paths } from '~/types/api'

type StudentActivityMyGetResponse =
    paths['/api/v1/student/activity/users/{user_id}/activities']['get']['responses'][200]['content']['application/json']

type PortfolioAttachToActivityResponse =
    paths['/api/v1/student/portfolio/attach-to-activity']['post']['responses'][200]['content']['application/json']

export type StudentPortfolioDropdownActivity = {
    id?: number | string
    title?: string
    date_start?: string
    date_end?: string
    planet_name?: string
    status_eng?: string
}

export function useStudentPortfolioApi() {
    const { get, post } = useApi()

    async function getMyActivitiesPage(userId: string | number, page: number): Promise<{
        items: StudentPortfolioDropdownActivity[]
        currentPage: number | null
        lastPage: number | null
    }> {
        const response = await get<StudentActivityMyGetResponse>(
            `/api/v1/student/activity/users/${userId}/activities?page=${page}`,
        )

        const raw = Array.isArray(response?.data) ? response.data : []

        const items: StudentPortfolioDropdownActivity[] = raw.map((a) => ({
            id: a.id,
            title: a.title,
            date_start: a.date_start,
            date_end: a.date_end,
            planet_name: a.planet_name,
            status_eng: a.status_eng,
        }))

        return {
            items,
            currentPage: response.current_page ?? null,
            lastPage: response.last_page ?? null,
        }
    }

    async function attachPortfolioToActivity(payload: { portfolio_id: number; activity_id: number }): Promise<PortfolioAttachToActivityResponse> {
        return await post<PortfolioAttachToActivityResponse>(
            '/api/v1/student/portfolio/attach-to-activity',
            payload,
        )
    }

    return { getMyActivitiesPage, attachPortfolioToActivity }
}

