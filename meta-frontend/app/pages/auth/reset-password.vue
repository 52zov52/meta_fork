<template>
    <NuxtLayout name="auth">
        <template #title>
            <h1 class="text-2xl font-semibold text-gray-900">Восстановление пароля</h1>
        </template>

        <template #description>
            <p class="text-sm text-gray-500">Введите новый пароль для завершения процесса сброса</p>
        </template>

        <UForm :state="form" class="space-y-4" @submit="resetPassword">
            <UFormField label="Адрес эл. почты" name="email">
                <UInput v-model="form.email" type="email" disabled />
            </UFormField>

            <UFormField label="Новый пароль" name="password" required :error="errors.password?.[0]">
                <UInput v-model="form.password" type="password" placeholder="Пароль" />
            </UFormField>

            <UFormField label="Подтверждение пароля" name="password_confirmation" required>
                <UInput v-model="form.password_confirmation" type="password" placeholder="Подтверждение пароля" />
            </UFormField>

            <UButton
                type="submit"
                color="primary"
                block
                :loading="status === 'pending'"
            >
                Сбросить пароль
            </UButton>
        </UForm>

        <div class="text-center text-sm">
            <NuxtLink to="/auth/login" class="text-primary hover:underline">Назад ко входу</NuxtLink>
        </div>
    </NuxtLayout>
</template>

<script setup>
definePageMeta({
    middleware: ['guest-redirect'],
    layout: false
});

const route = useRoute();
const { post } = useApi()
const { show: showApiError } = useApiError();
const toast = useToast();

const status = ref('idle');

const form = ref({
    token: route.query.token || "",
    email: route.query.email || "",
    password: "",
    password_confirmation: ""
});

const errors = ref({
    email: [],
    password: []
});

function resetPassword() {
    status.value = 'pending';
    post('reset-password', { ...form.value }).then((data) => {
        status.value = 'success';
        errors.value.email = [];
        errors.value.password = [];
        toast.add({ title: data.message });
    }).catch(error => {
        status.value = 'error';
        if (error.statusCode === 422) {
            errors.value.email = error.response._data?.messages?.email || [];
            errors.value.password = error.response._data?.messages?.password || [];
        } else {
            errors.value.email = [];
            errors.value.password = [];
            showApiError(error, 'Ошибка сброса пароля');
        }
    }).finally(() => {
        status.value = 'idle';
    });
}
</script>
