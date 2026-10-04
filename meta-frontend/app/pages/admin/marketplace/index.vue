<template>
    <div class="flex flex-col space-y-4">
        <UTabs v-model="activeTab" :items="tabItems" :default-value="0" class="w-auto px-4" :ui="{ list: 'gap-1', trigger: 'px-3 py-1.5 text-sm' }">
            <template #content>
                <section v-if="activeTab === 0" class="flex flex-col space-y-4 mt-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Покупки студентов</h2>
                            <p class="text-sm text-gray-500">Очередь выдачи и история решений администратора.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <UButton
                                label="Ожидают выдачи"
                                size="xs"
                                :color="orderStatusFilter === 'pending' ? 'primary' : 'neutral'"
                                variant="solid"
                                @click="setOrderStatusFilter('pending')"
                            />
                            <UButton
                                label="Выданы"
                                size="xs"
                                :color="orderStatusFilter === 'issued' ? 'primary' : 'neutral'"
                                variant="solid"
                                @click="setOrderStatusFilter('issued')"
                            />
                            <UButton
                                label="Отменены"
                                size="xs"
                                :color="orderStatusFilter === 'cancelled' ? 'primary' : 'neutral'"
                                variant="solid"
                                @click="setOrderStatusFilter('cancelled')"
                            />
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <UInput
                            v-model="orderSearchTxt"
                            type="text"
                            icon="i-heroicons-magnifying-glass"
                            size="sm"
                            class="w-full"
                            placeholder="Поиск по ФИО, группе, email или товару"
                            @input="debouncedOrderSearch"
                            @keyup.enter.prevent="searchOrders"
                        />
                        <UButton
                            icon="i-heroicons-magnifying-glass"
                            size="xs"
                            color="primary"
                            variant="solid"
                            class="shrink-0"
                            @click="searchOrders()"
                        />
                    </div>

                    <div v-if="ordersStatus === 'pending'">
                        ...loading
                    </div>

                    <div v-if="ordersStatus === 'success'">
                        <div v-if="orders.length > 0" class="flex flex-col gap-2">
                            <div class="hidden lg:block">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="border-b border-default px-4 py-2 text-left font-semibold">Студент</th>
                                            <th class="border-b border-default px-4 py-2 text-left font-semibold">Товар</th>
                                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">Баллы</th>
                                            <th class="w-28 border-b border-default px-4 py-2 font-semibold">Статус</th>
                                            <th class="w-36 border-b border-default px-4 py-2 font-semibold">Дата покупки</th>
                                            <th class="w-36 border-b border-default px-4 py-2 font-semibold">Дата обработки</th>
                                            <th v-if="orderStatusFilter === 'pending'" class="w-48 border-b border-default px-4 py-2 font-semibold">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="order in orders" :key="order.id" class="hover:bg-zinc-950/2.5">
                                            <td class="border-b border-muted px-4 py-2">{{ formatStudent(order) }}</td>
                                            <td class="border-b border-muted px-4 py-2">{{ order.item_name_snapshot }}</td>
                                            <td class="w-20 border-b border-muted px-4 py-2 text-center">{{ order.item_score_snapshot }}</td>
                                            <td class="w-28 border-b border-muted px-4 py-2 text-center">
                                                <UBadge :color="orderStatusColor(order.status)" variant="solid">
                                                    {{ orderStatusLabel(order.status) }}
                                                </UBadge>
                                            </td>
                                            <td class="w-36 border-b border-muted px-4 py-2 text-center">{{ formatDate(order.created_at) }}</td>
                                            <td class="w-36 border-b border-muted px-4 py-2 text-center">{{ formatOrderDecision(order) }}</td>
                                            <td v-if="orderStatusFilter === 'pending'" class="w-48 border-b border-muted px-4 py-2 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <UButton
                                                        label="Выдано"
                                                        color="primary"
                                                        variant="outline"
                                                        size="xs"
                                                        :loading="isOrderUpdating(order.id, 'issued')"
                                                        @click="updateOrderStatus(order.id!, 'issued')"
                                                    />
                                                    <UButton
                                                        label="Отменить"
                                                        color="error"
                                                        variant="outline"
                                                        size="xs"
                                                        :loading="isOrderUpdating(order.id, 'cancelled')"
                                                        @click="updateOrderStatus(order.id!, 'cancelled')"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="lg:hidden grid gap-2">
                                <div v-for="order in orders" :key="order.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Студент</span>
                                        <span>{{ formatStudent(order) }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Товар</span>
                                        <span>{{ order.item_name_snapshot }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Баллы</span>
                                        <span>{{ order.item_score_snapshot }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Статус</span>
                                        <UBadge :color="orderStatusColor(order.status)" variant="solid" class="w-fit">
                                            {{ orderStatusLabel(order.status) }}
                                        </UBadge>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Дата покупки</span>
                                        <span>{{ formatDate(order.created_at) }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Дата обработки</span>
                                        <span>{{ formatOrderDecision(order) }}</span>
                                    </div>
                                    <div v-if="orderStatusFilter === 'pending'" class="grid grid-cols-2 gap-2">
                                        <UButton
                                            label="Выдано"
                                            color="primary"
                                            variant="outline"
                                            size="sm"
                                            block
                                            :loading="isOrderUpdating(order.id, 'issued')"
                                            @click="updateOrderStatus(order.id!, 'issued')"
                                        />
                                        <UButton
                                            label="Отменить"
                                            color="error"
                                            variant="outline"
                                            size="sm"
                                            block
                                            :loading="isOrderUpdating(order.id, 'cancelled')"
                                            @click="updateOrderStatus(order.id!, 'cancelled')"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <ListPagination :pagination="ordersPagination" @update:page="onOrdersPageChange" />
                            </div>
                        </div>

                        <UAlert
                            v-if="orders.length === 0"
                            icon="i-heroicons-information-circle"
                            color="info"
                            variant="solid"
                            title="Информация"
                            description="Покупки по выбранному фильтру не найдены"
                        />
                    </div>
                </section>

                <section v-if="activeTab === 1" class="flex flex-col space-y-4 mt-4">
                    <div class="flex justify-end space-x-2">
                        <UButton
                            to="/admin/marketplace/add"
                            label="Добавить новую запись"
                            icon="i-heroicons-plus-circle"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                        />

                        <UButton
                            :label="sortMethod == 'asc' ? 'От старых к новым' : 'От новых к старым'"
                            icon="i-heroicons-arrows-up-down"
                            size="xs"
                            color="primary"
                            square
                            variant="solid"
                            @click="sort()"
                        />
                    </div>

                    <div class="flex space-x-2">
                        <UInput
                            v-model="searchTxt"
                            type="text"
                            icon="i-heroicons-magnifying-glass"
                            size="sm"
                            class="w-full"
                            placeholder="Поиск позиций по наименованию"
                            @input="debouncedSearch"
                            @keyup.enter.prevent="search"
                        />
                        <UButton
                            icon="i-heroicons-magnifying-glass"
                            size="xs"
                            color="primary"
                            variant="solid"
                            class="shrink-0"
                            @click="search()"
                        />
                    </div>

                    <div v-if="status === 'pending'">
                        ...loading
                    </div>

                    <div v-if="status === 'success'">
                        <div v-if="marketplace.length != 0" class="flex flex-col gap-2">
                            <div class="hidden lg:block">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">Фото</th>
                                            <th class="w-16 border-b border-default px-4 py-2 font-semibold">№ п/п</th>
                                            <th class="w-auto border-b border-default px-4 py-2 text-left font-semibold">Наименование</th>
                                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">Стоимость</th>
                                            <th class="w-24 border-b border-default px-4 py-2 font-semibold">Статус</th>
                                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in marketplace" :key="index" class="hover:bg-zinc-950/2.5">
                                            <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                                <img v-if="item.photo_path" :src="productPhotoUrl(item.photo_path)" class="mx-auto h-12 w-12 rounded object-cover border border-default cursor-zoom-in" @click="openImagePreview(productPhotoUrl(item.photo_path), item.name)">
                                                <UIcon v-else name="i-heroicons-photo" class="mx-auto h-8 w-8 text-gray-300" />
                                            </td>
                                            <td class="w-16 border-b border-muted px-4 py-2 text-center">{{ (pagination.currentPage - 1) * pagination.perPage + index + 1 }}</td>
                                            <td class="w-auto border-b border-muted px-4 py-2">{{ item.name }}</td>
                                            <td class="w-20 border-b border-muted px-4 py-2 text-center">{{ item.score }}</td>
                                            <td class="w-24 border-b border-muted px-4 py-2 text-center">
                                                <UBadge v-if="Number(item.is_archive) === 0" color="primary" variant="solid">Активный</UBadge>
                                                <UBadge v-if="Number(item.is_archive) === 1" color="error" variant="solid">В архиве</UBadge>
                                            </td>
                                            <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <UButton
                                                        icon="i-heroicons-pencil-square"
                                                        color="neutral"
                                                        variant="outline"
                                                        size="sm"
                                                        square
                                                        class="text-orange-500"
                                                        @click="edit(item.id!)"
                                                    />
                                                    <UButton
                                                        icon="i-heroicons-trash"
                                                        color="neutral"
                                                        variant="outline"
                                                        size="sm"
                                                        square
                                                        class="text-red-500"
                                                        @click="destroy(item.id!)"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="lg:hidden grid gap-2">
                                <div v-for="(item, index) in marketplace" :key="index" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
                                <img v-if="item.photo_path" :src="productPhotoUrl(item.photo_path)" class="h-32 w-full rounded object-cover border border-default cursor-zoom-in" @click="openImagePreview(productPhotoUrl(item.photo_path), item.name)">
                                <div v-else class="flex h-32 w-full items-center justify-center rounded border border-default bg-gray-50">
                                    <UIcon name="i-heroicons-photo" class="h-10 w-10 text-gray-300" />
                                </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Наименование</span>
                                        <span>{{ item.name }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Стоимость (баллы)</span>
                                        <span>{{ item.score }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="font-semibold">Статус</span>
                                        <span v-if="Number(item.is_archive) === 0" class="text-green-400 font-semibold">Активный</span>
                                        <span v-if="Number(item.is_archive) === 1" class="text-red-400 font-semibold">В архиве</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <UButton
                                            icon="i-heroicons-pencil-square"
                                            color="neutral"
                                            variant="outline"
                                            size="sm"
                                            block
                                            class="text-orange-500 justify-center"
                                            @click="edit(item.id!)"
                                        />
                                        <UButton
                                            icon="i-heroicons-trash"
                                            color="neutral"
                                            variant="outline"
                                            size="sm"
                                            block
                                            class="text-red-500 justify-center"
                                            @click="destroy(item.id!)"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <ListPagination :pagination="pagination" @update:page="onPageChange" />
                            </div>
                        </div>

                        <UAlert
                            v-if="marketplace.length == 0"
                            icon="i-heroicons-information-circle"
                            color="info"
                            variant="solid"
                            title="Информация"
                            description="Список элементов пуст. Для добавления нового элемента нажмите на кнопку «Добавить новую запись»"
                        />
                    </div>
                </section>
            </template>
        </UTabs>

        <ImagePreviewModal
            v-model:open="showImagePreview"
            :src="previewImageSrc"
            :alt="previewImageAlt"
        />
    </div>
</template>

<script setup lang="ts">
import type { paths } from '~/types/api'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Маркетплейс',
  description: 'Управляйте бонусной программой и настройками акций',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Маркетплейс' }],
});

type OrderStatus = 'pending' | 'issued' | 'cancelled'

type AdminMarketplaceListResponse =
    paths['/api/v1/admin/marketplace/products']['get']['responses'][200]['content']['application/json']

type AdminMarketplaceListItem = NonNullable<AdminMarketplaceListResponse['data']>[number]

type AdminMarketplaceDestroyResponse =
    paths['/api/v1/admin/marketplace/products/{id}']['delete']['responses'][200]['content']['application/json']

type AdminMarketplaceOrdersResponse =
    paths['/api/v1/admin/marketplace/orders']['get']['responses'][200]['content']['application/json']

type AdminMarketplaceOrderItem = NonNullable<AdminMarketplaceOrdersResponse['data']>[number]

type AdminMarketplaceOrderUpdateResponse =
    paths['/api/v1/admin/marketplace/orders/{order_id}']['patch']['responses'][200]['content']['application/json']

onMounted(() => {
    loadOrders(buildOrdersUrl(1));
});

const { get, patch, delete: del } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast()
const config = useRuntimeConfig()

const activeTab = ref(0);
const tabItems = [
    { label: 'Покупки студентов', value: 0 },
    { label: 'Товары', value: 1 },
];

watch(activeTab, (value) => {
    if (value === 0 && ordersStatus.value === 'idle') {
        loadOrders(buildOrdersUrl(1));
    }
    if (value === 1 && status.value === 'idle') {
        loadData(apiUrl.value);
    }
});

const marketplace = ref<AdminMarketplaceListItem[]>([]);
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
const sortMethod = ref<'asc' | 'desc'>('asc');
const searchTxt = ref('');

const apiUrl = ref(buildMarketplaceUrl(1));

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    totalItems: 0,
});

const orders = ref<AdminMarketplaceOrderItem[]>([]);
const ordersStatus = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
const orderStatusFilter = ref<OrderStatus>('pending');
const orderSearchTxt = ref('');
const updatingOrder = ref<{ id: number; status: OrderStatus } | null>(null);

const ordersPagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    totalItems: 0,
});

const showImagePreview = ref(false);
const previewImageSrc = ref('');
const previewImageAlt = ref('');

function openImagePreview(src: string, alt?: string) {
    previewImageSrc.value = src || '';
    previewImageAlt.value = alt || '';
    showImagePreview.value = true;
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let orderSearchTimeout: ReturnType<typeof setTimeout> | null = null;

function getSearchQuery() {
    const query = String(searchTxt.value || '').trim();
    return query.length ? query : '';
}

function getOrderSearchQuery() {
    const query = String(orderSearchTxt.value || '').trim();
    return query.length ? query : '';
}

const debouncedSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        search();
    }, 500);
};

const debouncedOrderSearch = () => {
    if (orderSearchTimeout) {
        clearTimeout(orderSearchTimeout);
    }
    orderSearchTimeout = setTimeout(() => {
        searchOrders();
    }, 500);
};

function buildMarketplaceUrl(page = 1) {
    const params = new URLSearchParams({
        page: String(page),
        sort: 'id',
        direction: sortMethod.value,
    });

    const query = getSearchQuery();
    if (query) {
        params.set('search', query);
    }

    return `/api/v1/admin/marketplace/products?${params.toString()}`;
}

function buildOrdersUrl(page = 1) {
    const params = new URLSearchParams({
        page: String(page),
        status: orderStatusFilter.value,
        sort: 'created_at',
        direction: 'desc',
    });

    const query = getOrderSearchQuery();
    if (query) {
        params.set('search', query);
    }

    return `/api/v1/admin/marketplace/orders?${params.toString()}`;
}

function loadData(url: string) {
    status.value = 'pending';

    get<AdminMarketplaceListResponse>(url).then((data) => {
        marketplace.value = Array.isArray(data.data) ? data.data : [];

        pagination.value.currentPage = data.current_page ?? 1;
        pagination.value.lastPage = data.last_page ?? 1;
        pagination.value.perPage = data.per_page ?? pagination.value.perPage;
        pagination.value.totalItems = data.total ?? ((data.last_page ?? 1) * pagination.value.perPage);

        status.value = 'success';
    }).catch(error => {
        status.value = 'error';
        showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
    });
}

function loadOrders(url: string) {
    ordersStatus.value = 'pending';

    get<AdminMarketplaceOrdersResponse>(url).then((data) => {
        orders.value = Array.isArray(data.data) ? data.data : [];

        ordersPagination.value.currentPage = data.current_page ?? 1;
        ordersPagination.value.lastPage = data.last_page ?? 1;
        ordersPagination.value.perPage = data.per_page ?? ordersPagination.value.perPage;
        ordersPagination.value.totalItems = data.total ?? ((data.last_page ?? 1) * ordersPagination.value.perPage);

        ordersStatus.value = 'success';
    }).catch(error => {
        ordersStatus.value = 'error';
        showApiError(error, 'Произошла ошибка в процессе получения покупок. Пожалуйста, попробуйте позже.');
    });
}

function sort() {
    sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc';

    const url = buildMarketplaceUrl(1);
    apiUrl.value = url;
    pagination.value.currentPage = 1;
    loadData(url);
}

function search() {
    pagination.value.currentPage = 1;
    const url = buildMarketplaceUrl(1);
    apiUrl.value = url;
    loadData(url);
}

function searchOrders() {
    ordersPagination.value.currentPage = 1;
    loadOrders(buildOrdersUrl(1));
}

function setOrderStatusFilter(status: OrderStatus) {
    orderStatusFilter.value = status;
    ordersPagination.value.currentPage = 1;
    loadOrders(buildOrdersUrl(1));
}

function edit(id: number) {
    return navigateTo('/admin/marketplace/edit/' + id);
}

function destroy(id: number) {
    if (confirm('Вы уверены, что хотите удалить данную запись?')) {
        del<AdminMarketplaceDestroyResponse>('/api/v1/admin/marketplace/products/' + id).then((data) => {
            status.value = 'success';
            loadData(apiUrl.value);
            toast.add({ title: data.message });
        }).catch(error => {
            status.value = 'error';
            showApiError(error, 'Ошибка');
        });
    }
}

function updateOrderStatus(id: number, status: Exclude<OrderStatus, 'pending'>) {
    const confirmMessage = status === 'issued'
        ? 'Отметить покупку как выданную?'
        : 'Отменить покупку и вернуть баллы студенту?';

    if (!confirm(confirmMessage)) {
        return;
    }

    updatingOrder.value = { id, status };
    patch<AdminMarketplaceOrderUpdateResponse>('/api/v1/admin/marketplace/orders/' + id, { status }).then((data) => {
        toast.add({ title: data.message });
        loadOrders(buildOrdersUrl(ordersPagination.value.currentPage));
    }).catch(error => {
        showApiError(error, 'Ошибка');
    }).finally(() => {
        updatingOrder.value = null;
    });
}

function isOrderUpdating(id: number | undefined, status: OrderStatus) {
    return updatingOrder.value?.id === id && updatingOrder.value?.status === status;
}

function onPageChange(page: number) {
    const url = buildMarketplaceUrl(page);
    apiUrl.value = url;
    pagination.value.currentPage = page;
    loadData(url);
}

function onOrdersPageChange(page: number) {
    ordersPagination.value.currentPage = page;
    loadOrders(buildOrdersUrl(page));
}

function formatStudent(order: AdminMarketplaceOrderItem) {
    const name = order.student_full_name || order.student_email || `ID ${order.user_id}`;
    if (order.student_group) {
        return `${name} (${order.student_group})`;
    }
    return name;
}

function productPhotoUrl(photoPath: string) {
    return `${config.public.API_PATH_STORAGE}${photoPath}`;
}

function orderStatusLabel(status?: string) {
    if (status === 'issued') return 'Выдано';
    if (status === 'cancelled') return 'Отменено';
    return 'Ожидает';
}

function orderStatusColor(status?: string) {
    if (status === 'issued') return 'primary';
    if (status === 'cancelled') return 'error';
    return 'warning';
}

function formatOrderDecision(order: AdminMarketplaceOrderItem) {
    if (order.status === 'issued') return formatDate(order.issued_at);
    if (order.status === 'cancelled') return formatDate(order.cancelled_at);
    return '—';
}

function formatDate(date: string | undefined) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>
