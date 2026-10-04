<template>
    <div class="max-w-2xl">
        <form @submit.prevent="createNotification" class="space-y-6">
            <!-- Заголовок уведомления -->
            <div class="space-y-2">
                <label for="title" class="block text-sm font-medium text-default">
                    Заголовок уведомления <span class="text-error">*</span>
                </label>
                <UInput
                    id="title"
                    v-model="notification.title"
                    type="text"
                    placeholder="Введите заголовок уведомления"
                    class="w-full"
                />
                <div v-if="errors.title" class="text-error text-xs mt-1">
                    {{ errors.title[0] }}
                </div>
            </div>

            <!-- Сообщение -->
            <div class="space-y-2">
                <label for="message" class="block text-sm font-medium text-default">
                    Текст сообщения <span class="text-error">*</span>
                </label>
                <UTextarea
                    id="message"
                    v-model="notification.message"
                    :rows="6"
                    placeholder="Введите текст уведомления"
                    class="w-full"
                />
                <div v-if="errors.message" class="text-error text-xs mt-1">
                    {{ errors.message[0] }}
                </div>
            </div>

            <!-- Тип получателей -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-default">
                    Получатели <span class="text-error">*</span>
                </label>
                <USelect
                    v-model="notification.recipient_type"
                    :items="recipientTypeOptions"
                    placeholder="Выберите получателей"
                    class="w-full"
                />
                <div v-if="errors.recipient_type" class="text-error text-xs mt-1">
                    {{ errors.recipient_type[0] }}
                </div>
            </div>

            <!-- Выбор конкретного пользователя -->
            <div v-if="notification.recipient_type === 'individual'" class="space-y-2">
                <label class="block text-sm font-medium text-default">
                    Поиск пользователя <span class="text-error">*</span>
                </label>
                <USelectMenu
                    v-model="selectedUser"
                    :items="users"
                    :loading="loadingUsers"
                    placeholder="Введите ФИО для поиска..."
                    searchable
                    @update:search-term="onSearch"
                >
                    <template #item-label="{ item }">
                        <div class="flex flex-col">
                            <span>{{ item.label }}</span>
                            <span class="text-xs text-muted">{{ item.role }}</span>
                        </div>
                    </template>
                </USelectMenu>
                <div v-if="errors.user_id" class="text-error text-xs mt-1">
                    {{ errors.user_id[0] }}
                </div>
            </div>

            <!-- Приоритет -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-default">
                    Приоритет
                </label>
                <USelect
                    v-model="notification.priority"
                    :items="priorityOptions"
                    placeholder="Выберите приоритет"
                    class="w-full"
                />
                <div v-if="errors.priority" class="text-error text-xs mt-1">
                    {{ errors.priority[0] }}
                </div>
            </div>

            <!-- Отложенная отправка -->
            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <UCheckbox
                        v-model="isScheduled"
                        label="Отложенная отправка"
                    />
                </div>

                <div v-if="isScheduled" class="ml-6 space-y-2">
                    <label class="block text-sm font-medium text-default">
                        Дата и время отправки
                    </label>
                    <UInput
                        v-model="notification.scheduled_at"
                        type="datetime-local"
                        class="w-full"
                    />
                    <div v-if="errors.scheduled_at" class="text-error text-xs mt-1">
                        {{ errors.scheduled_at[0] }}
                    </div>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="flex justify-end space-x-3 pt-6">
                <UButton
                    type="button"
                    color="neutral"
                    variant="outline"
                    label="Отмена"
                    @click="navigateTo('/admin/notifications')"
                />
                <UButton
                    type="submit"
                    :loading="status === 'pending'"
                    color="primary"
                    variant="solid"
                >
                    {{ isScheduled ? 'Запланировать' : 'Создать и отправить' }}
                </UButton>
            </div>
        </form>
    </div>
</template>

<script setup>
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Создание уведомления',
  description: 'Создайте новое уведомление для пользователей',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Уведомления', to: '/admin/notifications' }, { label: 'Создание' }],
});

const { post, get } = useApi();
const toast = useToast();

const notification = ref({
    title: '',
    message: '',
    recipient_type: '',
    user_id: null,
    priority: 'normal',
    scheduled_at: null
});

const isScheduled = ref(false);
const status = ref('idle');
const errors = ref({});

const users = ref([]);
const selectedUser = ref(null);
const loadingUsers = ref(false);

const recipientTypeOptions = [
    { value: 'all', label: 'Все пользователи' },
    { value: 'students', label: 'Только студенты' },
    { value: 'mentors', label: 'Только наставники' },
    { value: 'individual', label: 'Конкретный пользователь' },
];

const priorityOptions = [
    { value: 'low', label: 'Низкий' },
    { value: 'normal', label: 'Обычный' },
    { value: 'high', label: 'Высокий' },
    { value: 'urgent', label: 'Срочный' },
];

// Обновляем user_id при выборе пользователя
watch(selectedUser, (user) => {
    notification.value.user_id = user ? user.id : null;
});

// Сброс данных при смене типа получателя
watch(() => notification.value.recipient_type, (newValue) => {
    if (newValue !== 'individual') {
        notification.value.user_id = null;
        selectedUser.value = null;
    }
});

// Очищаем scheduled_at когда отключаем отложенную отправку
watch(isScheduled, (newValue) => {
    if (!newValue) {
        notification.value.scheduled_at = null;
    }
});

async function onSearch(query) {
    if (!query || query.length < 2) {
        users.value = [];
        return;
    }

    loadingUsers.value = true;
    try {
        const response = await get(`/api/v1/admin/member/search/desc/${query}`);
        users.value = response.data.map(user => ({
            id: user.user_id,
            label: user.fio,
            role: user.role
        }));
    } catch (error) {
        console.error('Ошибка при поиске пользователей:', error);
    } finally {
        loadingUsers.value = false;
    }
}

function createNotification() {
    status.value = 'pending';

    // Очищаем предыдущие ошибки
    errors.value = {};

    const payload = {
        ...notification.value,
        send_immediately: !isScheduled.value
    };

    post('/api/v1/admin/notification/store', payload).then((data) => {
        status.value = 'success';
        toast.add({
            title: data.message || 'Уведомление успешно создано'
        });

        // Перенаправляем на список уведомлений
        setTimeout(() => {
            navigateTo('/admin/notifications');
        }, 1000);
    }).catch(error => {
        status.value = 'error';

        if (error.statusCode === 422) {
            errors.value = error.response._data?.messages || {};
            toast.add({
                title: 'Пожалуйста, исправьте ошибки в форме',
                color: 'error'
            });
        } else {
            toast.add({
                title: error.response._data?.message || 'Произошла ошибка при создании уведомления',
                color: 'error'
            });
        }
    }).finally(() => {
        status.value = 'idle';
    });
}
</script>
