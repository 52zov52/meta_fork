import type { User } from '~/types'

/**
 * Global middleware для проверки активации аккаунта.
 * Работает ДО всех остальных middleware.
 *
 * Логика:
 * 1. Если не авторизован — пропускаем (sanctum:auth сам редиректит)
 * 2. Если на /auth/inactivate и активирован → редирект по роли
 * 3. Если на /auth/inactivate и не активирован → пропускаем
 * 4. Если страница с sanctum:auth и не активирован → редирект на /auth/inactivate
 */
export default defineNuxtRouteMiddleware((to) => {
    // Получаем данные аутентификации
    const { user } = useSanctumAuth();

    // Не авторизован — sanctum:auth сам редиректит на /auth/login
    if (!user.value) {
        return;
    }

    const userData = user.value as User

    // === Специальная логика для /auth/inactivate ===
    if (to.path === '/auth/inactivate') {
        // Если активирован → редирект туда, где был до деактивации
        if (userData.is_activated === 1) {
            const redirect = to.query.redirect as string;
            if (redirect) {
                return navigateTo(redirect);
            }
            // Если redirect нет → редирект по роли
            return navigateTo(useRoleRedirect().getPath(userData.role));
        }
        // Не активирован → оставляем на странице
        return;
    }

    // Проверяем, есть ли sanctum:auth в middleware страницы
    const pageMeta = to.meta.middleware;
    const hasAuthMiddleware = Array.isArray(pageMeta)
        ? pageMeta.includes('sanctum:auth')
        : pageMeta === 'sanctum:auth';

    // Если на странице нет sanctum:auth — пропускаем (публичная страница)
    if (!hasAuthMiddleware) {
        return;
    }

    // Если пользователь авторизован, но не активирован — редирект на /auth/inactivate
    if (userData.is_activated !== 1) {
        return navigateTo({
            path: '/auth/inactivate',
            query: { redirect: to.fullPath }
        });
    }
});
