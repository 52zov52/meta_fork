<template>
    <div class="max-w-6xl space-y-8">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold">Новый студент</h1>
            <p class="text-sm text-muted">Создайте учётную запись и заполните профиль студента</p>
        </div>

        <UForm :state="form" class="space-y-6" @submit="register">
            <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                <UFormField label="Email" name="email" required class="lg:col-span-2" :error="fieldError('email')">
                    <UInput v-model="form.email" type="email" placeholder="student@example.ru" class="w-full" />
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

                <UFormField label="Класс / курс" name="course" :error="fieldError('course')">
                    <UInput v-model="form.course" type="number" placeholder="Например, 1" class="w-full" />
                </UFormField>

                <UFormField label="Группа" name="student_group" required :error="fieldError('student_group')">
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
                    Создать студента
                </UButton>
            </div>
        </UForm>
    </div>
</template>

<script setup lang="ts">

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['mentor'],
    layout: 'mentor',
    title: 'Пользователи',
    description: 'Добавление нового студента',
    breadcrumb: [
        { label: 'Главная', to: '/mentor' },
        { label: 'Пользователи', to: '/mentor/users' },
        { label: 'Создание' }
    ]
});

const { post } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
const apiUrl = '/api/v1/mentor/students';
const clientErrors = ref<Record<string, string>>({});
const serverErrors = ref<Record<string, string>>({});
const showPassword = ref(false);
const selectedFile = ref<File | null>(null);

const form = reactive({
    last_name: '',
    first_name: '',
    middle_name: '',
    gender: '',
    birthday: '',
    course: '',
    student_group: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_terms_accepted: true
});

const genderOptions = [
    { label: 'Мужской', value: '0' },
    { label: 'Женский', value: '1' }
];

watch(
    form,
    () => {
        clientErrors.value = {};
        serverErrors.value = {};
    },
    { deep: true }
);

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
    formData.append('last_name', form.last_name.trim());
    formData.append('first_name', form.first_name.trim());
    formData.append('middle_name', form.middle_name.trim());
    formData.append('gender', String(form.gender));
    if (form.birthday) formData.append('birthday', form.birthday);
    if (form.course !== null && form.course !== '') {
        formData.append('course', String(form.course));
    }
    formData.append('student_group', form.student_group.trim());
    formData.append('email', form.email.trim());
    formData.append('password', form.password);
    formData.append('password_confirmation', form.password_confirmation);
    formData.append('is_terms_accepted', '1');
    if (selectedFile.value) {
        formData.append('file', selectedFile.value);
    }

    try {
        const data = await post<{ message?: string }>(apiUrl, formData);
        status.value = 'success';
        toast.add({ title: data?.message ?? 'Студент успешно создан', color: 'success' });
        await navigateTo('/mentor/users');
    } catch (error) {
        const err = error as { 
            statusCode?: number; 
            data?: { errors?: Record<string, string[] | string> }; 
            response?: { _data?: { errors?: Record<string, string[] | string> } } 
        };
        status.value = 'error';
        if (err?.statusCode === 422) {
            const backendErrors = err?.data?.errors ?? err?.response?._data?.errors;
            if (backendErrors && typeof backendErrors === 'object') {
                for (const [field, messages] of Object.entries(backendErrors)) {
                    const firstMessage = Array.isArray(messages) ? messages[0] : messages;
                    if (typeof firstMessage === 'string' && firstMessage.length) {
                        serverErrors.value[field] = firstMessage;
                    }
                }
            }
            showApiError(error, 'Исправьте ошибки формы');
            return;
        }
        showApiError(error, 'Ошибка создания студента');
    } finally {
        status.value = 'idle';
    }
}

function fieldError(field: string) {
    return clientErrors.value[field] ?? serverErrors.value[field];
}

function validateClientForm() {
    const errors: Record<string, string> = {};
    if (!form.last_name.trim()) errors.last_name = 'Введите фамилию';
    if (!form.first_name.trim()) errors.first_name = 'Введите имя';
    if (form.gender === null || form.gender === undefined || form.gender === '') {
        errors.gender = 'Выберите пол';
    }
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
    
    if (!form.student_group.trim()) {
        errors.student_group = 'Введите группу';
    } else if (!/^[\u0400-\u04FF\d\s().,-]+$/.test(form.student_group.trim())) {
        errors.student_group = 'Группа: только кириллица, цифры и символы ( ) . , - пробел; латиница не допускается.';
    }

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

function onFileChange(event: Event) {
    const target = event?.target;
    const file = target instanceof HTMLInputElement ? target.files?.[0] : undefined;
    selectedFile.value = file ?? null;
    delete clientErrors.value.file;
    delete serverErrors.value.file;
}
</script>
