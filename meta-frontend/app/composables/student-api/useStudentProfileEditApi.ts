import type { paths } from '~/types/api'

type GetRegionsResponse =
    paths['/api/v1/regions']['get']['responses'][200]['content']['application/json']

type GetPlanetsResponse =
    paths['/api/v1/planets']['get']['responses'][200]['content']['application/json']

type StudentProfileEditResponse =
    paths['/api/v1/student/profile/edit/{user_id}']['get']['responses'][200]['content']['application/json']

type StudentProfileUpdatePersonalResponse =
    paths['/api/v1/student/profile/update/personal/{user_id}']['post']['responses'][200]['content']['application/json']

type StudentProfileUpdateInstitutionResponse =
    paths['/api/v1/student/profile/update/institution/{user_id}']['post']['responses'][200]['content']['application/json']

type StudentProfileUpdateAccountResponse =
    paths['/api/v1/student/profile/update/account/{user_id}']['post']['responses'][200]['content']['application/json']

type StudentProfileUpdatePasswordResponse =
    paths['/api/v1/student/profile/update/password/{user_id}']['post']['responses'][200]['content']['application/json']

type StudentGetMyResponse =
    paths['/api/v1/student/users/{user_id}']['get']['responses'][200]['content']['application/json']

export type RegionOption = { id: number; name: string }
export type PlanetOption = { id: number; name: string }

export type ProfileEditState = {
    last_name?: string
    first_name?: string
    middle_name?: string
    gender?: string | number
    birthday?: string
    region_id?: number
    planet_id?: number
    institution_id?: number
    institution?: string
    course?: string
    student_group?: string
    email?: string
}

export function useStudentProfileEditApi() {
    const { get, post } = useApi()

    async function getRegions(): Promise<RegionOption[]> {
        const data = await get<GetRegionsResponse>('/api/v1/regions')
        return data.map((r) => ({ id: Number(r.id), name: String(r.name ?? '') }))
    }

    async function getPlanets(): Promise<PlanetOption[]> {
        const data = await get<GetPlanetsResponse>('/api/v1/planets')
        return data.map((p) => ({ id: Number(p.id), name: String(p.name ?? '') }))
    }

    async function getProfileEdit(userId: string | number): Promise<ProfileEditState> {
        const data = await get<StudentProfileEditResponse>(`/api/v1/student/profile/edit/${userId}`)

        return {
            last_name: data.last_name,
            first_name: data.first_name,
            middle_name: data.middle_name,
            gender: data.gender,
            birthday: data.birthday,
            region_id: data.region_id,
            planet_id: data.planet_id,
            institution_id: data.institution_id,
            institution: data.institution,
            course: data.course != null ? String(data.course) : undefined,
            student_group: data.student_group,
            email: data.email,
        }
    }

    async function updatePersonal(userId: string | number, payload: Record<string, unknown>): Promise<StudentProfileUpdatePersonalResponse> {
        return await post<StudentProfileUpdatePersonalResponse>(`/api/v1/student/profile/update/personal/${userId}`, payload)
    }

    async function updateInstitution(userId: string | number, payload: Record<string, unknown>): Promise<StudentProfileUpdateInstitutionResponse> {
        return await post<StudentProfileUpdateInstitutionResponse>(`/api/v1/student/profile/update/institution/${userId}`, payload)
    }

    async function updateAccount(userId: string | number, payload: FormData): Promise<StudentProfileUpdateAccountResponse> {
        return await post<StudentProfileUpdateAccountResponse>(
            `/api/v1/student/profile/update/account/${userId}`,
            payload,
        )
    }

    async function updatePassword(userId: string | number, payload: Record<string, unknown>): Promise<StudentProfileUpdatePasswordResponse> {
        return await post<StudentProfileUpdatePasswordResponse>(`/api/v1/student/profile/update/password/${userId}`, payload)
    }

    async function pingGetMy(userId: string | number): Promise<StudentGetMyResponse> {
        return await get<StudentGetMyResponse>(`/api/v1/student/users/${userId}`)
    }

    return { getRegions, getPlanets, getProfileEdit, updatePersonal, updateInstitution, updateAccount, updatePassword, pingGetMy }
}

