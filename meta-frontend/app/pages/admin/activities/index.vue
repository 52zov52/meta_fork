<template>
    <div class="flex flex-col space-y-4">
            <div class="md:hidden">
                <div class="flex flex-wrap justify-between items-center gap-2">
                        <UButton
                            label="Фильтры"
                            icon="i-heroicons-funnel"
                            size="sm"
                            color="neutral"
                        variant="outline"
                        @click="showMobileFilters = !showMobileFilters"
                    />

                    <div class="flex space-x-2">
                        <UButton
                            label="Справка"
                            icon="i-heroicons-question-mark-circle"
                            size="xs"
                            color="neutral"
                            square
                            variant="solid"
                            @click="showPointsGuide = true"
                        />

                        <UButton
                            :label="sortMethod == 'desc' ? 'A-Z' : 'Z-A'"
                            icon="i-heroicons-arrows-up-down"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                            @click="sort()"
                        />

                        <UButton
                            to="/admin/activities/add"
                            label="Добавить"
                            icon="i-heroicons-plus-circle"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                        />
                    </div>
                </div>

                <div v-if="showMobileFilters" class="mt-3 p-4 bg-muted rounded-lg border border-default space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-default mb-2">Статус</label>
                            <USelect
                                v-model="statusFilter"
                                :items="statusOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="filterByStatus"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-default mb-2">Начисления баллов</label>
                            <USelect
                                v-model="pointsStatusFilter"
                                :items="pointsStatusOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="filterByPointsStatus"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-default mb-2">Участники</label>
                            <USelect
                                v-model="hasConfirmedParticipantsFilter"
                                :items="participantsOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-default mb-2">Категория</label>
                            <USelect
                                v-model="categoryIdFilter"
                                :items="categorySelectOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-default mb-2">Сортировка</label>
                            <USelect
                                v-model="sortField"
                                :items="sortFieldOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="applySorting"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden md:block">
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <div class="flex flex-wrap space-x-2 gap-y-2">
                        <USelect
                            v-model="statusFilter"
                            :items="statusOptions"
                            size="sm"
                            class="min-w-[160px]"
                            @update:model-value="filterByStatus"
                        />

                        <USelect
                            v-model="pointsStatusFilter"
                            :items="pointsStatusOptions"
                            size="sm"
                            class="min-w-[180px]"
                            @update:model-value="filterByPointsStatus"
                        />

                        <USelect
                            v-model="hasConfirmedParticipantsFilter"
                            :items="participantsOptions"
                            size="sm"
                            class="min-w-[190px]"
                            @update:model-value="applyFilters"
                        />

                        <USelect
                            v-model="categoryIdFilter"
                            :items="categorySelectOptions"
                            size="sm"
                            class="min-w-[180px]"
                            @update:model-value="applyFilters"
                        />

                        <USelect
                            v-model="sortField"
                            :items="sortFieldOptions"
                            size="sm"
                            class="min-w-[190px]"
                            @update:model-value="applySorting"
                        />
                    </div>

                    <div class="flex flex-wrap space-x-2 gap-y-2">
                        <UButton
                            label="Справка по баллам"
                            icon="i-heroicons-question-mark-circle"
                            size="xs"
                            color="neutral"
                            square
                            variant="solid"
                            @click="showPointsGuide = true"
                        />

                        <UButton
                            :label="sortMethod == 'desc' ? 'По названию A-Z' : 'По названию Z-A'"
                            icon="i-heroicons-arrows-up-down"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                            @click="sort()"
                        />

                        <UButton
                            to="/admin/activities/add"
                            label="Добавить новую запись"
                            icon="i-heroicons-plus-circle"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                        />
                    </div>
                </div>
            </div>

            <div class="flex space-x-2">
                <UInput
                    v-model="searchTxt"
                    type="text"
                    icon="i-heroicons-magnifying-glass"
                    size="sm"
                    class="w-full"
                    placeholder="Поиск мероприятий по наименованию"
                    @input="debouncedSearch"
                    @keyup.enter.prevent="search"
                />
                <UButton
                    icon="i-heroicons-magnifying-glass"
                    size="xs"
                    color="primary"
                    variant="solid"
                    class="shrink-0"
                    @click="search()"
                />
            </div>

            <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
                <div class="text-center">
                    <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
                    <p class="text-muted">Загрузка мероприятий...</p>
                </div>
            </div>

            <div v-if="status === 'success'">
                <div v-if="activities.length != 0" class="flex flex-col gap-2">
                    <ul>
                        <li v-for="item in activities" :key="item.id">
                            <hr role="presentation" class="w-full border-t border-default dark:border-inverted">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 md:p-6">
                                <div class="flex flex-col sm:flex-row gap-4 md:gap-6">
                                    <div
                                        :style="{ backgroundImage: item.photo_path
                                            ? `url('${config.public.API_PATH_STORAGE + item.photo_path}')`
                                            : `url('${staticImg.bgCard}')` }"
                                        class="h-20 w-28 md:h-24 md:w-32 shrink-0 bg-cover rounded self-center sm:self-start cursor-pointer hover:opacity-90 transition-opacity"
                                        @click="openImagePreview(item.photo_path)"
                                    />
                                    <div class="space-y-2 md:space-y-1.5 flex-1">
                                        <NuxtLink
                                            :to="`/admin/activities/show/${item.id}`"
                                            class="block w-full text-base md:text-base/6 font-semibold text-center sm:text-left text-primary hover:underline"
                                        >
                                            {{ item.title }}
                                        </NuxtLink>
                                        <div class="text-xs text-muted text-center sm:text-left">
                                            с {{ formatDate(item.date_start) }} по {{ formatDate(item.date_end) }}
                                        </div>
                                        <div v-if="item.description" class="w-full text-xs text-muted mt-1 max-w-md mx-auto text-center sm:mx-0 sm:text-left">
                                            {{ truncuteText(item.description, 100) }}
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-xs">
                                            <span class="text-muted text-center sm:text-left">
                                                <span v-if="!!item.is_dynamic_score" class="text-info">КСУ</span>
                                                <span v-else>{{ item.score }} балл(ов)</span>
                                            </span>
                                        <div class="flex items-center justify-center sm:justify-start space-x-3">
                                            <UTooltip text="Всего участников">
                                                <div class="flex items-center space-x-1">
                                                <UIcon name="i-heroicons-users" class="w-4 h-4 text-primary" />
                                                <span class="text-primary font-medium tabular-nums">
                                                    {{ item.participants_count ?? '0' }}
                                                </span>
                                                </div>
                                            </UTooltip>

                                            <UTooltip text="Подтвержденные участники">
                                                <div class="flex items-center space-x-1">
                                                <UIcon name="i-heroicons-users" class="w-4 h-4 text-green-500" />
                                                <span class="text-green-600 font-medium tabular-nums">
                                                    {{ item.confirmed_participants_count ?? '0' }}
                                                </span>
                                                </div>
                                            </UTooltip>
                                        </div>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2">
                                            <span class="text-xs text-muted text-center sm:text-left">{{ item.planet_name }}</span>
                                            <UBadge
                                                v-if="item.points_status"
                                                :color="item.points_status === 'awarded' ? 'success' :
                                                       item.points_status === 'pending' ? 'warning' : 'neutral'"
                                                variant="soft"
                                                size="xs"
                                                class="self-center sm:self-start">
                                                {{ item.points_status === 'awarded' ? 'Начислено' :
                                                   item.points_status === 'pending' ? 'На проверке' : 'Не начислено' }}
                                            </UBadge>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row items-center gap-2 mt-4 md:mt-0">
                                    <USelect
                                        :model-value="item.status_eng"
                                        :items="activityStatusOptions"
                                        size="xs"
                                        class="min-w-[180px]"
                                        :disabled="isUpdatingStatus(item.id)"
                                        @update:model-value="(value) => changeStatus(item, value)"
                                    >
                                        <template #leading="{ modelValue, ui }">
                                            <UChip
                                                v-if="modelValue"
                                                v-bind="getActivityStatusChip(modelValue)"
                                                inset
                                                standalone
                                                :size="ui.itemLeadingChipSize()"
                                                :class="ui.itemLeadingChip()"
                                            />
                                        </template>
                                    </USelect>
                                    <div class="flex items-center justify-center gap-2">
                                        <UButton
                                            icon="i-heroicons-pencil-square"
                                            color="warning"
                                            variant="outline"
                                            size="sm"
                                            square
                                            @click="edit(item.id)"
                                        />
                                        <UButton
                                            icon="i-heroicons-trash"
                                            color="error"
                                            variant="outline"
                                            size="sm"
                                            square
                                            @click="destroy(item.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="flex justify-center md:justify-end">
                        <ListPagination :pagination="pagination" @update:page="onPageChange" />
                    </div>
                </div>

                <UAlert
                    v-if="activities.length == 0"
                    icon="i-heroicons-information-circle"
                    color="info"
                    variant="solid"
                    title="Информация"
                    description="Список мероприятий пуст. Для добавления нового мероприятия нажмите на кнопку «Добавить новую запись" />
            </div>

            <!-- Модальное окно справки по баллам -->
            <PointsInfoModal v-model:open="showPointsGuide" />

            <ImagePreviewModal
                v-model:open="showImagePreview"
                :src="previewImageSrc"
                :fallback-src="staticImg.bgCard"
            />
        </div>
</template>

<script setup lang="ts">
import { adminActivityStatusOptions as activityStatusOptions, buildAdminActivityUpdateFormData, getAdminActivityStatusChip as getActivityStatusChip } from '~/utils/adminActivityStatus'
import { staticImg } from '~/assets/img'
import type { paths } from '~/types/api'

const route = useRoute()
const router = useRouter()
const { user } = useSanctumAuth()

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Мероприятия',
  description: 'Управляйте мероприятиями и их настройками',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Мероприятия' }],
})

const config = useRuntimeConfig()
const { get, post, delete: del } = useApi()
const { show: showApiError } = useApiError();
const toast = useToast()

type CategoriesResponse =
    paths['/api/v1/categories']['get']['responses'][200]['content']['application/json']
type CategoryItem = CategoriesResponse[number]

type AdminActivitiesListResponse =
    paths['/api/v1/admin/activities']['get']['responses'][200]['content']['application/json']
type AdminActivitiesSearchResponse =
    paths['/api/v1/admin/activity/search/{sortMethod}/{searchTxt}']['get']['responses'][200]['content']['application/json']
type AdminActivityEditResponse =
    paths['/api/v1/admin/activity/edit/{id}']['get']['responses'][200]['content']['application/json']
type AdminActivityItem = NonNullable<AdminActivitiesListResponse['data']>[number] & {
    /** В спеке поиска может не быть — поле для фильтра/отображения статуса начислений. */
    points_status?: string
}

type ActivitiesQueryParams = {
    page: number
    per_page: number
    sort_by?: string
    sort_direction?: string
    status?: string
    points_status?: string
    category_id?: number
    has_confirmed_participants?: boolean
    search?: string
}

type AdminActivityDestroyResponse =
    paths['/api/v1/admin/activity/destroy/{id}']['delete']['responses'][200]['content']['application/json']

const activities = ref<AdminActivityItem[]>([])
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle')

const sortMethod = ref('desc')
const sortField = ref('created_at')
const statusFilter = ref('all')


const pointsStatusFilter = ref('all')
const searchTxt = ref('')

const page = ref(1)
const perPage = ref(20)

const pagination = ref({
currentPage: 1,
lastPage: 1,
prevPageUrl: null as string | null,
nextPageUrl: null as string | null,
totalItems: 0,
perPage: 20,
})

const showPointsGuide = ref(false)
const showMobileFilters = ref(false)
const updatingStatusId = ref<number | null>(null)

const showImagePreview = ref(false)
const previewImageSrc = ref('')

function openImagePreview(photoPath?: string | null) {
    previewImageSrc.value = photoPath ? config.public.API_PATH_STORAGE + photoPath : ''
    showImagePreview.value = true
}

const statusOptions = [
    { value: 'all', label: 'Все статусы' },
    { value: 'draft', label: 'Черновик' },
    { value: 'scheduled', label: 'Запланировано' },
    { value: 'started', label: 'Начато' },
    { value: 'completed', label: 'Завершено' },
    { value: 'canceled', label: 'Отменено' },
]

const pointsStatusOptions = [
    { value: 'all', label: 'Все начисления' },
    { value: 'awarded', label: 'Начислено' },
    { value: 'pending', label: 'На проверке' },
    { value: 'not_awarded', label: 'Не начислено' },
]

const participantsOptions = [
    { value: 'all', label: 'Все участники' },
    { value: '1', label: 'Есть подтвержденные' },
    { value: '0', label: 'Нет подтвержденных' },
]

const sortFieldOptions = [
    { value: 'created_at', label: 'По дате создания' },
    { value: 'date_start', label: 'По дате начала' },
    { value: 'score', label: 'По баллам' },
    { value: 'status', label: 'По статусу' },
    { value: 'participants_count', label: 'По участникам' },
    { value: 'title', label: 'По названию' },
]

const sortByApi = computed(() => {
    if (sortField.value === 'participants_count') return 'participants'
    return sortField.value
})

const isSearchApplied = ref(false)



const categoryIdFilter = ref('all')
const hasConfirmedParticipantsFilter = ref('all')
const categories = ref<CategoryItem[]>([])
const categorySelectOptions = computed(() => {
    const base = [{ value: 'all', label: 'Все категории' }]
    if (!categories.value?.length) return base
    return [
        ...base,
        ...categories.value.map(c => ({ value: String(c.id), label: c.name })),
    ]
})

function initFiltersFromQuery() {
    const q = route.query

    statusFilter.value = typeof q.status === 'string' ? q.status : 'all'
    pointsStatusFilter.value = typeof q.points_status === 'string' ? q.points_status : 'all'
    categoryIdFilter.value = typeof q.category_id === 'string' ? q.category_id : 'all'
    hasConfirmedParticipantsFilter.value = typeof q.has_confirmed_participants === 'string'
        ? (q.has_confirmed_participants === 'true' ? '1' : q.has_confirmed_participants === 'false' ? '0' : 'all')
        : 'all'

    sortField.value = typeof q.sort_by === 'string' ? q.sort_by : 'created_at'
    sortMethod.value = typeof q.sort_direction === 'string' ? q.sort_direction : 'desc'

    page.value = typeof q.page === 'string' ? Number(q.page) || 1 : 1
    perPage.value = typeof q.per_page === 'string' ? Number(q.per_page) || 20 : 20

    searchTxt.value = typeof q.search === 'string' ? q.search : ''
    isSearchApplied.value = !!searchTxt.value.trim()
}

function syncQueryToUrl() {
    const query = {
        ...(statusFilter.value !== 'all' ? { status: statusFilter.value } : {}),
        ...(pointsStatusFilter.value !== 'all' ? { points_status: pointsStatusFilter.value } : {}),
        ...(categoryIdFilter.value !== 'all' ? { category_id: categoryIdFilter.value } : {}),
        ...(hasConfirmedParticipantsFilter.value !== 'all'
        ? { has_confirmed_participants: hasConfirmedParticipantsFilter.value === '1' ? 'true' : 'false' }
        : {}),

        ...(sortField.value ? { sort_by: sortField.value } : {}),
        ...(sortMethod.value ? { sort_direction: sortMethod.value } : {}),

        ...(perPage.value ? { per_page: String(perPage.value) } : {}),
        ...(page.value ? { page: String(page.value) } : {}),

        ...(isSearchApplied.value && searchTxt.value.trim() ? { search: searchTxt.value.trim() } : {}),
    }

    router.replace({ query })
}

function buildParams({ includeSort = true } = {}) {
    const params: ActivitiesQueryParams = {
        page: page.value,
        per_page: perPage.value,
    }

    if (includeSort) {
        params.sort_by = sortByApi.value
        params.sort_direction = sortMethod.value
    }

    if (statusFilter.value !== 'all') params.status = statusFilter.value
    if (pointsStatusFilter.value !== 'all') params.points_status = pointsStatusFilter.value
    if (categoryIdFilter.value !== 'all') params.category_id = Number(categoryIdFilter.value)

    if (hasConfirmedParticipantsFilter.value !== 'all') {
        params.has_confirmed_participants = hasConfirmedParticipantsFilter.value === '1'
    }

    return params
}

async function loadCategories() {
    try {
        const data = await get<CategoriesResponse>('/api/v1/categories')
        categories.value = Array.isArray(data) ? data : []
    } catch {
        categories.value = []
        toast.add({ title: 'Не удалось загрузить категории', color: 'error' })
    }
}

function getSearchQuery() {
    const query = String(searchTxt.value || '').trim()
    return query.length ? query : ''
}

async function fetchActivitiesForPage(pageNumber: number) {
    status.value = 'pending'

    try {
        const query = isSearchApplied.value ? getSearchQuery() : ''

        let url = '/api/v1/admin/activities'
        let params: ActivitiesQueryParams = buildParams({ includeSort: true })
        params.page = pageNumber

        if (query) {
        url = `/api/v1/admin/activity/search/${sortMethod.value}/${encodeURIComponent(query)}`
        params = buildParams({ includeSort: false })

        // Добавляем фильтры к поисковому запросу
        if (statusFilter.value) params.status = statusFilter.value
        if (pointsStatusFilter.value) params.points_status = pointsStatusFilter.value
        if (categoryIdFilter.value) params.category_id = Number(categoryIdFilter.value)
        if (hasConfirmedParticipantsFilter.value !== '') {
            params.has_confirmed_participants = hasConfirmedParticipantsFilter.value === '1'
        }
        params.page = pageNumber
        }

        const data = await get<AdminActivitiesListResponse | AdminActivitiesSearchResponse>(url, { query: params })

        activities.value = Array.isArray(data.data) ? data.data : []

        pagination.value.currentPage = data.current_page ?? 1
        pagination.value.lastPage = data.last_page ?? 1
        pagination.value.prevPageUrl = data.prev_page_url ?? null
        pagination.value.nextPageUrl = data.next_page_url ?? null
        pagination.value.perPage = data.per_page ?? perPage.value
        pagination.value.totalItems = data.total ?? ((data.last_page ?? 1) * pagination.value.perPage)

        page.value = data.current_page ?? 1
        status.value = 'success'
    } catch (error) {
        status.value = 'error'
        showApiError(error, 'Произошла ошибка в процессе получения данных. Попробуйте позже.')
    }
}

async function loadDataForPage(urlOrPage?: number | string) {
    const pageNum = typeof urlOrPage === 'number' && Number.isFinite(urlOrPage)
        ? urlOrPage
        : (parseInt(String(urlOrPage || page.value), 10) || 1)

    page.value = pageNum
    await fetchActivitiesForPage(pageNum)
}

function applyFilters() {
    isSearchApplied.value = false
    page.value = 1
    syncQueryToUrl()
    loadDataForPage(1)
}

function sort() {
    if (sortField.value !== 'title') {
        sortField.value = 'title'
        sortMethod.value = 'asc'
    } else {
        sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc'
    }
    syncQueryToUrl()
    applyFilters()
}

function filterByStatus() {
    applyFilters()
}

function applySorting() {
    page.value = 1
    syncQueryToUrl()
    applyFilters()
}

function filterByPointsStatus() {
    applyFilters()
}

function search() {
    const query = getSearchQuery()
    if (!query) {
        isSearchApplied.value = false
        page.value = 1
        syncQueryToUrl()
        loadDataForPage(1)
        return
    }

    isSearchApplied.value = true
    page.value = 1
    syncQueryToUrl()
    loadDataForPage(1)
}

function buildUrl(pageNum?: number) {
    return String(pageNum ?? 1)
}

const { onPageChange, getInitialPage } = usePaginationRedirect(loadDataForPage, buildUrl)

function edit(id?: number) {
    if (!id) return
    return navigateTo('/admin/activities/edit/' + id)
}

function getCurrentUserId() {
    return Number((user.value as { user_id?: number } | null | undefined)?.user_id ?? 0)
}

function isUpdatingStatus(id?: number) {
    return updatingStatusId.value === id
}

async function changeStatus(item: AdminActivityItem, nextStatus: string) {
    if (!item.id || !nextStatus || nextStatus === item.status_eng) return

    updatingStatusId.value = item.id

    try {
        const data = await get<AdminActivityEditResponse>(`/api/v1/admin/activity/edit/${item.id}`)
        const submitData = buildAdminActivityUpdateFormData({
            ...data,
            user_id: getCurrentUserId(),
        }, { status: nextStatus })

        const response = await post<{ message?: string }>(`/api/v1/admin/activity/update/${item.id}`, submitData)
        toast.add({ title: response.message ?? 'Статус мероприятия обновлен' })
        await loadDataForPage(page.value)
    } catch (error) {
        showApiError(error, 'Не удалось обновить статус мероприятия')
    } finally {
        updatingStatusId.value = null
    }
}

function destroy(id?: number) {
    if (!id) return
    if (confirm('Вы уверены, что хотите удалить данную запись?')) {
        del<AdminActivityDestroyResponse>('/api/v1/admin/activity/destroy/' + id)
            .then((data) => {
            toast.add({ title: data.message })
            loadDataForPage(page.value)
            })
            .catch(error => {
            showApiError(error, 'Ошибка удаления')
            })
    }
}

function truncuteText(text: string, length: number) {
    if (text && text.length > length) {
        return text.substring(0, length) + '...'
    }
    return text || ''
}

function formatDate(value?: string | null): string {
  if (!value) return '—'

  const parts = value.split('T')
  const datePart = parts[0]
  if (!datePart) return value

  const dateSegments = datePart.split('-')
  const [year, month, day] = dateSegments

  if (!year || !month || !day) return value

  return `${day.padStart(2, '0')}.${month.padStart(2, '0')}.${year}`
}

// Автоматический поиск при вводе текста (с общим debounce)
let searchTimeout: ReturnType<typeof setTimeout> | null = null
const debouncedSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
    searchTimeout = setTimeout(() => {
        const value = String(searchTxt.value || '')
        if (!value.trim()) {
            isSearchApplied.value = false
        }
        if (typeof search === 'function') {
            search()
        }
    }, 400)
}


onMounted(async () => {
    initFiltersFromQuery()
    const initialPage = getInitialPage()
    page.value = initialPage
    await Promise.all([loadCategories(), loadDataForPage(initialPage)])
})
</script>
