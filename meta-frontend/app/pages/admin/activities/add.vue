<template>
    <form @submit.prevent="store">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Наименование<span class="ml-1 text-red-500">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Максимум 255 символов.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <UInput v-model="activity.title" class="w-full" placeholder="Название мероприятия" />
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
                            class="w-full ksu-wrap-select"
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
                    />
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
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-default">

            <div class="flex justify-end">
                <UButton type="submit" color="success" :loading="status === 'pending'">
                    Добавить запись
                </UButton>
            </div>
        </form>
</template>

<script setup>
import { ruDateToApiDate } from '~/utils/ruDate'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Мероприятия',
  description: 'Управляйте мероприятиями и их настройками',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Мероприятия', to: '/admin/activities' }, { label: 'Создание' }],
});

const statusOptions = [
    { label: 'Черновик', value: 'draft' },
    { label: 'Запланировано', value: 'scheduled' },
    { label: 'Начато', value: 'started' },
    { label: 'Завершено', value: 'completed' },
    { label: 'Отменено', value: 'canceled' },
];

function fieldErrors(value) {
    if (value == null || value === '') return [];
    if (Array.isArray(value)) return value;
    return [String(value)];
}

function formatDateForApi(value) {
    return ruDateToApiDate(value);
}

const { user } = useSanctumAuth();
const { get, post, uploadFileApi } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();

const apiUrl = ref('/api/v1/admin/activity/store');
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
    is_dynamic_score: true,
    status: 'scheduled',
    planet_id: 0,
    academy_id: 0,
    category_id: 0,
    user_id: user.value.user_id,
    files: [],
    preview_index: null,
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

onMounted(() => {
    fetchPlanets();
    fetchAcademies();
    fetchCategory();
    fetchEducationLevels();
})

function fetchEducationLevels() {
    get('/api/v1/education-levels').then((data) => {
        educationLevels.value = data;
    }).catch(error => {
        showApiError(error, 'Ошибка получения уровней обучения');
    });
}

// Reset parameters when category changes
watch(() => activity.value.category_id, () => {
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
    // category 1, 2, 4 требуют activity_type перед расчётом
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
    // has_implementation: только Проектная (1) + internal
    if (categoryId == 1 && parameters.activity_type === 'internal') {
        params.has_implementation = !!parameters.has_implementation;
    }
    // has_places: внешние + nir_competition + cultural_external
    if (
        (categoryId == 1 && parameters.activity_type === 'external') ||
        (categoryId == 2 && parameters.activity_type === 'nir_competition') ||
        (categoryId == 4 && parameters.activity_type === 'cultural_external')
    ) {
        params.has_places = !!parameters.has_places;
    }
    // is_leader: только Общественная (3)
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
    } catch {
        previewData.value = [];
    } finally {
        previewLoading.value = false;
    }
}

const uploadFile = ref(null);
const materialFiles = ref([]);

function asIdList(val) {
    if (Array.isArray(val)) return val;
    if (val && typeof val === 'object') return Object.values(val);
    return [];
}

const defaultPlanetId = ref(0);

function setDefaultPlanet() {
    const list = asIdList(planets.value);

    const defaultPlanet = list.find((planet) =>
        planet.name?.trim() === 'ИУБиП 360'
    );

    if (defaultPlanet) {
        defaultPlanetId.value = defaultPlanet.id;

        if (!activity.value.planet_id) {
            activity.value.planet_id = defaultPlanet.id;
        }
    }
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
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ title: `Файл ${file.name} слишком большой. Максимальный размер 2MB.`, color: 'error' });
    uploadFile.value = null;
    materialFiles.value = [];
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        activity.value.files = [{
            file,
            preview: e.target.result
        }];
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
        setDefaultPlanet();
    }).catch(error => {
        showApiError(error, 'Ошибка получения планет');
    })
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

function store() {
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

    // Добавляем файл если он был выбран
    if (activity.value.files.length > 0) {
        submitData.append('file', activity.value.files[0].file);
    }

    post(apiUrl.value, submitData).then(async (data) => {
        status.value = 'success';
        toast.add({ title: data.message });

        const activityId = data.activity_id;
        if (activityId && materialFiles.value.length > 0) {
            await uploadMaterialFiles(activityId);
        }

        // Сбрасываем форму после успешного создания
        resetForm();

        // Перенаправляем на список мероприятий
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
    });
}

function resetForm() {
    activity.value = {
        title: "",
        description: "",
        date_start: "",
        date_end: "",
        score: 0,
        is_dynamic_score: true,
        status: 'scheduled',
        planet_id: defaultPlanetId.value || 0,
        academy_id: 0,
        category_id: 0,
        user_id: user.value.user_id,
        files: [],
        preview_index: null,
        parameters: {
            activity_type: null,
            has_places: false,
            has_implementation: false,
            is_leader: false,
            target_education_level_ids: []
        }
    };

    previewData.value = [];
    previewHasPlaces.value = false;
    uploadFile.value = null;

    // Очищаем ошибки
    errors.value = {};
}


function removeImage(_index) {
    activity.value.files = [];
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
}
</script>
