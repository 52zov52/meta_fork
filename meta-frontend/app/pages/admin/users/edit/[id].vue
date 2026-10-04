<template>
    <div>
        <div v-if="status === 'pending'" class="mt-4">
            ...loading
        </div>

        <div v-if="status === 'success'" class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-2">
            <div class="flex flex-col space-y-2 rounded-md shadow-md p-4">
                <h2 class="text-lg font-semibold">Персональная информация</h2>
                <div class="flex flex-col space-y-2">
                    <div class="flex flex-col">
                        <label class="font-semibold">Фамилия:</label>
                        <p class="text-sm">{{ profile.last_name }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Имя:</label>
                        <p class="text-sm">{{ profile.first_name }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Отчество (при наличии):</label>
                        <p class="text-sm">{{ profile.middle_name }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Пол:</label>
                        <p class="text-sm">{{ profile.gender }}</p>
                    </div><div class="flex flex-col">
                        <label class="font-semibold">Дата рождения:</label>
                        <p class="text-sm">{{ profile.birthday }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Регион:</label>
                        <p class="text-sm">{{ profile.region_name }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col space-y-2 rounded-md shadow-md p-4">
                <h2 class="text-lg font-semibold">Информация об учреждении</h2>
                <div class="flex flex-col space-y-2">
                    <div class="flex flex-col">
                        <label class="font-semibold">Учреждение:</label>
                        <p class="text-sm">{{ profile.institution_full }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Класс или курс (для студентов):</label>
                        <p class="text-sm">{{ profile.course }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Группа (для студентов):</label>
                        <p class="text-sm">{{ profile.student_group }}</p>
                    </div>
                    <div class="flex flex-col" v-if="profile.academy_name">
                        <label class="font-semibold">Академия:</label>
                        <p class="text-sm">{{ profile.academy_name }}</p>
                    </div>
                    <div class="flex flex-col" v-if="profile.education_level">
                        <label class="font-semibold">Уровень обучения:</label>
                        <p class="text-sm">{{ profile.education_level }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Направление:</label>
                        <p class="text-sm">{{ profile.planet_name }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col space-y-2 rounded-md shadow-md p-4">
                <h2 class="text-lg font-semibold">Информация об аккаунте</h2>
                <div class="flex flex-col space-y-2">
                    <div class="flex flex-col">
                        <label class="font-semibold">Роль:</label>
                        <p class="text-sm">{{ profile.role }}</p>
                    </div>
                    <div class="flex flex-col">
                        <label class="font-semibold">Email:</label>
                        <p class="text-sm">{{ profile.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Форма изменения пароля -->
            <div class="flex flex-col space-y-2 rounded-md shadow-md p-4">
                <h2 class="text-lg font-semibold">Изменение пароля</h2>
                <form class="flex flex-col space-y-3" @submit.prevent="updatePassword">
                    <div class="flex flex-col">
                        <label class="font-semibold text-sm mb-1">Новый пароль:</label>
                        <UInput
                            v-model="newPassword"
                            type="password"
                            placeholder="Введите новый пароль"
                            :disabled="isUpdatingPassword"
                            class="w-full"
                        />
                    </div>
                    <UButton
                        type="submit"
                        :loading="isUpdatingPassword"
                        :disabled="!newPassword || newPassword.length < 8"
                        color="primary"
                        size="sm"
                        class="w-fit"
                    >
                        {{ isUpdatingPassword ? 'Изменение...' : 'Изменить пароль' }}
                    </UButton>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Пользователи',
  description: 'Редактирование профиля пользователя',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Пользователи', to: '/admin/users' }, { label: 'Редактирование' }],
});

onMounted(() => {
    loadData(apiUrl.value)
});

const route = useRoute()
const { get, patch } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();
const status = ref('idle');

const profile = ref([]);
const newPassword = ref('');
const isUpdatingPassword = ref(false);

const apiUrl = ref('/api/v1/admin/member/show/' + route.params.id);

function loadData(url) {
    status.value = 'pending';

    get(url).then((data) => {
        profile.value = data;
        status.value = 'success';
    }).catch(error => {
        status.value = 'error';
        showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
    });
}

async function updatePassword() {
    if (!newPassword.value || newPassword.value.length < 6) {
        toast.add({
            title: 'Пароль должен содержать минимум 6 символов',
            color: 'error'
        });
        return;
    }

    isUpdatingPassword.value = true;

    try {
        await patch(`/api/v1/admin/member/update-password/${route.params.id}`, {
            password: newPassword.value,
            password_confirmation: newPassword.value
        });

        toast.add({
            title: 'Пароль успешно изменен',
            color: 'green'
        });

        newPassword.value = '';
    } catch (error) {
        if (error.statusCode === 422) {
            const errorData = error.response._data;
            console.log('Детали ошибки валидации:', errorData);

            if (errorData?.errors) {
                const errorMessages = Object.values(errorData.errors).flat();
                toast.add({
                    title: `Ошибка валидации: ${errorMessages.join(', ')}`,
                    color: 'error'
                });
            } else {
                toast.add({
                    title: errorData?.message || 'Ошибка валидации данных',
                    color: 'error'
                });
            }
        } else {
            toast.add({
                title: error.response._data?.message || 'Произошла ошибка при изменении пароля. Пожалуйста, попробуйте позже.',
                color: 'error'
            });
        }
    } finally {
        isUpdatingPassword.value = false;
    }
}
</script>
