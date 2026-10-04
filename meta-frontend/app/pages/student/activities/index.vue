<template>
    <NuxtLayout name="student">
        <div class="py-4 space-y-3 min-w-0 w-full max-w-full overflow-x-hidden">
            <!-- Grid: колонка фильтров с minmax(0,1fr) сжимается; кнопка в отдельной колонке auto — без налезания flex -->
            <div
                class="grid w-full min-w-0 gap-3 grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start"
            >
                <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4 sm:gap-y-3">
                    <div class="flex min-w-0 w-full flex-col gap-2 sm:w-auto sm:max-w-full sm:flex-row sm:items-center">
                        <label for="status-filter" class="text-sm font-medium text-gray-700 shrink-0">Статус:</label>
                        <USelect
                            id="status-filter"
                            v-model="statusFilter"
                            :items="statusOptions"
                            class="w-full min-w-0 sm:min-w-45 sm:w-45"
                            @update:model-value="onStatusChange"
                        />
                    </div>
                    <div class="flex min-w-0 w-full flex-col gap-2 sm:w-auto sm:max-w-full sm:flex-row sm:items-center">
                        <label for="academy-filter" class="text-sm font-medium text-gray-700 shrink-0">Академия:</label>
                        <USelect
                            id="academy-filter"
                            v-model="academyFilter"
                            :items="academyOptions"
                            class="w-full min-w-0 sm:w-95 sm:max-w-95"
                            :ui="{ value: 'truncate', trailingIcon: 'shrink-0' }"
                            @update:model-value="onAcademyChange"
                        />
                    </div>
                    <div class="flex min-w-0 w-full flex-col gap-2 lg:flex-row lg:items-center lg:gap-3 lg:overflow-x-auto">
                        <span class="text-sm font-medium text-gray-700 shrink-0 lg:whitespace-nowrap">Категории:</span>
                        <UCheckboxGroup
                            v-model="selectedCategories"
                            :items="categoryOptions"
                            :orientation="categoryGroupOrientation"
                            variant="list"
                            class="min-w-0 max-w-full"
                            @update:model-value="onCategoriesChange"
                        />
                    </div>
                </div>
                <UButton
                    class="shrink-0 justify-self-end sm:justify-self-start"
                    :label="sortMethod == 'asc' ? 'От старых к новым' : 'От новых к старым'"
                    icon="i-heroicons-arrows-up-down"
                    size="xs" color="primary" square variant="solid"
                    @click="sort()"
                />
            </div>
            <UInput
                v-model="searchQuery"
                type="text"
                placeholder="Поиск мероприятий..."
                class="w-full"
            />
        </div>

        <!-- Loading State -->
        <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
            <div class="text-center">
                <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
                <p class="text-gray-600">Загрузка мероприятий...</p>
            </div>
        </div>

        <!-- Success State -->
        <div v-else-if="status === 'success'">
            <div v-if="activities.length !== 0" class="flex flex-col space-y-2">
                <ActivityCardGrid>
                    <ActivityCard
                        v-for="(item, index) in activities"
                        :key="getActivityKey(item, index)"
                        :activity="item"
                        :api-path-storage="config.public.API_PATH_STORAGE"
                        variant="catalog"
                        @open="openActivity(item)"
                        @wishlist-add="(id) => addToWishlist(id)"
                        @wishlist-remove="(id) => deleteFromWishlist(id)"
                        @register="(id) => registerToActivity(id)"
                        @undo-register="(id) => undoRegisterToActivity(id)"
                    />
                </ActivityCardGrid>

                <ListPagination :pagination="pagination" @update:page="onPageChange" />
            </div>

            <UAlert
                v-if="activities.length === 0 && !searchQuery"
                icon="i-heroicons-information-circle"
                color="info"
                variant="solid"
                title="Информация"
                description="Список текущих мероприятий или событий пуст"
            />

            <UAlert
                v-if="activities.length === 0 && searchQuery"
                icon="i-heroicons-information-circle"
                color="info"
                variant="solid"
                title="Информация"
                :description="`«${searchQuery}» не найдено`"
            />
        </div>

        <!-- Error State -->
        <UCard v-else-if="status === 'error'" class="mt-4 py-12">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <UIcon name="i-heroicons-exclamation-triangle" class="w-8 h-8 text-red-500" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ошибка загрузки</h3>
                <p class="text-sm text-gray-500">Не удалось загрузить мероприятия</p>
            </div>
        </UCard>

        <ActivityModal
            v-model:open="showActivityModal"
            :activity="activeActivity"
            :files="activityFiles"
            :api-path-storage="config.public.API_PATH_STORAGE"
            @close="handleCloseActivity"
        >
            <template #footer>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <UIcon name="i-heroicons-information-circle" class="w-4 h-4" />
                    <span v-if="activeActivity?.is_registered == 0">Для участия нажмите кнопку "Зарегистрироваться"</span>
                    <span v-if="activeActivity?.is_registered == 1">Вы уже зарегистрированы на это мероприятие</span>
                </div>
                <div class="flex gap-2">
                    <UButton
                        v-if="activeActivity?.is_registered == 0"
                        color="primary"
                        variant="solid"
                        :loading="registerLoading"
                        @click="registerToActivity(Number(activeActivity.id))"
                    >
                        Зарегистрироваться
                    </UButton>
                    <UButton
                        v-if="activeActivity?.is_registered == 1"
                        color="error"
                        variant="solid"
                        :loading="registerLoading"
                        @click="undoRegisterToActivity(Number(activeActivity.id))"
                    >
                        Отменить регистрацию
                    </UButton>
                </div>
            </template>
        </ActivityModal>
    </NuxtLayout>
</template>

<script setup>
import { useMediaQuery } from '@vueuse/core';

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['student'],
    layout: false,
});

const config = useRuntimeConfig();
const toast = useToast();

/** На широком экране — горизонтально и в одну строку с остальными фильтрами, как до правки мобилки. */
const isLgUp = useMediaQuery('(min-width: 1024px)');
const categoryGroupOrientation = computed(() => (isLgUp.value ? 'horizontal' : 'vertical'));
const { getCategories, getAcademies } = useStudentActivitiesApi()
const { getMy } = useStudentHeaderApi()
const { user } = useSanctumAuth();

const statusFilter = ref('started');
const sortMethod = ref('desc');
const searchQuery = ref('');
const academyFilter = ref('all');
const myAcademyId = ref(null);

const selectedCategories = ref([]);

const categoryOptions = ref([]);
const defaultAcademyOption = { value: 'all', label: 'Для всех' };
const myAcademyOption = { value: 'my', label: 'Моя академия' };
const academyOptions = ref([
    defaultAcademyOption,
]);

function fetchCategories() {
    getCategories()
        .then((list) => {
            categoryOptions.value = list
                .filter((c) => c.id != null)
                .map((c) => ({ value: String(c.id), label: c.name ?? '' }));
        })
        .catch(() => {
            toast.add({
                title: 'Не удалось загрузить категории',
                color: 'warning',
            });
            categoryOptions.value = [];
        });
}

function formatAcademyLabel(academy) {
    const fullName = String(academy?.name || '').trim();
    return fullName || `ID ${academy?.id ?? ''}`;
}

async function fetchAcademies() {
    try {
        const data = await getAcademies();
        const fromApi = (Array.isArray(data) ? data : [])
            .filter((academy) => academy.id != null)
            .map((academy) => ({
                value: String(academy.id),
                label: formatAcademyLabel(academy),
            }));

        academyOptions.value = [
            defaultAcademyOption,
            ...(myAcademyId.value ? [myAcademyOption] : []),
            ...fromApi,
        ];
    } catch {
        academyOptions.value = [defaultAcademyOption];
        toast.add({
            title: 'Не удалось загрузить список академий',
            color: 'warning',
        });
    }
}

async function fetchMyAcademy() {
    const userId = user.value?.user_id;
    if (userId == null) return;

    try {
        const data = await getMy(userId);
        const academyId = data.academy?.id;
        if (academyId == null) return;

        const academyShortName = data.academy?.short_name;
        const academyName = data.academy?.name;
        const labelName = formatAcademyLabel({
            id: academyId,
            short_name: academyShortName,
            name: academyName,
            institution_name: data.institution,
        });
        myAcademyId.value = String(academyId);
        myAcademyOption.label = `Моя академия: ${labelName}`;
        academyOptions.value = [
            defaultAcademyOption,
            myAcademyOption,
            ...academyOptions.value.filter((option) => option.value !== 'all' && option.value !== 'my'),
        ];
    } catch {
        toast.add({
            title: 'Не удалось загрузить академию студента',
            color: 'warning',
        });
    }
}

const statusOptions = [
    { value: 'scheduled', label: 'Запланировано' },
    { value: 'started', label: 'Начато' },
    { value: 'completed', label: 'Завершено' },
];

const buildApiUrl = (page) => {
    const params = new URLSearchParams();
    params.set('status', statusFilter.value);
    params.set('sort_method', sortMethod.value);
    params.set('sort_by', 'date_start');
    params.set('per_page', String(pagination.value.perPage ?? 20));
    const academyValue = academyFilter.value === 'my'
        ? (myAcademyId.value ?? 'all')
        : academyFilter.value;
    params.set('academy', String(academyValue ?? 'all'));

    const query = searchQuery.value.trim();
    if (query) {
        params.set('search', query);
    }

    for (const categoryId of selectedCategories.value) {
        params.append('category_ids[]', categoryId);
    }

    if (page != null) {
        params.set('page', String(page));
    }

    return `/api/v1/student/activity?${params.toString()}`;
}

const activities = ref([]);

function getActivityKey(item, index) {
    const id = item.id;
    return typeof id === 'string' || typeof id === 'number' ? id : index;
}

function syncActivities() {
    activities.value = [...(allActivities.value || [])];
}

const {
    status,
    allActivities,
    pagination,
    loadData,
    openActivity: openActivityBase,
    closeActivity,
    showActivityModal,
    activeActivity,
    registerLoading,
    addToWishlist,
    deleteFromWishlist,
    registerToActivity,
    undoRegisterToActivity,
} = useStudentActivityList({
    mode: 'catalog',
    buildUrl: (page) => buildApiUrl(page),
    onAfterLoad: syncActivities,
});

const { getActivityFiles } = useStudentActivityFilesApi();
const activityFiles = ref([]);

async function openActivity(item) {
    openActivityBase(item);
    activityFiles.value = [];
    if (item.id) {
        try {
            activityFiles.value = await getActivityFiles(item.id);
        } catch {
            activityFiles.value = [];
        }
    }
}

function handleCloseActivity() {
    closeActivity();
    activityFiles.value = [];
}

onMounted(async () => {
    fetchCategories();
    await Promise.allSettled([fetchAcademies(), fetchMyAcademy()]);
    loadData(buildApiUrl(getInitialPage()));
});

const router = useRouter();
const route = useRoute();

function reloadFirstPage() {
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildApiUrl(1));
}

function onStatusChange() {
    reloadFirstPage();
}

function onCategoriesChange() {
    reloadFirstPage();
}

function onAcademyChange(value) {
    academyFilter.value = String(value ?? 'all');
    reloadFirstPage();
}

function sort() {
    sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc';
    reloadFirstPage();
}

const { onPageChange, getInitialPage } = usePaginationRedirect(loadData, (page) => buildApiUrl(page));

watch(searchQuery, () => {
    if (searchDebounceTimeout) {
        clearTimeout(searchDebounceTimeout);
    }

    searchDebounceTimeout = setTimeout(() => {
        reloadFirstPage();
    }, 500);
});

let searchDebounceTimeout = null;
</script>
