<template>
    <UModal
        :open="open"
        :ui="{
            content: 'sm:max-w-3xl',
            overlay: 'bg-gray-900/70 backdrop-blur-sm'
        }"
        :dismissible="!isImageOpen"
        @close="$emit('close')"
        @update:open="$emit('update:open', $event)"
    >
        <template v-if="activity" #content>
            <div class="p-4 space-y-4 overflow-y-auto max-h-[80vh]">

                <!-- Image at top -->
                <div class="w-full rounded-xl overflow-hidden border border-default dark:border-muted bg-gray-100 dark:bg-gray-800 shadow-lg aspect-[4/3] max-h-[350px] cursor-zoom-in grid place-items-center">
                    <img
                        :src="activity.photo_path
                            ? `${apiPathStorage}${activity.photo_path}`
                            : staticImg.bgCard"
                        :alt="getTitle(activity)"
                        class="block w-full h-full object-contain"
                        @click.stop="openImage"
                        @error="handleImageError"
                    >
                </div>
                <!-- Header -->
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">
                        {{ activity.title }}
                    </h3>
                    <UButton
                        color="neutral"
                        variant="ghost"
                        icon="i-heroicons-x-mark"
                        class="shrink-0"
                        @click="$emit('close')"
                    />
                </div>

                <!-- Portfolio Section (optional) -->
                <div v-if="portfolio && portfolio.length > 0" class="bg-info/10 dark:bg-primary/90/20 p-4 rounded-lg border border-info/30 dark:border-primary/80">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                        <UIcon name="i-heroicons-folder" class="w-4 h-4 text-info" />
                        Портфолио мероприятия
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Сертификаты, дипломы и документы, подтверждающие участие в мероприятии
                    </p>
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in portfolio"
                            :key="index"
                            class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-default dark:border-muted hover:border-primary/40 hover:shadow-md transition-all"
                        >
                            <div class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ item.title || 'Без названия' }}</div>
                            <div class="flex flex-wrap gap-3">
                                <a
                                    v-if="getCertificateLink(item)"
                                    :href="getCertificateLink(item)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-primary hover:text-primary/80 underline text-sm"
                                >
                                    <UIcon name="i-heroicons-link" class="w-4 h-4" />
                                    <span class="truncate max-w-xs">{{ getCertificateLink(item) }}</span>
                                </a>
                                <a
                                    v-if="getFileUrl(item) && item.original_name"
                                    :href="getFileUrl(item)"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 text-primary hover:text-primary/80 underline text-sm"
                                >
                                    <UIcon name="i-heroicons-document-arrow-down" class="w-4 h-4" />
                                    <span>{{ item.original_name }}</span>
                                </a>
                            </div>
                            <p v-if="getCertificateDescription(item)" class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                {{ getCertificateDescription(item) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="activity.description" class="bg-gray-50 dark:bg-gray-800/30 p-4 rounded-lg">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                        <UIcon name="i-heroicons-document-text" class="w-4 h-4 text-primary" />
                        Описание
                    </h4>
                    <div class="whitespace-pre-line leading-relaxed text-sm text-gray-700 dark:text-gray-300">
                        {{ activity.description }}
                    </div>
                </div>

                <!-- Activity Files Section -->
                <div v-if="activityFiles.length > 0" class="bg-gray-50 dark:bg-gray-800/30 p-4 rounded-lg">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                        <UIcon name="i-heroicons-paper-clip" class="w-4 h-4 text-primary" />
                        Материалы
                    </h4>
                    <div class="space-y-2">
                        <a
                            v-for="file in activityFiles"
                            :key="file.id"
                            :href="file.id ? getDownloadUrl(file.id) : '#'"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-default dark:border-muted hover:border-primary/40 hover:shadow-md transition-all"
                        >
                            <UIcon name="i-heroicons-document-arrow-down" class="w-5 h-5 text-primary shrink-0" />
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ file.original_name }}</span>
                            <span class="text-xs text-gray-400 shrink-0">{{ file.formatted_size }}</span>
                        </a>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <UContainer class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-calendar-days" class="w-4 h-4 text-primary" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Начало</div>
                                <div class="text-sm font-semibold">{{ formatDate(activity.date_start) }}</div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-calendar" class="w-4 h-4 text-primary" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Окончание</div>
                                <div class="text-sm font-semibold">{{ formatDate(activity.date_end) }}</div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-star" class="w-4 h-4 text-yellow-500" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Баллы за участие</div>
                                <div class="text-sm font-semibold">
                                    <span v-if="activity.is_dynamic_score && personalParticipationScore != null">{{ personalParticipationScore }}</span>
                                    <span v-else-if="!!activity.is_dynamic_score">Автоматический расчёт</span>
                                    <span v-else>{{ activity.score }}</span>
                                </div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer v-if="activity.is_dynamic_score && ksuTargetLabel" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-academic-cap" class="w-4 h-4 text-info" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Уровень мероприятия</div>
                                <div class="text-sm font-semibold">{{ ksuTargetLabel }}</div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer v-if="activity.max_participants" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-user-group" class="w-4 h-4 text-primary" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Участников</div>
                                <div class="text-sm font-semibold">{{ activity.max_participants }}</div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer v-if="activity.status" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-check-circle" class="w-4 h-4 text-green-500" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Статус</div>
                                <div class="text-sm font-semibold">{{ activity.status }}</div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer v-if="activity.category_name" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-tag" class="w-4 h-4 text-info" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Категория</div>
                                <div class="text-sm font-semibold">{{ activity.category_name }}</div>
                            </div>
                        </div>
                    </UContainer>

                    <UContainer v-if="activity.planet_name" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-heroicons-globe-alt" class="w-4 h-4 text-green-500" />
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Планета</div>
                                <div class="text-sm font-semibold">{{ activity.planet_name }}</div>
                            </div>
                        </div>
                    </UContainer>
                </div>

                <!-- KSU Scores Table -->
                <div v-if="!!activity.is_dynamic_score && ksuScores.length > 0" class="bg-gray-50 dark:bg-gray-800/30 p-4 rounded-lg">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                        <UIcon name="i-heroicons-table-cells" class="w-4 h-4 text-primary" />
                        Баллы по уровням обучения
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-default dark:border-muted">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="border border-default dark:border-muted px-3 py-2 text-left">Уровень обучения</th>
                                    <th v-if="ksuHasPlaces" class="border border-default dark:border-muted px-3 py-2 text-center">1 место</th>
                                    <th v-if="ksuHasPlaces" class="border border-default dark:border-muted px-3 py-2 text-center">2 место</th>
                                    <th v-if="ksuHasPlaces" class="border border-default dark:border-muted px-3 py-2 text-center">3 место</th>
                                    <th class="border border-default dark:border-muted px-3 py-2 text-center">Участие</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in ksuScores" :key="row.education_level_id"
                                    :class="{ 'bg-primary/5 dark:bg-primary/20': row.education_level_id === ksuUserLevelId }"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="border border-default dark:border-muted px-3 py-2 font-medium">{{ row.education_level_name }}</td>
                                    <td v-if="ksuHasPlaces" class="border border-default dark:border-muted px-3 py-2 text-center">{{ row['1st_place'] ?? '—' }}</td>
                                    <td v-if="ksuHasPlaces" class="border border-default dark:border-muted px-3 py-2 text-center">{{ row['2nd_place'] ?? '—' }}</td>
                                    <td v-if="ksuHasPlaces" class="border border-default dark:border-muted px-3 py-2 text-center">{{ row['3rd_place'] ?? '—' }}</td>
                                    <td class="border border-default dark:border-muted px-3 py-2 text-center">{{ row.participation ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="ksuUserScores" class="mt-3 p-3 bg-primary/5 dark:bg-primary/20 rounded border border-primary/20 dark:border-primary/50">
                        <p class="text-sm font-medium">Баллы для вашего уровня обучения ({{ ksuUserLevelName }}):</p>
                        <div class="flex flex-wrap gap-4 mt-1 text-sm">
                            <span v-if="ksuUserScores['1st_place']">1 место: <strong>{{ ksuUserScores['1st_place'] }}</strong></span>
                            <span v-if="ksuUserScores['2nd_place']">2 место: <strong>{{ ksuUserScores['2nd_place'] }}</strong></span>
                            <span v-if="ksuUserScores['3rd_place']">3 место: <strong>{{ ksuUserScores['3rd_place'] }}</strong></span>
                            <span v-if="ksuUserScores['participation']">Участие: <strong>{{ ksuUserScores['participation'] }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Footer Slot -->
                <div v-if="$slots.footer" class="flex justify-between items-center gap-3 flex-wrap pt-3 border-t border-default dark:border-muted">
                    <slot name="footer" />
                </div>
            </div>
        </template>
    </UModal>

    <ImagePreviewModal
        v-model:open="isImageOpen"
        :src="activity?.photo_path ? `${apiPathStorage}${activity.photo_path}` : undefined"
        :alt="getTitle(activity)"
        :fallback-src="staticImg.bgCard"
        @close="isImageOpen = false"
    />
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { staticImg } from '~/assets/img'
import type { ActivityFile } from '~/composables/student-api/useStudentActivityFilesApi'


const isImageOpen = ref(false)

type UnknownRecord = Record<string, unknown>

interface KsuScoreRow {
  education_level_id?: number
  education_level_name?: string
  '1st_place'?: number
  '2nd_place'?: number
  '3rd_place'?: number
  participation?: number
}

interface KsuScoresResponse {
  scores?: KsuScoreRow[]
  has_places?: boolean
  user_level_id?: number
  user_level_name?: string
  user_scores?: Record<string, number>
  user_personal_score?: number | null
  target_education_level_ids?: number[]
  target_education_level_names?: string[]
}

const props = defineProps<{
    open: boolean
    activity: UnknownRecord | null
    portfolio?: UnknownRecord[]
    files?: ActivityFile[]
    apiPathStorage: string
}>()

defineEmits(['close', 'update:open'])

const activityFiles = ref<ActivityFile[]>([])
const filesLoading = ref(false)

const ksuScores = ref<KsuScoreRow[]>([])
const ksuHasPlaces = ref(false)
const ksuUserLevelId = ref<number | null>(null)
const ksuUserLevelName = ref<string>('')
const ksuUserScores = ref<Record<string, number> | null>(null)
const ksuTargetNames = ref<string[]>([])
const ksuTargetIds = ref<number[]>([])
const ksuUserPersonalScore = ref<number | null>(null)

const ksuTargetLabel = computed(() => ksuTargetNames.value.join(' + '))

const personalParticipationScore = computed<number | null>(() => {
    if (ksuUserPersonalScore.value != null) return ksuUserPersonalScore.value
    const value = props.activity?.user_personal_score
    return typeof value === 'number' ? value : null
})

watch(() => props.open, async (value) => {
    if (!value) {
        isImageOpen.value = false
        activityFiles.value = []
        ksuScores.value = []
        ksuUserScores.value = null
        ksuTargetNames.value = []
        ksuTargetIds.value = []
        ksuUserPersonalScore.value = null
        return
    }
    if (props.activity?.id) {
        await loadActivityFiles(Number(props.activity.id))
        if (props.activity?.is_dynamic_score) {
            await loadKsuScores(Number(props.activity.id))
        }
    }
})

async function loadActivityFiles(activityId: number) {
    if (!activityId) return
    filesLoading.value = true
    try {
        const { get } = useApi()
        const data = await get<{ files: ActivityFile[] }>(`/api/v1/activities/${activityId}/files`)
        activityFiles.value = data.files || []
    } catch {
        activityFiles.value = []
    } finally {
        filesLoading.value = false
    }
}

async function loadKsuScores(activityId: number) {
    try {
        const { get } = useApi()
        const data = await get<KsuScoresResponse>(`/api/v1/activities/${activityId}/scores`)
        ksuScores.value = data.scores || []
        ksuHasPlaces.value = data.has_places ?? false
        ksuUserLevelId.value = data.user_level_id ?? null
        ksuUserLevelName.value = data.user_level_name ?? ''
        ksuUserScores.value = data.user_scores ?? null
        ksuTargetNames.value = Array.isArray(data.target_education_level_names) ? data.target_education_level_names : []
        ksuTargetIds.value = Array.isArray(data.target_education_level_ids) ? data.target_education_level_ids : []
        ksuUserPersonalScore.value = data.user_personal_score ?? null
    } catch {
        ksuScores.value = []
        ksuHasPlaces.value = false
        ksuUserLevelId.value = null
        ksuUserLevelName.value = ''
        ksuUserScores.value = null
        ksuTargetNames.value = []
        ksuTargetIds.value = []
        ksuUserPersonalScore.value = null
    }
}

function getDownloadUrl(fileId: number): string {
    const config = useRuntimeConfig()
    return `${config.public.API_URL}/api/v1/activities/files/${fileId}/download`
}



function handleImageError(event: Event) {
    const target = event.target as HTMLImageElement | null
    if (target) target.src = staticImg.bgCard
}

function formatDate(dateString: unknown): string | undefined {
    if (typeof dateString !== 'string' || !dateString) return undefined
    return new Date(dateString).toLocaleDateString('ru-RU')
}

function getCertificateLink(item: UnknownRecord | null | undefined) {
    if (!item) return undefined
    const link = item.link
    if (typeof link === 'string' && link) return link

    const description = item.description
    if (typeof description !== 'string' || !description) return undefined
    const linkMatch = description.match(/^LINK:(.+?)(?:\n\n|$)/)
    return linkMatch?.[1]?.trim()
}

function getCertificateDescription(item: UnknownRecord | null | undefined) {
    if (!item) return undefined
    const description = item.description
    if (typeof description !== 'string' || !description) return undefined
    const linkMatch = description.match(/^LINK:.+?(\r\n\r\n|\n\n)(.+)$/s)
    if (linkMatch?.[2]) return linkMatch[2].trim()
    return description.startsWith('LINK:') ? undefined : description
}

function getFileUrl(item: UnknownRecord | null | undefined): string | undefined {
    const fileUrl = item?.file_url
    return typeof fileUrl === 'string' ? fileUrl : undefined
}

function getTitle(item: UnknownRecord | null | undefined): string | undefined {
    const title = item?.title
    return typeof title === 'string' ? title : undefined
}

const isModalActive = computed(() => props.open)

watch(() => props.open, (value) => {
    if (!value) isImageOpen.value = false
})

function openImage() {
    if (!isModalActive.value) return
    isImageOpen.value = true
}

</script>
