<template>
  <UModal
    v-model:open="isOpen"
    title="Детали портфолио"
    :ui="{ content: 'w-full sm:max-w-3xl' }"
  >
    <template #body>
      <div v-if="portfolio" class="space-y-4">
        <div class="flex justify-center">
          <div class="relative rounded-xl overflow-hidden max-w-md max-h-80 shadow-lg border border-default bg-linear-to-br from-gray-50 to-gray-100">
            <!-- Картинки -->
            <img
              v-if="portfolio.extension && isImageExtension(portfolio.extension)"
              :src="portfolio.file_url"
              :alt="portfolio.title || portfolio.original_name"
              class="w-full h-full object-contain"
              @error="onPortfolioImageError"
            >
            <!-- Документы и другие файлы -->
            <div
              v-else-if="portfolio.extension"
              class="w-64 h-48 bg-linear-to-br from-gray-100 to-gray-200 flex flex-col items-center justify-center border border-accented rounded-xl"
            >
              <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-3">
                <UIcon name="i-heroicons-document-text" class="w-8 h-8 text-gray-600" />
              </div>
              <span class="text-sm font-medium text-gray-600">{{ portfolio.extension.toUpperCase() }} документ</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white rounded-lg p-4 shadow-sm border border-muted">
            <div class="flex items-center gap-2 mb-2">
              <UIcon name="i-heroicons-document-text" class="w-4 h-4 text-primary" />
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Название</span>
            </div>
            <p class="text-sm font-medium text-gray-900">{{ portfolio.title || portfolio.original_name || 'Без названия' }}</p>
          </div>

          <div v-if="getPortfolioActivityName(portfolio)" class="bg-white rounded-lg p-4 shadow-sm border border-muted">
            <div class="flex items-center gap-2 mb-2">
              <UIcon name="i-heroicons-calendar" class="w-4 h-4 text-green-600" />
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Мероприятие</span>
            </div>
            <a
              href="#"
              class="text-sm font-medium text-primary hover:text-primary/80 hover:underline cursor-pointer"
              @click.prevent="openActivityModal"
            >
              {{ getPortfolioActivityName(portfolio) }}
            </a>
          </div>

          <div v-if="getPortfolioActivityDates(portfolio)" class="bg-white rounded-lg p-4 shadow-sm border border-muted">
            <div class="flex items-center gap-2 mb-2">
              <UIcon name="i-heroicons-calendar-days" class="w-4 h-4 text-blue-600" />
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Дата проведения</span>
            </div>
            <p class="text-sm font-medium text-gray-900">{{ getPortfolioActivityDates(portfolio) }}</p>
          </div>

          <div v-if="formatDate(portfolio.created_at)" class="bg-white rounded-lg p-4 shadow-sm border border-muted">
            <div class="flex items-center gap-2 mb-2">
              <UIcon name="i-heroicons-clock" class="w-4 h-4 text-purple-600" />
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Дата загрузки</span>
            </div>
            <p class="text-sm font-medium text-gray-900">{{ formatDate(portfolio.created_at) }}</p>
          </div>
        </div>

        <div v-if="getPortfolioDescription(portfolio)" class="bg-white rounded-lg p-4 shadow-sm border border-muted">
          <div class="flex items-center gap-2 mb-2">
            <UIcon name="i-heroicons-chat-bubble-left-right" class="w-4 h-4 text-green-600" />
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Описание</span>
          </div>
          <p class="text-sm text-gray-700 leading-relaxed break-all">{{ getPortfolioDescription(portfolio) }}</p>
        </div>

        <div v-if="getPortfolioLink(portfolio)" class="bg-white rounded-lg p-4 shadow-sm border border-muted">
          <div class="flex items-center gap-2 mb-2">
            <UIcon name="i-heroicons-link" class="w-4 h-4 text-primary" />
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ссылка</span>
          </div>
          <a
            :href="getPortfolioLink(portfolio)"
            class="text-sm text-primary hover:text-primary/80 hover:underline break-all"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ getPortfolioLink(portfolio) }}
          </a>
        </div>
      </div>

      <ActivityModal
        v-model:open="showActivityModal"
        :activity="activeActivity"
        :files="activityFiles"
        :api-path-storage="apiPathStorage"
        @close="closeActivityModal"
      />
    </template>

    <template #footer>
      <div v-if="portfolio?.file_url" class="flex flex-wrap gap-2">
        <UButton
          :to="portfolio.file_url"
          target="_blank"
          rel="noopener noreferrer"
          color="neutral"
          variant="outline"
          size="sm"
        >
          <UIcon name="i-heroicons-arrow-top-right-on-square" class="w-4 h-4" />
          <span>Открыть в новой вкладке</span>
        </UButton>
        <UButton
          color="primary"
          variant="solid"
          size="sm"
          @click="downloadFile(portfolio)"
        >
          <UIcon name="i-heroicons-arrow-down-tray" class="w-4 h-4" />
          <span>Скачать</span>
        </UButton>
      </div>
    </template>
  </UModal>
</template>

<script setup>
import { staticImg } from '~/assets/img'

function onPortfolioImageError(e) {
  const t = e.target
  if (t instanceof HTMLImageElement) t.src = staticImg.bgCard
}

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  portfolio: {
    type: Object,
    default: null
  },
  apiPathStorage: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
});

const showActivityModal = ref(false)
const activeActivity = ref(null)
const activityFiles = ref([])

const { getPublicActivity } = useStudentHistoryScoreApi()
const { getActivityFiles } = useStudentActivityFilesApi()

async function openActivityModal() {
  const activity = props.portfolio?.activities?.[0]
  if (!activity?.id) return

  try {
    activeActivity.value = await getPublicActivity(activity.id)
    activityFiles.value = await getActivityFiles(activity.id)
    showActivityModal.value = true
  } catch {
    // silently fail
  }
}

function closeActivityModal() {
  showActivityModal.value = false
  activeActivity.value = null
  activityFiles.value = []
}

function getFileName(item) {
  if (!item?.file_url) return item?.title || item?.original_name || 'file';

  try {
    const url = new URL(item.file_url);
    const name = decodeURIComponent(url.pathname.split('/').pop() || '');
    return name || item.title || item.original_name || 'file';
  } catch {
    const raw = item.file_url.split('/').pop();
    return raw || item.title || item.original_name || 'file';
  }
}

async function downloadFile(item) {
  try {
    const response = await fetch(item.file_url, { credentials: 'include' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = getFileName(item);
    document.body.appendChild(link);
    link.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
  } catch {
    window.open(item.file_url, '_blank');
  }
}

function getPortfolioActivityName(item) {
  if (item.activities && item.activities.length > 0) {
    return item.activities[0].title;
  }
  return null;
}

function getPortfolioActivityDates(item) {
  if (item.activities && item.activities.length > 0) {
    const activity = item.activities[0];
    if (activity.date_start && activity.date_end) {
      const start = new Date(activity.date_start).toLocaleDateString('ru-RU');
      const end = new Date(activity.date_end).toLocaleDateString('ru-RU');
      return `${start} — ${end}`;
    }
    if (activity.date_start) {
      return new Date(activity.date_start).toLocaleDateString('ru-RU');
    }
  }
  return null;
}

function formatDate(dateString) {
  return dateString ? new Date(dateString).toLocaleDateString('ru-RU') : null;
}

function isImageExtension(ext) {
  return ['jpg', 'jpeg', 'png'].includes(ext.toLowerCase());
}

function getPortfolioDescription(item) {
  if (!item.description) return null;
  const linkMatch = item.description.match(/^LINK:.+?(\r\n\r\n|\n\n)(.+)$/s);
  return linkMatch
    ? linkMatch[2].trim()
    : item.description.startsWith('LINK:')
      ? null
      : item.description;
}

function getPortfolioLink(item) {
  if (!item) return null;
  if (item.link) {
    return item.link;
  }
  if (!item.description) return null;
  const linkMatch = item.description.match(/^LINK:(.+?)(\r\n\r\n|\n\n|$)/);
  return linkMatch ? linkMatch[1].trim() : null;
}
</script>
