import type { User } from '~/types';
import type { paths } from '~/types/api'

export type StudentActivityListMode = 'catalog' | 'my' | 'wishlist';

export type StudentActivityListOptions = {
    mode: StudentActivityListMode;
    /** URL for initial load. For catalog: (page?) => url with optional page. For my: () => initial users/.../activities url. */
    buildUrl: (page?: number) => string;
    /** Called after load in catalog mode so page can run filterActivities(). */
    onAfterLoad?: () => void;
};

export function useStudentActivityList(options: StudentActivityListOptions) {
    const { mode, buildUrl, onAfterLoad } = options;
    const { user } = useSanctumAuth();
    /** Auth user shape matches API: user_id, role, is_activated */
    const authUser = computed(() => user.value as User | null);
    const { get, post } = useApi();
    const { show: showApiError } = useApiError();
    const toast = useToast();

    const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
    const allActivities = ref<Record<string, unknown>[]>([]);
    const activities = ref<Record<string, unknown>[]>([]);
    const lastLoadedUrl = ref<string>('');
    const showActivityModal = ref(false);
    const activeActivity = ref<Record<string, unknown> | null>(null);
    const registerLoading = ref(false);

    type StudentActivityRegisterResponse =
        paths['/api/v1/student/activity/register']['post']['responses'][200]['content']['application/json']
    type StudentActivityUndoResponse =
        paths['/api/v1/student/activity/undo']['post']['responses'][200]['content']['application/json']

    /** Каталог / «мои» / избранное — один контракт пагинации Laravel. */
    type ActivityListPage = {
        data?: Record<string, unknown>[]
        current_page?: number
        last_page?: number
        total?: number
        per_page?: number
        prev_page_url?: string | null
        next_page_url?: string | null
    }

    type StudentActivityWishlistStoreResponse =
        paths['/api/v1/student/activity/wishlist/store']['post']['responses'][200]['content']['application/json']
    type StudentActivityWishlistDeleteResponse =
        paths['/api/v1/student/activity/wishlist/delete']['post']['responses'][200]['content']['application/json']

    /** Метаданные пагинации из ответа API (current_page, total и т.д.) — для отображения в ListPagination. */
    const pagination = ref({
        currentPage: 1 as number | null,
        lastPage: 1 as number | null,
        totalItems: 0,
        perPage: 20,
        prevPageUrl: null as string | null,
        nextPageUrl: null as string | null,
    });

    async function loadData(urlOrPage?: string | number) {
        status.value = 'pending';
        let url: string;
        if (typeof urlOrPage === 'number') {
            url = buildUrl(urlOrPage);
        } else if (typeof urlOrPage === 'string') {
            url = urlOrPage;
        } else {
            url = buildUrl(mode === 'catalog' ? 1 : undefined);
        }

        try {
            lastLoadedUrl.value = url;
            const data = await get<ActivityListPage>(url);
            const rows = data.data ?? [];

            if (mode === 'catalog') {
                allActivities.value = rows;
                onAfterLoad?.();
            } else {
                activities.value = rows;
            }

            pagination.value.currentPage = data.current_page ?? 1;
            pagination.value.lastPage = data.last_page ?? 1;
            pagination.value.totalItems = data.total ?? 0;
            pagination.value.perPage = data.per_page ?? 20;
            pagination.value.prevPageUrl = data.prev_page_url ?? null;
            pagination.value.nextPageUrl = data.next_page_url ?? null;

            status.value = 'success';
        } catch (error) {
            status.value = 'error';
            showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
        }
    }

    function getCardClass(list: { length: number }, index: number) {
        const total = list.length;
        const remainder = total % 3;
        if (remainder !== 0 && index >= total - remainder) {
            return 'w-full lg:flex-[1_1_calc(33.333%-0.34rem)]';
        }
        return 'w-full lg:w-[calc(33.333%-0.34rem)]';
    }

    function openActivity(item: Record<string, unknown>) {
        activeActivity.value = item;
        showActivityModal.value = true;
    }

    function closeActivity() {
        showActivityModal.value = false;
        activeActivity.value = null;
        registerLoading.value = false;
    }

    function updateWishlistState(activityId: number, isWishlist: 0 | 1) {
        const list = mode === 'catalog' ? allActivities.value : activities.value;
        const item = list.find((a) => Number(a.id) === Number(activityId));
        if (item) item.is_wishlist = isWishlist;
    }

    function updateRegisterState(activityId: number, isRegistered: 0 | 1) {
        const list = mode === 'catalog' ? allActivities.value : activities.value;
        const item = list.find((a) => Number(a.id) === Number(activityId));
        if (item) item.is_registered = isRegistered;
    }

    async function addToWishlist(activityId: number) {
        const form = new FormData();
        form.append('user_id', String(authUser.value?.user_id));
        form.append('activity_id', String(activityId));
        try {
            const data = await post<StudentActivityWishlistStoreResponse>(
                '/api/v1/student/activity/wishlist/store',
                form,
            );
            toast.add({ title: data.message });
            updateWishlistState(activityId, 1);
        } catch (error) {
            showApiError(error, 'Ошибка');
        }
    }

    async function deleteFromWishlist(activityId: number, _reload?: () => Promise<void>) {
        const form = new FormData();
        form.append('user_id', String(authUser.value?.user_id));
        form.append('activity_id', String(activityId));
        try {
            const data = await post<StudentActivityWishlistDeleteResponse>(
                '/api/v1/student/activity/wishlist/delete',
                form,
            );
            toast.add({ title: data.message });
            if (mode === 'wishlist') {
                activities.value = activities.value.filter((a) => Number(a.id) !== Number(activityId));
            } else {
                updateWishlistState(activityId, 0);
            }
        } catch (error) {
            showApiError(error, 'Ошибка');
        }
    }

    async function registerToActivity(activityId: number) {
        registerLoading.value = true;
        
        // Проверка: уже зарегистрирован
        const list = mode === 'catalog' ? allActivities.value : activities.value;
        const item = list.find((a) => Number(a.id) === Number(activityId));
        if (item?.is_registered === 1) {
            toast.add({ 
                title: 'Вы уже зарегистрированы на это мероприятие',
                color: 'warning'
            });
            registerLoading.value = false;
            return;
        }
        
        const form = new FormData();
        form.append('user_id', String(authUser.value?.user_id));
        form.append('activity_id', String(activityId));
        try {
            const data = await post<StudentActivityRegisterResponse>('/api/v1/student/activity/register', form);
            toast.add({ title: data.message });
            closeActivity();
            updateRegisterState(activityId, 1);
        } catch (error) {
            showApiError(error, 'Ошибка');
        } finally {
            registerLoading.value = false;
        }
    }

    async function undoRegisterToActivity(activityId: number) {
        registerLoading.value = true;
        const form = new FormData();
        form.append('user_id', String(authUser.value?.user_id));
        form.append('activity_id', String(activityId));
        try {
            const data = await post<StudentActivityUndoResponse>('/api/v1/student/activity/undo', form);
            toast.add({ title: data.message });
            closeActivity();
            if (mode === 'my') {
                activities.value = activities.value.filter((a) => Number(a.id) !== Number(activityId));
            } else {
                updateRegisterState(activityId, 0);
            }
        } catch (error) {
            showApiError(error, 'Ошибка');
        } finally {
            registerLoading.value = false;
        }
    }

    function reloadCurrent() {
        return lastLoadedUrl.value ? loadData(lastLoadedUrl.value) : Promise.resolve();
    }

    return {
        status,
        allActivities,
        activities,
        pagination,
        lastLoadedUrl,
        reloadCurrent,
        showActivityModal,
        activeActivity,
        registerLoading,
        loadData,
        getCardClass,
        openActivity,
        closeActivity,
        addToWishlist,
        deleteFromWishlist,
        registerToActivity,
        undoRegisterToActivity,
    };
}
