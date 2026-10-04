<template>
    <div class="max-w-6xl space-y-8">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold">Новый пользователь</h1>
            <p class="text-sm text-muted">Создайте учётную запись и заполните профиль пользователя</p>
        </div>

        <UForm :state="form" class="space-y-6" @submit="register">
            <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                <UFormField label="Роль" name="role" required :error="fieldError('role')">
                    <USelect v-model="form.role" :items="roleOptions" placeholder="Выберите роль" class="w-full" />
                </UFormField>

                <UFormField label="Email" name="email" required class="lg:col-span-2" :error="fieldError('email')">
                    <UInput v-model="form.email" type="email" placeholder="user@example.ru" class="w-full" />
                </UFormField>

                <UFormField label="Пароль" name="password" required class="lg:col-span-2" :error="fieldError('password')">
                    <UInput v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Пароль" class="w-full">
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

                <UFormField label="Подтверждение пароля" name="password_confirmation" required class="lg:col-span-2" :error="fieldError('password_confirmation')">
                    <UInput v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Повторите пароль" class="w-full">
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

                <UFormField label="Фамилия" name="last_name" required :error="fieldError('last_name')">
                    <UInput v-model="form.last_name" placeholder="Фамилия" class="w-full" />
                </UFormField>

                <UFormField label="Имя" name="first_name" required :error="fieldError('first_name')">
                    <UInput v-model="form.first_name" placeholder="Имя" class="w-full" />
                </UFormField>

                <UFormField label="Отчество" name="middle_name">
                    <UInput v-model="form.middle_name" placeholder="Отчество" class="w-full" />
                </UFormField>

                <UFormField label="Пол" name="gender" required :error="fieldError('gender')">
                    <USelect v-model="form.gender" :items="genderOptions" placeholder="Выберите" class="w-full" />
                </UFormField>

                <UFormField label="Дата рождения" name="birthday" :error="fieldError('birthday')">
                    <UInput v-model="form.birthday" type="date" class="w-full" />
                </UFormField>

                <UFormField label="Регион" name="region_id" required class="col-span-2" :error="fieldError('region_id')">
                    <USelect v-model="form.region_id" :items="regionOptions" placeholder="Выберите регион" class="w-full max-w-md" />
                </UFormField>

                <UFormField label="Тип учреждения" name="institution_id" required :error="fieldError('institution_id')">
                    <USelect v-model="form.institution_id" :items="institutionOptions" placeholder="Выберите тип" class="w-full" />
                </UFormField>

                <UFormField label="Направление" name="planet_id" required class="col-span-2 w-2/3" :error="fieldError('planet_id')">
                    <USelect v-model="form.planet_id" :items="planetOptions" placeholder="Выберите направление" class="w-full" />
                </UFormField>

                <UFormField
                    v-if="form.institution_id === 4"
                    label="Наименование учреждения"
                    name="institution"
                    required
                    class="lg:col-span-2"
                    :error="fieldError('institution')"
                >
                    <UInput v-model="form.institution" placeholder="Полное название учреждения" class="w-full" />
                </UFormField>

                <UFormField label="Класс / курс" name="course" :error="fieldError('course')">
                    <UInput v-model="form.course" type="number" placeholder="Например, 1" class="w-full" />
                </UFormField>

                <UFormField label="Группа" name="student_group" :error="fieldError('student_group')">
                    <UInput v-model="form.student_group" placeholder="Например, К1И1(11)" class="w-full" />
                </UFormField>

                <UFormField label="Фото профиля" name="file" class="lg:col-span-2" :error="fieldError('file')">
                    <UInput
                        type="file"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        class="w-full"
                        @change="onFileChange"
                    />
                </UFormField>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <UButton variant="ghost" color="neutral" @click="$router.back()">Отмена</UButton>
                <UButton type="submit" color="primary" :loading="status === 'pending'">
                    Создать пользователя
                </UButton>
            </div>
        </UForm>
    </div>
</template>

<script setup>

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['admin'],
    layout: 'admin',
    title: 'Пользователи',
    description: 'Управляйте пользователями и их профилями',
    breadcrumb: [
        { label: 'Главная', to: '/admin' },
        { label: 'Пользователи', to: '/admin/users' },
        { label: 'Создание' }
    ]
});

const { get, post } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();
const status = ref('idle');
const apiUrl = '/api/v1/admin/member/store';
const clientErrors = ref({});
const serverErrors = ref({});
const showPassword = ref(false);
const selectedFile = ref(null);

const regions = ref([]);
const planets = ref([]);
const institutions = ref([]);

const form = reactive({
    last_name: '',
    first_name: '',
    middle_name: '',
    gender: '',
    birthday: '',
    region_id: 0,
    planet_id: 0,
    institution_id: 0,
    institution: '',
    course: '',
    student_group: '',
    role: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_terms_accepted: true
});

const roleOptions = [
    { label: 'Администратор', value: 'admin' },
    { label: 'Модератор', value: 'moderator' },
    { label: 'Учащийся', value: 'student' },
    { label: 'Наставник', value: 'mentor' }
];

// Select вместо radio — компактнее и единообразнее с остальными полями
const genderOptions = [
    { label: 'Мужской', value: '0' },
    { label: 'Женский', value: '1' }
];

const institutionOptions = computed(() => [
    { label: 'Выберите тип учреждения', value: 0 },
    ...institutions.value.map((inst) => ({ label: inst.name, value: inst.id }))
]);

const regionOptions = computed(() => [
    { label: 'Выберите регион', value: 0 },
    ...regions.value.map((region) => ({ label: region.name, value: region.id }))
]);

const planetOptions = computed(() => [
    { label: 'Выберите направление', value: 0 },
    ...planets.value.map((planet) => ({ label: planet.name, value: planet.id }))
]);

onMounted(() => {
    fetchRegions();
    fetchPlanets();
    fetchInstitutions();
});

watch(
    () => form.institution_id,
    (value) => {
        if (value !== 4) {
            form.institution = '';
            delete clientErrors.value.institution;
            delete serverErrors.value.institution;
        }
    }
);

watch(
    form,
    () => {
        for (const key of Object.keys(clientErrors.value)) {
            if (clientErrors.value[key]) delete clientErrors.value[key];
        }
        for (const key of Object.keys(serverErrors.value)) {
            if (serverErrors.value[key]) delete serverErrors.value[key];
        }
    },
    { deep: true }
);

function fetchRegions() {
    get('/api/v1/regions')
        .then((data) => { regions.value = data; })
        .catch((error) => { showApiError(error, 'Ошибка получения регионов'); });
}

function fetchPlanets() {
    get('/api/v1/planets')
        .then((data) => { planets.value = data; })
        .catch((error) => { showApiError(error, 'Ошибка получения направлений'); });
}

function fetchInstitutions() {
    get('/api/v1/institutions')
        .then((data) => { institutions.value = data; })
        .catch((error) => { showApiError(error, 'Ошибка получения типов учреждения'); });
}

async function register() {
    clientErrors.value = {};
    serverErrors.value = {};
    status.value = 'pending';

    if (!validateClientForm()) {
        status.value = 'idle';
        toast.add({ title: 'Проверьте корректность заполнения полей', color: 'warning' });
        return;
    }

    const formData = new FormData();
    formData.append('last_name', form.last_name);
    formData.append('first_name', form.first_name);
    formData.append('middle_name', form.middle_name);
    formData.append('gender', form.gender);
    formData.append('birthday', form.birthday);
    formData.append('region_id', String(form.region_id));
    formData.append('planet_id', String(form.planet_id));
    formData.append('institution_id', String(form.institution_id));
    formData.append('institution', form.institution_id === 4 ? form.institution : '');
    formData.append('course', form.course);
    formData.append('student_group', form.student_group);
    formData.append('role', form.role);
    formData.append('email', form.email);
    formData.append('password', form.password);
    formData.append('password_confirmation', form.password_confirmation);
    formData.append('is_terms_accepted', form.is_terms_accepted ? '1' : '0');
    if (selectedFile.value) {
        formData.append('file', selectedFile.value);
    }

    try {
        const data = await post(apiUrl, formData);
        status.value = 'success';
        toast.add({ title: data?.message ?? 'Пользователь успешно создан', color: 'success' });
        await navigateTo('/admin/users');
    } catch (error) {
        status.value = 'error';
        if (error?.statusCode === 422) {
            const backendErrors = error?.data?.errors ?? error?.response?._data?.errors;
            if (backendErrors && typeof backendErrors === 'object') {
                for (const [field, messages] of Object.entries(backendErrors)) {
                    const firstMessage = Array.isArray(messages) ? messages[0] : '';
                    if (typeof firstMessage === 'string' && firstMessage.length) {
                        serverErrors.value[field] = firstMessage;
                    }
                }
            }
            showApiError(error, 'Исправьте ошибки формы');
            return;
        }
        showApiError(error, 'Ошибка создания пользователя');
    } finally {
        status.value = 'idle';
    }
}

function fieldError(field) {
    return clientErrors.value[field] ?? serverErrors.value[field];
}

function validateClientForm() {
    const errors = {};
    if (!form.last_name.trim()) errors.last_name = 'Введите фамилию';
    if (!form.first_name.trim()) errors.first_name = 'Введите имя';
    if (!form.gender) errors.gender = 'Выберите пол';
    if (!form.role) errors.role = 'Выберите роль';
    if (!form.email.trim()) {
        errors.email = 'Введите email';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        errors.email = 'Некорректный формат email';
    }
    if (!form.password) {
        errors.password = 'Введите пароль';
    } else if (form.password.length < 8) {
        errors.password = 'Пароль должен содержать минимум 8 символов';
    }
    if (!form.password_confirmation) {
        errors.password_confirmation = 'Подтвердите пароль';
    } else if (form.password_confirmation !== form.password) {
        errors.password_confirmation = 'Пароли не совпадают';
    }
    if (!Number(form.region_id) || Number(form.region_id) < 1) errors.region_id = 'Выберите регион';
    if (!Number(form.planet_id) || Number(form.planet_id) < 1) errors.planet_id = 'Выберите направление';
    if (!Number(form.institution_id) || Number(form.institution_id) < 1) errors.institution_id = 'Выберите тип учреждения';
    if (Number(form.institution_id) === 4 && !form.institution.trim()) errors.institution = 'Укажите учреждение';
    if (selectedFile.value) {
        const allowedTypes = ['image/jpeg', 'image/png'];
        if (!allowedTypes.includes(selectedFile.value.type)) {
            errors.file = 'Допустимы только JPG/JPEG/PNG';
        } else if (selectedFile.value.size > 2 * 1024 * 1024) {
            errors.file = 'Размер файла не должен превышать 2MB';
        }
    }

    clientErrors.value = errors;
    return Object.keys(errors).length === 0;
}

function onFileChange(event) {
    const target = event?.target;
    const file = target instanceof HTMLInputElement ? target.files?.[0] : undefined;
    selectedFile.value = file ?? null;
    delete clientErrors.value.file;
    delete serverErrors.value.file;
}
</script>
