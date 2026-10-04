import type { paths } from '~/types/api'

type ActivityFilesResponse =
    paths['/api/v1/activities/{activity_id}/files']['get']['responses'][200]['content']['application/json']

type UploadFileResponse =
    paths['/api/v1/admin/activities/{activity_id}/files']['post']['responses'][200]['content']['application/json']

export type AdminActivityFile = NonNullable<ActivityFilesResponse['files']>[number]

export function useAdminActivityFilesApi() {
    const { get, post, delete: del } = useApi()
    const toast = useToast()

    async function getActivityFiles(activityId: number | string): Promise<AdminActivityFile[]> {
        const data = await get<ActivityFilesResponse>(`/api/v1/activities/${activityId}/files`)
        return data.files ?? []
    }

    async function uploadFile(activityId: number | string, file: File): Promise<AdminActivityFile | null> {
        const formData = new FormData()
        formData.append('file', file)

        const data = await post<UploadFileResponse>(
            `/api/v1/admin/activities/${activityId}/files`,
            formData
        )

        if (data.file) {
            toast.add({ title: data.message ?? 'Файл успешно загружен!', color: 'success' })
            return data.file as unknown as AdminActivityFile
        }

        return null
    }

    async function deleteFile(fileId: number | string): Promise<boolean> {
        try {
            const data = await del<paths['/api/v1/admin/activities/files/{file_id}']['delete']['responses'][200]['content']['application/json']>(
                `/api/v1/admin/activities/files/${fileId}`
            )
            toast.add({ title: data.message ?? 'Файл успешно удалён!', color: 'success' })
            return true
        } catch {
            toast.add({ title: 'Файл не найден', color: 'error' })
            return false
        }
    }

    return { getActivityFiles, uploadFile, deleteFile }
}
