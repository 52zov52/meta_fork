<template>
    <div class="flex flex-col space-y-4">
        <!-- Mobile filters (admin-like UI) -->
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
                        :label="sortMethod === 'desc' ? 'A-Z' : 'Z-A'"
                        icon="i-heroicons-arrows-up-down"
                        size="xs"
                        color="primary"
                        square
                        variant="solid"
                        @click="sort()"
                    />

                    <UButton
                        to="/mentor/activities/add"
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
                        <label class="block text-sm font-medium text-default mb-2">Тип мероприятий</label>
                        <USelect
                            v-model="ownFilter"
                            :items="ownFilterOptions"
                            size="sm"
                            class="w-full"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-default mb-2">Статус</label>
                        <USelect
                            v-model="statusFilter"
                            :items="statusSelectOptions"
                            size="sm"
                            class="w-full"
                            @update:model-value="applyFilters"
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
                            v-model="sortBy"
                            :items="sortFieldOptions"
                            size="sm"
                            class="w-full"
                            @update:model-value="applyFilters"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop filters (admin-like UI) -->
        <div class="hidden md:block">
            <div class="flex flex-wrap justify-between items-center gap-2">
                <div class="flex flex-wrap space-x-2 gap-y-2">
                    <USelect
                        v-model="ownFilter"
                        :items="ownFilterOptions"
                        size="sm"
                        class="min-w-[170px]"
                        @update:model-value="applyFilters"
                    />

                    <USelect
                        v-model="statusFilter"
                        :items="statusSelectOptions"
                        size="sm"
                        class="min-w-[160px]"
                        @update:model-value="applyFilters"
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
                        v-model="sortBy"
                        :items="sortFieldOptions"
                        size="sm"
                        class="min-w-[190px]"
                        @update:model-value="applyFilters"
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
                        :label="sortMethod === 'desc' ? 'По названию A-Z' : 'По названию Z-A'"
                        icon="i-heroicons-arrows-up-down"
                        size="xs"
                        color="primary"
                        square
                        variant="solid"
                        @click="sort()"
                    />

                    <UButton
                        to="/mentor/activities/add"
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
                        <li v-for="(item, index) in activities" :key="index">
                            <hr role="presentation" class="w-full border-t border-default dark:border-inverted">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 md:p-6">
                                <div class="flex flex-col sm:flex-row gap-4 md:gap-6">
                                    <div
                                        :style="{ backgroundImage: item.photo_path
                                        ? `url('${config.public.API_PATH_STORAGE + item.photo_path}')`
                                        : `url('${staticImg.bgCard}')` }"
                                        class="h-20 w-28 md:h-24 md:w-32 shrink-0 bg-cover rounded self-center sm:self-start cursor-pointer hover:opacity-90 transition-opacity"
                                        @click="openImagePreview(item.photo_path)"/>
                                    <div class="space-y-2 md:space-y-1.5 flex-1">
                                        <NuxtLink
                                            :to="`/mentor/activities/show/${item.id}`"
                                            class="block w-full text-base md:text-base/6 font-semibold text-center sm:text-left text-primary hover:underline"
                                        >
                                            {{ item.title }}
                                        </NuxtLink>
                                        <div class="text-xs text-muted text-center sm:text-left">
                                            с {{ formatDate(item.date_start) }} по {{ formatDate(item.date_end) }}
                                        </div>
                                        <div v-if="item.description" class="text-xs text-muted mt-1 max-w-md text-center sm:text-left">
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
                                            :to="`/mentor/activities/edit/${item.id}`"
                                            icon="i-heroicons-pencil-square"
                                            color="warning"
                                            variant="outline"
                                            size="sm"
                                            square
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

        <PointsInfoModal v-model:open="showPointsGuide" />

        <ImagePreviewModal
            v-model:open="showImagePreview"
            :src="previewImageSrc"
            :fallback-src="staticImg.bgCard"
        />
    </div>
</template>

<script setup lang="ts">
import { staticImg } from '~/assets/img'
import type { paths } from '~/types/api'
import { adminActivityStatusOptions as activityStatusOptions, buildAdminActivityUpdateFormData, getAdminActivityStatusChip as getActivityStatusChip } from '~/utils/adminActivityStatus'

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['mentor'],
    layout: 'mentor',
    title: 'Мероприятия',
    description: 'Управление мероприятиями и участниками',
    breadcrumb: [{ label: 'Главная', to: '/mentor' }, { label: 'Мероприятия' }],
});

type MentorActivitiesSearchResponse =
    paths['/api/v1/mentor/activity/search-mentor/{sortMethod}/{searchTxt}']['get']['responses'][200]['content']['application/json']
type MentorActivityItem = NonNullable<MentorActivitiesSearchResponse['data']>[number] & {
    participants_count?: number
    planet_id?: number | null
    photo_path?: string | null
}

type MentorActivitiesOwnResponse =
    paths['/api/v1/mentor/activity/mentor/activities/{user_id}']['get']['responses'][200]['content']['application/json']

type MentorActivityStoreResponse =
    paths['/api/v1/mentor/activity/store-mentor']['post']['responses'][200]['content']['application/json']

const config = useRuntimeConfig();
const route = useRoute();
const router = useRouter();
const { user } = useSanctumAuth();
const authUser = computed(() => user.value as { user_id: number } | null)
const { get, post, delete: del } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast()

type MentorActivitiesQueryParams = {
    page: number
    per_page: number
    sort_by?: string
    sort_direction?: 'asc' | 'desc'
    status?: string
    category_id?: number
    has_confirmed_participants?: boolean
}

const activities = ref<MentorActivityItem[]>([]);
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
const sortMethod = ref<'asc' | 'desc'>('desc');
const sortBy = ref<'created_at' | 'title' | 'date_start' | 'score' | 'status' | 'participants'>('created_at');
const FILTER_ALL = '__all__' as const

const statusFilter = ref<string>(FILTER_ALL);
const categoryIdFilter = ref<number | typeof FILTER_ALL>(FILTER_ALL);
const hasConfirmedParticipantsFilter = ref<string>(FILTER_ALL);
const ownFilter = ref('all');
const searchTxt = ref('');
const isSearchApplied = ref(false);

const categories = ref<{ id: number; name: string }[]>([]);
const categoryOptions = ref<{ label: string; value: number }[]>([]);
const showMobileFilters = ref(false);
const showPointsGuide = ref(false);
const isLoading = ref(false);

const ownFilterOptions = [
    { label: 'Все мероприятия', value: 'all' },
    { label: 'Мои мероприятия', value: 'own' },
]

const statusOptions = [
    { label: 'Черновик', value: 'draft' },
    { label: 'Запланировано', value: 'scheduled' },
    { label: 'Началось', value: 'started' },
    { label: 'Завершено', value: 'completed' },
    { label: 'Отменено', value: 'canceled' }
]

const statusSelectOptions = computed(() => [
    { label: 'Все статусы', value: FILTER_ALL },
    ...statusOptions,
])

const participantsOptions = [
    { label: 'Все участники', value: FILTER_ALL },
    { label: 'Есть подтвержденные', value: '1' },
    { label: 'Нет подтвержденных', value: '0' },
]

const categorySelectOptions = computed(() => [
    { label: 'Все категории', value: FILTER_ALL },
    ...categoryOptions.value,
])

const sortFieldOptions = [
    { label: 'По дате создания', value: 'created_at' },
    { label: 'По названию', value: 'title' },
    { label: 'По дате начала', value: 'date_start' },
    { label: 'По баллам', value: 'score' },
    { label: 'По статусу', value: 'status' },
    { label: 'По участникам', value: 'participants' },
]

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    prevPageUrl: null as string | null,
    nextPageUrl: null as string | null,
    perPage: 20,
    totalItems: 0,
});

// --- Для превью картинок ---
const showImagePreview = ref(false)
const previewImageSrc = ref('')

function openImagePreview(photoPath?: string | null) {
    previewImageSrc.value = photoPath ? config.public.API_PATH_STORAGE + photoPath : ''
    showImagePreview.value = true
}

// --- Для смены статуса ---
const updatingStatusId = ref<number | null>(null)

function isUpdatingStatus(id?: number) {
    return updatingStatusId.value === id
}

async function changeStatus(item: MentorActivityItem, nextStatus: string) {
    if (!item.id || !nextStatus || nextStatus === item.status_eng) return

    updatingStatusId.value = item.id

    try {
        // Запрашиваем текущие данные мероприятия
        const data = await get<any>(`/api/v1/mentor/activity/edit/${item.id}`)

        // Формируем форм-дату (используем функцию из утилит)
        const submitData = buildAdminActivityUpdateFormData({
            ...data,
            user_id: authUser.value?.user_id ?? 0,
        }, { status: nextStatus })

        // Отправляем обновленный статус
        const response = await post<{ message?: string }>(`/api/v1/mentor/activity/update-mentor/${item.id}`, submitData)
        toast.add({ title: response.message ?? 'Статус мероприятия обновлен' })

        await loadData() // Обновляем список
    } catch (error) {
        showApiError(error, 'Не удалось обновить статус мероприятия')
    } finally {
        updatingStatusId.value = null
    }
}

async function loadCategories() {
    try {
        const data = await get('/api/v1/categories');
        categories.value = Array.isArray(data) ? data : [];
        categoryOptions.value = categories.value.map((c: any) => ({
            label: c.name ?? '',
            value: c.id ?? 0
        }));
    } catch {
        categories.value = [];
        categoryOptions.value = [];
    }
}

async function loadData() {
    if (isLoading.value) return; // Предотвращаем множественные одновременные запросы

    isLoading.value = true;
    status.value = 'pending';

    try {
        const query = isSearchApplied.value ? getSearchQuery() : '';

        let url: string;
        let params: MentorActivitiesQueryParams = buildParams({ includeSort: true });

        // Выбор endpoint в зависимости от фильтра "Мои мероприятия"
        if (ownFilter.value === 'own') {
            if (!authUser.value?.user_id) {
                throw new Error('User not found')
            }
            url = `/api/v1/mentor/activity/mentor/activities/${authUser.value.user_id}`;
            // Для mentor-specific endpoint передаем поддерживаемые фильтры
            params = {
                page: pagination.value.currentPage,
                per_page: 20,
            };
            if (statusFilter.value !== FILTER_ALL) params.status = statusFilter.value;
            // category_id и has_confirmed_participants могут не поддерживаться
        } else {
            url = '/api/v1/mentor/activities-mentor';
        }

        if (query) {
            url = `/api/v1/mentor/activity/search-mentor/${sortMethod.value}/${encodeURIComponent(query)}`;
            params = buildParams({ includeSort: false });

            // Добавляем фильтры к поисковому запросу
            if (statusFilter.value !== FILTER_ALL) params.status = statusFilter.value;
            if (categoryIdFilter.value !== FILTER_ALL) params.category_id = Number(categoryIdFilter.value);
            if (hasConfirmedParticipantsFilter.value !== FILTER_ALL) {
                params.has_confirmed_participants = hasConfirmedParticipantsFilter.value === '1';
            }
        }

        // Note: some mentor list endpoints are missing a 200 response in OpenAPI.
        // We type the list payload using the search endpoint shape (which is present).
        const data = await get<MentorActivitiesSearchResponse | MentorActivitiesOwnResponse | MentorActivityItem[]>(url, { query: params });

        const perPageReq = params.per_page ?? 20;
        let list: MentorActivityItem[] = [];
        let currentPage = 1;
        let lastPage = 1;
        let perPageOut = perPageReq;
        let totalItems = 0;

        if (Array.isArray(data)) {
            list = data;
            totalItems = list.length;
            lastPage = 1;
            currentPage = 1;
            pagination.value.prevPageUrl = null;
            pagination.value.nextPageUrl = null;
        } else {
            const paginated = data as MentorActivitiesSearchResponse;
            list = Array.isArray(paginated.data) ? (paginated.data ?? []) : [];
            currentPage = paginated.current_page ?? 1;
            lastPage = paginated.last_page ?? 1;
            perPageOut = paginated.per_page ?? perPageReq;
            totalItems = paginated.total ?? lastPage * perPageOut;
            pagination.value.prevPageUrl = paginated.prev_page_url ?? null;
            pagination.value.nextPageUrl = paginated.next_page_url ?? null;
        }

        activities.value = list;

        pagination.value.currentPage = currentPage;
        pagination.value.lastPage = lastPage;
        pagination.value.perPage = perPageOut;
        pagination.value.totalItems = totalItems;

        status.value = 'success';
    } catch (error) {
        status.value = 'error';
        showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
    } finally {
        isLoading.value = false;
    }
}

function sort() {
    if(sortMethod.value == 'asc'){
        sortMethod.value = 'desc';
    } else {
        sortMethod.value = 'asc';
    }
    applyFilters();
}

function buildParams({ includeSort = true } = {}) {
    const params: MentorActivitiesQueryParams = {
        page: pagination.value.currentPage,
        per_page: 20,
    };

    if (includeSort) {
        params.sort_by = sortBy.value;
        params.sort_direction = sortMethod.value;
    }

    if (statusFilter.value !== FILTER_ALL) params.status = statusFilter.value;
    if (categoryIdFilter.value !== FILTER_ALL) params.category_id = Number(categoryIdFilter.value);
    if (hasConfirmedParticipantsFilter.value !== FILTER_ALL) {
        params.has_confirmed_participants = hasConfirmedParticipantsFilter.value === '1';
    }

    return params;
}

function getSearchQuery() {
    const query = String(searchTxt.value || '').trim();
    return query.length ? query : '';
}


function search() {
    const query = getSearchQuery();
    if (!query) {
        isSearchApplied.value = false;
        pagination.value.currentPage = 1;
        router.replace({ path: route.path, query: { ...route.query, page: '1' } });
        if (!isLoading.value) {
            loadData();
        }
        return;
    }

    isSearchApplied.value = true;
    pagination.value.currentPage = 1;
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    if (!isLoading.value) {
        loadData();
    }
}



function applyFilters() {
    isSearchApplied.value = false;
    pagination.value.currentPage = 1;
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    if (!isLoading.value) {
        loadData();
    }
}

async function destroy(id?: number) {
    if (!id) return
    if(confirm('Вы уверены, что хотите удалить данную запись?')) {
        try {
            if (!authUser.value?.user_id) {
                throw new Error('User not found')
            }
            const data = await del<MentorActivityStoreResponse>(`/api/v1/mentor/activity/destroy-mentor/${id}`, {
                data: { user_id: authUser.value.user_id }
            });
            loadData();
            toast.add({ title: data.message });
        } catch (error) {
            showApiError(error, 'Ошибка при удалении мероприятия');
        }
    }
}

async function loadDataForPage(urlOrPage?: string | number) {
    const pageNum =
        typeof urlOrPage === 'number' && Number.isFinite(urlOrPage)
            ? urlOrPage
            : (parseInt(String(urlOrPage ?? 1), 10) || 1);
    pagination.value.currentPage = pageNum;
    await loadData();
}

function buildUrl(pageNum?: number) {
    return String(pageNum ?? 1);
}

const { onPageChange, getInitialPage } = usePaginationRedirect(loadDataForPage, buildUrl);

onMounted(async () => {
    await loadCategories();
    pagination.value.currentPage = getInitialPage();
    await loadData();
});

function truncuteText(text: string, length: number) {
    if(text && text.length > length) {
        return text.substring(0, length) + '...';
    }
    return text;
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

let searchTimeout: ReturnType<typeof setTimeout> | null = null
const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        search()
    }, 400)
}
</script>
