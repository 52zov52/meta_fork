<template>
  <div>
    <PointsInfoModal v-model:open="showInfoModal" />

    <NuxtLayout name="public">
      <template #header>
        <div class="flex flex-row bg-white border border-default px-4 lg:px-32 py-4">
          <h2 class="text-base md:text-xl font-semibold">Рейтинги по результатам семестра</h2>
        </div>
      </template>

      <div class="w-full px-4 lg:px-32 py-4">
        <div class="flex flex-wrap gap-3 items-end mb-6">
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted">Уровень образования</label>
            <USelect
              v-model="filterEducationLevelId"
              :items="educationLevelSelectItems"
              placeholder="Все уровни"
              class="w-56"
              size="sm"
              @change="onFilterChange"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted">Курсы</label>
            <USelect
              v-model="courseFilter31"
              :items="courseFilterItems"
              placeholder="Все курсы"
              class="w-44"
              size="sm"
              @change="onFilterChange"
            />
          </div>
        </div>

        <!-- Вкладки -->
        <UTabs v-model="activeTab" :items="tabItems" :default-value="0" class="w-full">
          <template #content>
            <!-- Вкладка: Рейтинг пользователей -->
            <div v-if="activeTab === 0" class="mt-4">
              <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
                <div class="text-center">
                  <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
                  <p class="text-muted">Загрузка рейтинга...</p>
                </div>
              </div>

              <div v-if="status === 'success'" class="space-y-6 border border-default rounded-md">
                <div v-if="usersRating.length != 0" class="bg-white rounded-xl shadow-lg p-4 lg:p-6">
                  <div class="hidden lg:block">
                    <table class="w-full text-sm">
                      <thead>
                        <tr>
                          <th class="w-16 border-b border-default px-4 py-2 text-center font-semibold">Место</th>
                          <th class="w-auto border-b border-default px-4 py-2 text-left font-semibold">ФИО</th>
                          <th class="w-32 border-b border-default px-4 py-2 text-left font-semibold">Академия</th>
                          <th class="w-20 border-b border-default px-4 py-2 text-center font-semibold">Баллы</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="item in usersRating"
                          :key="item.user_id ?? item.id"
                          class="hover:bg-zinc-950/2.5"
                        >
                          <td class="w-16 border-b border-default px-4 py-2 text-center">
                            <UIcon v-if="placeMedalClass(item)" name="i-lucide-medal" class="size-6" :class="placeMedalClass(item)" />
                            <span v-else>{{ item.place }}</span>
                          </td>
                          <td class="w-auto border-b border-default px-4 py-2 text-left font-medium">
                            <NuxtLink
                              :to="isCurrentUser(item) ? '/student' : `/student/profile/${item.user_id ?? item.id}`"
                              target="_blank"
                              rel="noopener noreferrer"
                              class="block p-0 h-auto font-medium text-primary hover:text-primary/80 hover:underline cursor-pointer"
                            >
                              {{ item.fio }}
                            </NuxtLink>
                          </td>
                          <td class="w-32 border-b border-default px-4 py-2 text-left text-default">
                            {{ item.academy_short_name || item.academy_name || '—' }}
                          </td>
                          <td class="w-20 border-b border-default px-4 py-2 text-center">
                            <span class="font-bold text-primary">{{ Math.round(item.score || 0) }}</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="lg:hidden grid gap-2">
                    <div
                      v-for="item in usersRating"
                      :key="item.user_id ?? item.id"
                      class="flex items-center gap-3 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-3 text-sm"
                    >
                      <div class="flex size-9 shrink-0 items-center justify-center">
                        <UIcon v-if="placeMedalClass(item)" name="i-lucide-medal" class="size-6" :class="placeMedalClass(item)" />
                        <span v-else class="text-muted">{{ item.place }}</span>
                      </div>

                      <div class="min-w-0 flex-1">
                        <NuxtLink
                          :to="isCurrentUser(item) ? '/student' : `/student/profile/${item.user_id ?? item.id}`"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="block truncate p-0 h-auto font-medium text-primary hover:text-primary/80 hover:underline cursor-pointer"
                        >
                          {{ item.fio }}
                        </NuxtLink>
                        <span class="block truncate text-xs text-muted">
                          {{ item.academy_short_name || item.academy_name || '—' }}
                        </span>
                      </div>

                      <div class="shrink-0 text-right">
                        <span class="block text-xs text-muted">Баллы</span>
                        <span class="block text-base font-bold text-primary">{{ Math.round(item.score || 0) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="flex justify-end mt-4">
                    <ListPagination :pagination="pagination" @update:page="onPageChange" />
                  </div>
                </div>

                <UAlert
                  v-if="usersRating.length == 0"
                  icon="i-heroicons-information-circle"
                  color="info"
                  variant="solid"
                  title="Информация"
                  description="Рейтинг пользователей отсутствует"
                />
              </div>
            </div>

            <!-- Вкладка: Общая статистика -->
            <div v-if="activeTab === 1" class="mt-4">
              <RatingAnalyticsCharts
                :loading="analyticsLoading"
                :analytics-data="analyticsData"
                :total-score="totalScore"
                :score-by-academy-and-course="chartScoreByAcademyAndCourse"
                :chart31-loading="chart31Loading"
                :course-colors="courseColors"
                :course-legends="courseLegends"
                :course-totals="courseTotals"
                :engagement-grouped-data="chartEngagementGroupedData"
                :chart32-loading="chart32Loading"
                :academy-filter33="academyFilter33"
                :academy-select-items="academySelectItems"
                :events-by-category-bar="chartEventsByCategoryBar"
                :total-events="totalEvents"
                :chart33-loading="chart33Loading"
                :event-category-colors="eventCategoryColors"
                :events-by-academy-bar="chartEventsByAcademyBar"
                :total-events-by-academy="totalEventsByAcademy"
                :chart34-loading="chart34Loading"
                :score-by-academy-category-grouped="chartScoreByAcademyCategoryGrouped"
                :chart35-loading="chart35Loading"
                :category-colors="categoryColors"
                :category-legends="categoryLegends"
                :category-totals="categoryTotals"
                @update:academy-filter33="academyFilter33 = $event"
                @load-chart33="loadChart33"
            />
            </div>
          </template>
        </UTabs>
      </div>
    </NuxtLayout>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false,
  middleware: false,
});

const { user, isAuthenticated } = useSanctumAuth();
const showInfoModal = ref(false);
const { get } = useApi();
const { show: showApiError } = useApiError();

const categoryColors = ['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'];
const categoryNames = ['Проектная', 'Научная', 'Культурно-творческая', 'Общественная'];

const eventCategoryColors = ['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'];

const courseFilterItems = [
  { label: 'Все курсы', value: null },
  { label: '1 курс', value: 1 },
  { label: '2 курс', value: 2 },
  { label: '3 курс', value: 3 },
  { label: '4 курс', value: 4 },
];

const filterEducationLevelId = ref(null);

const courseFilter31 = ref(null);
const academyFilter33 = ref(null);

const courseColors = ['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'];
const courseLegends = ['1 курс', '2 курс', '3 курс', '4 курс'];

const chart31DataByCourse = ref({});

const academyOptions = ref([]);
const educationLevelOptions = ref([]);

const educationLevelSelectItems = computed(() => [
  { label: 'Все уровни', value: null },
  ...educationLevelOptions.value.map(l => ({ label: l.name, value: l.id })),
]);

const academySelectItems = computed(() => [
  { label: 'Все академии', value: null },
  ...academyOptions.value.map(a => ({ label: a.name, value: a.id })),
]);

const activeTab = ref(0);

const tabItems = [
  { label: 'Рейтинг пользователей', value: 0 },
  { label: 'Общая статистика', value: 1 },
];

const status = ref('idle');
const usersRating = ref([]);
const pagination = ref({
  currentPage: null,
  lastPage: null,
  prevPageUrl: null,
  nextPageUrl: null,
  perPage: null,
  total: null,
});

const analyticsData = ref(null);
const analyticsLoading = ref(false);

const chart31Data = ref(null);
const chart31Loading = ref(false);
const chart32Data = ref(null);
const chart32Loading = ref(false);
const chart33Data = ref(null);
const chart33Loading = ref(false);
const chart34Data = ref(null);
const chart34Loading = ref(false);
const chart35Data = ref(null);
const chart35Loading = ref(false);

const totalScore = computed(() => {
  return courseTotals.value.reduce((sum, v) => sum + v, 0);
});

function fillMissingAcademies(items, idKey, labelKey, defaults) {
  const allAcademies = academyOptions.value;
  if (!allAcademies.length) return items;

  const dataMap = {};
  for (const item of items) {
    dataMap[item[idKey]] = item;
  }

  const academies = allAcademies.filter(a => a.institution_id === 1);
  const institutions = allAcademies.filter(a => a.institution_id !== 1);
  const ordered = [...academies, ...institutions];

  return ordered.map((academy) => {
    const existing = dataMap[academy.id];
    const result = { ...defaults, ...existing };
    result[idKey] = academy.id;
    result[labelKey] = existing?.[labelKey] ?? academy.short_name ?? academy.name;
    return result;
  }).map((item, ix) => ({ ...item, ix }));
}

const chartScoreByAcademyAndCourse = computed(() => {
  const academyMap = {};

  for (const course of [1, 2, 3, 4]) {
    const data = chart31DataByCourse.value[course];
    const items = data?.score?.byAcademy ?? [];
    for (const item of items) {
      if (!academyMap[item.id]) {
        academyMap[item.id] = {
          x: item.label ?? '',
          values: [0, 0, 0, 0],
        };
      }
      academyMap[item.id].values[course - 1] = item.value ?? 0;
    }
  }

  const items = Object.entries(academyMap).map(([id, data]) => ({
    id: Number(id),
    ...data,
  }));

  return fillMissingAcademies(items, 'id', 'x', { values: [0, 0, 0, 0] });
});

const courseTotals = computed(() => {
  const totals = [0, 0, 0, 0];
  for (const course of [1, 2, 3, 4]) {
    const data = chart31DataByCourse.value[course];
    const items = data?.score?.byAcademy ?? [];
    for (const item of items) {
      totals[course - 1] += item.value ?? 0;
    }
  }
  return totals;
});

const totalEvents = computed(() => {
  const items = chartEventsByCategory.value;
  return items.reduce((sum, i) => sum + (i.count ?? 0), 0);
});

const totalEventsByAcademy = computed(() => {
  const items = chartEventsByAcademyRaw.value;
  return items.reduce((sum, i) => sum + (i.count ?? 0), 0);
});



const chartEngagementRaw = computed(() => {
  const data = courseFilter31.value ? chart32Data.value : analyticsData.value;
  return (data?.engagement?.byAcademy ?? []).map(item => ({
    academy_id: item.academy_id,
    academy_name: item.academy_name,
    active_students: item.active_students,
    total_students: item.total_students,
  }));
});

const chartEngagementGroupedData = computed(() => {
  const items = chartEngagementRaw.value.map(item => ({
    academy_id: item.academy_id,
    x: item.academy_name ?? '',
    values: [item.active_students ?? 0, item.total_students ?? 0],
  }));

  return fillMissingAcademies(items, 'academy_id', 'x', { values: [0, 0] });
});

const chartEventsByCategoryBar = computed(() => {
  const items = chartEventsByCategory.value;
  const order = ['Проектная', 'Научная', 'Культурно-творческая', 'Общественная'];
  const sorted = [...items].sort((a, b) => {
    const ai = order.indexOf(a.category_name);
    const bi = order.indexOf(b.category_name);
    return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
  });
  return sorted.map((item, ix) => ({
    x: item.category_name,
    y: item.count ?? 0,
    ix,
  }));
});

const chartEventsByCategory = computed(() => {
  const data = academyFilter33.value ? chart33Data.value : analyticsData.value;
  const items = data?.events?.byCategory ?? [];
  return items.map(item => ({
    category_name: item.category_name ?? '',
    count: item.count ?? 0,
    percent: item.percent ?? 0,
  }));
});

const chartEventsByAcademyBar = computed(() => {
  const items = chartEventsByAcademyRaw.value.map(item => ({
    academy_id: item.academy_id,
    x: item.academy_name ?? '',
    y: item.count ?? 0,
  }));

  return fillMissingAcademies(items, 'academy_id', 'x', { y: 0 });
});

const chartEventsByAcademyRaw = computed(() => {
  return analyticsData.value?.events?.byAcademy?.items ?? [];
});

const chartScoreByAcademyCategoryGrouped = computed(() => {
  const data = courseFilter31.value ? chart35Data.value : analyticsData.value;
  if (!data) return [];
  const items = data?.score?.byAcademyAndCategory ?? [];
  const categoryOrder = ['Проектная', 'Научная', 'Культурно-творческая', 'Общественная'];

  const mapped = items.map(academy => {
    const categoriesMap = {};
    for (const cat of academy.categories ?? []) {
      categoriesMap[cat.label] = cat.value ?? 0;
    }
    const values = categoryOrder.map(cat => categoriesMap[cat] ?? 0);
    return {
      academy_id: academy.id,
      x: academy.label ?? '',
      values,
    };
  });

  return fillMissingAcademies(mapped, 'academy_id', 'x', { values: [0, 0, 0, 0] });
});

const categoryLegends = computed(() => categoryNames);

const categoryTotals = computed(() => {
  const data = courseFilter31.value ? chart35Data.value : analyticsData.value;
  if (!data) return [];
  const items = data?.score?.byAcademyAndCategory ?? [];
  const totals = {};
  for (const item of items) {
    for (const cat of item.categories ?? []) {
      totals[cat.label] = (totals[cat.label] ?? 0) + (cat.value ?? 0);
    }
  }
  return categoryNames.map(name => totals[name] ?? 0);
});

function buildRatingUrl(page) {
  const params = new URLSearchParams();
  params.set('page', page);
  if (filterEducationLevelId.value) params.set('education_level_id', filterEducationLevelId.value);
  if (courseFilter31.value) params.set('course', courseFilter31.value);
  return `/api/v1/rating/users?${params.toString()}`;
}

function buildAnalyticsUrl(overrides = {}) {
  const params = new URLSearchParams();
  const educationLevelId = overrides.education_level_id ?? filterEducationLevelId.value;
  const academyId = overrides.academy_id ?? academyFilter33.value;
  const course = overrides.course ?? courseFilter31.value;
  if (educationLevelId) params.set('education_level_id', educationLevelId);
  if (academyId) params.set('academy_id', academyId);
  if (course) params.set('course', course);
  const qs = params.toString();
  return `/api/v1/rating/analytics${qs ? '?' + qs : ''}`;
}

function loadData(page) {
  if (activeTab.value === 0) {
    getUsersRating(buildRatingUrl(page));
  } else {
    loadAllAnalytics();
  }
}

function onFilterChange() {
  saveFiltersToStorage();
  if (activeTab.value === 0) {
    pagination.value.currentPage = 1;
    getUsersRating(buildRatingUrl(1));
  } else {
    loadAllAnalytics();
  }
}

function saveFiltersToStorage() {
  if (typeof window !== 'undefined') {
    localStorage.setItem('rating-filters', JSON.stringify({
      educationLevelId: filterEducationLevelId.value,
      activeTab: activeTab.value,
      courseFilter31: courseFilter31.value,
      academyFilter33: academyFilter33.value,
    }));
  }
}

function loadFiltersFromStorage() {
  if (typeof window !== 'undefined') {
    try {
      const saved = JSON.parse(localStorage.getItem('rating-filters'));
      if (saved) {
        filterEducationLevelId.value = saved.educationLevelId ?? null;
        activeTab.value = saved.activeTab ?? 0;
        courseFilter31.value = saved.courseFilter31 ?? null;
        academyFilter33.value = saved.academyFilter33 ?? null;
      }
    } catch {
      // ignore parse errors
    }
  }
}

function getUsersRating(url) {
  status.value = 'pending';
  get(url).then((data) => {
    usersRating.value = data.data;
    pagination.value.currentPage = data.current_page;
    pagination.value.lastPage = data.last_page;
    pagination.value.prevPageUrl = data.prev_page_url || null;
    pagination.value.nextPageUrl = data.next_page_url || null;
    pagination.value.perPage = data.per_page;
    pagination.value.total = data.total ?? (data.last_page * data.per_page);
    status.value = 'success';
  }).catch(error => {
    status.value = 'error';
    showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
  });
}

async function loadAllAnalytics() {
  analyticsLoading.value = true;
  analyticsData.value = null;
  try {
    analyticsData.value = await get(buildAnalyticsUrl());
  } catch (error) {
    showApiError(error, 'Произошла ошибка при загрузке аналитики.');
  } finally {
    analyticsLoading.value = false;
  }
  loadChart31();
  loadChart32();
  loadChart33();
  loadChart34();
  loadChart35();
}

async function loadChart31() {
  chart31Loading.value = true;
  chart31DataByCourse.value = {};
  try {
    const courses = courseFilter31.value ? [courseFilter31.value] : [1, 2, 3, 4];
    const promises = courses.map(async (course) => {
      const data = await get(buildAnalyticsUrl({ course }));
      chart31DataByCourse.value[course] = data;
    });
    await Promise.all(promises);
  } catch {
    chart31DataByCourse.value = {};
  } finally {
    chart31Loading.value = false;
  }
}

async function loadChart32(course) {
  chart32Loading.value = true;
  chart32Data.value = null;
  try {
    await nextTick();
    chart32Data.value = await get(buildAnalyticsUrl({ course: course ?? courseFilter31.value }));
  } catch {
    chart32Data.value = null;
  } finally {
    chart32Loading.value = false;
  }
}

async function loadChart33(academyId) {
  chart33Loading.value = true;
  chart33Data.value = null;
  try {
    await nextTick();
    chart33Data.value = await get(buildAnalyticsUrl({ academy_id: academyId ?? academyFilter33.value }));
  } catch {
    chart33Data.value = null;
  } finally {
    chart33Loading.value = false;
  }
}

async function loadChart34() {
  chart34Loading.value = true;
  chart34Data.value = null;
  try {
    chart34Data.value = await get(buildAnalyticsUrl());
  } catch {
    chart34Data.value = null;
  } finally {
    chart34Loading.value = false;
  }
}

async function loadChart35(course) {
  chart35Loading.value = true;
  chart35Data.value = null;
  try {
    await nextTick();
    chart35Data.value = await get(buildAnalyticsUrl({ course: course ?? courseFilter31.value }));
  } catch {
    chart35Data.value = null;
  } finally {
    chart35Loading.value = false;
  }
}

function onPageChange(page) {
  getUsersRating(buildRatingUrl(page));
}

function isCurrentUser(userItem) {
  return isAuthenticated.value && (userItem.user_id === user.value?.user_id || userItem.id === user.value?.user_id);
}

function placeMedalClass(userItem) {
  if (pagination.value.currentPage !== 1) return '';

  return { 1: 'text-amber-500', 2: 'text-slate-400', 3: 'text-amber-700' }[Number(userItem.place)] ?? '';
}

onMounted(() => {
  showInfoModal.value = true;
  loadFiltersFromStorage();
  get('/api/v1/academies').then((data) => { academyOptions.value = data; });
  get('/api/v1/education-levels').then((data) => { educationLevelOptions.value = data; });
  loadData(1);
});

watch(activeTab, () => {
  saveFiltersToStorage();
  loadData(1);
});
</script>
