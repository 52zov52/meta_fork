<template>
    <div class="flex flex-col space-y-4">
            <div class="flex justify-between items-center">
                <UButton
                    :icon="currentSort === 'asc' ? 'i-heroicons-bars-arrow-up' : 'i-heroicons-bars-arrow-down'"
                    :label="currentSort === 'asc' ? 'Сначала старые' : 'Сначала новые'"
                    size="xs"
                    color="neutral"
                    variant="ghost"
                    @click="toggleSort"
                />
                <UButton
                    to="/admin/notifications/create"
                    label="Создать уведомление"
                    icon="i-heroicons-plus-circle"
                    size="xs"
                    color="primary"
                    square
                    variant="solid"
                />
            </div>

            <div class="flex space-x-2">
                <UInput
                    v-model="searchTxt"
                    type="text"
                    icon="i-heroicons-magnifying-glass"
                    size="sm"
                    class="w-full"
                    placeholder="Поиск по заголовку или тексту"
                />
            </div>

            <div v-if="status === 'pending'" class="flex justify-center py-8">
                <UIcon name="i-heroicons-arrow-path" class="animate-spin w-6 h-6 text-primary" />
            </div>

            <div v-if="status === 'success'">
                <div v-if="filteredNotifications.length > 0" class="flex flex-col gap-4">
                    <div
                        v-for="(notification, index) in filteredNotifications" :key="index"
                        class="bg-elevated border border-default border-t-2 border-t-primary rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-default">{{ notification.title }}</h3>
                                    <UBadge
                                        :color="notification.checked ? 'success' : 'warning'"
                                        variant="soft"
                                        size="xs">
                                        {{ notification.checked ? 'Прочитано' : 'Не прочитано' }}
                                    </UBadge>
                                </div>
                                <p class="text-muted mb-3">{{ notification.description }}</p>
                                <div class="flex items-center space-x-4 text-sm text-muted">
                                    <div class="flex items-center space-x-1">
                                        <UIcon name="i-heroicons-calendar" class="w-4 h-4 text-primary" />
                                        <span>{{ formatDate(notification.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row items-end md:items-center gap-2 ml-4">
                                <UBadge
                                    color="info"
                                    variant="soft"
                                    size="xs">
                                    ID {{ notification.id }}
                                </UBadge>
                                <div class="flex items-center gap-1">
                                    <UButton
                                        icon="i-heroicons-eye"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        title="Просмотреть"
                                        class="hover:bg-white"
                                        @click="viewNotification(notification.id!)"
                                    />
                                    <UButton
                                        icon="i-heroicons-paper-airplane"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        title="Отправить"
                                        class="hover:bg-white"
                                        @click="sendNotification(notification.id!)"
                                    />
                                    <UButton
                                        icon="i-heroicons-trash"
                                        color="error"
                                        variant="ghost"
                                        size="sm"
                                        title="Удалить"
                                        class="hover:bg-white"
                                        @click="deleteNotification(notification.id!)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <ListPagination :pagination="pagination" @update:page="onPageChange" />
                    </div>
                </div>

                <UAlert
                    v-if="filteredNotifications.length === 0"
                    icon="i-heroicons-information-circle"
                    color="info"
                    variant="solid"
                    title="Информация"
                    description="Список уведомлений пуст. Для создания нового уведомления нажмите кнопку «Создать уведомление»" />
            </div>
    </div>
</template>

<script setup lang="ts">
import type { paths } from '~/types/api'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Уведомления',
  description: 'Управляйте уведомлениями пользователей',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Уведомления' }],
});

onMounted(() => {
    loadData();
});

type AdminNotificationsResponse =
    paths['/api/v1/admin/notifications/order/{sortMethod}']['get']['responses'][200]['content']['application/json']

type AdminNotificationDestroyResponse =
    paths['/api/v1/admin/notification/destroy/{id}']['delete']['responses'][200]['content']['application/json']

type AdminNotificationSendResponse =
    paths['/api/v1/admin/notification/send/{id}']['post']['responses'][200]['content']['application/json']

type AdminNotificationListItem = NonNullable<AdminNotificationsResponse['data']>[number]

const { get, post, delete: del } = useApi();
const toast = useToast();

const notifications = ref<AdminNotificationListItem[]>([]);
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');
const currentSort = ref<'asc' | 'desc'>('desc');
const searchTxt = ref('');

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    totalItems: 0
});

const filteredNotifications = computed(() => {
    const query = searchTxt.value.trim().toLowerCase();
    if (!query) return notifications.value;
    return notifications.value.filter(n =>
        n.title?.toLowerCase().includes(query) ||
        n.description?.toLowerCase().includes(query)
    );
});

function loadData(url?: string) {
    status.value = 'pending';
    const fetchUrl = url || `/api/v1/admin/notifications/order/${currentSort.value}?page=${pagination.value.currentPage}`;

    get<AdminNotificationsResponse>(fetchUrl).then((data) => {
        notifications.value = Array.isArray(data.data) ? data.data : [];

        pagination.value.currentPage = data.current_page ?? 1;
        pagination.value.lastPage = data.last_page ?? 1;
        pagination.value.perPage = data.per_page ?? 20;
        pagination.value.totalItems = data.total ?? 0;

        status.value = 'success';
    }).catch(_error => {
        status.value = 'error';
        toast.add({
            title: 'Произошла ошибка при загрузке уведомлений',
            color: 'error'
        });
    });
}

function toggleSort() {
    currentSort.value = currentSort.value === 'asc' ? 'desc' : 'asc';
    pagination.value.currentPage = 1;
    loadData();
}

function viewNotification(id: number) {
    navigateTo(`/admin/notifications/show/${id}`);
}

function sendNotification(id: number) {
    if (confirm('Вы уверены, что хотите отправить это уведомление всем получателям?')) {
        post<AdminNotificationSendResponse>(`/api/v1/admin/notification/send/${id}`).then((data) => {
            toast.add({ title: data.message });
            loadData();
        }).catch(error => {
            toast.add({
                title: error.response._data?.message || 'Произошла ошибка при отправке уведомления',
                color: 'error'
            });
        });
    }
}

function deleteNotification(id: number) {
    if (confirm('Вы уверены, что хотите удалить это уведомление?')) {
        del<AdminNotificationDestroyResponse>(`/api/v1/admin/notification/destroy/${id}`).then((data) => {
            toast.add({ title: data.message });
            loadData();
        }).catch(error => {
            toast.add({
                title: error.response._data?.message || 'Произошла ошибка при удалении уведомления',
                color: 'error'
            });
        });
    }
}

function onPageChange(page: number) {
    pagination.value.currentPage = page;
    loadData();
}

function formatDate(date: string | undefined) {
    if (!date) return '';
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    };
    return new Date(date).toLocaleDateString('ru-RU', options);
}
</script>
