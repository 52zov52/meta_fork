<template>
    <div class="min-w-0 w-full flex flex-col space-y-4">
        <div v-if="loading" class="flex justify-center items-center py-12">
            <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary" />
        </div>

        <form v-else @submit.prevent="save">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Наименование<span class="ml-1 text-error">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Максимум 255 символов.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <UInput v-model="form.title" class="w-full" placeholder="Название мероприятия" />
                    <ul v-for="(error, index) in fieldErrors(errors.title)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Описание<span class="ml-1 text-error">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Максимум 5000 символов.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <UTextarea v-model="form.description" class="w-full" :rows="10" autoresize />
                    <ul v-for="(error, index) in fieldErrors(errors.description)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Дата старта<span class="ml-1 text-error">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <RuDateInput v-model="form.date_start" />
                    <ul v-for="(error, index) in fieldErrors(errors.date_start)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Дата завершения<span class="ml-1 text-error">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <RuDateInput v-model="form.date_end" />
                    <ul v-for="(error, index) in fieldErrors(errors.date_end)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Категория<span class="ml-1 text-error">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect v-model="form.category_id" :items="categorySelectItems" placeholder="Выберите категорию" class="w-full" />
                    <ul v-for="(error, index) in fieldErrors(errors.category_id)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Баллы за участие</h2>
                </div>
                <div class="flex flex-col space-y-4 justify-center">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Автоматический расчёт баллов (КСУ)</span>
                        <USwitch v-model="form.is_dynamic_score" />
                    </div>
                    <div v-if="!form.is_dynamic_score" class="space-y-1 mt-2">
                        <UInput v-model="form.score" type="number" class="w-full" placeholder="Укажите баллы" />
                        <ul v-for="(error, index) in fieldErrors(errors.score)" :key="index">
                            <li class="text-xs text-error">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <template v-if="form.is_dynamic_score">
                <section v-if="form.category_id == 1 || form.category_id == 2 || form.category_id == 4" class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Тип активности<span class="ml-1 text-error">*</span></h2>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <USelect v-model="form.parameters.activity_type" :items="activityTypeOptions" placeholder="Выберите тип активности" class="w-full" />
                        <ul v-if="errors['parameters.activity_type']" class="mt-1">
                            <li class="text-xs text-error">{{ errors['parameters.activity_type'] }}</li>
                        </ul>
                        <template v-if="form.category_id == 2">
                            <p class="text-xs text-error font-medium mt-1">Публикационная активность: уровень публикации обязательно минимум РИНЦ</p>
                        </template>
                        <template v-if="form.category_id == 4">
                            <p class="text-xs text-muted mt-1"><span class="font-medium">Внешний</span> — мероприятия вне вуза.</p>
                            <p class="text-xs text-muted"><span class="font-medium">Внутренний</span> — мероприятия внутри вуза.</p>
                            <p class="text-xs text-muted"><span class="font-medium">Просветительские</span> — диктанты, экскурсии в мемориалы, культурные события (5 баллов, без мест).</p>
                        </template>
                    </div>
                </section>
                <hr v-if="form.category_id == 1 || form.category_id == 2 || form.category_id == 4" role="presentation" class="my-10 w-full border-t border-default">

                <section v-if="form.category_id == 1 && form.parameters.activity_type === 'internal'" class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                    <div class="space-y-1"><h2 class="text-base/7 font-semibold sm:text-sm/6">Внедрение</h2></div>
                    <div class="flex flex-col space-y-1 justify-center"><UCheckbox v-model="form.parameters.has_implementation" label="С внедрением" /></div>
                </section>
                <hr v-if="form.category_id == 1 && form.parameters.activity_type === 'internal'" role="presentation" class="my-10 w-full border-t border-default">

                <section v-if="(form.category_id == 1 && form.parameters.activity_type === 'external') || (form.category_id == 2 && form.parameters.activity_type === 'nir_competition') || (form.category_id == 4 && form.parameters.activity_type === 'cultural_external')" class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                    <div class="space-y-1"><h2 class="text-base/7 font-semibold sm:text-sm/6">Призовые места</h2></div>
                    <div class="flex flex-col space-y-1 justify-center"><UCheckbox v-model="form.parameters.has_places" label="Есть призовые места" /></div>
                </section>
                <hr v-if="(form.category_id == 1 && form.parameters.activity_type === 'external') || (form.category_id == 2 && form.parameters.activity_type === 'nir_competition') || (form.category_id == 4 && form.parameters.activity_type === 'cultural_external')" role="presentation" class="my-10 w-full border-t border-default">

                <section v-if="form.category_id == 3" class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                    <div class="space-y-1"><h2 class="text-base/7 font-semibold sm:text-sm/6">Особая благодарность</h2></div>
                    <div class="flex flex-col space-y-1 justify-center"><UCheckbox v-model="form.parameters.is_leader" label="Особая благодарность (лидер движения)" /></div>
                </section>
                <hr v-if="form.category_id == 3" role="presentation" class="my-10 w-full border-t border-default">

                <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Целевой уровень обучения</h2>
                        <p data-slot="text" class="text-base/6 sm:text-sm/6">Можно выбрать 1 или 2 соседних уровня.</p>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <USelect v-model="form.parameters.target_education_level_ids" :items="targetLevelItems" placeholder="Все" multiple class="w-full" />
                    </div>
                </section>
                <hr role="presentation" class="my-10 w-full border-t border-default">

                <section v-if="previewData.length > 0" class="space-y-4">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Предварительный расчёт баллов</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-default">
                            <thead>
                                <tr class="bg-muted">
                                    <th class="border border-default px-3 py-2 text-left">Уровень обучения</th>
                                    <th v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">1 место</th>
                                    <th v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">2 место</th>
                                    <th v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">3 место</th>
                                    <th class="border border-default px-3 py-2 text-center">Участие</th>
                                    <th class="border border-default px-3 py-2 text-center">КСУ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in previewData" :key="row.education_level_id" :class="row.is_target ? 'bg-primary/10 font-medium' : 'hover:bg-muted'">
                                    <td class="border border-default px-3 py-2">{{ row.education_level_name }}<span v-if="row.is_target" class="ml-2 text-xs text-primary">(целевой)</span></td>
                                    <td v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">{{ row.scores?.['1st_place'] ?? '—' }}</td>
                                    <td v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">{{ row.scores?.['2nd_place'] ?? '—' }}</td>
                                    <td v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">{{ row.scores?.['3rd_place'] ?? '—' }}</td>
                                    <td class="border border-default px-3 py-2 text-center">{{ row.scores?.participation ?? '—' }}</td>
                                    <td class="border border-default px-3 py-2 text-center text-xs text-muted">×{{ row.ksu?.toFixed(2) ?? '1.00' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <hr v-if="previewData.length > 0" role="presentation" class="my-10 w-full border-t border-default">
            </template>

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Статус<span class="ml-1 text-error">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect v-model="form.status" :items="statusOptions" placeholder="Выберите статус" class="w-full" />
                    <ul v-for="(error, index) in fieldErrors(errors.status)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Направление<span class="ml-1 text-error">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect v-model="form.planet_id" :items="planetSelectItems" placeholder="Выберите направление" class="w-full" />
                    <ul v-for="(error, index) in fieldErrors(errors.planet_id)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1"><h2 class="text-base/7 font-semibold sm:text-sm/6">Академия</h2></div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect v-model="form.academy_id" :items="academySelectItems" placeholder="Выберите академию" class="w-full" />
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Изображение</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Загрузите изображение для карточки. Максимум 2 Мб.</p>
                </div>
                <div class="flex flex-col space-y-3 justify-center">
                    <UFileUpload v-model="uploadFile" accept="image/*" :multiple="false" :preview="false" variant="area" label="Выберите файл" description="Изображение до 2 Мб" :disabled="form.files.length >= 1" class="w-full" />
                    <div v-if="form.files.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Новое изображение:</p>
                        <div class="relative inline-block">
                            <img v-if="form.files[0]" :src="form.files[0].preview" class="w-32 h-32 object-cover rounded border border-default">
                            <UButton type="button" color="error" size="xs" class="absolute top-1 right-1" icon="i-heroicons-trash" @click="removeImage" />
                        </div>
                    </div>
                    <div v-if="form.photo_path && form.files.length === 0" class="space-y-2">
                        <p class="text-sm font-medium">Текущее изображение:</p>
                        <img :src="config.public.API_PATH_STORAGE + form.photo_path" class="w-20 h-20 object-cover rounded border border-default">
                    </div>
                    <ul v-for="(error, index) in fieldErrors(errors.file)" :key="index">
                        <li class="text-xs text-error">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Файлы мероприятия</h2>
                </div>
                <div class="flex flex-col space-y-3 justify-center">
                    <UFileUpload v-model="materialFiles" accept=".pdf,.docx,.doc,.zip" multiple variant="area" layout="list" label="Выберите файлы" description="Файлы до 10 Мб" class="w-full" />
                    <div v-if="existingFiles.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Загруженные файлы:</p>
                        <div v-for="file in existingFiles" :key="file.id" class="flex items-center gap-2 text-sm">
                            <UIcon name="i-heroicons-document" class="w-5 h-5 text-muted" />
                            <span class="truncate">{{ file.original_name }}</span>
                            <span class="text-muted">({{ file.formatted_size }})</span>
                            <UButton type="button" color="error" size="xs" variant="ghost" icon="i-heroicons-trash" :loading="deletingFileId === file.id" @click="removeExistingFile(file.id)" />
                        </div>
                    </div>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <div class="flex justify-end gap-3">
                <UButton color="neutral" variant="outline" @click="navigateTo('/mentor/activities')">Отмена</UButton>
                <UButton type="submit" color="success" :loading="saving">Сохранить изменения</UButton>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { apiDateToRuDate, ruDateToApiDate } from '~/utils/ruDate'
import type { paths } from '~/types/api'

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['mentor'],
    layout: 'mentor',
    title: 'Редактирование мероприятия',
    description: 'Редактирование мероприятия',
    breadcrumb: [
        { label: 'Главная', to: '/mentor' },
        { label: 'Мероприятия', to: '/mentor/activities' },
        { label: 'Редактирование' },
    ],
});

type PlanetsResponse = paths['/api/v1/admin/planets']['get']['responses'][200]['content']['application/json']
type PlanetItem = PlanetsResponse[number]
type CategoriesResponse = paths['/api/v1/categories']['get']['responses'][200]['content']['application/json']
type CategoryItem = CategoriesResponse[number]
type AcademiesResponse = paths['/api/v1/academies']['get']['responses'][200]['content']['application/json']
type AcademyItem = AcademiesResponse[number]

const config = useRuntimeConfig()
const route = useRoute()
const { user } = useSanctumAuth()
const authUser = computed(() => user.value as { user_id: number } | null)
const { get, post, delete: del, uploadFileApi } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const loading = ref(true)
const saving = ref(false)
const errors = ref<Record<string, any>>({})
const activityId = Number(route.params.id)

type ActivityFile = { id: number; original_name: string; formatted_size: string }

function fieldErrors(value: any): string[] {
    if (value == null || value === '') return []
    if (Array.isArray(value)) return value
    return [String(value)]
}
function clearErrors() { errors.value = {} }

const form = ref({
    title: '', description: '', date_start: '', date_end: '', score: 0,
    status: 'draft', planet_id: 0, academy_id: 0, category_id: 0,
    photo_path: null as string | null,
    files: [] as { file: File; preview: string }[],
    is_dynamic_score: true,
    parameters: {
        activity_type: undefined as string | undefined,
        has_places: false, has_implementation: false, is_leader: false,
        target_education_level_ids: [] as number[]
    }
})

const planets = ref<PlanetItem[]>([])
const categories = ref<CategoryItem[]>([])
const academies = ref<AcademyItem[]>([])
const educationLevels = ref<{ id: number; name: string; nuo_value?: number }[]>([])
const previewData = ref<any[]>([])
const previewHasPlaces = ref(false)
const uploadFile = ref<File | null>(null)
const materialFiles = ref<File[]>([])
const existingFiles = ref<ActivityFile[]>([])
const deletingFileId = ref<number | null>(null)
let previewTimeout: ReturnType<typeof setTimeout> | null = null

const statusOptions = [
    { label: 'Черновик', value: 'draft' }, { label: 'Запланировано', value: 'scheduled' },
    { label: 'Началось', value: 'started' }, { label: 'Завершено', value: 'completed' },
    { label: 'Отменено', value: 'canceled' }
]

function asIdList(val: unknown) {
    if (Array.isArray(val)) return val
    if (val && typeof val === 'object') return Object.values(val)
    return []
}

const planetSelectItems = computed(() => [
    { label: 'Выберите направление', value: 0 },
    ...asIdList(planets.value).map((p: PlanetItem) => ({ label: p.name ?? '', value: p.id ?? 0 })),
])
const categorySelectItems = computed(() => [
    { label: 'Выберите категорию', value: 0 },
    ...asIdList(categories.value).map((c: CategoryItem) => ({ label: c.name ?? '', value: c.id ?? 0 })),
])
const academySelectItems = computed(() => [
    { label: 'Для всех', value: 0 },
    ...asIdList(academies.value).map((a: AcademyItem) => ({ label: a.name ?? '', value: a.id ?? 0 })),
])

const activityTypeOptions = computed(() => {
    if (form.value.category_id == 1) return [{ label: 'Внутреннее', value: 'internal' }, { label: 'Внешнее', value: 'external' }]
    if (form.value.category_id == 2) return [
        { label: 'Публикация в сборнике РИНЦ', value: 'rinz_collection' },
        { label: 'Выступление на конференции РИНЦ с публикацией', value: 'conference_presentation' },
        { label: 'Публикация в журнале РИНЦ', value: 'rinz_journal' },
        { label: 'Публикация в сборнике ВАК', value: 'vak_collection' },
        { label: 'Хоздоговорная НИР', value: 'nir_contract' },
        { label: 'НИР в рамках конкурса', value: 'nir_competition' }
    ]
    if (form.value.category_id == 4) return [
        { label: 'Внутреннее', value: 'cultural_internal' },
        { label: 'Внешнее', value: 'cultural_external' },
        { label: 'Просветительские', value: 'cultural_outreach' }
    ]
    return []
})

const targetLevelItems = computed(() => {
    const selected = form.value.parameters.target_education_level_ids || []
    const sorted = [...educationLevels.value].sort((a, b) => (a.nuo_value ?? 0) - (b.nuo_value ?? 0))
    return sorted.map((level) => {
        let disabled = false
        if (selected.length >= 2 && !selected.includes(level.id)) disabled = true
        else if (selected.length === 1 && !selected.includes(level.id)) {
            const selectedNuo = educationLevels.value.find(l => l.id === selected[0])?.nuo_value
            if (selectedNuo != null && Math.abs((level.nuo_value ?? 0) - selectedNuo) !== 1) disabled = true
        }
        return { label: level.name, value: level.id, disabled }
    })
})

function buildKsuParams(categoryId: number, parameters: any) {
    const params: Record<string, any> = {}
    if (parameters.activity_type) params.activity_type = parameters.activity_type
    if (categoryId == 1 && parameters.activity_type === 'internal') params.has_implementation = !!parameters.has_implementation
    if ((categoryId == 1 && parameters.activity_type === 'external') || (categoryId == 2 && parameters.activity_type === 'nir_competition') || (categoryId == 4 && parameters.activity_type === 'cultural_external'))
        params.has_places = !!parameters.has_places
    if (categoryId == 3) params.is_leader = !!parameters.is_leader
    return params
}
function ensureNumberIds(ids: any[]) { return (ids || []).map(id => Number(id)).filter(id => !isNaN(id)) }

async function calculatePreview() {
    const params = buildKsuParams(form.value.category_id || 0, form.value.parameters)
    const targetIds = ensureNumberIds(form.value.parameters.target_education_level_ids)
    try {
        const data = await post<{ preview?: any[] }>('/api/v1/activities/calculate-preview', {
            category_id: form.value.category_id, parameters: params, target_education_level_ids: targetIds
        })
        previewData.value = data.preview || []
        previewHasPlaces.value = previewData.value.length > 0 && previewData.value.some((r: any) => r.scores?.['1st_place'] !== undefined)
    } catch { previewData.value = [] }
}

watch(() => form.value.category_id, (newVal, oldVal) => {
    if (oldVal === undefined || oldVal === null || oldVal === 0) return
    form.value.parameters = { activity_type: undefined, has_places: false, has_implementation: false, is_leader: false, target_education_level_ids: [] }
    previewData.value = []
})

watch([
    () => form.value.is_dynamic_score, () => form.value.category_id,
    () => form.value.parameters.activity_type, () => form.value.parameters.has_places,
    () => form.value.parameters.has_implementation, () => form.value.parameters.is_leader,
    () => form.value.parameters.target_education_level_ids
], () => {
    if (!form.value.is_dynamic_score) return
    if (!form.value.category_id) { previewData.value = []; return }
    if ([1, 2, 4].includes(Number(form.value.category_id)) && !form.value.parameters.activity_type) { previewData.value = []; return }
    if (previewTimeout) clearTimeout(previewTimeout)
    previewTimeout = setTimeout(() => { calculatePreview() }, 400)
}, { deep: true })

watch(uploadFile, (file) => {
    if (!file) { form.value.files = []; return }
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ title: `Файл ${file.name} слишком большой.`, color: 'error' })
        uploadFile.value = null; return
    }
    const reader = new FileReader()
    reader.onload = (e) => { form.value.files = [{ file, preview: e.target?.result as string }] }
    reader.readAsDataURL(file)
})

function removeImage() { form.value.files = []; uploadFile.value = null }

async function loadActivity() {
    loading.value = true
    try {
        const data = await get<any>(`/api/v1/mentor/activity/edit-mentor/${activityId}`, {
            query: { user_id: authUser.value?.user_id }
        })
        form.value = {
            title: data.title ?? '',
            description: data.description ?? '',
            date_start: data.date_start ? apiDateToRuDate(data.date_start) : '',
            date_end: data.date_end ? apiDateToRuDate(data.date_end) : '',
            score: data.score || 0,
            status: data.status ?? 'draft',
            planet_id: data.planet_id ?? 0,
            academy_id: data.academy_id ?? 0,
            category_id: data.category_id ?? 0,
            photo_path: data.photo_path ?? null,
            files: [],
            is_dynamic_score: !!data.is_dynamic_score,
            parameters: {
                activity_type: data.parameters?.activity_type ?? undefined,
                has_places: !!data.parameters?.has_places,
                has_implementation: data.parameters?.has_implementation ?? false,
                is_leader: !!data.parameters?.is_leader,
                target_education_level_ids: Array.isArray(data.parameters?.target_education_level_ids)
                    ? data.parameters.target_education_level_ids.map((x: any) => Number(x))
                    : []
            }
        }
        existingFiles.value = data.files || []
    } catch (error) {
        showApiError(error, 'Ошибка загрузки данных мероприятия')
        navigateTo('/mentor/activities')
    } finally {
        loading.value = false
    }
}

async function save() {
    saving.value = true
    clearErrors()

    let hasError = false
    if (!form.value.title.trim()) { errors.value.title = 'Название обязательно.'; hasError = true }
    if (!form.value.description.trim()) { errors.value.description = 'Описание обязательно.'; hasError = true }
    if (!form.value.date_start) { errors.value.date_start = 'Дата начала обязательна.'; hasError = true }
    if (!form.value.date_end) { errors.value.date_end = 'Дата окончания обязательна.'; hasError = true }
    if (!form.value.is_dynamic_score && (!form.value.score || form.value.score < 1)) { errors.value.score = 'Баллы >= 1.'; hasError = true }
    if (!form.value.status) { errors.value.status = 'Статус обязателен.'; hasError = true }
    if (!form.value.planet_id || form.value.planet_id <= 0) { errors.value.planet_id = 'Выберите направление.'; hasError = true }
    if (!form.value.category_id || form.value.category_id <= 0) { errors.value.category_id = 'Выберите категорию.'; hasError = true }

    if (hasError) {
        saving.value = false
        showApiError({ response: { _data: { message: 'Проверьте поля' } } })
        return
    }

    try {
        const submitData = new FormData()
        submitData.append('title', form.value.title)
        submitData.append('description', form.value.description)
        submitData.append('date_start', ruDateToApiDate(form.value.date_start))
        submitData.append('date_end', ruDateToApiDate(form.value.date_end))
        submitData.append('status', form.value.status)
        submitData.append('planet_id', String(form.value.planet_id))
        if (form.value.academy_id > 0) submitData.append('academy_id', String(form.value.academy_id))
        submitData.append('user_id', String(authUser.value?.user_id))
        submitData.append('category_id', String(form.value.category_id))

        if (!form.value.is_dynamic_score) {
            submitData.append('is_dynamic_score', '0')
            submitData.append('score', String(form.value.score))
        } else {
            submitData.append('is_dynamic_score', '1')
            const p = form.value.parameters
            const ksuParams = buildKsuParams(form.value.category_id || 0, p)
            if (ksuParams.activity_type) submitData.append('parameters[activity_type]', ksuParams.activity_type)
            if ('has_implementation' in ksuParams) submitData.append('parameters[has_implementation]', ksuParams.has_implementation ? '1' : '0')
            if ('has_places' in ksuParams) submitData.append('parameters[has_places]', ksuParams.has_places ? '1' : '0')
            if ('is_leader' in ksuParams) submitData.append('parameters[is_leader]', ksuParams.is_leader ? '1' : '0')
            for (const id of ensureNumberIds(p.target_education_level_ids)) {
                submitData.append('parameters[target_education_level_ids][]', String(id))
            }
        }

        if (form.value.files.length > 0 && form.value.files[0]) {
            submitData.append('file', form.value.files[0].file)
        }

        await post<any>(`/api/v1/mentor/activity/update-mentor/${activityId}`, submitData)
        toast.add({ title: 'Мероприятие обновлено', color: 'success' })

        if (materialFiles.value.length > 0) {
            for (const file of materialFiles.value) {
                if (file.size > 10 * 1024 * 1024) {
                    toast.add({ title: `Файл ${file.name} слишком большой.`, color: 'error' })
                    continue
                }
                const fd = new FormData()
                fd.append('file', file)
                await uploadFileApi(`/api/v1/mentor/activities/${activityId}/files`, fd)
            }
        }

        navigateTo('/mentor/activities')
    } catch (error: any) {
        if (error?.statusCode === 422) {
            const errorData = error.response?._data?.errors || error.response?._data?.messages
            if (errorData) Object.keys(errorData).forEach(k => { errors.value[k] = errorData[k] })
        } else {
            showApiError(error, 'Ошибка сохранения')
        }
    } finally {
        saving.value = false
    }
}

async function removeExistingFile(fileId: number) {
    deletingFileId.value = fileId
    try {
        await del(`/api/v1/mentor/activities/files/${fileId}`)
        existingFiles.value = existingFiles.value.filter(f => f.id !== fileId)
        toast.add({ title: 'Файл удалён' })
    } catch (error) {
        showApiError(error, 'Ошибка удаления файла')
    } finally {
        deletingFileId.value = null
    }
}

onMounted(async () => {
    const [planetsData, categoriesData, academiesData, levelsData] = await Promise.all([
        get<PlanetsResponse>('/api/v1/planets').catch(() => []),
        get<CategoriesResponse>('/api/v1/categories').catch(() => []),
        get<AcademiesResponse>('/api/v1/academies').catch(() => []),
        get('/api/v1/education-levels').catch(() => []),
    ])
    planets.value = Array.isArray(planetsData) ? planetsData : []
    categories.value = Array.isArray(categoriesData) ? categoriesData : []
    academies.value = Array.isArray(academiesData) ? academiesData : []
    educationLevels.value = Array.isArray(levelsData) ? levelsData : []
    await loadActivity()
})
</script>
