<template>
    <div class="flex flex-col space-y-4">
        <!-- Mobile filters -->
        <div class="md:hidden">
            <div class="flex flex-wrap justify-between items-center gap-2">
                <UButton
                    :label="showMobileFilters ? 'Скрыть фильтры' : 'Фильтры'"
                    icon="i-heroicons-funnel"
                    size="sm"
                    color="neutral"
                    variant="outline"
                    @click="showMobileFilters = !showMobileFilters"
                />
                <div class="flex space-x-2">
                    <UButton
                        to="/mentor/users/add"
                        label="Добавить студента"
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
                        <label class="block text-sm font-medium text-default mb-1">Статус</label>
                        <USelect v-model="statusFilter" :items="statusOptions" size="sm" class="w-full" @update:model-value="applyFilters" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-default mb-1">Группа</label>
                        <USelect v-model="groupFilter" :items="groupOptions" size="sm" class="w-full" @update:model-value="applyFilters" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-default mb-1">Уровень образования</label>
                        <USelect v-model="educationLevelFilter" :items="educationLevelOptions" size="sm" class="w-full" @update:model-value="applyFilters" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-default mb-1">Сортировка</label>
                        <USelect v-model="sortBy" :items="sortByOptions" size="sm" class="w-full" @update:model-value="applyFilters" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop filters -->
        <div class="hidden md:flex flex-wrap justify-between items-center gap-2">
            <div class="flex flex-wrap space-x-2 gap-y-2">
                <USelect v-model="statusFilter" :items="statusOptions" size="sm" class="min-w-[160px]" @update:model-value="applyFilters" />
                <USelect v-model="groupFilter" :items="groupOptions" size="sm" class="min-w-[160px]" @update:model-value="applyFilters" />
                <USelect v-model="educationLevelFilter" :items="educationLevelOptions" size="sm" class="min-w-[180px]" @update:model-value="applyFilters" />
                <USelect v-model="sortBy" :items="sortByOptions" size="sm" class="min-w-[180px]" @update:model-value="applyFilters" />
                <UButton
                    :label="sortMethod === 'asc' ? 'По возрастанию' : 'По убыванию'"
                    icon="i-heroicons-arrows-up-down"
                    size="xs"
                    color="primary"
                    square
                    variant="solid"
                    @click="toggleSort"
                />
            </div>
            <div class="flex flex-wrap space-x-2 gap-y-2">
                <UButton
                    to="/mentor/users/add"
                    label="Добавить студента"
                    icon="i-heroicons-plus-circle"
                    size="xs"
                    color="primary"
                    square
                    variant="solid"
                />
            </div>
        </div>

        <!-- Search -->
        <div class="flex space-x-2">
            <UInput
                v-model="searchTxt"
                type="text"
                icon="i-heroicons-magnifying-glass"
                size="sm"
                class="w-full"
                placeholder="Поиск по ФИО, группе или email"
                @input="debouncedSearch"
                @keyup.enter.prevent="applyFilters"
            />
            <UButton
                icon="i-heroicons-magnifying-glass"
                size="xs"
                color="primary"
                variant="solid"
                class="shrink-0"
                @click="applyFilters"
            />
        </div>

        <!-- Loading -->
        <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
            <div class="text-center">
                <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
                <p class="text-muted">Загрузка студентов...</p>
            </div>
        </div>

        <div v-if="status === 'success'">
            <!-- Desktop table -->
            <div v-if="students.length > 0" class="flex flex-col gap-2">
                <div class="hidden lg:block">
                    <table class="w-full text-sm table-fixed">
                        <colgroup>
                            <col style="width: 4%;">
                            <col style="width: 22%;">
                            <col style="width: 20%;">
                            <col style="width: 15%;">
                            <col style="width: 10%;">
                            <col style="width: 29%;">
                        </colgroup>
                        <thead>
                            <tr class="bg-muted">
                                <th class="border-b border-default py-2 font-semibold text-center px-4">№</th>
                                <th class="border-b border-default px-4 py-2 text-left font-semibold">ФИО</th>
                                <th class="border-b border-default px-4 py-2 text-left font-semibold">Email</th>
                                <th class="border-b border-default px-4 py-2 font-semibold">Академия / Институт</th>
                                <th class="border-b border-default px-4 py-2 font-semibold">Статус</th>
                                <th class="border-b border-default px-4 py-2 font-semibold text-center">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in students" :key="item.id" :class="index % 2 === 0 ? 'hover:bg-primary/10' : 'bg-muted/30 hover:bg-primary/10'">
                                <td class="text-center border-b border-default px-4 py-2">{{ (pagination.currentPage - 1) * pagination.perPage + index + 1 }}</td>
                                <td class="border-b border-default px-4 py-2">
                                    <NuxtLink
                                        :to="`/student/profile/${item.id}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium text-primary hover:underline"
                                    >
                                        {{ item.name }}
                                    </NuxtLink>
                                </td>
                                <td class="border-b border-default px-4 py-2 text-muted">{{ item.email }}</td>
                                <td class="border-b border-default px-4 py-2 text-center">
                                    <UBadge color="primary" variant="soft">
                                        {{ item.academy?.short_name || item.academy?.name || '—' }}
                                    </UBadge>
                                </td>
                                <td class="border-b border-default px-4 py-2 text-center">
                                    <UBadge :color="item.is_activated ? 'success' : 'error'" variant="soft">
                                        {{ item.is_activated ? 'Активен' : 'Заблокирован' }}
                                    </UBadge>
                                </td>
                                <td class="border-b border-default px-4 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <UTooltip text="Подробнее" :content="{ side: 'top' }">
                                            <UButton icon="i-heroicons-eye" color="primary" variant="outline" size="sm" square @click="showStudent(item.id)" />
                                        </UTooltip>
                                        <UTooltip text="Редактировать" :content="{ side: 'top' }">
                                            <UButton icon="i-heroicons-pencil-square" color="warning" variant="outline" size="sm" square @click="openEditStudent(item.id)" />
                                        </UTooltip>
                                        <UTooltip :text="item.is_activated ? 'Заблокировать' : 'Разблокировать'" :content="{ side: 'top' }">
                                            <UButton
                                                :icon="item.is_activated ? 'i-heroicons-lock-closed' : 'i-heroicons-lock-open'"
                                                :color="item.is_activated ? 'error' : 'success'"
                                                variant="outline"
                                                size="sm"
                                                square
                                                @click="toggleBlock(item)"
                                            />
                                        </UTooltip>
                                        <UTooltip text="Сбросить пароль" :content="{ side: 'top' }">
                                            <UButton icon="i-heroicons-key" color="neutral" variant="outline" size="sm" square @click="openResetPassword(item)" />
                                        </UTooltip>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="lg:hidden grid gap-2">
                    <div
                        v-for="item in students" :key="item.id"
                        class="relative grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm"
                    >
                        <div class="grid gap-1">
                            <span class="font-semibold">ФИО</span>
                            <div class="text-default">{{ item.name }}</div>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Email</span>
                            <div class="text-muted">{{ item.email }}</div>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Академия</span>
                            <UBadge color="primary" variant="soft" class="w-fit">
                                {{ item.academy?.short_name || item.academy?.name || '—' }}
                            </UBadge>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Статус</span>
                            <UBadge :color="item.is_activated ? 'success' : 'error'" variant="soft" class="w-fit">
                                {{ item.is_activated ? 'Активен' : 'Заблокирован' }}
                            </UBadge>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <UButton icon="i-heroicons-eye" label="Подробнее" color="primary" variant="outline" size="sm" class="w-full justify-center" @click="showStudent(item.id)" />
                            <UButton icon="i-heroicons-pencil-square" label="Редактировать" color="warning" variant="outline" size="sm" class="w-full justify-center" @click="openEditStudent(item.id)" />
                            <UButton
                                :icon="item.is_activated ? 'i-heroicons-lock-closed' : 'i-heroicons-lock-open'"
                                :label="item.is_activated ? 'Заблокировать' : 'Разблокировать'"
                                :color="item.is_activated ? 'error' : 'success'"
                                variant="outline"
                                size="sm"
                                class="w-full justify-center"
                                @click="toggleBlock(item)"
                            />
                            <UButton icon="i-heroicons-key" label="Сбросить пароль" color="neutral" variant="outline" size="sm" class="w-full justify-center" @click="openResetPassword(item)" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-4">
                    <p class="text-sm text-muted">Всего: {{ pagination.totalItems }} студент(ов)</p>
                    <div class="flex justify-center md:justify-end w-full sm:w-auto">
                        <ListPagination :pagination="pagination" @update:page="onPageChange" />
                    </div>
                </div>
            </div>

            <UAlert
                v-if="students.length === 0"
                icon="i-heroicons-information-circle"
                color="info"
                variant="solid"
                title="Информация"
                description="Список студентов пуст. Студенты появятся здесь после регистрации в системе."
            />
        </div>

        <!-- Модальное окно просмотра студента -->
        <UModal v-model:open="showStudentModal" :ui="{ content: 'w-full sm:max-w-2xl' }">
            <template #header>
                <h3 class="text-lg font-semibold leading-6 text-default">Информация о студенте</h3>
            </template>
            <template #body>
                <div v-if="selectedStudent" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-muted">ФИО</label>
                            <p class="text-base text-default">{{ selectedStudent.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted">Email</label>
                            <p class="text-base text-default">{{ selectedStudent.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted">Академия / Институт</label>
                            <p class="text-base text-default">{{ selectedStudent.academy?.name || '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted">Уровень образования</label>
                            <p class="text-base text-default">{{ selectedStudent.education_level_id ? getEducationLevelName(selectedStudent.education_level_id) : (selectedStudent.education_level || '—') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted">Группа</label>
                            <p class="text-base text-default">{{ selectedStudent.student_group || '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted">Статус</label>
                            <UBadge :color="selectedStudent.is_activated ? 'success' : 'error'" variant="soft">
                                {{ selectedStudent.is_activated ? 'Активен' : 'Заблокирован' }}
                            </UBadge>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <UButton
                            :to="`/student/profile/${selectedStudent.id}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            label="Публичный профиль"
                            icon="i-heroicons-user-circle"
                            color="primary"
                            variant="outline"
                            size="sm"
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <UButton color="neutral" variant="outline" @click="showStudentModal = false">Закрыть</UButton>
                </div>
            </template>
        </UModal>

        <!-- Модальное окно сброса пароля -->
        <UModal v-model:open="showResetPasswordModal" :ui="{ content: 'w-full sm:max-w-md' }">
            <template #header>
                <h3 class="text-lg font-semibold leading-6 text-default">Сброс пароля</h3>
            </template>
            <template #body>
                <div class="space-y-4">
                    <p class="text-sm text-muted">
                        Введите новый пароль для студента <strong>{{ selectedStudent?.name }}</strong>
                    </p>
                    <UFormField label="Новый пароль" required>
                        <UInput v-model="resetPasswordForm.password" :type="showPassword ? 'text' : 'password'" placeholder="Введите пароль" autocomplete="new-password" name="new-student-password" class="w-full">
                            <template #trailing>
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    :icon="showPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
                                    :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
                                    @click.prevent="showPassword = !showPassword"
                                />
                            </template>
                        </UInput>
                    </UFormField>
                    <UFormField label="Подтвердите пароль" required>
                        <UInput v-model="resetPasswordForm.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Повторите пароль" autocomplete="new-password" name="new-student-password-confirm" class="w-full">
                            <template #trailing>
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    :icon="showPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'"
                                    :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
                                    @click.prevent="showPassword = !showPassword"
                                />
                            </template>
                        </UInput>
                    </UFormField>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end space-x-3">
                    <UButton color="neutral" variant="outline" @click="showResetPasswordModal = false">Отмена</UButton>
                    <UButton color="primary" :loading="resettingPassword" @click="resetPassword">Сбросить пароль</UButton>
                </div>
            </template>
        </UModal>

        <!-- Модальное окно редактирования студента -->
        <UModal v-model:open="showEditStudentModal" :ui="{ content: 'w-full sm:max-w-2xl' }">
            <template #header>
                <h3 class="text-lg font-semibold leading-6 text-default">Редактирование студента</h3>
            </template>
            <template #body>
                <div v-if="editForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <UFormField label="Фамилия">
                            <UInput v-model="editForm.last_name" placeholder="Фамилия" />
                        </UFormField>
                        <UFormField label="Имя">
                            <UInput v-model="editForm.first_name" placeholder="Имя" />
                        </UFormField>
                        <UFormField label="Отчество">
                            <UInput v-model="editForm.middle_name" placeholder="Отчество" />
                        </UFormField>
                        <UFormField label="Email">
                            <UInput v-model="editForm.email" type="email" placeholder="Email" />
                        </UFormField>
                        <UFormField label="Пол">
                            <USelectMenu v-model="editForm.gender" :items="genderOptions" placeholder="Выберите пол" class="w-full" value-key="value" />
                        </UFormField>
                        <UFormField label="Дата рождения">
                            <UInput v-model="editForm.birthday" type="date" />
                        </UFormField>
                        <UFormField label="Курс">
                            <UInput v-model.number="editForm.course" type="number" placeholder="Курс" />
                        </UFormField>
                        <UFormField label="Группа">
                            <UInput v-model="editForm.student_group" placeholder="Группа" />
                        </UFormField>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end space-x-3">
                    <UButton color="neutral" variant="outline" @click="showEditStudentModal = false">Отмена</UButton>
                    <UButton color="primary" :loading="savingStudent" @click="saveStudent">Сохранить</UButton>
                </div>
            </template>
        </UModal>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['mentor'],
    layout: 'mentor',
    title: 'Пользователи',
    description: 'Управление студентами наставника',
    breadcrumb: [{ label: 'Главная', to: '/mentor' }, { label: 'Пользователи' }],
});

type StudentItem = {
    id: number
    name: string
    email: string
    academy: { id: number; name: string; short_name: string } | null
    education_level: string | null
    education_level_id: number | null
    student_group: string | null
    is_activated: boolean
}

type StudentDetail = StudentItem & {
    user_id?: number
    region_name?: string
    planet_name?: string
    institution_full?: string
    academy_short_name?: string
    academy_name?: string
    academy_id?: number
    birthday?: string
    course?: number
    last_name?: string
    first_name?: string
    middle_name?: string
    gender?: number
}

const route = useRoute();
const router = useRouter();
const { get, post } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();

type DropdownItem = {
    label: string
    value: number | string | null
}

const students = ref<StudentItem[]>([]);
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
const searchTxt = ref('');
const statusFilter = ref<string>('all');
const groupFilter = ref<string>('all');
const educationLevelFilter = ref<string>('all');
const sortBy = ref<string>('fio');
const sortMethod = ref<'asc' | 'desc'>('asc');
const showMobileFilters = ref(false);
const showStudentModal = ref(false);
const showResetPasswordModal = ref(false);
const showEditStudentModal = ref(false);
const selectedStudent = ref<StudentDetail | null>(null);
const resettingPassword = ref(false);
const savingStudent = ref(false);
const showPassword = ref(false);
const groups = ref<string[]>([]);
const educationLevels = ref<{ id: number; name: string }[]>([]);

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    prevPageUrl: null as string | null,
    nextPageUrl: null as string | null,
    perPage: 20,
    totalItems: 0,
});

const editForm = ref<{
    last_name: string
    first_name: string
    middle_name: string
    email: string
    gender: number | null
    birthday: string
    course: number | null
    student_group: string
} | null>(null);
const editingStudentId = ref<number | null>(null);

const resetPasswordForm = ref({
    password: '',
    password_confirmation: ''
});

type StudentsResponse = {
    data: StudentItem[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    prev_page_url: string | null
    next_page_url: string | null
}

const genderOptions: DropdownItem[] = [
    { label: 'Мужской', value: 0 },
    { label: 'Женский', value: 1 }
];


function getEducationLevelName(id: number | null | undefined): string {
    if (!id) return '—';
    const level = educationLevels.value.find(l => l.id === id);
    return level?.name || '—';
}

const statusOptions: DropdownItem[] = [
    { label: 'Все статусы', value: 'all' },
    { label: 'Активные', value: 'active' },
    { label: 'Заблокированные', value: 'blocked' }
];

const groupOptions = computed(() => [
    { label: 'Все группы', value: 'all' },
    ...groups.value.map(g => ({ label: g, value: g }))
]);

const educationLevelOptions = computed(() => [
    { label: 'Все уровни', value: 'all' },
    ...educationLevels.value.map(l => ({ label: l.name, value: String(l.id) }))
]);

const sortByOptions = computed(() => [
    { label: 'По ФИО', value: 'fio' },
    { label: 'По дате регистрации', value: 'date_reg' },
]);



async function loadStudents() {
    status.value = 'pending';
    try {
        const params: Record<string, string | number | null | undefined> = {
            page: pagination.value.currentPage,
            per_page: 20,
            sortBy: sortBy.value,
            sortMethod: sortMethod.value,
        };

        if (statusFilter.value === 'active') {
            params.status = 'active';
        } else if (statusFilter.value === 'blocked') {
            params.status = 'blocked';
        }

        if (groupFilter.value !== 'all') {
            params.student_group = groupFilter.value;
        }

        if (educationLevelFilter.value !== 'all') {
            params.education_level_id = educationLevelFilter.value;
        }

        if (searchTxt.value) {
            params.search = searchTxt.value.trim();
        }

        const data = await get<StudentsResponse>('/api/v1/mentor/students', { query: params });

        students.value = data.data || [];
        pagination.value.currentPage = data.current_page || 1;
        pagination.value.lastPage = data.last_page || 1;
        pagination.value.perPage = data.per_page || 20;
        pagination.value.totalItems = data.total || 0;
        pagination.value.prevPageUrl = data.prev_page_url || null;
        pagination.value.nextPageUrl = data.next_page_url || null;

        status.value = 'success';
    } catch (error) {
        status.value = 'error';
        showApiError(error, 'Ошибка загрузки списка студентов');
    }
}

async function loadGroups() {
    try {
        const data = await get<{ data: string[] }>('/api/v1/mentor/students/groups');
        groups.value = data.data || [];
    } catch {
        groups.value = [];
    }
}

function applyFilters() {
    pagination.value.currentPage = 1;
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadStudents();
}

function toggleSort() {
    sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc';
    applyFilters();
}

function search() {
    applyFilters();
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { search(); }, 400);
};

function showStudent(id: number) {
    get<StudentDetail>(`/api/v1/mentor/students/${id}`).then(data => {
        selectedStudent.value = {
            ...data,
            id: data.user_id || data.id,
            name: `${data.last_name || ''} ${data.first_name || ''} ${data.middle_name || ''}`.trim(),
            academy: data.academy_name ? {
                id: data.academy_id ?? 0,
                name: data.academy_name,
                short_name: data.academy_short_name ?? ''
            } : null,
            education_level_id: data.education_level_id ?? null,
            education_level: data.education_level || '—',
            student_group: data.student_group ?? null
        };
        showStudentModal.value = true;
    }).catch(error => {
        showApiError(error, 'Ошибка загрузки данных студента');
    });
}

function openResetPassword(student: StudentItem) {
    selectedStudent.value = student;
    resetPasswordForm.value = { password: '', password_confirmation: '' };
    showPassword.value = false;
    showResetPasswordModal.value = true;
}

async function resetPassword() {
    if (!selectedStudent.value) return;
    if (resetPasswordForm.value.password !== resetPasswordForm.value.password_confirmation) {
        toast.add({ title: 'Пароли не совпадают', color: 'error' });
        return;
    }
    if (resetPasswordForm.value.password.length < 8) {
        toast.add({ title: 'Пароль должен содержать минимум 8 символов', color: 'error' });
        return;
    }
    resettingPassword.value = true;
    try {
        const data = await post<{ message?: string }>(
            `/api/v1/mentor/students/${selectedStudent.value.id}/reset-password`,
            { password: resetPasswordForm.value.password }
        );
        toast.add({ title: data?.message || 'Пароль успешно сброшен', color: 'success' });
        showResetPasswordModal.value = false;
    } catch (error) {
        showApiError(error, 'Ошибка сброса пароля');
    } finally {
        resettingPassword.value = false;
    }
}

async function toggleBlock(student: StudentItem) {
    const action = student.is_activated ? 'заблокировать' : 'разблокировать';
    if (!confirm(`Вы уверены, что хотите ${action} студента ${student.name}?`)) return;
    try {
        const data = await post<{ message?: string }>(`/api/v1/mentor/students/${student.id}/toggle-block`);
        toast.add({ title: data?.message || `Студент успешно ${action}`, color: 'success' });
        await loadStudents();
    } catch (error) {
        showApiError(error, `Ошибка при попытке ${action} студента`);
    }
}

async function openEditStudent(id: number) {
    try {
        const data = await get<StudentDetail>(`/api/v1/mentor/students/${id}`);
        editingStudentId.value = id;
        editForm.value = {
            last_name: data.last_name || '',
            first_name: data.first_name || '',
            middle_name: data.middle_name || '',
            email: data.email || '',
            gender: data.gender ?? null,
            birthday: data.birthday || '',
            course: data.course ?? null,
            student_group: data.student_group || ''
        };
        showEditStudentModal.value = true;
    } catch (error) {
        showApiError(error, 'Ошибка загрузки данных студента');
    }
}

function normalizeSelectValue(v: unknown): number | null {
    if (v === null || v === undefined) return null;
    if (typeof v === 'number') return v;
    if (typeof v === 'object' && 'value' in v) return (v as { value: unknown }).value as number | null;
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
}

async function saveStudent() {
    if (!editingStudentId.value || !editForm.value) return;
    savingStudent.value = true;
    try {
        const body: Record<string, unknown> = { ...editForm.value };
        const g = normalizeSelectValue(body.gender);
        if (g === null || g === undefined) {
            delete body.gender;
        } else {
            body.gender = g;
        }
        await post(`/api/v1/mentor/students/${editingStudentId.value}`, body);
        toast.add({ title: 'Данные студента обновлены', color: 'success' });
        showEditStudentModal.value = false;
        await loadStudents();
    } catch (error) {
        showApiError(error, 'Ошибка сохранения данных студента');
    } finally {
        savingStudent.value = false;
    }
}

async function loadEducationLevels() {
    try {
        const data = await get<{ data: { id: number; name: string }[] }>('/api/v1/education-levels');
        educationLevels.value = Array.isArray(data) ? data : (data.data || []);
    } catch {
        educationLevels.value = [];
    }
}



async function loadDataForPage(urlOrPage?: string | number) {
    const pageNum =
        typeof urlOrPage === 'number' && Number.isFinite(urlOrPage)
            ? urlOrPage
            : (parseInt(String(urlOrPage ?? 1), 10) || 1);
    pagination.value.currentPage = pageNum;
    await loadStudents();
}

function buildUrl(pageNum?: number) {
    return String(pageNum ?? 1);
}

const { onPageChange, getInitialPage } = usePaginationRedirect(loadDataForPage, buildUrl);

onMounted(async () => {
    await Promise.all([
        loadGroups(),
        loadEducationLevels()
    ]);
    pagination.value.currentPage = getInitialPage();
    await loadStudents();
});
</script>
