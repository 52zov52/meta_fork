<template>
    <div class="min-w-0 w-full flex flex-col gap-4">
        <div class="min-w-0 w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
            <div ref="usersKpiRef" :class="[kpiCardClass, kpiCardShellClass, 'relative']">
                <div data-snapshot-exclude class="absolute top-1.5 right-1.5 z-10 flex items-center gap-1">
                    <button
                        v-if="role === 'admin'"
                        type="button"
                        class="p-1 rounded hover:bg-muted"
                        aria-label="Скачать отчет по количеству участников"
                        @click="generateUsersReport"
                    >
                        <ClientOnly>
                            <UTooltip text="Скачать отчет по количеству участников" :content="{ side: 'top' }">
                                <UIcon name="i-heroicons-document-chart-bar" class="w-4 h-4 md:w-5 md:h-5 text-success" />
                            </UTooltip>
                        </ClientOnly>
                    </button>
                    <CardSnapshotButton
                        :target-el="usersKpiRef"
                        filename="dashboard-users-kpi.png"
                        tooltip="Скопировать карточку пользователей как изображение"
                        aria-label="Скопировать карточку пользователей как изображение"
                    />
                    <ClientOnly>
                        <UPopover :content="{ side: 'left', align: 'start' }">
                            <button
                                type="button"
                                class="p-1 rounded-md hover:bg-muted text-muted hover:text-default"
                                aria-label="Как считаются пользователи"
                            >
                                <UIcon name="i-heroicons-information-circle" class="w-4 h-4 md:w-5 md:h-5" />
                            </button>
                            <template #content>
                                <div class="max-w-sm p-3 md:p-4 text-left text-sm space-y-3">
                                    <p class="font-medium text-default">Пользователи</p>
                                    <p class="text-muted leading-relaxed">
                                        Все зарегистрированные студенты / из них
                                        <span class="text-default font-medium">активированные</span>.
                                        Период: <span class="text-default font-medium">всё время</span>.
                                    </p>
                                </div>
                            </template>
                        </UPopover>
                    </ClientOnly>
                </div>
                <div :class="kpiCardWithIconContentClass">
                    <h2 :class="kpiTitleClass">Пользователи</h2>
                    <span class="text-xl md:text-2xl tabular-nums min-w-0 max-w-full wrap-break-word leading-tight">{{ countActiveUsers }} / {{ countUsers }}</span>
                    <p class="text-[11px] md:text-xs text-muted min-w-0 wrap-break-word leading-tight">активные / всего</p>
                </div>
            </div>
            <div ref="semesterActivitiesKpiRef" :class="[kpiCardClass, kpiCardShellClass, 'relative']">
                <div data-snapshot-exclude class="absolute top-1.5 right-1.5 z-10 flex items-center gap-1">
                    <CardSnapshotButton
                        :target-el="semesterActivitiesKpiRef"
                        filename="dashboard-semester-activities-kpi.png"
                        tooltip="Скопировать карточку мероприятий как изображение"
                        aria-label="Скопировать карточку мероприятий как изображение"
                    />
                    <ClientOnly>
                        <UPopover :content="{ side: 'left', align: 'start' }">
                            <button
                                type="button"
                                class="p-1 rounded-md hover:bg-muted text-muted hover:text-default"
                                aria-label="Как считаются активные мероприятия в семестре"
                            >
                                <UIcon name="i-heroicons-information-circle" class="w-4 h-4 md:w-5 md:h-5" />
                            </button>
                            <template #content>
                              <div class="max-w-sm p-3 md:p-4 text-left text-sm space-y-3">
                                  <p class="font-medium text-default">Мероприятия в семестре</p>
                                  <p class="text-muted leading-relaxed">
                                      Мероприятия с <span class="text-default font-medium">датой окончания в текущем семестре</span>,
                                      кроме черновиков (<span class="font-mono text-xs">draft</span>).
                                  </p>
                                  <div>
                                      <p class="text-xs font-medium text-default uppercase tracking-wide mb-1.5">Активные / Всего</p>
                                      <ul class="text-muted text-sm space-y-1 list-disc pl-4">
                                          <li><span class="text-default font-medium">Всего</span> — запланированные, идущие, завершённые и отменённые</li>
                                          <li><span class="text-default font-medium">Активные</span> — только
                                              <span class="font-mono text-xs">scheduled</span> и <span class="font-mono text-xs">started</span>
                                          </li>
                                      </ul>
                                  </div>
                              </div>
                            </template>
                        </UPopover>
                    </ClientOnly>
                </div>
                <div :class="kpiCardWithIconContentClass">
                    <h2 :class="kpiTitleClass">Мероприятия в текущем семестре</h2>
                    <span class="text-xl md:text-2xl tabular-nums min-w-0 max-w-full wrap-break-word leading-tight">{{ countActiveActivitiesInCurrentSemester }} / {{ countActivitiesInCurrentSemester }}</span>
                    <p class="text-[11px] md:text-xs text-muted min-w-0 wrap-break-word leading-tight">активные / всего</p>
                </div>
            </div>
            <div ref="scoreKpiRef" :class="[kpiCardClass, kpiCardShellClass, 'relative']">
                <div data-snapshot-exclude class="absolute top-1.5 right-1.5 z-10 flex items-center gap-1">
                    <CardSnapshotButton
                        :target-el="scoreKpiRef"
                        filename="dashboard-score-kpi.png"
                        tooltip="Скопировать карточку баллов как изображение"
                        aria-label="Скопировать карточку баллов как изображение"
                    />
                    <ClientOnly>
                        <UPopover :content="{ side: 'left', align: 'start' }">
                            <button
                                type="button"
                                class="p-1 rounded-md hover:bg-muted text-muted hover:text-default"
                                aria-label="Как считается сумма баллов за учебный год"
                            >
                                <UIcon name="i-heroicons-information-circle" class="w-4 h-4 md:w-5 md:h-5" />
                            </button>
                            <template #content>
                                <div class="max-w-sm p-3 md:p-4 text-left text-sm space-y-3">
                                    <p class="font-medium text-default">Сумма баллов (семестр / год)</p>
                                    <p class="text-muted leading-relaxed">
                                        Сумма по <span class="text-default font-medium">подтверждённым</span> участиям
                                        <span class="text-default font-medium">активированных студентов</span> по системе.
                                        Считаются по <span class="font-mono text-xs">date_end</span>.
                                    </p>
                                    <div>
                                        <p class="text-xs font-medium text-default uppercase tracking-wide mb-1.5">Учебный год</p>
                                        <p class="text-muted leading-relaxed">
                                            {{ dynamicAcademicYearDates }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </UPopover>
                    </ClientOnly>
                </div>
                <div :class="kpiCardWithIconContentClass">
                    <h2 :class="kpiTitleClass">Сумма баллов</h2>
                    <span class="text-xl md:text-2xl min-w-0 max-w-full wrap-break-word leading-tight">{{ sumScoreBySemester }} / {{ sumScoreByStudyYear }}</span>
                    <p class="text-[11px] md:text-xs text-muted min-w-0 wrap-break-word leading-tight">семестр / уч. год</p>
                </div>
            </div>
        </div>
        <div ref="ratingAnalyticsSectionRef" :class="segmentSectionShellClass">
            <div :class="segmentSectionHeaderClass">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-[auto_1fr_auto] md:items-center relative">
                    <div class="flex flex-wrap justify-start gap-3 items-end text-left">
                        <div class="flex flex-col gap-1 text-left">
                            <label class="text-xs font-medium text-muted">Уровень образования</label>
                            <USelect
                                v-model="ratingEducationLevelId"
                                :items="ratingEducationLevelItems"
                                placeholder="Все уровни"
                                class="w-44"
                                size="sm"
                                @change="loadRatingAnalytics"
                            />
                        </div>

                        <div class="flex flex-col gap-1 text-left">
                            <label class="text-xs font-medium text-muted">Курсы</label>
                            <USelect
                                v-model="ratingCourseFilter"
                                :items="ratingCourseItems"
                                placeholder="Все курсы"
                                class="w-36"
                                size="sm"
                                @change="loadRatingAnalytics"
                            />
                        </div>
                    </div>

                    <h2 class="font-medium text-sm md:text-base text-balance text-center md:px-4 md:absolute md:left-1/2 md:-translate-x-1/2">
                        Показатели за текущий семестр
                    </h2>

                    <div data-snapshot-exclude class="flex items-center justify-start md:justify-end gap-1">
                        <CardSnapshotButton
                            :target-el="ratingAnalyticsSectionRef"
                            filename="dashboard-rating-analytics-section.png"
                            tooltip="Скопировать секцию аналитики как изображение"
                            aria-label="Скопировать секцию аналитики как изображение"
                        />
                        <ClientOnly>
                            <UPopover :content="{ side: 'left', align: 'start' }">
                                <button
                                    type="button"
                                    class="p-1 rounded-md hover:bg-muted text-muted hover:text-default"
                                    aria-label="Как считаются показатели за текущий семестр"
                                >
                                    <UIcon name="i-heroicons-information-circle" class="w-4 h-4 md:w-5 md:h-5" />
                                </button>
                                <template #content>
                                    <div class="max-w-sm p-3 md:p-4 text-left text-sm space-y-3">
                                        <p class="font-medium text-default">Текущий семестр</p>
                                        <p class="text-muted leading-relaxed whitespace-pre-line">
                                            {{ dynamicSemesterDates }}
                                        </p>
                                        <p class="text-muted leading-relaxed">
                                            На графиках учитываются только <span class="text-default font-medium">активированные студенты</span>.
                                        </p>
                                        <p class="text-muted leading-relaxed">
                                            <span class="text-default font-medium">Баллы</span> считаются только по
                                            <span class="text-default font-medium">подтверждённым</span> участием студентов в мероприятиях,
                                            которые закончились в этом семестре.
                                        </p>
                                        <p class="text-muted leading-relaxed">
                                            <span class="text-default font-medium">Сертификаты</span> считаются по дате добавления в семестр.
                                        </p>
                                    </div>
                                </template>
                            </UPopover>
                        </ClientOnly>
                    </div>
                </div>
            </div>
            <div :class="segmentSectionBodyClass">
                <RatingAnalyticsCharts
                    :loading="ratingLoading"
                    :analytics-data="ratingAnalyticsData"
                    :total-score="ratingTotalScore"
                    :score-by-academy-and-course="ratingScoreByAcademyAndCourse"
                    :chart31-loading="ratingChart31Loading"
                    :course-colors="ratingCourseColors"
                    :course-legends="ratingCourseLegends"
                    :course-totals="ratingCourseTotals"
                    :engagement-grouped-data="ratingEngagementData"
                    :chart32-loading="ratingChart32Loading"
                    :academy-filter33="ratingAcademyFilter33"
                    :academy-select-items="ratingAcademyItems"
                    :events-by-category-bar="ratingEventsByCategory"
                    :total-events="ratingTotalEvents"
                    :chart33-loading="ratingChart33Loading"
                    :event-category-colors="ratingEventCategoryColors"
                    :events-by-academy-bar="ratingEventsByAcademy"
                    :total-events-by-academy="ratingTotalEventsByAcademy"
                    :chart34-loading="ratingChart34Loading"
                    :score-by-academy-category-grouped="ratingScoreByCategory"
                    :chart35-loading="ratingChart35Loading"
                    :category-colors="['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe']"
                    :category-legends="['Проектная', 'Научная', 'Культурно-творческая', 'Общественная']"
                    :category-totals="ratingCategoryTotals"
                    @update:academy-filter33="ratingAcademyFilter33 = $event"
                    @load-chart33="loadRatingChart33"
                />
            </div>
        </div>
        <!-- Диаграммы аналитики (из публичного рейтинга) -->
        <div class="min-w-0 w-full flex flex-col gap-4">
            <div ref="activitiesDistributionSectionRef" class="relative min-w-0 w-full flex flex-col gap-4 bg-elevated border border-default border-t-2 border-t-primary rounded-lg shadow-sm p-3 md:p-4 text-center">

                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(18rem,1fr)_auto_minmax(18rem,1fr)] md:items-start">
                        <div class="flex flex-col items-start gap-2 text-left">
                            <div v-if="activitiesChartMode === 'year'" class="flex flex-wrap items-end gap-2 md:gap-3">
                                <div class="flex flex-col gap-1 text-left">
                                    <label class="text-[11px] font-medium text-muted">Год 1</label>
                                    <USelect
                                        v-model="selectedYearA"
                                        :items="academicYearSelectAItems"
                                        class="w-36"
                                        size="sm"
                                    />
                                </div>

                                <div class="flex flex-col gap-1 text-left">
                                    <label class="text-[11px] font-medium text-muted">Год 2</label>
                                    <USelect
                                        v-model="selectedYearB"
                                        :items="academicYearSelectBItems"
                                        :disabled="availableAcademicYears.length < 2"
                                        class="w-36"
                                        size="sm"
                                    />
                                </div>
                            </div>

                            <p v-if="activitiesChartMode === 'year' && availableAcademicYears.length < 2" class="text-[11px] text-muted">
                                Для сравнения двух лет нужен минимум один дополнительный учебный год.
                            </p>
                        </div>

                        <div class="flex flex-col items-center justify-start gap-3 md:pt-1">
                            <h2 class="font-medium text-sm md:text-base text-balance text-center">Распределение мероприятий</h2>

                            <div class="inline-flex p-0.5 bg-muted rounded-lg text-xs font-semibold select-none">
                                <button
                                    type="button"
                                    class="px-3 py-1 rounded-md transition-all duration-200"
                                    :class="activitiesChartMode === 'year' ? 'bg-elevated shadow-sm text-default' : 'text-muted hover:text-default'"
                                    @click="activitiesChartMode = 'year'"
                                >
                                    По учебным годам
                                </button>
                                <button
                                    type="button"
                                    class="px-3 py-1 rounded-md transition-all duration-200"
                                    :class="activitiesChartMode === 'semester' ? 'bg-elevated shadow-sm text-default' : 'text-muted hover:text-default'"
                                    @click="activitiesChartMode = 'semester'"
                                >
                                    По семестрам
                                </button>
                            </div>
                        </div>

                        <div data-snapshot-exclude class="flex items-center justify-start md:justify-end md:pt-0.5 gap-1">
                            <CardSnapshotButton
                                :target-el="activitiesDistributionSectionRef"
                                filename="dashboard-activities-distribution.png"
                                tooltip="Скопировать диаграмму мероприятий как изображение"
                                aria-label="Скопировать диаграмму мероприятий как изображение"
                            />
                            <ClientOnly>
                                <UPopover :content="{ side: 'left', align: 'start' }">
                                    <button
                                        type="button"
                                        class="p-1 rounded-md hover:bg-muted text-muted hover:text-default"
                                        aria-label="Как считается распределение мероприятий"
                                    >
                                        <UIcon name="i-heroicons-information-circle" class="w-4 h-4 md:w-5 md:h-5" />
                                    </button>
                                    <template #content>
                                        <div class="max-w-sm p-3 md:p-4 text-left text-sm space-y-3">
                                            <p class="font-medium text-default">
                                                {{ activitiesChartMode === 'semester' ? 'Распределение по семестрам' : 'Распределение по учебным годам' }}
                                            </p>
                                            <p class="text-muted leading-relaxed">
                                                <template v-if="activitiesChartMode === 'semester'">
                                                    Количество мероприятий по семестрам (Осень / Весна) в рамках <span class="text-default font-medium">текущего учебного года</span>.
                                                </template>
                                                <template v-else>
                                                    Количество мероприятий по учебным годам (на основе даты начала <span class="font-mono text-xs">date_start</span>).
                                                </template>
                                            </p>
                                        </div>
                                    </template>
                                </UPopover>
                            </ClientOnly>
                        </div>
                    </div>
                </div>

                <div v-if="activitiesDistributionData.length" class="min-w-0 w-full h-56 md:h-72 flex items-center justify-center pt-1 md:pt-2">
                    <VisSingleContainer
                        :data="activitiesDistributionData"
                        class="w-full h-full"
                        style="--vis-donut-central-label-font-size: 24px; --vis-donut-central-label-font-weight: 700; --vis-donut-central-label-text-color: #1e293b; --vis-donut-central-sub-label-font-size: 11px; --vis-donut-central-sub-label-font-weight: 500; --vis-donut-central-sub-label-text-color: #6b7280;"
                    >
                        <VisDonut
                            :value="d => d.value"
                            :color="(_, i) => donutColors[i % donutColors.length]"
                            :central-label="String(activitiesDistributionTotal)"
                            :central-sub-label="centralSubLabelText"
                            :arc-width="24"
                            :corner-radius="4"
                            :pad-angle="0.02"
                        />
                        <VisTooltip :triggers="donutTriggers" />
                    </VisSingleContainer>
                </div>

                <div v-if="activitiesDistributionData.length" class="flex flex-wrap justify-center gap-3 mt-2 w-full">
                    <div
                        v-for="(row, i) in activitiesDistributionData"
                        :key="row.label"
                        class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-full border text-xs font-semibold tabular-nums shadow-sm transition-all hover:scale-105 duration-200"
                        :style="{ borderColor: `${donutColors[i % donutColors.length]}35`, backgroundColor: `${donutColors[i % donutColors.length]}08` }"
                    >
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: donutColors[i % donutColors.length] }" />
                        <span class="text-default font-bold text-sm leading-none">{{ row.label }}</span>
                        <span class="text-muted/40 font-normal">|</span>
                        <span class="text-default font-bold">{{ row.value }}</span>
                        <span class="text-muted/80 text-[10px] font-normal">мероприятий</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold leading-none ml-1" :style="{ color: donutColors[i % donutColors.length], backgroundColor: `${donutColors[i % donutColors.length]}18` }">
                            {{ getActivitiesDistributionPercent(row.value) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { VisDonut, VisSingleContainer, VisTooltip } from '@unovis/vue';
import { Donut } from '@unovis/ts';
import { buildActivitiesDistributionForYears, getAvailableAcademicYears, syncSelectedAcademicYears } from '~/utils/dashboardActivitiesDistribution';
import { computeScoringSemestersFromBlackout, formatScoringSemesterSummaryLines, formatRuDayMonth } from '~/utils/scoringSemesterRanges';

const props = defineProps({
    endpoint: { type: String, required: true },
    role: { type: String, default: 'admin' },
});

const { get, getBlob } = useApi();

const dynamicAcademicYearDates = ref('Загрузка...');
const dynamicSemesterDates = ref('Загрузка...');
const usersKpiRef = ref(null);
const semesterActivitiesKpiRef = ref(null);
const scoreKpiRef = ref(null);
const ratingAnalyticsSectionRef = ref(null);
const activitiesDistributionSectionRef = ref(null);

const segmentSectionShellClass =
    'min-w-0 w-full flex flex-col rounded-lg border border-default border-t-2 border-t-primary bg-elevated shadow-sm overflow-hidden ring-1 ring-default/40';
const segmentSectionHeaderClass =
    'px-4 md:px-5 py-3 border-b border-default bg-primary/10';
const segmentSectionBodyClass =
    'min-w-0 w-full p-3 md:p-4';

const kpiCardClass =
    'min-w-0 flex flex-col gap-1 bg-elevated border border-default border-t-2 border-t-primary rounded-lg shadow-sm py-2.5 px-3 md:py-3 md:px-3.5 text-center';

const kpiCardShellClass = 'min-h-24 md:min-h-28 justify-center';

const kpiTitleClass =
    'font-medium text-xs md:text-sm text-balance wrap-break-word min-w-0 leading-snug';

const kpiCardWithIconContentClass =
    'flex flex-col min-h-0 min-w-0 w-full px-9 md:px-10 text-center gap-1';
const countUsers = ref(0);
const countActiveUsers = ref(0);
const countActivitiesInCurrentSemester = ref(0);
const countActiveActivitiesInCurrentSemester = ref(0);
const sumScoreByStudyYear = ref(0);
const sumScoreBySemester = ref(0);
const countActivitiesByYears = ref([]);
const countActivitiesBySemesters = ref([]);
const activitiesChartMode = ref('semester');
const selectedYearA = ref(null);
const selectedYearB = ref(null);

const availableAcademicYears = computed(() => getAvailableAcademicYears(countActivitiesByYears.value));

const academicYearSelectItems = computed(() => {
    return availableAcademicYears.value.map(year => ({
        label: year.label,
        value: year.value,
    }));
});

const academicYearSelectAItems = computed(() => {
    return academicYearSelectItems.value.filter(item => item.value === selectedYearA.value || item.value !== selectedYearB.value);
});

const academicYearSelectBItems = computed(() => {
    return academicYearSelectItems.value.filter(item => item.value === selectedYearB.value || item.value !== selectedYearA.value);
});

watch(availableAcademicYears, (years) => {
    const nextSelection = syncSelectedAcademicYears(years, selectedYearA.value, selectedYearB.value);

    selectedYearA.value = nextSelection.selectedYearA;
    selectedYearB.value = nextSelection.selectedYearB;
}, { immediate: true });

const activitiesDistributionData = computed(() => {
    if (activitiesChartMode.value === 'semester') {
        return countActivitiesBySemesters.value.map(item => ({
            label: String(item?.label ?? item?.semester ?? ''),
            value: Number(item?.count ?? 0),
        }));
    }

    return buildActivitiesDistributionForYears(
        availableAcademicYears.value,
        selectedYearA.value,
        selectedYearB.value,
    );
});

const donutColors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#7c3aed', '#1e40af', '#ea580c', '#eab308'];

const activitiesDistributionTotal = computed(() => {
    return activitiesDistributionData.value.reduce((sum, d) => sum + d.value, 0);
});

const centralSubLabelText = computed(() => {
    return activitiesChartMode.value === 'semester' ? 'За уч. год' : 'Выбранные годы';
});

function getActivitiesDistributionPercent(value) {
    const total = activitiesDistributionTotal.value;
    if (!total) return '0.0%';
    return `${((value / total) * 100).toFixed(1)}%`;
}

const donutTriggers = computed(() => {
    const data = activitiesDistributionData.value;
    const total = activitiesDistributionTotal.value;
    return {
        [Donut.selectors.segment]: (d) => {
            const val = d?.value ?? 0;
            const item = data.find(x => x.value === val);
            const label = d?.label ?? item?.label ?? '';
            const pct = total ? ((val / total) * 100).toFixed(1) : '0.0';
            return `${label}<br>${val} мероприятий<br>${pct}%`;
        },
    };
});

// --- Состояние для графиков из рейтинга ---
const ratingAnalyticsData = ref(null);
const ratingLoading = ref(false);
const ratingCourseFilter = ref(null);
const ratingAcademyOptions = ref([]);
const ratingEducationLevelId = ref(null);
const ratingEducationLevelOptions = ref([]);
const ratingAcademyFilter33 = ref(null);
const ratingChart31DataByCourse = ref({});
const ratingChart31Loading = ref(false);
const ratingChart32Data = ref(null);
const ratingChart32Loading = ref(false);
const ratingChart33Data = ref(null);
const ratingChart33Loading = ref(false);
const ratingChart34Loading = ref(false);
const ratingChart35Data = ref(null);
const ratingChart35Loading = ref(false);
const ratingCourseColors = ['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'];
const ratingCourseLegends = ['1 курс', '2 курс', '3 курс', '4 курс'];
const ratingEventCategoryColors = ['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'];
const ratingCategoryNames = ['Проектная', 'Научная', 'Культурно-творческая', 'Общественная'];
const ratingCourseItems = [
    { label: 'Все курсы', value: null },
    { label: '1 курс', value: 1 },
    { label: '2 курс', value: 2 },
    { label: '3 курс', value: 3 },
    { label: '4 курс', value: 4 },
];
const ratingAcademyItems = computed(() => [
    { label: 'Все академии', value: null },
    ...ratingAcademyOptions.value.map(a => ({ label: a.name, value: a.id })),
]);

const ratingEducationLevelItems = computed(() => [
    { label: 'Все уровни', value: null },
    ...ratingEducationLevelOptions.value.map(l => ({ label: l.name, value: l.id })),
]);

function fillMissingAcademies(items, idKey, labelKey, defaults) {
    const allAcademies = ratingAcademyOptions.value;
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

const ratingTotalScore = computed(() => {
    return ratingCourseTotals.value.reduce((sum, v) => sum + v, 0);
});

const ratingScoreByAcademyAndCourse = computed(() => {
    const academyMap = {};

    for (const course of [1, 2, 3, 4]) {
        const data = ratingChart31DataByCourse.value[course];
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

const ratingCourseTotals = computed(() => {
    const totals = [0, 0, 0, 0];
    for (const course of [1, 2, 3, 4]) {
        const data = ratingChart31DataByCourse.value[course];
        const items = data?.score?.byAcademy ?? [];
        for (const item of items) {
            totals[course - 1] += item.value ?? 0;
        }
    }
    return totals;
});

const ratingEngagementData = computed(() => {
    const data = ratingCourseFilter.value ? ratingChart32Data.value : ratingAnalyticsData.value;
    const items = data?.engagement?.byAcademy ?? [];
    const mapped = items.map(item => ({
        academy_id: item.academy_id,
        x: item.academy_name ?? '',
        values: [item.active_students ?? 0, item.total_students ?? 0],
    }));
    return fillMissingAcademies(mapped, 'academy_id', 'x', { values: [0, 0] });
});

const ratingEventsByCategory = computed(() => {
    const data = ratingAcademyFilter33.value ? ratingChart33Data.value : ratingAnalyticsData.value;
    const items = data?.events?.byCategory ?? [];
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

const ratingTotalEvents = computed(() => {
    const data = ratingAcademyFilter33.value ? ratingChart33Data.value : ratingAnalyticsData.value;
    const items = data?.events?.byCategory ?? [];
    return items.reduce((sum, i) => sum + (i.count ?? 0), 0);
});

const ratingEventsByAcademy = computed(() => {
    const items = ratingAnalyticsData.value?.events?.byAcademy?.items ?? [];
    const mapped = items.map((item) => ({
        academy_id: item.academy_id,
        x: item.academy_name ?? '',
        y: item.count ?? 0,
    }));
    return fillMissingAcademies(mapped, 'academy_id', 'x', { y: 0 });
});

const ratingTotalEventsByAcademy = computed(() => {
    const items = ratingAnalyticsData.value?.events?.byAcademy?.items ?? [];
    return items.reduce((sum, i) => sum + (i.count ?? 0), 0);
});

const ratingScoreByCategory = computed(() => {
    const data = ratingCourseFilter.value ? ratingChart35Data.value : ratingAnalyticsData.value;
    const items = data?.score?.byAcademyAndCategory ?? [];
    const categoryOrder = ['Проектная', 'Научная', 'Культурно-творческая', 'Общественная'];
    const mapped = items.map(academy => {
        const categoriesMap = {};
        for (const cat of academy.categories ?? []) {
            categoriesMap[cat.label] = cat.value ?? 0;
        }
        const values = categoryOrder.map(cat => categoriesMap[cat] ?? 0);
        return {
            id: academy.id,
            x: academy.label ?? '',
            values,
        };
    });
    return fillMissingAcademies(mapped, 'id', 'x', { values: [0, 0, 0, 0] });
});

const ratingCategoryTotals = computed(() => {
    const data = ratingCourseFilter.value ? ratingChart35Data.value : ratingAnalyticsData.value;
    const items = data?.score?.byAcademyAndCategory ?? [];
    const totals = {};
    for (const item of items) {
        for (const cat of item.categories ?? []) {
            totals[cat.label] = (totals[cat.label] ?? 0) + (cat.value ?? 0);
        }
    }
    return ratingCategoryNames.map(name => totals[name] ?? 0);
});

function buildRatingAnalyticsUrl(overrides = {}) {
    const params = new URLSearchParams();
    const academyId = overrides.academy_id ?? ratingAcademyFilter33.value;
    const educationLevelId = overrides.education_level_id ?? ratingEducationLevelId.value;
    const course = overrides.course ?? ratingCourseFilter.value;
    if (academyId) params.set('academy_id', academyId);
    if (educationLevelId) params.set('education_level_id', educationLevelId);
    if (course) params.set('course', course);
    const qs = params.toString();
    return `/api/v1/rating/analytics${qs ? '?' + qs : ''}`;
}

async function loadRatingAnalytics() {
    ratingLoading.value = true;
    try {
        ratingAnalyticsData.value = await get(buildRatingAnalyticsUrl());
    } catch (error) {
        console.error('Rating analytics load error:', error);
    } finally {
        ratingLoading.value = false;
    }
    loadRatingChart31();
    loadRatingChart32();
    loadRatingChart33();
    loadRatingChart35();
}

async function loadRatingChart31() {
    ratingChart31Loading.value = true;
    try {
        const promises = [1, 2, 3, 4].map(async (course) => {
            const data = await get(buildRatingAnalyticsUrl({ course }));
            ratingChart31DataByCourse.value[course] = data;
        });
        await Promise.all(promises);
    } catch {
        return;
    } finally {
        ratingChart31Loading.value = false;
    }
}

async function loadRatingChart32(course) {
    ratingChart32Loading.value = true;
    try {
        await nextTick();
        ratingChart32Data.value = await get(buildRatingAnalyticsUrl({ course: course ?? ratingCourseFilter.value }));
    } catch {
        return;
    } finally {
        ratingChart32Loading.value = false;
    }
}

async function loadRatingChart33(academyId) {
    ratingChart33Loading.value = true;
    try {
        await nextTick();
        ratingChart33Data.value = await get(buildRatingAnalyticsUrl({ academy_id: academyId ?? ratingAcademyFilter33.value }));
    } catch {
        return;
    } finally {
        ratingChart33Loading.value = false;
    }
}

async function loadRatingChart35(course) {
    ratingChart35Loading.value = true;
    try {
        await nextTick();
        ratingChart35Data.value = await get(buildRatingAnalyticsUrl({ course: course ?? ratingCourseFilter.value }));
    } catch {
        return;
    } finally {
        ratingChart35Loading.value = false;
    }
}
// --- Конец состояния для графиков из рейтинга ---

function downloadFile(response, filename) {
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

async function generateUsersReport() {
    const response = await getBlob("/api/v1/admin/generate-users-report");
    downloadFile(response, "users_report.xlsx");
}

onMounted(() => {
    get('/api/v1/scoring-blackout')
        .then((response) => {
            const ranges = computeScoringSemestersFromBlackout(response?.data ?? {});
            if (ranges) {
                dynamicAcademicYearDates.value = `С ${formatRuDayMonth(ranges.first.start)} по ${formatRuDayMonth(ranges.second.end)}`;
                const { firstLine, secondLine } = formatScoringSemesterSummaryLines(ranges);
                dynamicSemesterDates.value = `${firstLine} · ${secondLine}`;
            } else {
                dynamicAcademicYearDates.value = 'Не удалось загрузить';
                dynamicSemesterDates.value = 'Не удалось загрузить';
            }
        })
        .catch(() => {
            dynamicAcademicYearDates.value = 'Ошибка загрузки';
            dynamicSemesterDates.value = 'Ошибка загрузки';
        });

    get(props.endpoint)
        .then((data) => {
            const periods = data.periods ?? {};
            const allTime = periods.all_time ?? {};
            const semester = periods.semester ?? {};
            const academicYear = periods.academic_year ?? {};

            countUsers.value = allTime.users?.total ?? 0;
            countActiveUsers.value = allTime.users?.active ?? 0;
            countActivitiesInCurrentSemester.value = semester.activities?.total ?? 0;
            countActiveActivitiesInCurrentSemester.value = semester.activities?.active ?? 0;
            sumScoreByStudyYear.value = academicYear.total_score_sum ?? 0;
            sumScoreBySemester.value = semester.total_score_sum ?? 0;

            countActivitiesByYears.value = data.activities_by_year ?? [];
            countActivitiesBySemesters.value = data.activities_by_semester ?? [];
        })
        .catch((error) => {
            console.error(props.role === 'admin' ? 'Dashboard error:' : `${props.role} Dashboard error:`, error);
        });

    get('/api/v1/academies').then((data) => {
        ratingAcademyOptions.value = data;
    }).catch(() => {});

    get('/api/v1/education-levels').then((data) => {
        ratingEducationLevelOptions.value = data;
    }).catch(() => {});

    loadRatingAnalytics();
});
</script>
