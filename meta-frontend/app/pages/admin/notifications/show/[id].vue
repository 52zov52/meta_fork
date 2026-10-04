<template>
    <div>
        <div v-if="status === 'pending'" class="flex justify-center py-8">
            <UIcon name="i-heroicons-arrow-path" class="animate-spin w-6 h-6 text-primary" />
        </div>

        <div v-if="status === 'success'" class="max-w-4xl">
            <!-- Основная информация об уведомлении -->
            <div class="bg-white rounded-lg shadow-sm border border-default p-6 mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ notification.title }}</h3>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <UBadge color="neutral" variant="outline">
                                Прочитано: {{ notification.checked ? 'да' : 'нет' }}
                            </UBadge>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <UButton
                            v-if="notification.id != null"
                            color="primary"
                            variant="solid"
                            icon="i-heroicons-paper-airplane"
                            label="Отправить"
                            @click="sendNotification"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center space-x-2">
                            <UIcon name="i-heroicons-calendar" class="w-5 h-5 text-primary" />
                            <div>
                                <p class="text-sm text-gray-600">Создано</p>
                                <p class="font-semibold">{{ formatDate(notification.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-default pt-4">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Содержание сообщения</h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-line">{{ notification.description }}</p>
                    </div>
                </div>
            </div>

            <!-- История отправки -->
            <div class="bg-white rounded-lg shadow-sm border border-default p-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">История отправки</h4>

                <div v-if="history.length > 0" class="space-y-3">
                    <div
v-for="(item, index) in history" :key="index"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="shrink-0">
                                <div
:class="[
                                    'w-2 h-2 rounded-full',
                                    item.status === 'sent' ? 'bg-green-400' :
                                    item.status === 'failed' ? 'bg-red-400' : 'bg-yellow-400'
                                ]"/>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ item.user_name }}</p>
                                <p class="text-xs text-gray-500">{{ item.user_email }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-900">{{ formatDate(item.sent_at) }}</p>
                            <UBadge
                                :color="item.status === 'sent' ? 'success' : item.status === 'failed' ? 'error' : 'warning'"
                                variant="soft"
                                size="xs"
                            >
                                {{ item.status === 'sent' ? 'Доставлено' :
                                   item.status === 'failed' ? 'Ошибка' : 'В очереди' }}
                            </UBadge>
                        </div>
                    </div>
                </div>

                <UAlert
v-if="history.length === 0"
                    icon="i-heroicons-information-circle"
                    color="primary"
                    variant="soft"
                    title="Информация"
                    description="История отправки пуста. Уведомление еще не было отправлено."
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { paths } from '~/types/api'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Просмотр уведомления',
  description: 'Детальная информация об уведомлении',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Уведомления', to: '/admin/notifications' }, { label: 'Просмотр' }],
});

/** Тот же объект, что в списке (`notifications/order`), т.к. `notification/edit` в спеке ссылается на дублирующийся `operations["ID"]` без стабильного 200. */
type NotificationEditResponse = NonNullable<
    paths['/api/v1/admin/notifications/order/{sortMethod}']['get']['responses'][200]['content']['application/json']['data']
>[number]

type NotificationHistoryResponse =
    paths['/api/v1/admin/notification/{id}/history']['get']['responses'][200]['content']['application/json']

type NotificationSendResponse =
    paths['/api/v1/admin/notification/send/{id}']['post']['responses'][200]['content']['application/json']

onMounted(() => {
    loadNotification();
    loadHistory();
});

const route = useRoute();
const { get, post } = useApi();
const toast = useToast();

const notification = ref<NotificationEditResponse>({});
const history = ref<unknown[]>([]);
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle');

function loadNotification() {
    status.value = 'pending';

    get<NotificationEditResponse>(`/api/v1/admin/notification/edit/${String(route.params.id)}`).then((data) => {
        notification.value = data;
        status.value = 'success';
    }).catch(() => {
        status.value = 'error';
        toast.add({
            title: 'Произошла ошибка при загрузке уведомления',
            color: 'error'
        });
    });
}

function loadHistory() {
    get<NotificationHistoryResponse>(`/api/v1/admin/notification/${String(route.params.id)}/history`).then((data) => {
        history.value = Array.isArray(data) ? data : [];
    }).catch(() => {
    });
}

function sendNotification() {
    if (confirm('Вы уверены, что хотите отправить это уведомление?')) {
        const id = notification.value.id;
        if (id == null) return;
        post<NotificationSendResponse>(`/api/v1/admin/notification/send/${id}`).then((data) => {
            toast.add({ title: data.message });
            loadNotification();
            loadHistory();
        }).catch(error => {
            toast.add({
                title: error.response._data?.message || 'Произошла ошибка при отправке уведомления',
                color: 'error'
            });
        });
    }
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
