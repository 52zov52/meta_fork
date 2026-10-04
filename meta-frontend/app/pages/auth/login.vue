<template>
    <NuxtLayout name="auth">
        <template #title>
            <h1 class="text-2xl font-semibold text-gray-900">Вход в систему</h1>
        </template>

        <template #description>
            <p class="text-sm text-gray-500">Введите свой адрес электронной почты и пароль ниже, чтобы войти в систему</p>
        </template>

        <UForm :state="form" class="space-y-4" @submit="handleLogin">
            <DevOnly>
                <UFormField v-if="devUser" label="Dev user" name="dev_user">
                    <USelect v-model="devUser" :items="devUserOptions" class="w-full" />
                </UFormField>
            </DevOnly>

            <UFormField label="Адрес эл. почты" name="email">
                <UInput
                    v-model="form.email"
                    type="email"
                    placeholder="email@example.ru"
                    class="w-full"
                />
            </UFormField>

            <UFormField label="Пароль" name="password">
                <UInput
                    v-model="form.password"
                    type="password"
                    placeholder="Пароль"
                    class="w-full"
                />
            </UFormField>

            <div class="flex items-center justify-between">
                <UCheckbox v-model="remember" name="remember" label="Запомнить меня" />
                <NuxtLink
                    to="/auth/forgot-password"
                    class="text-sm text-primary hover:underline"
                >
                    Забыли пароль?
                </NuxtLink>
            </div>

            <UButton
                type="submit"
                color="primary"
                block
                :loading="loading"
            >
                Войти
            </UButton>
        </UForm>
    </NuxtLayout>
</template>

<script setup>
definePageMeta({
    middleware: ['guest-redirect'],
    layout: false
});

const { login, user } = useSanctumAuth();
const { show: showApiError } = useApiError();
const route = useRoute();

const form = ref({
    email: '',
    password: ''
});

const devUser = ref('');
const devUserOptions = ref([]);

const remember = ref(false);
const loading = ref(false);

if (import.meta.dev) {
    const isDirect = route.query.direct === '1';

    if (!isDirect) {
        devUser.value = 'student';
        devUserOptions.value = [
            { label: 'Admin (admin@i.ru)', value: 'admin' },
            { label: 'Student (student@i.ru)', value: 'student' },
            { label: 'Mentor (mentor@i.ru)', value: 'mentor' },
            { label: 'Moderator (moderator@i.ru)', value: 'moderator' }
        ];

        const devEmails = {
            admin: 'admin@i.ru',
            student: 'student@i.ru',
            mentor: 'mentor@i.ru',
            moderator: 'moderator@i.ru'
        };

        watch(
            devUser,
            (role) => {
                if (!role) return;
                form.value.email = devEmails[role] || devEmails.student;
                form.value.password = 'password';
            },
            { immediate: true }
        );
    }
}

async function handleLogin() {
    loading.value = true;

        try {
            await login({ ...form.value, remember: remember.value });

        const redirect = route.query.redirect;
        if (redirect) {
            return navigateTo(redirect);
        }

        return navigateTo(useRoleRedirect().getPath(user.value.role));
    } catch (e) {
        loading.value = false;
        showApiError(e, 'Ошибка входа');
    }
}
</script>
