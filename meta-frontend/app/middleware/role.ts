import type { User } from '~/types'

/**
 * Middleware для проверки роли пользователя.
 * Работает ПОСЛЕ проверки активации (01.activation.global.ts).
 *
 * Использование в страницах:
 * definePageMeta({
 *     middleware: ['sanctum:auth', 'role'],
 *     roles: ['admin', 'mentor'] // допустимые роли
 * });
 */
export default defineNuxtRouteMiddleware((to) => {
    const { user } = useSanctumAuth();

    // Получаем разрешённые роли из meta страницы
    const allowedRoles = to.meta.roles as string[] | undefined;

    // Если роли не указаны — пропускаем все авторизованные
    if (!allowedRoles || allowedRoles.length === 0) {
        return;
    }

    const userData = user.value as User | undefined
    const userRole = userData?.role;

    // Если роль пользователя есть в списке разрешённых — пропускаем
    if (userRole && allowedRoles.includes(userRole)) {
        return;
    }

    // Роль не подходит — показываем toast и редиректим по роли пользователя
    const toast = useToast();
    const requiredRolesText = allowedRoles.length === 1
        ? `«${allowedRoles[0]}»`
        : `одна из: ${allowedRoles.map(r => `«${r}»`).join(', ')}`;

    toast.add({
        title: `Доступ запрещён: требуется роль ${requiredRolesText}`,
        color: 'error',
    });

    // Редирект на страницу по роли пользователя
    const { getPath } = useRoleRedirect();
    return navigateTo(getPath(userRole));
});
