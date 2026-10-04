<template>
    <NuxtLayout name="public">
        <template #header>
            <StudentHeader :user-id="userId" />
        </template>

        <div class="flex items-center justify-between mt-2 mb-2">
            <UButton
                icon="i-heroicons-arrow-left"
                size="sm"
                color="neutral"
                variant="ghost"
                class="shrink-0"
                @click="goBack"
            >
                <span>Назад</span>
            </UButton>
        </div>

        <h2 class="text-base md:text-xl font-semibold">История начисления баллов</h2>

        <ActivityModal
            v-model:open="showActivityModal"
            :activity="activeActivity"
            :files="activityFiles"
            :api-path-storage="config.public.API_PATH_STORAGE"
            @close="closeActivity"
        />

        <div class="mt-4 flex flex-col sm:flex-row gap-3">
            <USelect
                v-model="selectedCategory"
                :items="categoryOptions"
                size="md"
                class="min-w-[180px]"
                @update:model-value="applyFilters"
            />
            <USelect
                v-model="selectedSemester"
                :items="semesterOptions"
                size="md"
                class="min-w-[180px]"
                @update:model-value="applyFilters"
            />
        </div>

        <div v-if="status === 'pending'" class="mt-4 flex justify-center items-center py-12">
            <div class="text-center">
                <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
                <p class="text-gray-600">Загрузка истории начислений...</p>
            </div>
        </div>

        <div v-if="status === 'success'" class="mt-4">
            <div v-if="filteredHistory.length !== 0" class="flex flex-col space-y-2">
                <div class="hidden lg:block">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="w-auto border-b border-default px-4 py-2 text-left font-semibold">
                                    Наименование мероприятия
                                </th>
                                <th class="w-20 border-b border-default px-4 py-2 text-center font-semibold">
                                    Категория
                                </th>
                                <th class="w-20 border-b border-default px-4 py-2 text-center font-semibold">
                                    Баллы
                                </th>
                                <th class="w-24 border-b border-default px-4 py-2 text-center font-semibold">
                                    Дата начисления
                                </th>
                                <th class="w-16 border-b border-default px-4 py-2 text-center font-semibold">
                                    Портфолио
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in filteredHistory" :key="index" class="hover:bg-zinc-950/2.5">
                                <td class="w-auto border-b border-muted px-4 py-2">
                                    <a
                                        href="#"
                                        class="text-primary hover:text-primary/80 hover:underline cursor-pointer"
                                        @click.prevent="openActivityModal(item.activity_id)"
                                    >
                                        {{ item.title }}
                                    </a>
                                </td>
                                <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                        :class="getCategoryColor(item.category)"
                                    >
                                        {{ item.category }}
                                    </span>
                                </td>
                                <td class="w-20 border-b border-muted px-4 py-2 text-center">+{{ item.score }}</td>
                                <td class="w-24 border-b border-muted px-4 py-2 text-center">{{ formatDate(item.score_assignment_date) }}</td>
                                <td class="w-16 border-b border-muted px-4 py-2 text-center">
                                    <div class="flex items-center justify-center">
                                        <UTooltip :text="hasPortfolio(item.activity_id) ? 'Загружено' : 'Не загружено'">
                                            <UIcon
                                                :name="hasPortfolio(item.activity_id) ? 'i-heroicons-document-check' : 'i-heroicons-document-minus'"
                                                :class="hasPortfolio(item.activity_id) ? 'text-green-600' : 'text-gray-400'"
                                                class="w-5 h-5"
                                            />
                                        </UTooltip>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden grid gap-2">
                    <div v-for="(item, index) in filteredHistory" :key="index" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
                        <div class="grid gap-1">
                            <span class="font-semibold">Наименование мероприятия</span>
                            <a
                                href="#"
                                class="text-primary hover:text-primary/80 hover:underline cursor-pointer"
                                @click.prevent="openActivityModal(item.activity_id)"
                            >
                                {{ item.title }}
                            </a>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Категория</span>
                            <span>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    :class="getCategoryColor(item.category)"
                                >
                                    {{ item.category }}
                                </span>
                            </span>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Баллы</span>
                            <span>+{{ item.score }}</span>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Дата начисления баллов</span>
                            <span>{{ formatDate(item.score_assignment_date) }}</span>
                        </div>
                        <div class="grid gap-1">
                            <span class="font-semibold">Портфолио</span>
                            <div class="flex items-center gap-1.5">
                                <UIcon
                                    :name="hasPortfolio(item.activity_id) ? 'i-heroicons-document-check' : 'i-heroicons-document-minus'"
                                    :class="hasPortfolio(item.activity_id) ? 'text-green-600' : 'text-gray-400'"
                                    class="w-4 h-4"
                                />
                                <span class="text-xs text-gray-600">
                                    {{ hasPortfolio(item.activity_id) ? 'Загружено' : 'Нет файла' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <ListPagination :pagination="pagination" @update:page="onPageChange" />
            </div>

            <UAlert
                v-if="filteredHistory.length === 0 && status === 'success'"
                icon="i-heroicons-information-circle"
                color="info"
                variant="solid"
                title="Информация"
                :description="noDataMessage"
            />
        </div>
    </NuxtLayout>
</template>

<script setup lang="ts">
import type { StudentHistoryScoreItem } from '~/composables/student-api/useStudentHistoryScoreApi'
import type { ActivityFile } from '~/composables/student-api/useStudentActivityFilesApi'

definePageMeta({
    layout: false,
    middleware: false,
})

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const toast = useToast()
const userId = computed(() => route.params.id as string)

const { getCategories: fetchCategoriesApi, getHistoryScore, getPublicActivity } = useStudentHistoryScoreApi()

const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle')
const originalHistory = ref<StudentHistoryScoreItem[]>([])
const selectedCategory = ref('all')
const selectedSemester = ref('all')
const portfolioActivityIds = ref<Set<number | string>>(new Set())

const pagination = ref({
    currentPage: null as number | null,
    perPage: null as number | null,
    totalItems: null as number | null,
})

const categoryOptions = ref([
    { value: 'all', label: 'Все категории' },
])

const semesterOptions = [
    { value: 'all', label: 'Все семестры' },
    { value: 'current', label: 'Текущий семестр' },
    { value: 'previous', label: 'Предыдущий семестр' },
]

const filteredHistory = computed(() => {
    if (!originalHistory.value.length) return []
    let filtered = [...originalHistory.value]
    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(item => item.category === selectedCategory.value)
    }
    return filtered
})

const noDataMessage = computed(() => {
    if (originalHistory.value.length === 0) {
        if (selectedSemester.value === 'previous') return 'Нет записей за предыдущий семестр'
        if (selectedSemester.value === 'current') return 'Нет записей за текущий семестр'
        return 'История начисления баллов отсутствует'
    }
    if (filteredHistory.value.length === 0 && selectedCategory.value !== 'all') {
        return `Нет записей в категории "${selectedCategory.value}"`
    }
    return 'История начисления баллов отсутствует'
})

const showActivityModal = ref(false)
const activeActivity = ref<Record<string, unknown> | null>(null)
const activityFiles = ref<ActivityFile[]>([])
const activityLoading = ref(false)

const { getActivityFiles } = useStudentActivityFilesApi()

onMounted(() => {
    fetchCategories()
    loadData(buildUrl(getInitialPage()))
    fetchPortfolioActivityIds()
})

function buildUrl(page?: number) {
    return String(page ?? 1)
}

function loadData(urlOrPage: string | number = 1) {
    const pageNum = typeof urlOrPage === 'string'
        ? (parseInt(urlOrPage, 10) || 1)
        : (Number.isFinite(urlOrPage) ? urlOrPage : 1)

    const params: { page: number; semester?: string } = { page: pageNum }
    if (selectedSemester.value !== 'all') params.semester = selectedSemester.value

    status.value = 'pending'
    getHistoryScore(userId.value, params)
        .then((data) => {
            originalHistory.value = data.items
            pagination.value.currentPage = data.currentPage
            pagination.value.perPage = data.perPage
            pagination.value.totalItems = data.totalItems
            status.value = 'success'
        })
        .catch(() => {
            toast.add({ title: 'Ошибка загрузки истории', color: 'error' })
            originalHistory.value = []
            pagination.value = { currentPage: 1, perPage: 10, totalItems: 0 }
            status.value = 'success'
        })
}

const { onPageChange, getInitialPage } = usePaginationRedirect(loadData, buildUrl)

function applyFilters() {
    router.replace({ path: route.path, query: { ...route.query, page: '1' } }).catch(() => {})
    loadData(buildUrl(1))
}

function formatDate(dateString?: string) {
    if (!dateString) return 'Не указано'
    const date = new Date(dateString)
    const day = date.getDate().toString().padStart(2, '0')
    const month = (date.getMonth() + 1).toString().padStart(2, '0')
    const year = date.getFullYear()
    return `${day}.${month}.${year}`
}

function getCategoryColor(category?: string) {
    const colors: Record<string, string> = {
        'Проектная': 'bg-purple-100 text-purple-800',
        'Научная': 'bg-primary/10 text-primary/80',
        'Культурно-творческая': 'bg-orange-100 text-orange-800',
        'Общественная': 'bg-yellow-100 text-yellow-800',
    }
    return category ? (colors[category] || 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-800'
}

function goBack() {
    if (window.history.length > 1) {
        router.back()
    } else {
        router.push(`/student/profile/${userId.value}`)
    }
}

function fetchCategories() {
    fetchCategoriesApi()
        .then((list) => {
            categoryOptions.value = [
                { value: 'all', label: 'Все категории' },
                ...list.map((c) => ({ value: c.name ?? '', label: c.name ?? '' })),
            ]
        })
        .catch(() => {
            toast.add({ title: 'Не удалось загрузить категории', color: 'warning' })
            categoryOptions.value = [{ value: 'all', label: 'Все категории' }]
        })
}

async function fetchPortfolioActivityIds() {
    const { get } = useApi()
    const ids = new Set<number | string>()
    let page = 1
    let lastPage = 1

    try {
        do {
            const response = await get<any>(`/api/v1/public/student/portfolio-public/${userId.value}`, { query: { page, sort_by: 'created_at', sort_order: 'desc' } })
            const items = Array.isArray(response?.data) ? response.data : []
            for (const item of items) {
                if (item.activities && Array.isArray(item.activities)) {
                    for (const act of item.activities) {
                        if (act.id != null) ids.add(act.id)
                    }
                }
            }
            lastPage = response?.last_page ?? 1
            page++
        } while (page <= lastPage)
    } catch { /* ignore */ }

    portfolioActivityIds.value = ids
}

function hasPortfolio(activityId?: number | string | null): boolean {
    if (activityId == null) return false
    return portfolioActivityIds.value.has(activityId)
}

async function openActivityModal(activityId: number | string | undefined) {
    if (!activityId) {
        toast.add({ title: 'ID мероприятия не указан', color: 'error' })
        return
    }

    activityLoading.value = true
    activityFiles.value = []

    try {
        activeActivity.value = await getPublicActivity(activityId)
        activityFiles.value = await getActivityFiles(activityId)
        showActivityModal.value = true
    } catch {
        toast.add({ title: 'Мероприятие не найдено', color: 'warning' })
    } finally {
        activityLoading.value = false
    }
}

function closeActivity() {
    showActivityModal.value = false
    activeActivity.value = null
    activityFiles.value = []
}
</script>
