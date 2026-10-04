import type { paths } from '~/types/api'

type StudentProfileShowResponse =
    paths['/api/v1/student/profile/show/{user_id}']['get']['responses'][200]['content']['application/json']

export function useStudentProfileApi() {
    const { get } = useApi()

    async function getProfileShow(userId: string | number): Promise<StudentProfileShowResponse> {
        return await get<StudentProfileShowResponse>(`/api/v1/student/profile/show/${userId}`)
    }

    return { getProfileShow }
}

