<template>
    <NuxtLayout name="auth">
        <template #title>
            <h1 class="text-2xl font-semibold text-gray-900">Восстановление пароля</h1>
        </template>

        <template #description>
            <p class="text-sm text-gray-500">Введите свой адрес электронной почты, чтобы сбросить пароль</p>
        </template>

        <UForm :state="form" class="space-y-4" @submit="sendResetPasswordLink">
            <UFormField label="Адрес эл. почты" name="email" :error="errors.email?.[0]">
                <UInput
                    v-model="form.email"
                    type="email"
                    placeholder="email@example.ru"
                    class="w-full"
                />
            </UFormField>

            <UButton
                type="submit"
                color="primary"
                block
                :loading="status === 'pending'"
            >
                Отправить ссылку
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

const { post } = useApi()
const { show: showApiError } = useApiError();
const toast = useToast();

const status = ref('idle');

const form = ref({
    email: ""
});

const errors = ref({
    email: []
});

function sendResetPasswordLink() {
    status.value = 'pending';
    post('forgot-password', { ...form.value }).then((data) => {
        status.value = 'success';
        errors.value.email = [];
        toast.add({ title: data.message });
    }).catch(error => {
        status.value = 'error';
        if (error.statusCode === 422) {
            errors.value.email = error.response._data?.messages?.email || [];
        } else {
            errors.value.email = [];
            showApiError(error, 'Ошибка отправки запроса');
        }
    }).finally(() => {
        status.value = 'idle';
    });
}
</script>
