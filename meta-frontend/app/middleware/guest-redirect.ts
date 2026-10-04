import type { User } from '~/types'

/**
 * Middleware для страниц только для гостей (login, register, forgot-password, reset-password).
 * Авторизованных пользователей редиректит на их ролевую страницу.
 * Неавторизованных пропускает.
 *
 * Примечание: Проверка активации в 01.activation.global.ts
 */
export default defineNuxtRouteMiddleware((to) => {
    const { isAuthenticated, user } = useSanctumAuth();

    // Если пользователь не авторизован — пропускаем на гостевую страницу
    if (!isAuthenticated.value || !user.value) {
        return;
    }

    const userData = user.value as User

    // Приоритет: ?redirect= из query, иначе — по роли
    const redirect = to.query.redirect as string | undefined;
    if (redirect) {
        return navigateTo(redirect);
    }

    // Авторизован — редиректим на его ролевую страницу
    const { getPath } = useRoleRedirect();
    return navigateTo(getPath(userData.role));
});
