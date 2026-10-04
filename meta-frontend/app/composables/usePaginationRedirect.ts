/**
 * Переиспользуемая логика пагинации: при смене страницы вызывается loadData(buildUrl(page)).
 * По умолчанию пишет page в query (?page=N), после F5 загружается та же страница.
 */
export function usePaginationRedirect(
    loadData: (urlOrPage?: string | number) => Promise<void>,
    buildUrl: (page?: number) => string,
    options?: { syncToQuery?: boolean },
) {
    const route = useRoute();
    const router = useRouter();
    const syncToQuery = options?.syncToQuery !== false;

    function onPageChange(page: number) {
        if (syncToQuery) {
            router.replace({ path: route.path, query: { ...route.query, page: String(page) } });
        }
        loadData(buildUrl(page));
    }

    function getInitialPage(): number {
        if (syncToQuery && route.query?.page) {
            const p = Number(route.query.page);
            return Number.isFinite(p) && p >= 1 ? p : 1;
        }
        return 1;
    }

    return { onPageChange, getInitialPage };
}
