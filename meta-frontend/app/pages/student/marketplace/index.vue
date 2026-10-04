<template>
    <NuxtLayout name="student">
        <div class="px-4 lg:px-32 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 bg-primary/5 border border-primary/10 rounded-lg px-3 py-2">
                    <UIcon name="i-heroicons-wallet" class="w-5 h-5 text-primary" />
                    <div class="text-sm">
                        <span class="text-gray-500">Доступно:</span>
                        <span v-if="scoreLoading" class="animate-pulse text-gray-800">...</span>
                        <span v-else class="font-semibold text-primary">{{ availableScore || 0 }} баллов</span>
                    </div>
                </div>
                <div v-if="status === 'success'" class="flex items-center gap-2 bg-gray-50 border border-default rounded-lg px-3 py-2 text-sm text-gray-600">
                    <UIcon name="i-heroicons-shopping-bag" class="w-5 h-5 text-gray-400" />
                    <span>Товаров: {{ products.length }}</span>
                </div>
                <div v-if="pendingOrdersCount > 0" class="flex items-center gap-2 bg-gray-50 border border-default rounded-lg px-3 py-2 text-sm text-gray-600">
                    <UIcon name="i-heroicons-clock" class="w-5 h-5 text-gray-400" />
                    <span>В обработке: {{ pendingOrdersCount }}</span>
                </div>
            </div>
        </div>

        <div v-if="pickupHint" class="px-4 lg:px-32 mb-4">
            <div class="flex gap-3 rounded-lg border border-primary/10 bg-primary/5 px-4 py-3 text-sm">
                <UIcon name="i-heroicons-information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                <div class="min-w-0">
                    <div class="font-medium text-primary">Где забрать товар?</div>
                    <p class="whitespace-pre-line text-muted">{{ pickupHint }}</p>
                </div>
            </div>
        </div>

        <div class="px-4 lg:px-32">
            <UTabs v-model="activeTab" :items="tabItems" :default-value="0" class="w-auto" :ui="{ list: 'gap-1', trigger: 'px-3 py-1.5 text-sm' }">
            <template #content>
                <div v-if="activeTab === 0" class="mt-4">
                    <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
                        <div class="text-center">
                            <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-info mx-auto mb-2" />
                            <p class="text-gray-600">Загрузка товаров...</p>
                        </div>
                    </div>

                    <div v-if="status === 'success'">
                        <div v-if="products.length > 0" class="flex flex-col space-y-2">
                            <div class="flex flex-row flex-wrap gap-3">
                                <div
                                    v-for="(product, index) in products"
                                    :key="index"
                                    class="w-full lg:w-78 flex flex-col border border-default rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 bg-white overflow-hidden"
                                >
                                    <div v-if="product.photo_path" class="h-40 w-full overflow-hidden cursor-zoom-in border-b border-default" @click="openImagePreview(productPhotoUrl(product.photo_path), product.name)">
                                        <img :src="productPhotoUrl(product.photo_path)" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300">
                                    </div>
                                    <div v-else class="flex h-40 w-full items-center justify-center bg-gray-50 border-b border-default">
                                        <UIcon name="i-heroicons-photo" class="h-12 w-12 text-gray-300" />
                                    </div>

                                    <div class="flex flex-col p-4">
                                        <h3 class="text-base font-semibold text-gray-900 mb-3">
                                            {{ product.name }}
                                        </h3>

                                        <div class="mt-auto pt-3 border-t border-muted">
                                            <div class="flex items-baseline justify-between mb-3">
                                                <div class="flex items-baseline space-x-1">
                                                    <span class="text-2xl font-bold text-primary">{{ product.score }}</span>
                                                    <span class="text-sm font-medium text-gray-500">баллов</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-4 pb-4">
                                        <UButton
                                            :disabled="product.score > (availableScore || 0)"
                                            :color="product.score > (availableScore || 0) ? 'neutral' : 'primary'"
                                            variant="solid"
                                            block
                                            :loading="purchaseLoading === product.id"
                                            @click="buyProduct(product)"
                                        >
                                            {{ product.score > (availableScore || 0) ? 'Недостаточно баллов' : 'Обменять' }}
                                        </UButton>
                                    </div>
                                </div>
                            </div>

                            <div v-if="pagination.lastPage > 1" class="flex justify-end px-4 lg:px-32">
                                <div class="flex flex-row space-x-2 items-center text-sm">
                                    <UButton
                                        :disabled="pagination.currentPage <= 1"
                                        icon="i-heroicons-chevron-left"
                                        size="xs"
                                        color="primary"
                                        square
                                        variant="solid"
                                        @click="loadPage(pagination.currentPage - 1)"
                                    />
                                    <span>Стр. {{ pagination.currentPage }} из {{ pagination.lastPage }}</span>
                                    <UButton
                                        :disabled="pagination.currentPage >= pagination.lastPage"
                                        icon="i-heroicons-chevron-right"
                                        size="xs"
                                        color="primary"
                                        square
                                        variant="solid"
                                        @click="loadPage(pagination.currentPage + 1)"
                                    />
                                </div>
                            </div>
                        </div>

                        <UAlert
                            v-else
                            icon="i-heroicons-information-circle"
                            color="info"
                            variant="solid"
                            title="Информация"
                            description="Товары не найдены"
                        />
                    </div>
                </div>

                <div v-if="activeTab === 1" class="mt-4 px-4 lg:px-32">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <UButton
                            label="Все"
                            size="xs"
                            :color="orderStatusFilter === null ? 'primary' : 'neutral'"
                            variant="solid"
                            @click="setOrderStatusFilter(null)"
                        />
                        <UButton
                            label="Ожидает"
                            size="xs"
                            :color="orderStatusFilter === 'pending' ? 'warning' : 'neutral'"
                            variant="solid"
                            @click="setOrderStatusFilter('pending')"
                        />
                        <UButton
                            label="Выдано"
                            size="xs"
                            :color="orderStatusFilter === 'issued' ? 'primary' : 'neutral'"
                            variant="solid"
                            @click="setOrderStatusFilter('issued')"
                        />
                        <UButton
                            label="Отменено"
                            size="xs"
                            :color="orderStatusFilter === 'cancelled' ? 'error' : 'neutral'"
                            variant="solid"
                            @click="setOrderStatusFilter('cancelled')"
                        />
                        <UButton
                            :icon="orderSortDirection === 'desc' ? 'i-heroicons-bars-arrow-down' : 'i-heroicons-bars-arrow-up'"
                            size="xs"
                            color="neutral"
                            variant="outline"
                            @click="toggleOrderSortDirection()"
                        />
                    </div>

                    <div v-if="ordersStatus === 'pending'" class="flex justify-center items-center py-12">
                        <div class="text-center">
                            <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-info mx-auto mb-2" />
                            <p class="text-gray-600">Загрузка покупок...</p>
                        </div>
                    </div>

                    <div v-if="ordersStatus === 'success'">
                        <div v-if="orders.length > 0" class="flex flex-col gap-2">
                            <div class="hidden lg:block">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="border-b border-default px-4 py-2 text-left font-semibold">Товар</th>
                                            <th class="w-24 border-b border-default px-4 py-2 font-semibold">Баллы</th>
                                            <th class="w-28 border-b border-default px-4 py-2 font-semibold">Статус</th>
                                            <th class="w-40 border-b border-default px-4 py-2 font-semibold">Дата покупки</th>
                                            <th class="w-40 border-b border-default px-4 py-2 font-semibold">Дата обработки</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="order in orders" :key="order.id" class="hover:bg-zinc-950/2.5" :class="orderStatusBorderClass(order.status)">
                                            <td class="border-b border-muted px-4 py-2">{{ order.item_name_snapshot }}</td>
                                            <td class="w-24 border-b border-muted px-4 py-2 text-center">{{ order.item_score_snapshot }}</td>
                                            <td class="w-28 border-b border-muted px-4 py-2 text-center">
                                                <UBadge :color="orderStatusColor(order.status)" variant="solid">
                                                    {{ orderStatusLabel(order.status) }}
                                                </UBadge>
                                            </td>
                                            <td class="w-40 border-b border-muted px-4 py-2 text-center">{{ formatDate(order.created_at) }}</td>
                                            <td class="w-40 border-b border-muted px-4 py-2 text-center">{{ formatOrderDecision(order) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="lg:hidden grid gap-2">
                                <div v-for="order in orders" :key="order.id" class="rounded-lg ring-1 shadow-sm ring-gray-200 overflow-hidden bg-white">
                                    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-100 gap-2">
                                        <span class="font-medium text-gray-900 truncate">{{ order.item_name_snapshot }}</span>
                                        <UBadge :color="orderStatusColor(order.status)" variant="solid" size="sm" class="shrink-0">
                                            {{ orderStatusLabel(order.status) }}
                                        </UBadge>
                                    </div>
                                    <div class="px-3 py-2 grid grid-cols-2 gap-y-2 gap-x-4 text-sm">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Баллы</span>
                                            <span class="font-semibold">{{ order.item_score_snapshot }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Дата покупки</span>
                                            <span>{{ formatDate(order.created_at) }}</span>
                                        </div>
                                        <div class="flex flex-col col-span-2">
                                            <span class="text-xs text-gray-500">Дата обработки</span>
                                            <span>{{ formatOrderDecision(order) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="ordersPagination.lastPage > 1" class="flex justify-end">
                                <div class="flex flex-row space-x-2 items-center text-sm">
                                    <UButton
                                        :disabled="ordersPagination.currentPage <= 1"
                                        icon="i-heroicons-chevron-left"
                                        size="xs"
                                        color="primary"
                                        square
                                        variant="solid"
                                        @click="loadOrdersPage(ordersPagination.currentPage - 1)"
                                    />
                                    <span>Стр. {{ ordersPagination.currentPage }} из {{ ordersPagination.lastPage }}</span>
                                    <UButton
                                        :disabled="ordersPagination.currentPage >= ordersPagination.lastPage"
                                        icon="i-heroicons-chevron-right"
                                        size="xs"
                                        color="primary"
                                        square
                                        variant="solid"
                                        @click="loadOrdersPage(ordersPagination.currentPage + 1)"
                                    />
                                </div>
                            </div>
                        </div>

                        <UAlert
                            v-else
                            icon="i-heroicons-information-circle"
                            color="info"
                            variant="solid"
                            title="Информация"
                            description="У вас пока нет покупок"
                        />
                    </div>
                </div>
            </template>
        </UTabs>
        </div>

        <UModal v-model:open="showPurchaseModal">
            <template #header>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Подтверждение покупки</h3>
                </div>
            </template>

            <template v-if="selectedProduct" #body>
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-900 text-lg">{{ selectedProduct.name }}</h4>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Цена:</span>
                            <span class="font-medium">{{ selectedProduct.score }} баллов</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Ваш баланс:</span>
                            <span class="font-medium">{{ availableScore || 0 }} баллов</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-900">Останется:</span>
                            <span class="font-bold text-primary">
                                {{ (availableScore || 0) - selectedProduct.score }} баллов
                            </span>
                        </div>
                    </div>
                </div>
            </template>

            <template v-if="selectedProduct" #footer>
                <div class="flex justify-end space-x-2">
                    <UButton
                        color="neutral"
                        variant="outline"
                        @click="showPurchaseModal = false"
                    >
                        Отмена
                    </UButton>
                    <UButton
                        color="primary"
                        :loading="purchaseLoading === selectedProduct?.id"
                        @click="confirmPurchase()"
                    >
                        Подтвердить покупку
                    </UButton>
                </div>
            </template>
        </UModal>

        <ImagePreviewModal
            v-model:open="showImagePreview"
            :src="previewImageSrc"
            :alt="previewImageAlt"
        />
    </NuxtLayout>
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['student'],
    layout: false
});

const toast = useToast();
const config = useRuntimeConfig();
const { getAvailableScore, getMarketplaceSettings, getProducts, purchaseProduct, getOrders } = useStudentMarketplaceApi()

const activeTab = ref(0);
const tabItems = [
    { label: 'Товары', value: 0 },
    { label: 'Мои покупки', value: 1 },
];

const status = ref('idle');
const products = ref([]);
const purchaseLoading = ref(null);
const showPurchaseModal = ref(false);
const selectedProduct = ref(null);
const availableScore = ref(0);
const scoreLoading = ref(false);
const pickupHint = ref('');

const showImagePreview = ref(false);
const previewImageSrc = ref('');
const previewImageAlt = ref('');

const pendingOrdersCount = computed(() => {
    if (!Array.isArray(orders.value)) return 0;
    return orders.value.filter(o => o.status === 'pending').length;
});

function openImagePreview(src, alt) {
    previewImageSrc.value = src || '';
    previewImageAlt.value = alt || '';
    showImagePreview.value = true;
}

const ordersStatus = ref('idle');
const orders = ref([]);
const orderStatusFilter = ref(null);
const orderSortField = ref('created_at');
const orderSortDirection = ref('desc');

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0
});

const ordersPagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0
});

function resolveDefaultSortField(status) {
    if (status === 'issued' || status === 'cancelled') {
        return 'processed_at';
    }
    return 'created_at';
}

const { data: scoreData, refresh: refreshScoreData } = await useAsyncData(
    'availableScore',
    async () => {
        try {
            scoreLoading.value = true;
            const score = await getAvailableScore()
            return { score }
        } catch (error) {
            const description = error?.response?._data?.message || error?.message || 'Не удалось загрузить баланс'
            console.error('Error loading available score:', error)
            toast.add({
                title: 'Ошибка загрузки баллов',
                description,
                color: 'error'
            });
            return { score: 0 };
        } finally {
            scoreLoading.value = false;
        }
    },
    {
        server: true,
        lazy: true,
        immediate: false
    }
);

onMounted(async () => {
    await loadInitialData();
});

watch(activeTab, async (value) => {
    if (value === 1 && ordersStatus.value === 'idle') {
        await loadOrders();
    }
});

async function loadInitialData() {
    status.value = 'pending';

    try {
        await Promise.all([
            loadProducts(),
            loadAvailableScore(),
            loadOrders(),
            loadMarketplaceSettings()
        ]);
    } catch {
        status.value = 'error';
    }
}

async function loadMarketplaceSettings() {
    try {
        const settings = await getMarketplaceSettings();
        pickupHint.value = String(settings.pickup_hint ?? '').trim();
    } catch (error) {
        console.error('Marketplace settings error:', error);
        pickupHint.value = '';
    }
}

async function loadProducts(page = 1) {
    try {
        const data = await getProducts(page)

        products.value = data.products;

        pagination.value = {
            currentPage: data.pagination.currentPage,
            lastPage: data.pagination.lastPage,
            total: data.pagination.total
        };

        status.value = 'success';
    } catch {
        status.value = 'error';
        toast.add({
            title: 'Ошибка загрузки',
            description: 'Не удалось загрузить товары маркетплейса',
            color: 'error'
        });
    }
}

async function loadOrders(page = 1) {
    ordersStatus.value = 'pending';

    try {
        const data = await getOrders(
            page,
            orderStatusFilter.value,
            orderSortField.value,
            orderSortDirection.value,
        )

        orders.value = data.orders;
        ordersPagination.value = {
            currentPage: data.pagination.currentPage,
            lastPage: data.pagination.lastPage,
            total: data.pagination.total
        };

        ordersStatus.value = 'success';
    } catch (error) {
        ordersStatus.value = 'error';
        toast.add({
            title: 'Ошибка загрузки',
            description: error?.response?._data?.message || 'Не удалось загрузить покупки',
            color: 'error'
        });
    }
}

function setOrderStatusFilter(status) {
    orderStatusFilter.value = status;
    orderSortField.value = resolveDefaultSortField(status);
    orderSortDirection.value = 'desc';
    ordersPagination.value.currentPage = 1;
    loadOrders(1);
}

function toggleOrderSortDirection() {
    orderSortDirection.value = orderSortDirection.value === 'desc' ? 'asc' : 'desc';
    ordersPagination.value.currentPage = 1;
    loadOrders(1);
}

async function loadAvailableScore() {
    try {
        scoreLoading.value = true;
        await refreshScoreData();

        if (scoreData.value) {
            availableScore.value = scoreData.value.score;
        }
    } catch (error) {
        console.error('Error refreshing score:', error);
    } finally {
        scoreLoading.value = false;
    }
}

function loadPage(page) {
    if (page >= 1 && page <= pagination.value.lastPage) {
        loadProducts(page);
    }
}

function loadOrdersPage(page) {
    if (page >= 1 && page <= ordersPagination.value.lastPage) {
        loadOrders(page);
    }
}

function buyProduct(product) {
    if (product.score > (availableScore.value || 0)) {
        toast.add({
            title: 'Недостаточно баллов',
            description: `Для обмена "${product.name}" нужно ${product.score} баллов`,
            color: 'error'
        });
        return;
    }

    selectedProduct.value = product;
    showPurchaseModal.value = true;
}

async function confirmPurchase() {
    if (!selectedProduct.value) return;

    const product = selectedProduct.value;
    purchaseLoading.value = product.id;

    try {
        const data = await purchaseProduct(product.id);

        toast.add({
            title: 'Покупка успешна!',
            description: `Товар "${product.name}" добавлен в мои покупки.`,
            color: 'success'
        });

        availableScore.value = data?.data?.previous_semester_score ?? availableScore.value - product.score;
        await Promise.all([
            loadProducts(pagination.value.currentPage),
            loadOrders(1)
        ]);

        showPurchaseModal.value = false;
        selectedProduct.value = null;
    } catch (error) {
        console.error('Purchase error:', error);
        toast.add({
            title: 'Ошибка покупки',
            description: error?.response?._data?.message || error?.message || 'Не удалось совершить покупку',
            color: 'error'
        });
    } finally {
        purchaseLoading.value = null;
    }
}

function productPhotoUrl(photoPath) {
    return `${config.public.API_PATH_STORAGE}${photoPath}`;
}

function orderStatusLabel(status) {
    if (status === 'issued') return 'Выдано';
    if (status === 'cancelled') return 'Отменено';
    return 'Ожидает';
}

function orderStatusColor(status) {
    if (status === 'issued') return 'primary';
    if (status === 'cancelled') return 'error';
    return 'warning';
}

function orderStatusBorderClass(status) {
    if (status === 'issued') return 'border-l-4 border-l-primary';
    if (status === 'cancelled') return 'border-l-4 border-l-error';
    return 'border-l-4 border-l-warning';
}

function formatOrderDecision(order) {
    if (order.status === 'issued') return formatDate(order.issued_at);
    if (order.status === 'cancelled') return formatDate(order.cancelled_at);
    return '—';
}

function formatDate(date) {
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
