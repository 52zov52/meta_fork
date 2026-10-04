<template>
  <div>
    <div v-if="props.loading && !props.analyticsData" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary" />
    </div>

    <template v-else-if="props.analyticsData">
      <div v-if="props.showEducationLevelFilter" class="flex flex-wrap gap-3 items-end mb-6">
        <div v-if="props.showEducationLevelFilter" class="flex flex-col gap-1">
          <label class="text-xs font-medium text-muted">Уровень образования</label>
          <USelect
            v-model="educationLevelId"
            :items="props.educationLevelItems"
            placeholder="Все уровни"
            class="w-full md:w-56"
            size="sm"
            @change="loadAnalytics"
          />
        </div>
      </div>

      <div class="relative">
        <div v-if="props.loading" class="absolute inset-0 z-10 flex items-start justify-center pt-8 bg-elevated/40 backdrop-blur-[1px] rounded-lg">
          <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary" />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6">
          <AnalyticsChartCard title="Сумма баллов по академиям/институтам" :total="props.totalScore" height-class="md:h-[32rem]" snapshot-filename="rating-score-by-academy.png">
            <AnalyticsChartHorizontal
              :data="props.scoreByAcademyAndCourse"
              :loading="props.chart31Loading"
              :colors="props.courseColors"
              :legends="props.courseLegends"
              :legend-totals="props.courseTotals"
              show-row-totals
            />
          </AnalyticsChartCard>

          <AnalyticsChartCard title="Баллы по академиям с распределением по категориям" height-class="md:h-[32rem]" snapshot-filename="rating-score-by-category.png">
            <AnalyticsChartHorizontal
              :data="props.scoreByAcademyCategoryGrouped"
              :loading="props.chart35Loading"
              :colors="props.categoryColors"
              :legends="props.categoryLegends"
              :legend-totals="props.categoryTotals"
              show-row-totals
              value-key="total"
            />
          </AnalyticsChartCard>

          <AnalyticsChartCard title="Распределение мероприятий по категориям" height-class="md:h-[26rem]" snapshot-filename="rating-events-by-category.png">
            <template #header-actions>
              <USelect
                v-model="academyFilter33"
                :items="props.academySelectItems"
                placeholder="Все академии"
                size="xs"
                class="w-32 md:w-40"
              />
            </template>
            <AnalyticsChart3
              :data="props.eventsByCategoryBar"
              :total="props.totalEvents"
              :loading="props.chart33Loading"
              :colors="props.eventCategoryColors"
            />
          </AnalyticsChartCard>

          <AnalyticsChartCard title="Распределение мероприятий по академиям / институту" height-class="md:h-[26rem]" snapshot-filename="rating-events-by-academy.png">
            <template #header-actions>
              <ClientOnly>
                <UPopover :content="{ side: 'left', align: 'start' }">
                  <button
                    type="button"
                    class="p-1 rounded-md hover:bg-muted text-muted hover:text-default"
                    aria-label="Как считается распределение мероприятий по академиям / институту"
                  >
                    <UIcon name="i-heroicons-information-circle" class="w-4 h-4 md:w-5 md:h-5" />
                  </button>
                  <template #content>
                    <div class="max-w-sm p-3 md:p-4 text-left text-sm space-y-3">
                      <p class="font-medium text-default">Распределение мероприятий по академиям / институту</p>
                      <p class="text-muted leading-relaxed">
                        Учитываются мероприятия текущего семестра с хотя бы одним подтверждённым участником — активированным студентом.
                      </p>
                      <p class="text-muted leading-relaxed">
                        Если у мероприятия указана академия, оно идёт в неё. Если академия не указана, но создатель — наставник, берётся его академия.
                      </p>
                      <p class="text-muted leading-relaxed">
                        Общие мероприятия в график не попадают.
                      </p>
                    </div>
                  </template>
                </UPopover>
              </ClientOnly>
            </template>
            <AnalyticsChart5
              :data="props.eventsByAcademyBar"
              :total="props.totalEventsByAcademy"
              :loading="props.chart34Loading"
            />
          </AnalyticsChartCard>

          <div class="xl:col-span-2">
            <AnalyticsChartCard title="Вовлеченность по академиям/институтам" height-class="md:h-[26rem]" snapshot-filename="rating-engagement-by-academy.png">
              <AnalyticsChart2
                :data="props.engagementGroupedData"
                :loading="props.chart32Loading"
              />
            </AnalyticsChartCard>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="flex justify-center items-center py-12">
      <p class="text-muted">Не удалось загрузить данные аналитики</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  loading: { type: Boolean, default: false },
  analyticsData: { type: Object, default: null },
  showEducationLevelFilter: { type: Boolean, default: false },
  educationLevelItems: { type: Array, default: () => [] },
  educationLevelId: { type: [String, Number], default: null },
  totalScore: { type: Number, default: 0 },
  scoreByAcademyAndCourse: { type: Array, default: () => [] },
  chart31Loading: { type: Boolean, default: false },
  courseColors: { type: Array, default: () => [] },
  courseLegends: { type: Array, default: () => [] },
  courseTotals: { type: Array, default: () => [] },
  engagementGroupedData: { type: Array, default: () => [] },
  chart32Loading: { type: Boolean, default: false },
  academyFilter33: { type: [String, Number], default: null },
  academySelectItems: { type: Array, default: () => [] },
  eventsByCategoryBar: { type: Array, default: () => [] },
  totalEvents: { type: Number, default: 0 },
  chart33Loading: { type: Boolean, default: false },
  eventCategoryColors: { type: Array, default: () => [] },
  eventsByAcademyBar: { type: Array, default: () => [] },
  totalEventsByAcademy: { type: Number, default: 0 },
  chart34Loading: { type: Boolean, default: false },
  scoreByAcademyCategoryGrouped: { type: Array, default: () => [] },
  chart35Loading: { type: Boolean, default: false },
  categoryColors: { type: Array, default: () => [] },
  categoryLegends: { type: Array, default: () => [] },
  categoryTotals: { type: Array, default: () => [] },
});

const emit = defineEmits([
  'update:educationLevelId',
  'update:academyFilter33',
  'loadAnalytics',
  'loadChart33',
]);

const educationLevelId = computed({
  get: () => props.educationLevelId,
  set: value => emit('update:educationLevelId', value),
});

const academyFilter33 = computed({
  get: () => props.academyFilter33,
  set: value => emit('update:academyFilter33', value),
});

function loadAnalytics() {
  emit('loadAnalytics');
}

watch(academyFilter33, (value, oldValue) => {
  if (value === oldValue || !props.analyticsData) return;
  emit('loadChart33', value);
});
</script>
