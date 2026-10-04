<template>
    <div class="flex flex-col space-y-4">
            <div class="md:hidden">
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <UButton
                        :label="showMobileFilters ? 'Фильтры' : 'Фильтры'"
                        icon="i-heroicons-funnel"
                        size="sm"
                        color="neutral"
                        variant="outline"
                        @click="showMobileFilters = !showMobileFilters"
                    />

                    <div class="flex space-x-2">
                        <UButton
                            :label="sortMethod == 'asc' ? 'По возрастанию' : 'По убыванию'"
                            icon="i-heroicons-arrows-up-down"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                            @click="sort()"
                        />

                        <UButton
                            to="/admin/users/add"
                            label="Добавить"
                            icon="i-heroicons-plus-circle"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                        />
                    </div>
                </div>

                <div v-if="showMobileFilters" class="mt-3 p-4 bg-muted rounded-lg border border-default space-y-3">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-default mb-1">Роль</label>
                            <USelect
                                v-model="userForm.role"
                                :items="roleOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="applySorting"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-default mb-1">Сортировка</label>
                            <USelect
                                v-model="sortField"
                                :items="userSortFieldOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="applySorting"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-default mb-1">Статус</label>
                            <USelect
                                v-model="userForm.status"
                                :items="userStatusOptions"
                                size="sm"
                                class="w-full"
                                @update:model-value="applySorting"
                            />
                        </div>
                    </div>

                    <div class="flex space-x-2 pt-2">
                        <UButton
                            label="Сбросить"
                            icon="i-heroicons-x-mark"
                            size="xs"
                            color="error"
                            variant="outline"
                            class="flex-1"
                            @click="resetFilters"
                        />

                        <UButton
                            label="Импорт"
                            icon="i-heroicons-arrow-up-tray"
                            size="xs"
                            color="primary"
                            variant="outline"
                            class="flex-1"
                            @click="openImportModal"
                        />
                    </div>
                </div>
            </div>

            <div class="hidden md:block">
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <div class="flex flex-wrap space-x-2 gap-y-2">
                        <USelect
                            v-model="userForm.role"
                            :items="roleOptions"
                            size="sm"
                            class="min-w-[160px]"
                            @update:model-value="applySorting"
                        />

                        <USelect
                            v-model="sortField"
                            :items="userSortFieldOptions"
                            size="sm"
                            class="min-w-[180px]"
                            @update:model-value="applySorting"
                        />

                        <USelect
                            v-model="userForm.status"
                            :items="userStatusOptions"
                            size="sm"
                            class="min-w-[160px]"
                            @update:model-value="applySorting"
                        />

                        <UButton
                            label="Сбросить фильтры"
                            icon="i-heroicons-x-mark"
                            size="xs"
                            color="error"
                            variant="outline"
                            @click="resetFilters"
                        />
                    </div>

                    <div class="flex flex-wrap space-x-2 gap-y-2">
                        <UButton
                            label="Импорт пользователей"
                            icon="i-heroicons-arrow-up-tray"
                            size="xs"
                            color="primary"
                            variant="outline"
                            @click="openImportModal"
                        />

                        <UButton
                            :label="sortMethod == 'asc' ? 'По возрастанию' : 'По убыванию'"
                            icon="i-heroicons-arrows-up-down"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                            @click="sort()"
                        />

                        <UButton
                            to="/admin/users/add"
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
                    placeholder="Поиск пользователей по ФИО"
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
                    <p class="text-muted">Загрузка пользователей...</p>
                </div>
            </div>

            <div v-if="status === 'success'">
                <div v-if="members.length != 0" class="flex flex-col gap-2">
                    <div class="hidden lg:block">
                        <table class="w-full text-sm table-fixed">

                            <colgroup>
                                <col style="width: 1%;">
                                <col style="width: 5%;">
                                <col style="width: 20%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 8%;">
                            </colgroup>

                            <thead class="">
                                <tr class="bg-muted">
                                    <th class="border-b border-default py-2 font-semibold text-center align-middle px-4">
                                        <input type="checkbox" :checked="isAllSelected" class="w-4 h-4" @change="toggleSelecteAll($event)" >
                                    </th>
                                    <th class="border-b border-default px-4 py-2 font-semibold">
                                        № п/п
                                    </th>
                                    <th class="border-b border-default px-4 py-2 text-left font-semibold">
                                        ФИО
                                    </th>
                                    <th class="border-b border-default px-4 py-2 font-semibold">
                                        Роль
                                    </th>
                                    <th class="border-b border-default px-4 py-2 font-semibold">
                                        Статус
                                    </th>
                                    <th class="border-b border-default px-4 py-2 font-semibold text-center">
                                        Действия
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in members" :key="index" :class="index % 2 === 0 ? 'hover:bg-primary/10' : 'bg-muted/30 hover:bg-primary/10'">

                                    <td class="text-center border-b border-default px-4 py-2">
                                        <input v-model="selectIds" type="checkbox" :value="item.user_id" class="w-4 h-4" >
                                    </td>

                                    <td class="text-center border-b border-default px-4 py-2">
                                        {{ index + 1 }}
                                    </td>

                                    <td class="border-b border-muted px-4 py-2">
                                        <div class="flex items-center space-x-2">
                                            <img
v-if="item.profile_photo_path == null" class="h-5 w-5 rounded-full"
                                                :src="staticImg.photo" alt="male">
                                            <img
v-if="item.profile_photo_path != null" class="h-5 w-5 rounded-full"
                                                :src="`${config.public.API_PATH_STORAGE + item.profile_photo_path}`"
                                                alt="photo">
                                            <NuxtLink
                                                :to="`/student/profile/${item.user_id}`"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-primary hover:underline cursor-pointer"
                                            >
                                                {{ item.fio }}
                                            </NuxtLink>
                                        </div>
                                    </td>
                                    <td class="border-b border-muted px-4 py-2 text-center">
                                        <UBadge :ui="{ rounded: 'rounded-full' }" color="white" variant="solid">{{
                                            item.role }}
                                        </UBadge>
                                    </td>
                                    <td class="border-b border-muted px-4 py-2 text-center">
                                        <UTooltip
                                            :text="item.is_activated ? 'Деактивировать' : 'Активировать'"
                                            :content="{ side: 'top' }"
                                        >
                                            <UButton
                                                :icon="item.is_activated ? 'i-heroicons-check-circle' : 'i-heroicons-x-circle'"
                                                :color="item.is_activated ? 'success' : 'error'"
                                                size="md"
                                                variant="soft"
                                                square
                                                @click="item.is_activated ? deactivate(item.user_id) : activate(item.user_id)"
                                            />
                                        </UTooltip>
                                    </td>
                                    <td class="border-b border-muted px-4 py-2 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <UButton
                                                icon="i-heroicons-pencil-square"
                                                color="warning"
                                                variant="outline"
                                                size="md"
                                                square
                                                @click="edit(item.user_id)"
                                            />
                                            <UButton
                                                icon="i-heroicons-trash"
                                                color="error"
                                                variant="outline"
                                                size="md"
                                                square
                                                @click="destroy(item.user_id)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="lg:hidden grid gap-2">
                        <div
v-for="(item, index) in members" :key="index"
                            class="relative grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
                            <div class="grid gap-1">
                                <span class="font-semibold">ФИО</span>
                                <div class="flex items-center space-x-2">
                                    <img
v-if="item.profile_photo_path == null" class="h-5 w-5 rounded-full"
                                        :src="staticImg.photo" alt="male">
                                    <img
v-if="item.profile_photo_path != null" class="h-5 w-5 rounded-full"
                                        :src="`${config.public.API_PATH_STORAGE + item.profile_photo_path}`"
                                        alt="photo">
                                    <NuxtLink
                                        :to="`/student/profile/${item.user_id}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-left text-primary hover:underline cursor-pointer"
                                    >
                                        {{ item.fio }}
                                    </NuxtLink>

                                </div>
                            </div>
                            <div class="grid gap-1">
                                <span class="font-semibold">Роль</span>
                                {{ item.role }}
                            </div>
                            <div class="grid gap-1">
                                <span class="font-semibold">Статус</span>
                                <span
v-if="item.is_activated == 1"
                                    class="text-green-400 font-semibold">Активирован</span>
                                <span v-if="item.is_activated == 0" class="text-red-400 font-semibold">Не
                                    активирован</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <UButton
                                    icon="i-heroicons-pencil-square"
                                    color="warning"
                                    variant="outline"
                                    size="md"
                                    class="w-full justify-center"
                                    @click="edit(item.user_id)"
                                />
                                <UButton
                                    icon="i-heroicons-trash"
                                    color="error"
                                    variant="outline"
                                    size="md"
                                    class="w-full justify-center"
                                    @click="destroy(item.user_id)"
                                />
                            </div>
                            <div class="absolute top-2 right-2">
                                <UTooltip
                                    :text="item.is_activated ? 'Деактивировать' : 'Активировать'"
                                    :content="{ side: 'top' }"
                                >
                                    <UButton
                                        :icon="item.is_activated ? 'i-heroicons-check-circle' : 'i-heroicons-x-circle'"
                                        :color="item.is_activated ? 'success' : 'error'"
                                        size="md"
                                        variant="soft"
                                        square
                                        @click="item.is_activated ? deactivate(item.user_id) : activate(item.user_id)"
                                    />
                                </UTooltip>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectIds.length" class="flex flex-col space-y-2 md:flex-row md:items-center md:space-y-0 md:space-x-3 p-3 bg-primary/10 border border-default rounded-md">
                        <span class="text-sm font-medium text-primary/80">Выбрано: {{ selectIds.length }}</span>

                        <div class="flex flex-col space-y-2 md:flex-row md:space-y-0 md:space-x-2">
                            <UButton
                                :disabled="selectIds.length === 0"
                                color="success"
                                variant="outline"
                                label="Активировать"
                                @click="multipleActivate"
                            />

                            <UButton
                                :disabled="selectIds.length === 0"
                                color="warning"
                                variant="outline"
                                label="Деактивировать"
                                @click="multipleDeactivate"
                            />

                            <UButton
                                :disabled="selectIds.length === 0"
                                color="error"
                                variant="outline"
                                label="Удалить"
                                @click="multipleDelete"
                            />
                        </div>
                    </div>


                    <div class="flex justify-center md:justify-end">
                        <ListPagination :pagination="pagination" @update:page="onPageChange" />
                    </div>
                </div>

                <UAlert
v-if="members.length == 0" icon="i-heroicons-information-circle" color="info" variant="solid"
                    title="Информация"
                    description="Список элементов пуст. Для добавления нового элемента нажмите на кнопку «Добавить новую запись" />
            </div>
        <UModal v-model:open="isImportOpen">
            <template #header>
                <h3 class="text-lg md:text-xl font-semibold">
                    Импорт пользователей из DOCX
                </h3>
            </template>

            <template #body>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-muted-foreground">
                            Загрузите .docx файл и выберите тип учреждения
                        </p>
                        <UButton
                            size="xs"
                            color="neutral"
                            variant="outline"
                            icon="i-heroicons-arrow-down-tray"
                            :loading="isDownloadingTemplate"
                            @click="downloadImportTemplate"
                        >
                            Скачать шаблон
                        </UButton>
                    </div>

        <div class="space-y-1">
        <label class="text-sm font-medium">Тип учреждения</label>

        <div class="relative">
            <select
                v-model="importForm.institutionTypeId"
                class="w-full border border-default rounded px-3 py-2 text-sm pr-10 appearance-none bg-white"
                :disabled="isInstitutionTypesLoading || isImportLoading || !institutionTypes.length">
                <option value="" disabled>
                {{ isInstitutionTypesLoading ? 'Загрузка типов учреждений...' : 'Выберите тип учреждения' }}
                </option>
                <option
                v-for="type in institutionTypes"
                :key="type.id"
                :value="type.id">
                {{ type.name }}
                </option>
            </select>

            <div
                v-if="isInstitutionTypesLoading"
                class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <UIcon
                name="i-heroicons-arrow-path"
                class="h-4 w-4 text-gray-400 animate-spin" />
            </div>

            <div
                v-else
                class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <UIcon
                name="i-heroicons-chevron-down"
                class="h-4 w-4 text-gray-400" />
            </div>
        </div>

        <p
            v-if="institutionTypesError"
            class="text-xs text-red-500 mt-1 flex items-center gap-2">
            {{ institutionTypesError }}
            <button
            type="button"
            class="text-primary underline text-xs"
            @click="fetchInstitutionTypes"
            >
            Повторить
            </button>
        </p>
        </div>

            </div>

            <div class="space-y-1">
            <label class="text-sm font-medium">Файл с пользователями (.docx)</label>
            <input
                type="file"
                accept=".docx"
                :disabled="isImportLoading"
                class="w-full text-sm border border-default rounded px-3 py-2"
                @change="handleImportFileChange">
            <p v-if="importForm.file" class="text-xs text-gray-500 mt-1">
                Выбран файл: {{ importForm.file.name }}
            </p>
            </div>
        </template>

        <template #footer>
            <div class="flex flex-col-reverse space-y-2 space-y-reverse md:flex-row md:justify-end md:space-y-0 md:space-x-2 pt-2">
            <UButton
                size="sm"
                variant="ghost"
                color="neutral"
                :disabled="isImportLoading"
                class="w-full md:w-auto"
                @click="closeImportModal">
                Отмена
            </UButton>

            <UButton
                size="sm"
                color="primary"
                :loading="isImportLoading"
                :disabled="isInstitutionTypesLoading || !institutionTypes.length"
                class="w-full md:w-auto"
                @click="startImport">
                Импортировать
            </UButton>
            </div>
        </template>
        </UModal>
</div>
</template>

<script setup>
import { staticImg } from '~/assets/img'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Пользователи',
  description: 'Управляйте пользователями и их профилями',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Пользователи' }],
});

const route = useRoute();
const router = useRouter();

const config = useRuntimeConfig();
const toast = useToast();
const { get, post, delete: del, postBlob, getBlob } = useApi();
const { show: showApiError } = useApiError();

const selectIds = ref([])
const isAllSelected = computed(() =>
    members.value.length > 0
    && selectIds.value.length === members.value.length
)

const showMobileFilters = ref(false)

function toggleSelecteAll(e) {
    if (e.target.checked) {
        selectIds.value = members.value.map(m => m.user_id)
    } else {
        selectIds.value = []
    }
}

async function multipleActivate() {
    const userIds = [...selectIds.value]
    status.value = 'pending'

    try {
        const data = await post('/api/v1/admin/user/bulk-activate', {
            user_ids: userIds
        })

        status.value = 'success'
        selectIds.value = []
        await loadData(buildApiUrl(pagination.value.currentPage))

        toast.add({
            title: data?.message || `Активировано ${data?.count ?? userIds.length} пользователей`
        })
    } catch {
        status.value = 'error'
        toast.add({
            title: 'Ошибка активации пользователей',
            color: 'error'
        })
    }
}

async function multipleDeactivate() {
    const userIds = [...selectIds.value]
    status.value = 'pending'

    try {
        const data = await post('/api/v1/admin/user/bulk-deactivate', {
            user_ids: userIds
        })

        status.value = 'success'
        selectIds.value = []
        await loadData(buildApiUrl(pagination.value.currentPage))

        toast.add({ title: data?.message || `Деактивировано ${data?.count ?? userIds.length} пользователей` })
    } catch {
        status.value = 'error'
        toast.add({ title: 'Ошибка деактивации пользователей', color: 'error' })
    }
}

async function multipleDelete() {
    const count = selectIds.value.length
    if (!confirm(`Вы уверены, что хотите удалить ${count} записей?`)) return

    const userIds = [...selectIds.value]
    status.value = 'pending'

    try {
        const data = await del('/api/v1/admin/member/bulk-destroy', {
            data: { user_ids: userIds }
        })

        status.value = 'success'
        selectIds.value = []
        await loadData(buildApiUrl(pagination.value.currentPage))

        toast.add({ title: data?.message || `Удалено ${data?.count ?? count} пользователей`})
    } catch {
        status.value = 'error'
        toast.add({
            title: 'Ошибка при удалении пользователей',
            color: 'error'
        })
    }
}

onMounted(() => {
    readFiltersFromQueryOrStorage();
    loadData(buildApiUrl(pagination.value.currentPage));
});

const members = ref([]);
const status = ref('idle');
const sortMethod = ref('desc');
const sortField = ref('date_reg');
const searchTxt = ref('');
const userForm = ref({
    role: 'all',
    status: 'all',
});

const baseApiUrl = ref('/api/v1/admin/members');

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    prevPageUrl: null,
    nextPageUrl: null,
    perPage: 20,
    totalItems: 0,
});

const roleOptions = [
    { value: 'all', label: 'Все роли' },
    { value: 'student', label: 'Студент' },
    { value: 'mentor', label: 'Наставник' },
    { value: 'employer', label: 'Работодатель' },
    { value: 'admin', label: 'Администратор' },
];

const userStatusOptions = [
    { value: 'all', label: 'Любой статус' },
    { value: '1', label: 'Активен' },
    { value: '0', label: 'Не активен' },
];

const userSortFieldOptions = [
    { value: 'date_reg', label: 'По дате регистрации' },
    { value: 'fio', label: 'По ФИО' },
    { value: 'role', label: 'По роли' },
    { value: 'status', label: 'По статусу' },
];

const isImportOpen = ref(false)
const institutionTypes = ref([])

const importForm = reactive({
    institutionTypeId: '',
    file: null
})

const isInstitutionTypesLoading = ref(false)
const institutionTypesError = ref('')

const isImportLoading = ref(false)
const isDownloadingTemplate = ref(false)

const STORAGE_KEY = 'admin_users_filters';

// Автоматический поиск при вводе текста (с общим debounce)
let searchTimeout = null
const debouncedSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
    searchTimeout = setTimeout(() => {
        if (typeof search === 'function') {
            search()
        }
    }, 400)
}


function readFiltersFromQueryOrStorage() {
    const q = route.query || {};

    if (Object.keys(q).length) {
        sortField.value = q.sortBy || sortField.value;
        sortMethod.value = q.sortMethod || sortMethod.value;
        searchTxt.value = q.search || '';
        userForm.value.role = q.role || 'all';
        userForm.value.status = q.status !== undefined ? q.status : 'all';
        pagination.value.currentPage = q.page ? Number(q.page) : 1;
    } else {
        try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (raw) {
            const s = JSON.parse(raw);
            sortField.value = s.sortField ?? sortField.value;
            sortMethod.value = s.sortMethod ?? sortMethod.value;
            searchTxt.value = s.searchTxt ?? '';
            userForm.value.role = s.role ?? 'all';
            userForm.value.status = s.status ?? 'all';
            pagination.value.currentPage = s.page ?? 1;
        }
        } catch {/* ignore */}
    }
    }

function saveFiltersToSession() {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        sortField: sortField.value,
        sortMethod: sortMethod.value,
        searchTxt: searchTxt.value,
        role: userForm.value.role,
        status: userForm.value.status,
        page: pagination.value.currentPage
        }));
    } catch {/* ignore */}
}

function resetFilters() {
    userForm.value.role = 'all'
    userForm.value.status = 'all'
    sortField.value = 'date_reg'
    sortMethod.value = 'desc'
    searchTxt.value = ''
    pagination.value.currentPage = 1

    router.replace({ path: '/admin/users', query: {} })

    loadData(buildApiUrl(1))
}

function buildQueryObject(page = pagination.value.currentPage) {
    const q = {};
    if (sortField.value) q.sortBy = sortField.value;
    if (sortMethod.value) q.sortMethod = sortMethod.value;
    if (userForm.value.role && userForm.value.role !== 'all') q.role = userForm.value.role;
    if (userForm.value.status !== '' && userForm.value.status !== 'all') q.status = userForm.value.status;
    if (searchTxt.value) q.search = searchTxt.value;
    if (page && page > 1) q.page = String(page);
    return q;
}

function syncUrlWithState(page = pagination.value.currentPage) {
    const q = buildQueryObject(page);
    router.replace({ path: '/admin/users', query: q }).catch(() => {});
    saveFiltersToSession();
}


function buildApiUrl(page = 1) {
    const trimmedSearch = (searchTxt.value || '').trim();
    if (trimmedSearch) {
        const path = `/api/v1/admin/member/search/${sortMethod.value}/${encodeURIComponent(trimmedSearch)}`;
        return page > 1 ? `${path}?page=${page}` : path;
    }
    const params = new URLSearchParams();
    if (sortField.value) params.append('sortBy', sortField.value);
    if (userForm.value.role && userForm.value.role !== 'all') params.append('role', userForm.value.role);
    if (userForm.value.status !== '' && userForm.value.status !== 'all') params.append('status', userForm.value.status);
    if (sortMethod.value) params.append('sortMethod', sortMethod.value);
    if (page > 1) params.append('page', page);
    const queryString = params.toString();
    return queryString ? `${baseApiUrl.value}?${queryString}` : baseApiUrl.value;
}

async function openImportModal() {
    isImportOpen.value = true

    if (!institutionTypes.value.length && !isInstitutionTypesLoading.value) {
        await fetchInstitutionTypes()
    }
}

function closeImportModal() {
    isImportOpen.value = false
    resetImportState()
}

function resetImportState() {
    importForm.institutionTypeId = ''
    importForm.file = null
    isImportLoading.value = false
    institutionTypesError.value = ''
}

async function fetchInstitutionTypes() {
    isInstitutionTypesLoading.value = true
    institutionTypesError.value = ''

    try {
        const data = await get('/api/v1/admin/import-users/institution-types')
        institutionTypes.value = data.institution_types ?? []
    } catch {
        institutionTypesError.value = 'Не удалось загрузить типы учреждений'
        toast.add({ title: 'Не удалось загрузить направления', color: 'error' })
    } finally {
        isInstitutionTypesLoading.value = false
    }
}

async function downloadImportTemplate() {
    isDownloadingTemplate.value = true
    try {
        const response = await getBlob('/api/v1/admin/import-users/template')
        const blob = new Blob([response.data], {
            type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = 'users-import-template.docx'
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
    } catch (error) {
        showApiError(error, 'Ошибка скачивания шаблона')
    } finally {
        isDownloadingTemplate.value = false
    }
}

async function startImport() {
    if (!importForm.institutionTypeId || !importForm.file) {
        toast.add({ title: 'Выберите тип учреждения и файл для импорта', color: 'error' })
        return
    }

    const fileName = importForm.file.name.toLowerCase()
    if (!fileName.endsWith('.docx')) {
        toast.add({ title: 'Файл должен быть в формате .docx', color: 'error' })
        return
    }

    isImportLoading.value = true

    const formData = new FormData()
    formData.append('institution_type', String(importForm.institutionTypeId))
    formData.append('file', importForm.file, importForm.file.name)

    try {
        const response = await postBlob(
            '/api/v1/admin/import-users',
            formData,
        )

        const blob = new Blob([response.data], {
            type: response.headers?.get?.('content-type') ||
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        })

        let fileName = 'import-result.docx'
        const disposition = response.headers?.get?.('content-disposition')
        if (disposition) {
            const match = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition)
            if (match && match[1]) {
                fileName = decodeURIComponent(match[1].replace(/['"]/g, ''))
            }
        }

        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        await loadData(buildApiUrl(pagination.value.currentPage))
        closeImportModal()
        toast.add({ title: 'Файл импорта получен' })
    } catch (error) {
        const backendMsg =
            error?.response?._data?.message ||
            error?._data?.message ||
            'Ошибка при обработке файла'
        toast.add({ title: backendMsg, color: 'error' })
    } finally {
        isImportLoading.value = false
    }
}

function handleImportFileChange(event) {
    const file = event.target.files && event.target.files[0]
    importForm.file = file || null
}

function loadData(url) {
    status.value = 'pending';

    get(url).then((data) => {
        members.value = data.data;

        pagination.value.currentPage = data.current_page;
        pagination.value.lastPage = data.last_page;
        pagination.value.perPage = data.per_page ?? pagination.value.perPage;
        pagination.value.totalItems = data.total ?? (data.last_page * pagination.value.perPage);

        const isSearchMode = (searchTxt.value || '').trim().length > 0;
        if (isSearchMode) {
            pagination.value.prevPageUrl = data.current_page > 1 ? buildApiUrl(data.current_page - 1) : null;
            pagination.value.nextPageUrl = data.current_page < data.last_page ? buildApiUrl(data.current_page + 1) : null;
        } else {
            if (data.prev_page_url) {
                const prevUrl = new URL(data.prev_page_url, window.location.origin);
                const params = new URLSearchParams(prevUrl.search);
                if (sortField.value) params.set('sortBy', sortField.value);
                if (userForm.value.role) params.set('role', userForm.value.role);
                if (userForm.value.status !== '') params.set('status', userForm.value.status);
                if (sortMethod.value) params.set('sortMethod', sortMethod.value);
                prevUrl.search = params.toString();
                pagination.value.prevPageUrl = prevUrl.toString().replace(window.location.origin, '');
            } else {
                pagination.value.prevPageUrl = null;
            }
            if (data.next_page_url) {
                const nextUrl = new URL(data.next_page_url, window.location.origin);
                const params = new URLSearchParams(nextUrl.search);
                if (sortField.value) params.set('sortBy', sortField.value);
                if (userForm.value.role) params.set('role', userForm.value.role);
                if (userForm.value.status !== '') params.set('status', userForm.value.status);
                if (sortMethod.value) params.set('sortMethod', sortMethod.value);
                nextUrl.search = params.toString();
                pagination.value.nextPageUrl = nextUrl.toString().replace(window.location.origin, '');
            } else {
                pagination.value.nextPageUrl = null;
            }
        }

        status.value = 'success';

        syncUrlWithState(pagination.value.currentPage);
    }).catch(error => {
        status.value = 'error';
        showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
    })
}

function sort() {
    if (sortMethod.value == 'asc') {
        sortMethod.value = 'desc';
    } else {
        sortMethod.value = 'asc';
    }

    applySorting();
}

function search() {
    if (searchTxt.value.length != 0) {
        pagination.value.currentPage = 1;
        const url = buildApiUrl();
        loadData(url);
    } else {
        loadData(buildApiUrl());
    }
}

async function activate(id) {
    try {
        const data = await get('/api/v1/admin/user/activate/' + id);
        const member = members.value.find(m => m.user_id === id);
        if (member) member.is_activated = 1;
        toast.add({ title: (data && data.message) ? data.message : 'Пользователь активирован', color: 'success' });
    } catch (error) {
        const err = error && typeof error === 'object' ? error : {};
        const statusCode = err.statusCode ?? (err.response && err.response.status);
        if (statusCode === 204 || statusCode === 200) {
            const member = members.value.find(m => m.user_id === id);
            if (member) member.is_activated = 1;
            toast.add({ title: 'Пользователь активирован', color: 'success' });
        } else {
            showApiError(error, 'Ошибка активации');
        }
    }
}

async function deactivate(id) {
    try {
        const data = await get('/api/v1/admin/user/deactivate/' + id);
        const member = members.value.find(m => m.user_id === id);
        if (member) member.is_activated = 0;
        toast.add({ title: (data && data.message) ? data.message : 'Пользователь деактивирован', color: 'success' });
    } catch (error) {
        const err = error && typeof error === 'object' ? error : {};
        const statusCode = err.statusCode ?? (err.response && err.response.status);
        if (statusCode === 204 || statusCode === 200) {
            const member = members.value.find(m => m.user_id === id);
            if (member) member.is_activated = 0;
            toast.add({ title: 'Пользователь деактивирован', color: 'success' });
        } else {
            showApiError(error, 'Ошибка деактивации');
        }
    }
}

function edit(id) {
    return navigateTo('/admin/users/edit/' + id);
}

function destroy(id) {
    if (confirm('Вы уверены, что хотите удалить данную запись?')) {
        del('/api/v1/admin/member/destroy/' + id).then((data) => {
            status.value = 'success';
            loadData(buildApiUrl(pagination.value.currentPage));
            toast.add({ title: data.message });
        }).catch(error => {
            status.value = 'error';
            showApiError(error, 'Ошибка');
        });
    }
}

function onPageChange(page) {
    pagination.value.currentPage = page;
    const url = buildApiUrl(page);
    loadData(url);
}

function applySorting() {
    pagination.value.currentPage = 1;
    const url = buildApiUrl(1);
    loadData(url);
}
</script>
