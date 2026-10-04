<template>
    <form @submit.prevent="update">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Наименование<span class="ml-1 text-red-500">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Максимум 255 символов.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <UInput v-model="activity.title" class="w-full" placeholder="Название мероприятия или события" />
                    <ul v-for="(error, index) in fieldErrors(errors.title)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Описание<span class="ml-1 text-red-500">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Максимум 5000 символов.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <UTextarea v-model="activity.description" class="w-full" :rows="10" autoresize />
                    <ul v-for="(error, index) in fieldErrors(errors.description)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Дата старта<span class="ml-1 text-red-500">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <RuDateInput v-model="activity.date_start" />
                    <ul v-for="(error, index) in fieldErrors(errors.date_start)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Дата завершения<span class="ml-1 text-red-500">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <RuDateInput v-model="activity.date_end" />
                    <ul v-for="(error, index) in fieldErrors(errors.date_end)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Категория<span class="ml-1 text-red-500">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect
                        v-model="activity.category_id"
                        :items="categorySelectItems"
                        placeholder="Выберите категорию"
                        class="w-full"
                    />
                    <ul v-for="(error, index) in fieldErrors(errors.category_id)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Автоматический расчёт баллов (КСУ)</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Баллы будут рассчитываться автоматически на основе категории и уровня обучения участника.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USwitch v-model="activity.is_dynamic_score" label="Включить автоматический расчёт" />
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <!-- Legacy score field -->
            <section v-if="!activity.is_dynamic_score" class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Кол-во баллов</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Укажите количество баллов за участие в мероприятии.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <UInput v-model="activity.score" type="number" class="w-full" />
                    <ul v-for="(error, index) in fieldErrors(errors.score)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr v-if="!activity.is_dynamic_score" role="presentation" class="my-10 w-full border-t border-default">

            <!-- Dynamic score parameters -->
            <template v-if="activity.is_dynamic_score">
                <!-- Activity type -->
                <section v-if="activity.category_id == 1 || activity.category_id == 2 || activity.category_id == 4" class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Тип активности<span class="ml-1 text-red-500">*</span></h2>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <USelect
                            v-model="activity.parameters.activity_type"
                            :items="activityTypeOptions"
                            placeholder="Выберите тип активности"
                            class="w-full"
                        />
                        <ul v-for="(error, index) in fieldErrors(errors['parameters.activity_type'])" :key="index">
                            <li class="text-xs text-red-500">{{ error }}</li>
                        </ul>
                        <template v-if="activity.category_id == 2">
                            <p class="text-xs text-red-600 font-medium">Публикационная активность: уровень публикации обязательно минимум РИНЦ</p>
                        </template>
                        <template v-if="activity.category_id == 4">
                            <p class="text-xs text-gray-600 mt-1"><span class="font-medium">Внешний</span> — мероприятия вне вуза.</p>
                            <p class="text-xs text-gray-600"><span class="font-medium">Внутренний</span> — мероприятия внутри вуза.</p>
                            <p class="text-xs text-gray-600"><span class="font-medium">Просветительские</span> — диктанты, экскурсии в мемориалы, культурные события (5 баллов, без мест).</p>
                        </template>
                    </div>
                </section>
                <hr v-if="activity.category_id == 1 || activity.category_id == 2 || activity.category_id == 4" role="presentation" class="my-10 w-full border-t border-default">

                <!-- Project internal: has_implementation -->
                <section v-if="activity.category_id == 1 && activity.parameters.activity_type === 'internal'" class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Внедрение</h2>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <UCheckbox v-model="activity.parameters.has_implementation" label="С внедрением" />
                    </div>
                </section>
                <hr v-if="activity.category_id == 1 && activity.parameters.activity_type === 'internal'" role="presentation" class="my-10 w-full border-t border-default">

                <!-- Places checkbox -->
                <section v-if="(activity.category_id == 1 && activity.parameters.activity_type === 'external') || (activity.category_id == 2 && activity.parameters.activity_type === 'nir_competition') || (activity.category_id == 4 && activity.parameters.activity_type === 'cultural_external')" class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Призовые места</h2>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <UCheckbox v-model="activity.parameters.has_places" label="Есть призовые места" />
                    </div>
                </section>
                <hr v-if="(activity.category_id == 1 && activity.parameters.activity_type === 'external') || (activity.category_id == 2 && activity.parameters.activity_type === 'nir_competition') || (activity.category_id == 4 && activity.parameters.activity_type === 'cultural_external')" role="presentation" class="my-10 w-full border-t border-default">

                <!-- Social: is_leader -->
                <section v-if="activity.category_id == 3" class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Особая благодарность</h2>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <UCheckbox v-model="activity.parameters.is_leader" label="Особая благодарность (лидер движения)" />
                    </div>
                </section>
                <hr v-if="activity.category_id == 3" role="presentation" class="my-10 w-full border-t border-default">

                <!-- Target education level -->
                <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <h2 class="text-base/7 font-semibold sm:text-sm/6">Целевой уровень обучения</h2>
                        <p data-slot="text" class="text-base/6 sm:text-sm/6">Для какого уровня предназначено мероприятие. Можно выбрать 1 или 2 соседних уровня. Без выбора — баллы одинаковые для всех.</p>
                    </div>
                    <div class="flex flex-col space-y-1 justify-center">
                        <USelect
                            v-model="activity.parameters.target_education_level_ids"
                            :items="targetLevelItems"
                            placeholder="Все (равные баллы для всех)"
                            multiple
                            class="w-full"
                        />
                        <ul v-for="(error, index) in fieldErrors(errors['parameters.target_education_level_ids'])" :key="index">
                            <li class="text-xs text-red-500">{{ error }}</li>
                        </ul>
                    </div>
                </section>
                <hr role="presentation" class="my-10 w-full border-t border-default">

                <!-- Preview table -->
                <section v-if="previewData.length > 0" class="space-y-4">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Предварительный расчёт баллов</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-default">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border border-default px-3 py-2 text-left">Уровень обучения</th>
                                    <th v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">1 место</th>
                                    <th v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">2 место</th>
                                    <th v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">3 место</th>
                                    <th class="border border-default px-3 py-2 text-center">Участие</th>
                                    <th class="border border-default px-3 py-2 text-center">КСУ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in previewData" :key="row.education_level_id"
                                    :class="row.is_target ? 'bg-primary/10 font-medium' : 'hover:bg-gray-50'">
                                    <td class="border border-default px-3 py-2">
                                        {{ row.education_level_name }}
                                        <span v-if="row.is_target" class="ml-2 text-xs text-primary">(целевой)</span>
                                    </td>
                                    <td v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">{{ row.scores?.['1st_place'] ?? '—' }}</td>
                                    <td v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">{{ row.scores?.['2nd_place'] ?? '—' }}</td>
                                    <td v-if="previewHasPlaces" class="border border-default px-3 py-2 text-center">{{ row.scores?.['3rd_place'] ?? '—' }}</td>
                                    <td class="border border-default px-3 py-2 text-center">{{ row.scores?.participation ?? '—' }}</td>
                                    <td class="border border-default px-3 py-2 text-center text-xs text-gray-500">×{{ row.ksu?.toFixed(2) ?? '1.00' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <hr v-if="previewData.length > 0" role="presentation" class="my-10 w-full border-t border-default">
            </template>
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Статус<span class="ml-1 text-red-500">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect
                        v-model="activity.status"
                        :items="statusOptions"
                        placeholder="Выберите статус мероприятия"
                        class="w-full"
                    >
                        <template #leading="{ modelValue, ui }">
                            <UChip
                                v-if="modelValue"
                                v-bind="getStatusChip(modelValue)"
                                inset
                                standalone
                                :size="ui.itemLeadingChipSize()"
                                :class="ui.itemLeadingChip()"
                            />
                        </template>
                    </USelect>
                    <ul v-for="(error, index) in fieldErrors(errors.status)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Направление<span class="ml-1 text-red-500">*</span></h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect
                        v-model="activity.planet_id"
                        :items="planetSelectItems"
                        placeholder="Выберите направление"
                        class="w-full"
                    />
                    <ul v-for="(error, index) in fieldErrors(errors.planet_id)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Академия</h2>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USelect
                        v-model="activity.academy_id"
                        :items="academySelectItems"
                        placeholder="Выберите академию"
                        class="w-full"
                    />
                    <ul v-for="(error, index) in fieldErrors(errors.academy_id)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Изображение</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Загрузите изображение для карточки мероприятия. Максимальный размер файла 2 Мб.</p>
                </div>
                <div class="flex flex-col space-y-3 justify-center">
                    <UFileUpload
                        v-model="uploadFile"
                        accept="image/*"
                        :multiple="false"
                        :preview="false"
                        variant="area"
                        label="Выберите файл"
                        description="Изображение до 2 Мб"
                        :disabled="activity.files.length >= 1"
                        class="w-full"
                    />
                    <div v-if="activity.files.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Загруженное изображение:</p>
                        <div class="relative inline-block">
                            <img :src="activity.files[0].preview" class="w-32 h-32 object-cover rounded border border-default" >
                            <UButton
                                type="button"
                                color="error"
                                size="xs"
                                class="absolute top-1 right-1"
                                icon="i-heroicons-trash"
                                aria-label="Удалить изображение"
                                @click="removeImage(0)"
                            />
                        </div>
                    </div>
                    <div v-if="activity.photo_path" class="space-y-2">
                        <p class="text-sm font-medium">Текущее изображение:</p>
                        <div class="flex flex-wrap gap-2">
                            <div class="relative inline-block">
                                <img :src="config.public.API_PATH_STORAGE + activity.photo_path" class="w-20 h-20 object-cover rounded border border-default" >
                                <UBadge color="success" size="xs" class="absolute bottom-1 left-1">
                                    Текущее превью
                                </UBadge>
                            </div>
                        </div>
                    </div>
                    <ul v-for="(error, index) in fieldErrors(errors.file)" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Файлы мероприятия</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Загрузите файлы с материалами (pdf, docx, doc, zip). Максимальный размер файла 10 Мб.</p>
                </div>
                <div class="flex flex-col space-y-3 justify-center">
                    <UFileUpload
                        v-model="materialFiles"
                        accept=".pdf,.docx,.doc,.zip"
                        multiple
                        variant="area"
                        layout="list"
                        label="Выберите файлы"
                        description="Файлы до 10 Мб"
                        class="w-full"
                    />
                    <div v-if="existingFiles.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Загруженные файлы:</p>
                        <div class="flex flex-col gap-2">
                            <div v-for="file in existingFiles" :key="file.id" class="flex items-center gap-2 text-sm">
                                <UIcon name="i-heroicons-document" class="w-5 h-5 text-gray-500" />
                                <span class="truncate">{{ file.original_name }}</span>
                                <span class="text-gray-400">({{ file.formatted_size }})</span>
                                <UButton
                                    type="button"
                                    color="error"
                                    size="xs"
                                    variant="ghost"
                                    icon="i-heroicons-trash"
                                    aria-label="Удалить файл"
                                    @click="removeExistingFile(file.id)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <div class="flex justify-end">
                <UButton type="submit" color="success" :loading="status === 'pending'">
                    Сохранить изменения
                </UButton>
            </div>
        </form>
</template>

<script setup>
import { adminActivityStatusOptions, getAdminActivityStatusChip as getStatusChip } from '~/utils/adminActivityStatus'
import { apiDateToRuDate, ruDateToApiDate } from '~/utils/ruDate'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Мероприятия',
  description: 'Управляйте мероприятиями и их настройками',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Мероприятия', to: '/admin/activities' }, { label: 'Редактирование' }],
});

const statusOptions = adminActivityStatusOptions;

function fieldErrors(value) {
    if (value == null || value === '') return [];
    if (Array.isArray(value)) return value;
    return [String(value)];
}

function formatDateForInput(value) {
    return apiDateToRuDate(value);
}

function formatDateForApi(value) {
    return ruDateToApiDate(value);
}

onMounted(() => {
    loadData();
    loadActivityFiles();
    fetchPlanets();
    fetchAcademies();
    fetchCategory();
    fetchEducationLevels();
});

const route = useRoute();
const config = useRuntimeConfig();
const { user } = useSanctumAuth();
const { get, post, uploadFileApi, client } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();

const apiUrl = ref('/api/v1/admin/activity/edit/' + route.params.id);
const status = ref('idle');

const planets = ref([]);
const academies = ref([]);

const category = ref(null);

const activity = ref({
    title: "",
    description: "",
    date_start: "",
    date_end: "",
    score: 0,
    is_dynamic_score: false,
    status: undefined,
    planet_id: 0,
    academy_id: 0,
    category_id: 0,
    photo_path: null,
    user_id: user.value.user_id,
    files: [],
    preview_index: null,
    additional_images: [],
    parameters: {
        activity_type: null,
        has_places: false,
        has_implementation: false,
        is_leader: false,
        target_education_level_ids: []
    }
});

const educationLevels = ref([]);
const previewData = ref([]);
const previewHasPlaces = ref(false);
const previewLoading = ref(false);
let previewTimeout = null;

const activityTypeOptions = computed(() => {
    if (activity.value.category_id == 1) {
        return [
            { label: 'Внутреннее', value: 'internal' },
            { label: 'Внешнее', value: 'external' }
        ];
    }
    if (activity.value.category_id == 2) {
        return [
            { label: 'Публикация в сборнике РИНЦ', value: 'rinz_collection' },
            { label: 'Выступление на конференции РИНЦ с публикацией', value: 'conference_presentation' },
            { label: 'Публикация в журнале РИНЦ', value: 'rinz_journal' },
            { label: 'Публикация в сборнике ВАК', value: 'vak_collection' },
            { label: 'Хоздоговорная НИР', value: 'nir_contract' },
            { label: 'НИР в рамках конкурса', value: 'nir_competition' }
        ];
    }
    if (activity.value.category_id == 4) {
        return [
            { label: 'Внутреннее', value: 'cultural_internal' },
            { label: 'Внешнее', value: 'cultural_external' },
            { label: 'Просветительские', value: 'cultural_outreach' }
        ];
    }
    return [];
});

const educationLevelItems = computed(() => {
    return educationLevels.value.map((level) => ({
        label: level.name,
        value: level.id
    }));
});

const targetLevelItems = computed(() => {
    const selected = activity.value.parameters.target_education_level_ids || [];
    const sorted = [...educationLevels.value].sort((a, b) => (a.nuo_value ?? 0) - (b.nuo_value ?? 0));
    return sorted.map((level) => {
        let disabled = false;
        if (selected.length >= 2 && !selected.includes(level.id)) {
            disabled = true;
        } else if (selected.length === 1 && !selected.includes(level.id)) {
            const selectedNuo = educationLevels.value.find(l => l.id === selected[0])?.nuo_value;
            if (selectedNuo != null && Math.abs((level.nuo_value ?? 0) - selectedNuo) !== 1) {
                disabled = true;
            }
        }
        return { label: level.name, value: level.id, disabled };
    });
});

function fetchEducationLevels() {
    get('/api/v1/education-levels').then((data) => {
        educationLevels.value = data;
    }).catch(error => {
        showApiError(error, 'Ошибка получения уровней обучения');
    });
}

// Reset parameters when category changes (skip on initial load — oldVal 0/undefined/null)
watch(() => activity.value.category_id, (newVal, oldVal) => {
    if (oldVal === 0 || oldVal === undefined || oldVal === null) return;
    activity.value.parameters = {
        activity_type: null,
        has_places: false,
        has_implementation: false,
        is_leader: false,
        target_education_level_ids: []
    };
    previewData.value = [];
});

// Debounced preview calculation
watch([
    () => activity.value.is_dynamic_score,
    () => activity.value.category_id,
    () => activity.value.parameters.activity_type,
    () => activity.value.parameters.has_places,
    () => activity.value.parameters.has_implementation,
    () => activity.value.parameters.is_leader,
    () => activity.value.parameters.target_education_level_ids
], () => {
    if (!activity.value.is_dynamic_score) return;
    if (!activity.value.category_id) {
        previewData.value = [];
        return;
    }
    if ([1, 2, 4].includes(Number(activity.value.category_id)) && !activity.value.parameters.activity_type) {
        previewData.value = [];
        return;
    }

    if (previewTimeout) clearTimeout(previewTimeout);
    previewTimeout = setTimeout(() => {
        calculatePreview();
    }, 400);
}, { deep: true });

function buildKsuParams(categoryId, parameters) {
    const params = {};
    if (parameters.activity_type) {
        params.activity_type = parameters.activity_type;
    }
    if (categoryId == 1 && parameters.activity_type === 'internal') {
        params.has_implementation = !!parameters.has_implementation;
    }
    if (
        (categoryId == 1 && parameters.activity_type === 'external') ||
        (categoryId == 2 && parameters.activity_type === 'nir_competition') ||
        (categoryId == 4 && parameters.activity_type === 'cultural_external')
    ) {
        params.has_places = !!parameters.has_places;
    }
    if (categoryId == 3) {
        params.is_leader = !!parameters.is_leader;
    }
    return params;
}

function ensureNumberIds(ids) {
    return (ids || []).map(id => Number(id)).filter(id => !isNaN(id));
}

async function calculatePreview() {
    const params = buildKsuParams(activity.value.category_id, activity.value.parameters);
    const targetIds = ensureNumberIds(activity.value.parameters.target_education_level_ids);

    previewLoading.value = true;
    try {
        const data = await post('/api/v1/activities/calculate-preview', {
            category_id: activity.value.category_id,
            parameters: params,
            target_education_level_ids: targetIds
        });
        previewData.value = data.preview || [];
        previewHasPlaces.value = previewData.value.length > 0 && previewData.value.some(r => r.scores?.['1st_place'] !== undefined);
    } catch (error) {
        previewData.value = [];
    } finally {
        previewLoading.value = false;
    }
}

const uploadFile = ref(null);
const materialFiles = ref([]);
const existingFiles = ref([]);

// Activity files
const { getActivityFiles, uploadFile: uploadActivityFile, deleteFile: deleteActivityFile } = useAdminActivityFilesApi()
const activityFiles = ref([])
const activityFileUpload = ref(null)
const isUploadingFiles = ref(false)
const deletingFileId = ref(null)

function asIdList(val) {
    if (Array.isArray(val)) return val;
    if (val && typeof val === 'object') return Object.values(val);
    return [];
}

const planetSelectItems = computed(() => {
    const list = asIdList(planets.value);
    return [
        { label: 'Выберите направление', value: 0 },
        ...list.map((planet) => ({ label: planet.name, value: planet.id })),
    ];
});

const categorySelectItems = computed(() => {
    const list = asIdList(category.value);
    return [
        { label: 'Выберите категорию', value: 0 },
        ...list.map((cat) => ({ label: cat.name, value: cat.id })),
    ];
});

const academySelectItems = computed(() => {
    const list = asIdList(academies.value);
    return [
        { label: 'Для всех', value: 0 },
        ...list.map((academy) => ({ label: academy.name, value: academy.id })),
    ];
});

watch(uploadFile, (file) => {
    if (!file) {
        activity.value.files = [];
        activity.value.preview_index = null;
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ title: `Файл ${file.name} слишком большой. Максимальный размер 2MB.`, color: 'error' });
        uploadFile.value = null;
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        activity.value.files = [{
            file,
            preview: e.target.result
        }];
        activity.value.preview_index = 0;
    };
    reader.readAsDataURL(file);
});

const errors = ref({});

function setValidationErrors(errorData) {
    errors.value = {};
    if (!errorData) return [];
    Object.keys(errorData).forEach(key => {
        errors.value[key] = errorData[key];
    });
    return Object.values(errorData).flat().filter(Boolean);
}

function fetchPlanets() {
    get('/api/v1/planets').then((data) => {
        planets.value = data;
    }).catch(error => {
        showApiError(error, 'Ошибка получения планет');
    });
}

function fetchCategory() {
    get('/api/v1/categories').then((data) => {
        category.value = data;
    }).catch(error => {
        showApiError(error, 'Ошибка получения категорий');
    });
}

function fetchAcademies() {
    get('/api/v1/academies').then((data) => {
        academies.value = data;
    }).catch(error => {
        showApiError(error, 'Ошибка получения академий');
    });
}

function loadData() {
    get(apiUrl.value).then((data) => {
        activity.value.title = data.title;
        activity.value.description = data.description;
        activity.value.date_start = formatDateForInput(data.date_start);
        activity.value.date_end = formatDateForInput(data.date_end);
        activity.value.score = data.score;
        activity.value.is_dynamic_score = !!data.is_dynamic_score;
        activity.value.status = data.status;
        activity.value.planet_id = data.planet_id;
        activity.value.academy_id = data.academy_id ?? 0;
        activity.value.photo_path = data.photo_path;
        activity.value.category_id = data.category_id;

        // Load parameters if present
        if (data.parameters) {
            activity.value.parameters = {
                activity_type: data.parameters.activity_type ?? null,
                has_places: !!data.parameters.has_places,
                has_implementation: data.parameters.has_implementation ?? false,
                is_leader: !!data.parameters.is_leader,
                target_education_level_ids: Array.isArray(data.parameters.target_education_level_ids)
                    ? data.parameters.target_education_level_ids.map(Number)
                    : []
            };
        }

        existingFiles.value = data.files || [];

        // Дополнительные изображения не поддерживаются бэкендом
        activity.value.additional_images = [];

        // Инициализируем preview_index для нового файла
        activity.value.preview_index = null;
    }).catch(error => {
        showApiError(error, 'Произошла ошибка в процессе получения данных');
    });
}

function update() {
    status.value = 'pending';

    // Очищаем ошибки перед отправкой
    Object.keys(errors.value).forEach(key => {
        errors.value[key] = "";
    });

    const dateStart = formatDateForApi(activity.value.date_start);
    const dateEnd = formatDateForApi(activity.value.date_end);
    if (!dateStart) {
        errors.value.date_start = 'Введите корректную дату в формате дд.мм.гггг.';
        status.value = 'idle';
        return;
    }
    if (!dateEnd) {
        errors.value.date_end = 'Введите корректную дату в формате дд.мм.гггг.';
        status.value = 'idle';
        return;
    }

    // Создаем новый FormData для каждого запроса
    const submitData = new FormData();
    submitData.append('title', activity.value.title);
    submitData.append('description', activity.value.description);
    submitData.append('date_start', dateStart);
    submitData.append('date_end', dateEnd);
    submitData.append('status', activity.value.status ?? '');
    submitData.append('planet_id', activity.value.planet_id);
    if (activity.value.academy_id > 0) {
        submitData.append('academy_id', activity.value.academy_id);
    }
    submitData.append('user_id', activity.value.user_id);
    submitData.append('category_id', activity.value.category_id);
    submitData.append('is_dynamic_score', activity.value.is_dynamic_score ? '1' : '0');

    if (activity.value.is_dynamic_score) {
        const p = activity.value.parameters;
        const ksuParams = buildKsuParams(activity.value.category_id, p);
        if (ksuParams.activity_type) submitData.append('parameters[activity_type]', ksuParams.activity_type);
        if ('has_implementation' in ksuParams) submitData.append('parameters[has_implementation]', ksuParams.has_implementation ? '1' : '0');
        if ('has_places' in ksuParams) submitData.append('parameters[has_places]', ksuParams.has_places ? '1' : '0');
        if ('is_leader' in ksuParams) submitData.append('parameters[is_leader]', ksuParams.is_leader ? '1' : '0');
        for (const id of ensureNumberIds(p.target_education_level_ids)) {
            submitData.append('parameters[target_education_level_ids][]', String(id));
        }
    } else {
        submitData.append('score', activity.value.score);
    }

    // Отправляем изображение если оно было загружено
    if (activity.value.files.length > 0) {
        submitData.append('file', activity.value.files[0].file);
    }

    post('/api/v1/admin/activity/update/' + route.params.id, submitData).then(async (data) => {
        status.value = 'success';
        toast.add({ title: data.message });

        if (materialFiles.value.length > 0) {
            await uploadMaterialFiles(Number(route.params.id));
        }

        // Перенаправляем на список мероприятий после успешного обновления
        setTimeout(() => {
            navigateTo('/admin/activities');
        }, 1500);
    }).catch(error => {
        status.value = 'error';

        if(error.statusCode == 422) {
            const errorData = error.response._data?.errors || error.response._data?.messages;
            setValidationErrors(errorData);
        }

        showApiError(error, 'Произошла ошибка');
    }).finally(() => {
        status.value = 'idle';
    })
}


function removeImage(_index) {
    activity.value.files = [];
    activity.value.preview_index = null;
    uploadFile.value = null;
}

async function uploadMaterialFiles(activityId) {
    for (const file of materialFiles.value) {
        try {
            const formData = new FormData();
            formData.append('file', file);
            await uploadFileApi(`/api/v1/admin/activities/${activityId}/files`, formData);
        } catch (error) {
            showApiError(error, `Ошибка загрузки файла ${file.name}`);
        }
    }
    materialFiles.value = [];
    await loadExistingFiles(activityId);
}

async function loadExistingFiles(activityId) {
    try {
        const data = await get(`/api/v1/admin/activity/edit/${activityId}`);
        existingFiles.value = data.files || [];
    } catch (error) {
        showApiError(error, 'Ошибка получения файлов мероприятия');
    }
}

async function removeExistingFile(fileId) {
    try {
        await client(`/api/v1/admin/activities/files/${fileId}`, { method: 'DELETE' });
        existingFiles.value = existingFiles.value.filter(f => f.id !== fileId);
        toast.add({ title: 'Файл успешно удалён!' });
    } catch (error) {
        showApiError(error, 'Ошибка удаления файла');
    }
}

async function handleActivityFileUpload(files) {
    if (!files || files.length === 0) return

    isUploadingFiles.value = true
    try {
        for (const file of Array.isArray(files) ? files : [files]) {
            if (file.size > 10 * 1024 * 1024) {
                toast.add({ title: `Файл ${file.name} слишком большой. Максимальный размер 10 МБ.`, color: 'error' })
                continue
            }
            const uploadedFile = await uploadActivityFile(route.params.id, file)
            if (uploadedFile) {
                activityFiles.value.push(uploadedFile)
            }
        }
    } finally {
        isUploadingFiles.value = false
        activityFileUpload.value = null
    }
}

async function handleDeleteFile(fileId) {
    deletingFileId.value = fileId
    try {
        const success = await deleteActivityFile(fileId)
        if (success) {
            activityFiles.value = activityFiles.value.filter(f => f.id !== fileId)
        }
    } finally {
        deletingFileId.value = null
    }
}

function getFileIcon(mimeType) {
    if (!mimeType) return 'i-heroicons-document'
    if (mimeType.startsWith('image/')) return 'i-heroicons-photo'
    if (mimeType === 'application/pdf') return 'i-heroicons-document-text'
    if (mimeType.includes('word') || mimeType.includes('document')) return 'i-heroicons-document-text'
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return 'i-heroicons-table-cells'
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'i-heroicons-presentation-chart-line'
    if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('archive')) return 'i-heroicons-archive-box'
    if (mimeType.includes('video')) return 'i-heroicons-video-camera'
    if (mimeType.includes('audio')) return 'i-heroicons-musical-note'
    return 'i-heroicons-document'
}

async function loadActivityFiles() {
    try {
        activityFiles.value = await getActivityFiles(route.params.id)
    } catch {
        activityFiles.value = []
    }
}
</script>
