<template>
    <NuxtLayout name="student">
        <PointsInfoModal v-model:open="showInfoModal" />

        <PortfolioDetailModal
            v-model:open="showPortfolioModal"
            :portfolio="selectedPortfolio ?? undefined"
            :api-path-storage="config.public.API_PATH_STORAGE"
        />

        <!-- Loading State -->
        <div v-if="status === 'pending'" class="mt-4 space-y-4">
            <USkeleton class="h-12 w-full" />
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3">
                <USkeleton v-for="i in 6" :key="i" class="h-56 w-full" />
            </div>
        </div>

        <div v-if="status === 'success'" class="mt-4 flex flex-col space-y-2">
            <template v-if="!isBlackout">
                <UCollapsible v-model:open="addFormExpanded" class="w-full flex flex-col gap-3">
                    <UButton
                        color="neutral"
                        variant="outline"
                        class="w-full justify-between"
                        trailing-icon="i-heroicons-chevron-down"
                        :ui="{ trailingIcon: addFormExpanded ? 'rotate-180' : '' }"
                    >
                        <span class="flex items-center gap-3">
                            <UIcon
                                name="i-heroicons-plus-circle"
                                class="w-5 h-5 text-primary shrink-0"
                            />
                            <span class="text-lg font-semibold text-gray-900">Добавить портфолио</span>
                        </span>
                        <template #trailing>
                            <span class="text-sm text-primary font-medium">{{ addFormExpanded ? 'Свернуть' : 'Развернуть' }}</span>
                        </template>
                    </UButton>

                    <template #content>
                        <UCard class="border border-default shadow-sm">
                            <UForm :state="formData" class="flex flex-col space-y-4" @submit="upload">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <UFormField
                                        label="Название"
                                        required
                                        class="md:col-span-2"
                                    >
                                        <UInput
                                            v-model="formData.title"
                                            placeholder="Введите название портфолио (минимум 3 символа)"
                                            class="w-full"
                                            icon="i-heroicons-document-text"
                                            :maxlength="255"
                                        />
                                    </UFormField>

                                    <UFormField
                                        label="Файл"
                                        required
                                        description="или ссылка"
                                    >
                                        <UInput
                                            type="file"
                                            class="w-full"
                                            icon="i-heroicons-paper-clip"
                                            @change="imageHandler"
                                        />
                                        <template #hint>
                                            <span class="text-xs text-gray-500">PDF, JPG, JPEG, PNG, DOC, DOCX (макс. 10 МБ)</span>
                                        </template>
                                    </UFormField>

                                    <UFormField
                                        label="Ссылка"
                                        required
                                        description="или файл"
                                    >
                                        <UInput
                                            v-model="formData.link"
                                            type="url"
                                            placeholder="https://example.com"
                                            class="w-full"
                                            icon="i-heroicons-link"
                                            :maxlength="1000"
                                        />
                                    </UFormField>
                                </div>

                                <UFormField
                                    label="Описание"
                                >
                                    <UTextarea
                                        v-model="formData.description"
                                        placeholder="Опишите, что это за работа или достижение"
                                        :rows="3"
                                        class="w-full"
                                        :maxlength="1000"
                                    />
                                    <template #hint>
                                        <span class="text-xs text-gray-500">Максимум 1000 символов</span>
                                    </template>
                                </UFormField>

                                <UFormField label="Мероприятие">
                                    <USelectMenu
                                        v-model="selectedActivity"
                                        :items="activityOptions"
                                        placeholder="Выберите мероприятие (необязательно)"
                                        searchable
                                        search-placeholder="Поиск мероприятий..."
                                        class="w-full"
                                        :loading="activitiesStatus === 'pending'"
                                        icon="i-heroicons-calendar"
                                    >
                                        <template #item-label="{ item }">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ item.label }}</span>
                                                <span class="text-xs text-gray-500">{{ item.sublabel }}</span>
                                            </div>
                                        </template>
                                    </USelectMenu>
                                    <template #hint>
                                        <span class="text-xs text-gray-500">Привяжите файл к мероприятию, в котором участвуете</span>
                                    </template>
                                </UFormField>

                                <div class="flex justify-end gap-2 pt-2">
                                    <UButton
                                        type="button"
                                        color="neutral"
                                        variant="outline"
                                        @click="addFormExpanded = false"
                                    >
                                        Отмена
                                    </UButton>
                                    <UButton
                                        type="submit"
                                        color="primary"
                                        icon="i-heroicons-cloud-arrow-up"
                                        :disabled="!isFormValid || isUploading"
                                        :loading="isUploading"
                                    >
                                        Загрузить
                                    </UButton>
                                </div>
                            </UForm>
                        </UCard>
                    </template>
                </UCollapsible>
            </template>
            <template v-else>
                <UAlert
                    icon="i-heroicons-exclamation-triangle"
                    color="warning"
                    variant="soft"
                    title="Загрузка временно недоступна"
                    :description="`Загрузка работ в портфолио доступна только во время учебного семестра ${dynamicSemesterDatesAlert}.`"
                />
            </template>

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
                        :key="index"
                        :item="item"
                        :show-delete="true"
                        @click="openPortfolioModal"
                        @delete="destroy"
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
                        <p class="text-sm text-gray-500 max-w-sm mb-4">
                            Добавьте свои достижения, сертификаты и работы, чтобы отслеживать прогресс
                        </p>
                        <UButton
                            color="primary"
                            icon="i-heroicons-plus"
                            @click="addFormExpanded = true"
                        >
                            Добавить работу
                        </UButton>
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
    </NuxtLayout>
</template>

<script setup>
import { staticImg } from '~/assets/img'
import { computeScoringSemestersFromBlackout } from '~/utils/scoringSemesterRanges';

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['student'],
    layout: false,
});

const { user } = useSanctumAuth();
const { get, post, delete: del } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();
const route = useRoute();
const router = useRouter();
const config = useRuntimeConfig();
const { getMyActivitiesPage, attachPortfolioToActivity } = useStudentPortfolioApi()

const { status, portfolio, pagination, sortBy, sortOptions, sortOrder, sortOrderOptions,
    month, monthOptions, categoryId, categoryOptions, categoriesStatus, loadCategories,
    semesterNumber, semesterOptions, semesterYear, hasActiveFilters, resetFilters,
    loadData, buildUrl } = usePortfolioList(
    computed(() => user.value?.user_id),
);
const { onPageChange, getInitialPage } = usePaginationRedirect(loadData, (page) => buildUrl(page ?? 1));

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

const isBlackout = ref(false);
const dynamicSemesterDatesAlert = ref('');

async function checkBlackout() {
    try {
        const response = await get('/api/v1/scoring-blackout');
        const ranges = computeScoringSemestersFromBlackout(response?.data ?? {});
        if (!ranges) {
            isBlackout.value = false;
            return;
        }

        const now = new Date();
        const inFirst = now >= ranges.first.start && now <= ranges.first.end;
        const inSecond = now >= ranges.second.start && now <= ranges.second.end;
        
        isBlackout.value = !(inFirst || inSecond);

        const { firstLine, secondLine } = formatScoringSemesterSummaryLines(ranges);
        dynamicSemesterDatesAlert.value = `(${firstLine} или ${secondLine})`;
    } catch {
        isBlackout.value = false;
    }
}

onMounted(() => {
    loadData(buildUrl(getInitialPage() ?? 1));
    loadActivities();
    loadCategories();
    checkBlackout();
    showInfoModal.value = true;
});

const isUploading = ref(false);
const showInfoModal = ref(false);
const addFormExpanded = ref(false);
const selectedActivity = ref(undefined);
const showPortfolioModal = ref(false);
const selectedPortfolio = ref(null);

function onPortfolioCardImageError(e) {
    const t = e.target
    if (t instanceof HTMLImageElement) t.src = staticImg.bgCard
}

// Computed property for activity options in SelectMenu format
const activityOptions = computed(() =>
    activities.value.map((activity) => ({
        value: Number(activity.id ?? 0),
        label: activity.title ?? '',
        sublabel: `${activity.date_start ?? ''} - ${activity.date_end ?? ''} • ${activity.planet_name ?? ''}`,
    })),
);

// Watch for changes in selectedActivity to update formData
watch(selectedActivity, (newValue) => {
    formData.value.activity_id = newValue?.value ?? null;
});

// Watch for sort changes and reload data
watch(sortBy, () => {
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildUrl(1));
});

watch(sortOrder, () => {
    router.replace({ path: route.path, query: { ...route.query, page: '1' } });
    loadData(buildUrl(1));
});

const formData = ref({
    title: "",
    link: "",
    description: "",
    activity_id: null,
});

const selectedFile = ref(null);

const isFormValid = computed(() => {
    const hasTitle = formData.value.title && formData.value.title.length >= 3;
    const hasContent = selectedFile.value || formData.value.link;
    return hasTitle && hasContent;
});
const activities = ref([]);
const activitiesStatus = ref("idle");

async function loadActivities() {
  activitiesStatus.value = "pending";

  try {
    const userId = user.value?.user_id;
    if (userId == null) throw new Error('User ID not found');

    const allActivities = [];
    let currentPage = 1;
    let hasMorePages = true;

    // Загружаем все страницы пагинации
  while (hasMorePages) {
      const data = await getMyActivitiesPage(userId, currentPage)
      allActivities.push(...data.items)

      // Проверяем, есть ли еще страницы
      if ((data.currentPage ?? 0) >= (data.lastPage ?? 0)) {
        hasMorePages = false
      } else {
        currentPage++
      }
    }

    // Оставляем только мероприятия в статусах, подходящих под ТЗ:
    // scheduled (Запланировано), started (В процессе), completed (Завершено)
    const allowedStatuses = ['scheduled', 'started', 'completed'];
    activities.value = allActivities.filter(a =>
        !a.status_eng || allowedStatuses.includes(a.status_eng)
    );
    activitiesStatus.value = "success";
  } catch {
    activitiesStatus.value = "error";
  }
}

// Initialize selectedActivity based on formData when activities are loaded
watch([activities, () => formData.value.activity_id], ([newActivities, activityId]) => {
  if (newActivities && activityId) {
    const activity = newActivities.find(a => a.id === activityId);
    if (activity) {
      selectedActivity.value = {
          value: Number(activityId),
          label: activity.title ?? '',
          sublabel: `${activity.date_start ?? ''} - ${activity.date_end ?? ''} • ${activity.planet_name ?? ''}`,
      };
  }
}
});


function upload() {
    isUploading.value = true;

    const uploadForm = new FormData();

    // Добавляем файл только если он выбран
    if (selectedFile.value) {
        uploadForm.append("file", selectedFile.value);
    }

    uploadForm.append("title", formData.value.title);

    // Add link if provided
    if (formData.value.link) {
        uploadForm.append("link", formData.value.link);
    }

    // Add activity_id if selected
    if (formData.value.activity_id) {
        uploadForm.append("activity_id", String(formData.value.activity_id));
    }

    // Add description if provided
    if (formData.value.description) {
        uploadForm.append("description", formData.value.description);
    }

    post("/api/v1/student/portfolio/store", uploadForm)
        .then(async (data) => {
            status.value = "success";
            toast.add({ title: data?.message ?? '' });

            // If activity was selected, attach portfolio to activity
            const portfolioId = data?.portfolio_id || data?.data?.portfolio_id;
            if (formData.value.activity_id != null && portfolioId) {
                const activityId = formData.value.activity_id
                try {
                    await attachPortfolioToActivity({ portfolio_id: portfolioId, activity_id: activityId })
                    toast.add({
                        title: "Файл загружен и привязан к мероприятию",
                        color: "success",
                    });
                } catch {
                    toast.add({
                        title: "Файл загружен, но не удалось привязать к мероприятию",
                        color: "warning",
                    });
                }
            }

            // Reset form
            formData.value = {
                title: "",
                link: "",
                description: "",
                activity_id: null,
            };
            selectedActivity.value = undefined;
            selectedFile.value = null;
            addFormExpanded.value = false;
            // Reset file input
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                if (fileInput instanceof HTMLInputElement) fileInput.value = "";
            }
        })
        .catch((error) => {
            status.value = "error";
            showApiError(error, 'Произошла ошибка при загрузке портфолио');
        })
        .finally(() => {
            isUploading.value = false;
            status.value = "idle";
            loadData(pagination.value.currentPage ?? 1);
        });
}

async function destroy(id) {
    if (confirm("Вы уверены, что хотите удалить данный файл?")) {
        try {
            // First detach from activity if attached
            try {
                await post("/api/v1/student/portfolio/detach-from-activity", {
                    portfolio_id: Number(id),
                });
            } catch {
                // Continue with deletion even if detach fails
            }

            // Then delete the portfolio
            const data = await del("/api/v1/student/portfolio/destroy/" + id);
            status.value = "success";
            loadData(pagination.value.currentPage);
            toast.add({ title: data?.message ?? '' });
        } catch (error) {
            status.value = "error";
            toast.add({
                title:
                    error?.response?._data?.message ||
                    "Произошла ошибка при удалении",
                color: "error",
            });
        }
    }
}

function imageHandler(e) {
    const target = e.target;
    const file = target instanceof HTMLInputElement ? target.files?.[0] : undefined;
    if (file) {
        selectedFile.value = file;
    }
}

function openPortfolioModal(item) {
    selectedPortfolio.value = item;
    showPortfolioModal.value = true;
}

</script>
