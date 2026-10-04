<template>
    <NuxtLayout name="student">
        <!-- Loading State -->
        <div v-if="status === 'pending'" class="mt-4 flex justify-center items-center py-12">
            <div class="text-center">
                <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
                <p class="text-gray-600">Загрузка избранного...</p>
            </div>
        </div>

        <!-- Success State -->
        <div v-else-if="status === 'success'" class="mt-4">
            <div v-if="activities.length !== 0" class="flex flex-col space-y-2">
                <ActivityCardGrid>
                    <ActivityCard
                        v-for="(item, index) in activities"
                        :key="item.id ?? index"
                        :activity="item"
                        :api-path-storage="config.public.API_PATH_STORAGE"
                        variant="wishlist"
                        @open="openActivity(item)"
                        @wishlist-remove="(id) => deleteFromWishlist(id)"
                        @register="(id) => registerToActivity(id)"
                        @undo-register="(id) => undoRegisterToActivity(id)"
                    />
                </ActivityCardGrid>

                <ListPagination :pagination="pagination" @update:page="onPageChange" />
            </div>

            <UAlert
                v-if="activities.length === 0"
                icon="i-heroicons-information-circle"
                color="info"
                variant="solid"
                title="Информация"
                description="Список избранных мероприятий или событий пуст"
            />
        </div>

        <!-- Error State -->
        <UCard v-else-if="status === 'error'" class="mt-4 py-12">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <UIcon name="i-heroicons-exclamation-triangle" class="w-8 h-8 text-red-500" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ошибка загрузки</h3>
                <p class="text-sm text-gray-500">Не удалось загрузить избранное</p>
            </div>
        </UCard>

        <ActivityModal
            v-model:open="showActivityModal"
            :activity="activeActivity"
            :files="activityFiles"
            :api-path-storage="config.public.API_PATH_STORAGE"
            @close="handleCloseActivity"
        >
            <template #footer>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <UIcon name="i-heroicons-information-circle" class="w-4 h-4" />
                    <span v-if="activeActivity?.is_registered == 0">Для участия нажмите кнопку «Зарегистрироваться»</span>
                    <span v-if="activeActivity?.is_registered == 1">Вы уже зарегистрированы на это мероприятие</span>
                </div>
                <div class="flex gap-2">
                    <UButton
                        v-if="activeActivity?.is_registered == 0"
                        color="primary"
                        variant="solid"
                        :loading="registerLoading"
                        @click="registerToActivity(activeActivity.id)"
                    >
                        Зарегистрироваться
                    </UButton>
                    <UButton
                        v-if="activeActivity?.is_registered == 1"
                        color="error"
                        variant="solid"
                        :loading="registerLoading"
                        @click="undoRegisterToActivity(activeActivity.id)"
                    >
                        Отменить регистрацию
                    </UButton>
                </div>
            </template>
        </ActivityModal>
    </NuxtLayout>
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['student'],
    layout: false,
});

const config = useRuntimeConfig();
const { user } = useSanctumAuth();

function buildUrl(page) {
    const base = `/api/v1/student/activity/wishlist/${user.value.user_id}`;
    return page != null ? `${base}?page=${page}` : base;
}

const {
    status,
    activities,
    pagination,
    loadData,
    openActivity: openActivityBase,
    closeActivity,
    showActivityModal,
    activeActivity,
    registerLoading,
    deleteFromWishlist,
    registerToActivity,
    undoRegisterToActivity,
} = useStudentActivityList({
    mode: 'wishlist',
    buildUrl,
});

const { getActivityFiles } = useStudentActivityFilesApi();
const activityFiles = ref([]);

async function openActivity(item) {
    openActivityBase(item);
    activityFiles.value = [];
    if (item.id) {
        try {
            activityFiles.value = await getActivityFiles(item.id);
        } catch {
            activityFiles.value = [];
        }
    }
}

function handleCloseActivity() {
    closeActivity();
    activityFiles.value = [];
}

const { onPageChange, getInitialPage } = usePaginationRedirect(loadData, buildUrl);

onMounted(() => {
    loadData(buildUrl(getInitialPage()));
});
</script>
