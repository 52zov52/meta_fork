import type { paths } from '~/types/api'

type CategoryItem =
    paths['/api/v1/categories']['get']['responses'][200]['content']['application/json'][number]
type AcademyItem =
    paths['/api/v1/academies']['get']['responses'][200]['content']['application/json'][number]

type GetCategoriesResponse =
    paths['/api/v1/categories']['get']['responses'][200]['content']['application/json']
type GetAcademiesResponse =
    paths['/api/v1/academies']['get']['responses'][200]['content']['application/json']

export function useStudentActivitiesApi() {
    const { get } = useApi()

    async function getCategories(): Promise<CategoryItem[]> {
        const data = await get<GetCategoriesResponse>('/api/v1/categories')
        return data
    }

    async function getAcademies(): Promise<AcademyItem[]> {
        const data = await get<GetAcademiesResponse>('/api/v1/academies')
        return data
    }

    return { getCategories, getAcademies }
}

