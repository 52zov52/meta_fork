import type { paths } from '~/types/api'

type GetShortResponse =
    paths['/api/v1/public/student/users/{user_id}/short']['get']['responses'][200]['content']['application/json']

type GetMyResponse =
    paths['/api/v1/student/users/{user_id}']['get']['responses'][200]['content']['application/json']

export function useStudentHeaderApi() {
    const { get } = useApi()

    async function getShort(userId: string | number): Promise<GetShortResponse> {
        return await get<GetShortResponse>(`/api/v1/public/student/users/${userId}/short`)
    }

    async function getMy(userId: string | number): Promise<GetMyResponse> {
        return await get<GetMyResponse>(`/api/v1/student/users/${userId}`)
    }

    return { getShort, getMy }
}

