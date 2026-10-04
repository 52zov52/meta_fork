import type { paths } from '~/types/api'

const baseApiUrl = '/api/v1/public/student/portfolio-public'

type PortfolioPublicResponse =
    paths['/api/v1/public/student/portfolio-public/{user_id}']['get']['responses']['200']['content']['application/json']
type PortfolioItem = NonNullable<PortfolioPublicResponse['data']>[number]

type SortByValue = 'created_at' | 'title'
type SortOrderValue = 'asc' | 'desc'

export function usePortfolioList(userIdRef: MaybeRef<string | number | undefined>) {
    const { get } = useApi()
    const { show: showApiError } = useApiError()

    const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle')
    const portfolio = ref<PortfolioItem[]>([])
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        totalItems: 0,
        perPage: 20,
    });

    const sortBy = ref<SortByValue>('created_at');
    const sortOrder = ref<SortOrderValue>('desc');
    const month = ref<number | null>(null);
    const categoryId = ref<number | null>(null);
    const semesterNumber = ref<number | null>(null);
    const semesterYear = ref<number | null>(null);

    const hasActiveFilters = computed(() => (
        month.value !== null
        || categoryId.value !== null
        || semesterNumber.value !== null
        || semesterYear.value !== null
    ));

    const sortOptions: { value: SortByValue; label: string }[] = [
        { value: 'created_at', label: 'По дате' },
        { value: 'title', label: 'По названию' },
    ];

    const sortOrderOptions: { value: SortOrderValue; label: string }[] = [
        { value: 'desc', label: 'По убыванию' },
        { value: 'asc', label: 'По возрастанию' },
    ];

    const monthOptions = computed(() => [
        { value: null as number | null, label: 'Все месяцы' },
        { value: 1, label: 'Январь' },
        { value: 2, label: 'Февраль' },
        { value: 3, label: 'Март' },
        { value: 4, label: 'Апрель' },
        { value: 5, label: 'Май' },
        { value: 6, label: 'Июнь' },
        { value: 7, label: 'Июль' },
        { value: 8, label: 'Август' },
        { value: 9, label: 'Сентябрь' },
        { value: 10, label: 'Октябрь' },
        { value: 11, label: 'Ноябрь' },
        { value: 12, label: 'Декабрь' },
    ]);

    const semesterOptions = computed(() => [
        { value: null as number | null, label: 'Все' },
        { value: 1, label: 'Первый' },
        { value: 2, label: 'Второй' },
    ]);

    const categories = ref<{ id: number; name: string }[]>([]);
    const categoriesStatus = ref<'idle' | 'pending' | 'success' | 'error'>('idle');

    async function loadCategories() {
        if (categoriesStatus.value !== 'idle') return;
        categoriesStatus.value = 'pending';
        try {
            const data = await get<paths['/api/v1/categories']['get']['responses'][200]['content']['application/json']>('/api/v1/categories');
            categories.value = Array.isArray(data)
                ? data.filter((category): category is { id: number; name: string } => (
                    typeof category.id === 'number' && typeof category.name === 'string'
                ))
                : [];
            categoriesStatus.value = 'success';
        } catch {
            categories.value = [];
            categoriesStatus.value = 'error';
        }
    }

    const categoryOptions = computed(() => [
        { value: null as number | null, label: 'Все категории' },
        ...categories.value.map(c => ({ value: c.id, label: c.name })),
    ]);

    function buildUrl(page: number) {
        const userId = toValue(userIdRef);
        const params = new URLSearchParams();
        params.set('page', String(page));
        params.set('sort_by', sortBy.value);
        params.set('sort_order', sortOrder.value);

        if (month.value !== null) params.set('month', String(month.value));
        if (categoryId.value !== null) params.set('category_id', String(categoryId.value));
        if (semesterYear.value !== null) {
            params.set('semester_year', String(semesterYear.value));

            if (semesterNumber.value !== null) {
                params.set('semester_number', String(semesterNumber.value));
            }
        }

        return `${baseApiUrl}/${userId}?${params.toString()}`;
    }

    async function loadData(urlOrPage: number | string = 1) {
        status.value = 'pending';

        try {
            const userId = toValue(userIdRef);
            if (userId === undefined || userId === null || userId === '') {
                throw new Error('User ID not found');
            }

            const url = typeof urlOrPage === 'number' ? buildUrl(urlOrPage) : urlOrPage
            const response = await get<PortfolioPublicResponse>(url)

            portfolio.value = Array.isArray(response?.data) ? response.data : []

            pagination.value.currentPage = response?.current_page ?? 1;
            pagination.value.lastPage = response?.last_page ?? 1;
            pagination.value.totalItems = response?.total ?? 0;
            pagination.value.perPage = response?.per_page ?? 20;

            status.value = 'success';
        } catch (error) {
            status.value = 'error';
            showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
        }
    }

    function resetFilters() {
        month.value = null;
        categoryId.value = null;
        semesterNumber.value = null;
        semesterYear.value = null;
    }

    return {
        status,
        portfolio,
        pagination,
        sortBy,
        sortOptions,
        sortOrder,
        sortOrderOptions,
        month,
        monthOptions,
        categoryId,
        categoryOptions,
        categoriesStatus,
        loadCategories,
        semesterNumber,
        semesterOptions,
        semesterYear,
        hasActiveFilters,
        resetFilters,
        loadData,
        buildUrl,
    };
}
