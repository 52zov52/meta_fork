/**
 * Единая функция для получения пути редиректа по роли пользователя.
 * Используется во всех middleware для согласованности.
 */
export function useRoleRedirect() {
    const redirects: Record<string, string> = {
        admin: '/admin',
        moderator: '/moderator',
        mentor: '/mentor',
        student: '/student',
        employer: '/employer',
    };

    function getPath(role: string | null | undefined): string {
        // Нет роли → редирект на логин
        if (!role) return '/auth/login';

        // Роль есть, но нет в маппинге
        const redirect = redirects[role];

        if (!redirect) {
            throw new Error(`Неизвестная роль: "${role}".`);
        }

        return redirect;
    }

    function getAllPaths(): string[] {
        return Object.values(redirects);
    }

    function hasValidRole(role: string | null | undefined): boolean {
        if (!role) return false;
        return role in redirects;
    }

    return {
        getPath,
        getAllPaths,
        hasValidRole,
    };
}
