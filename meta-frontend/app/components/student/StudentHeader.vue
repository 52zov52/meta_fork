<template>
  <div class="flex flex-col md:flex-row bg-white border-b border-default px-4 lg:px-32 py-4">
    <!-- Аватар как раньше: слева, чуть нависает над фоном -->
    <div class="relative w-full md:w-32 max-md:flex max-md:justify-center lg:-ml-4">
      <div
        :style="avatarStyle"
        class="absolute -top-18 h-32 w-32 bg-cover border-2 border-default rounded-full"
      />
    </div>

    <!-- Контент: имя, мета и кнопки -->
    <div class="max-md:mt-16 flex-1 flex max-md:flex-col items-center md:justify-between max-md:space-y-5 md:ml-4">
      <div class="flex flex-col max-md:items-center">
        <h2 class="text-base md:text-xl font-semibold">{{ displayName }}</h2>

        <div class="mt-1 text-xs text-gray-500 flex flex-wrap gap-x-2 gap-y-1 max-md:justify-center">
          <span v-if="profileData.academy">{{ profileData.academy }}</span>
          <span v-if="profileData.academy && profileData.education_level" class="max-md:hidden">•</span>
          <span v-if="profileData.education_level">{{ profileData.education_level }}</span>
          <span v-if="profileData.education_level && profileData.course" class="max-md:hidden">•</span>
          <span v-if="profileData.course">{{ profileData.course }} курс</span>
        </div>
      </div>

      <div class="flex gap-2 max-md:flex-col max-md:w-full">
        <UPopover mode="hover" :open-delay="120" :close-delay="120" class="max-md:w-full">
          <UButton
            :to="isPublicProfile ? `/student/history-score/${props.userId}` : undefined"
            color="success"
            variant="solid"
            class="gap-1 h-8 px-2 max-md:w-full"
            @click="!isPublicProfile && goHistory()"
          >
            <UIcon name="i-heroicons-circle-stack" class="w-5 h-5 shrink-0" />
            <span>{{ formatScore(profileData.total_score) }} баллов</span>
          </UButton>

          <template #content>
            <div class="p-2 flex flex-col gap-2 min-w-72">
              <UButton
                v-for="item in categoryButtons"
                :key="item.id"
                variant="solid"
                class="justify-between w-full hover:opacity-90"
                :class="item.bgClass">
                <span class="truncate">{{ item.label }}</span>
                <span class="font-semibold">{{ formatScore(item.score) }}</span>
              </UButton>
            </div>
          </template>
        </UPopover>

        <UButton
          v-if="!isPublicProfile"
          :to="studentEditLink"
          color="primary"
          variant="solid"
          class="gap-1 h-8 px-2 max-md:w-full">
          <UIcon name="i-heroicons-user" class="w-5 h-5" />
          <span>Мой профиль</span>
        </UButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { staticImg } from '~/assets/img'

const props = defineProps({
  userId: { type: [String, Number], required: false, default: undefined },
})

const CATEGORY_COLORS = [
    'bg-[#6100A7]! text-white',
    'bg-[#0149BF]! text-white',
    'bg-[#ED5726]! text-white',
    'bg-[#EEC60A]! text-white',
]

function getCategoryColor(index) {
    return CATEGORY_COLORS[index % CATEGORY_COLORS.length] ?? 'bg-default text-white'
}

const config = useRuntimeConfig()
const { user } = useSanctumAuth()
const router = useRouter()
const { show: showApiError } = useApiError()
const { getShort, getMy } = useStudentHeaderApi()

const currentUserId = computed(() => user.value?.user_id)
const studentEditLink = computed(() => {
  const id = currentUserId.value
  if (id == null) return undefined
  if (typeof id === 'string' && id === '') return undefined
  return `/student/profile/edit/${id}`
})

const isPublicProfile = computed(() => props.userId != null && props.userId !== '')

// Локальный state для данных профиля (не дублировать в глобальном store!)
const profileData = ref({
    fullName: '',
    shortName: '',
    region: '',
    institution: '',
    academy: '',
    education_level: '',
    course: null,
    total_score: 0,
    profile_photo_path: '',
})

const rawCategoryScores = ref([])

const displayName = computed(() =>
  isPublicProfile.value ? (profileData.value.shortName || 'Загрузка...') : profileData.value.fullName
)

const categoryButtons = computed(() => {
    const scores = rawCategoryScores.value || []
    return scores.map((item, idx) => ({
        id: idx,
        label: item?.category || '',
        score: Number(item?.score ?? 0) || 0,
        bgClass: getCategoryColor(idx),
    }))
})

function formatScore(v) {
    const n = Number(v ?? 0) || 0
    return Number.isInteger(n) ? String(n) : String(Number(n.toFixed(1)))
}

const avatarStyle = computed(() => {
    const path = profileData.value.profile_photo_path
        ? `${config.public.API_PATH_STORAGE}${profileData.value.profile_photo_path}`
        : staticImg.photo
    return { backgroundImage: `url('${path}')` }
})

function loadData() {
    if (isPublicProfile.value) {
        const id = props.userId
        if (id == null || id === '') return

        getShort(id)
            .then((data) => {
                profileData.value = {
                    fullName: '',
                    shortName: data.shortName || '',
                    region: '',
                    institution: data.institution || '',
                    academy: data.academy?.name || '',
                    education_level: data.education_level || '',
                    course: data.course || null,
                    total_score: Number(data.total_score ?? 0) || 0,
                    profile_photo_path: data.profile_photo_path || '',
                }
                rawCategoryScores.value = Array.isArray(data.category_scores)
                    ? data.category_scores
                    : []
            })
            .catch(() => {
                showApiError(new Error(), 'Произошла ошибка в процессе получения данных.')
            })
        return
    }

    if (!currentUserId.value) return

    getMy(currentUserId.value)
        .then((data) => {
            profileData.value = {
                fullName: data.fullName || '',
                shortName: '',
                region: data.region || '',
                institution: data.institution || '',
                academy: data.academy?.name || '',
                education_level: data.education_level || '',
                course: data.course || null,
                total_score: Number(data.total_score ?? 0) || 0,
                profile_photo_path: data.profile_photo_path || '',
            }

            rawCategoryScores.value = Array.isArray(data.category_scores)
                ? data.category_scores
                : []
        })
        .catch(() => {
            showApiError(new Error(), 'Произошла ошибка в процессе получения данных.');
        })
}

function goHistory() {
    const id = currentUserId.value
    if (id == null) return
    router.push(`/student/history-score/${id}`)
}

onMounted(loadData)

watch(() => props.userId, () => {
    if (isPublicProfile.value) loadData()
})

defineExpose({ refreshData: loadData })
</script>
