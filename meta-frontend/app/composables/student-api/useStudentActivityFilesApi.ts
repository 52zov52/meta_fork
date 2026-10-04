import type { paths } from '~/types/api'

type ActivityFilesResponse =
    paths['/api/v1/activities/{activity_id}/files']['get']['responses'][200]['content']['application/json']

export type ActivityFile = NonNullable<ActivityFilesResponse['files']>[number]

export function useStudentActivityFilesApi() {
    const { get, getBlob } = useApi()
    const toast = useToast()

    async function getActivityFiles(activityId: number | string): Promise<ActivityFile[]> {
        const data = await get<ActivityFilesResponse>(`/api/v1/activities/${activityId}/files`)
        return data.files ?? []
    }

    async function downloadActivityFile(fileId: number | string, fileName?: string): Promise<void> {
        try {
            const { data } = await getBlob(`/api/v1/activities/files/${fileId}/download`)
            const url = window.URL.createObjectURL(data)
            const a = document.createElement('a')
            a.href = url
            a.download = fileName ?? 'file'
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(url)
        } catch {
            toast.add({ title: 'Файл не найден', color: 'error' })
        }
    }

    return { getActivityFiles, downloadActivityFile }
}
