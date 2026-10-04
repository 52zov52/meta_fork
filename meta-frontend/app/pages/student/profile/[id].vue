<template>
    <NuxtLayout name="public">
        <template #header>
            <StudentHeader :user-id="userId" />
        </template>

        <UButton
            :to="`/student/ratings`"
            icon="i-heroicons-arrow-left"
            size="sm"
            color="gray"
            variant="ghost"
            class="shrink-0 mb-2"
        >
            <span>Рейтинги</span>
        </UButton>

        <!-- Loading State -->
        <div v-if="status === 'pending'" class="mt-4 space-y-4">
            <USkeleton class="h-12 w-full" />
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
                <USkeleton v-for="i in 6" :key="i" class="h-56 w-full" />
            </div>
        </div>

        <div v-if="status === 'success'" class="mt-4 flex flex-col space-y-2">
            <!-- Filters Bar -->
            <div class="flex flex-col gap-3 px-1">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-600">
                        Всего работ: <span class="font-semibold text-gray-900">{{ pagination.totalItems }}</span>
                    </p>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:flex-wrap">
                        <span class="text-sm text-gray-500 sm:shrink-0">Сортировка:</span>
                        <USelect
                            v-model="sortBy"
                            :items="sortOptions"
                            size="sm"
                            class="w-full sm:w-48"
                        />
                        <USelect
                            v-model="sortOrder"
                            :items="sortOrderOptions"
                            size="sm"
                            class="w-full sm:w-36"
                        />
                    </div>
                </div>

                <UCollapsible v-model:open="filtersExpanded" class="w-full">
                    <UButton
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        class="mb-2"
                        trailing-icon="i-heroicons-chevron-down"
                        :ui="{ trailingIcon: filtersExpanded ? 'rotate-180' : '' }"
                    >
                        <span class="text-sm text-gray-500">Фильтры</span>
                    </UButton>

                    <template #content>
                        <div class="flex flex-wrap gap-3 mb-3">
                            <UFormField label="Месяц" class="w-full sm:w-40">
                                <USelect
                                    v-model="month"
                                    :items="monthOptions"
                                    size="sm"
                                    @update:model-value="onFilterChange"
                                />
                            </UFormField>

                            <UFormField label="Категория" class="w-full sm:w-48">
                                <USelect
                                    v-model="categoryId"
                                    :items="categoryOptions"
                                    size="sm"
                                    :loading="categoriesStatus === 'pending'"
                                    @update:model-value="onFilterChange"
                                />
                            </UFormField>

                            <UFormField label="Семестр" class="w-full sm:w-36">
                                <USelect
                                    v-model="semesterNumber"
                                    :items="semesterOptions"
                                    size="sm"
                                    @update:model-value="onFilterChange"
                                />
                            </UFormField>

                            <UFormField
                                label="Год семестра"
                                class="w-full sm:w-44"
                            >
                                <UInput
                                    v-model="semesterYearInput"
                                    type="number"
                                    min="2000"
                                    max="2100"
                                    placeholder="2026"
                                    size="sm"
                                    @change="onSemesterYearChange"
                                />
                            </UFormField>

                            <UButton
                                color="neutral"
                                variant="outline"
                                size="sm"
                                icon="i-heroicons-x-mark"
                                class="w-full self-end sm:w-auto"
                                @click="onResetFilters"
                            >
                                Сбросить
                            </UButton>
                        </div>
                    </template>
                </UCollapsible>
            </div>

            <!-- Portfolio Grid -->
            <div v-if="portfolio.length > 0" class="flex flex-col space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 items-stretch">
                    <PortfolioCard
                        v-for="(item, index) in portfolio"
                        :key="item.id ?? index"
                        :item="item"
                        :show-delete="false"
                        @click="openPreview"
                        @image-error="onPortfolioCardImageError"
                    />
                </div>

                <ListPagination :pagination="pagination" @update:page="onPageChange" />
            </div>

            <!-- Empty State -->
            <UCard v-if="portfolio.length === 0" class="py-12">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <UIcon name="i-heroicons-folder-open" class="w-8 h-8 text-gray-400" />
                    </div>
                    <template v-if="hasActiveFilters">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ничего не найдено</h3>
                        <p class="text-sm text-gray-500 max-w-sm mb-4">
                            Попробуйте изменить параметры фильтрации
                        </p>
                        <UButton
                            color="primary"
                            icon="i-heroicons-x-mark"
                            @click="onResetFilters"
                        >
                            Сбросить фильтры
                        </UButton>
                    </template>
                    <template v-else>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Портфолио пусто</h3>
                        <p class="text-sm text-gray-500 max-w-sm">
                            У студента пока нет добавленных работ
                        </p>
                    </template>
                </div>
            </UCard>
        </div>

        <!-- Error State -->
        <UCard v-else-if="status === 'error'" class="mt-4 py-12">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <UIcon name="i-heroicons-exclamation-triangle" class="w-8 h-8 text-red-500" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Ошибка загрузки</h3>
                <p class="text-sm text-gray-500 mb-4">Не удалось загрузить портфолио</p>
                <UButton
                    color="primary"
                    icon="i-heroicons-arrow-path"
                    @click="loadData(buildUrl(pagination.currentPage ?? 1))"
                >
                    Попробовать снова
                </UButton>
            </div>
        </UCard>

        <PortfolioDetailModal
            v-model:open="showPreviewModal"
            :portfolio="previewItem"
            :api-path-storage="config.public.API_PATH_STORAGE"
        />
    </NuxtLayout>
</template>

<script setup>
import { staticImg } from '~/assets/img'

definePageMeta({
    layout: false,
    middleware: false,
});

const route = useRoute();
const router = useRouter();
const config = useRuntimeConfig();
const userId = computed(() => route.params.id);

const { status, portfolio, pagination, sortBy, sortOptions, sortOrder, sortOrderOptions,
    month, monthOptions, categoryId, categoryOptions, categoriesStatus, loadCategories,
    semesterNumber, semesterOptions, semesterYear, hasActiveFilters, resetFilters,
    loadData, buildUrl } = usePortfolioList(
    computed(() => route.params.id),
);
const { onPageChange, getInitialPage } = usePaginationRedirect(loadData, buildUrl);

const filtersExpanded = ref(false);
const semesterYearInput = ref('');

function onSemesterYearChange() {
    const val = parseInt(semesterYearInput.value, 10);
    if (isNaN(val)) {
        semesterYear.value = null;
    } else {
        semesterYear.value = val;
    }
    onFilterChange();
}

async function onFilterChange() {
    await nextTick();
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildUrl(1));
}

function onResetFilters() {
    resetFilters();
    semesterYearInput.value = '';
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildUrl(1));
}

watch(sortBy, () => {
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildUrl(1));
});

watch(sortOrder, () => {
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildUrl(1));
});

onMounted(() => {
    loadData(buildUrl(getInitialPage()));
    loadCategories();
});

const showPreviewModal = ref(false);
const previewItem = ref(null);

function openPreview(item) {
    previewItem.value = item;
    showPreviewModal.value = true;
}

function onPortfolioCardImageError(e) {
    const t = e.target
    if (t instanceof HTMLImageElement) t.src = staticImg.bgCard
}
</script>
