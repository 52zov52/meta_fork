<template>
  <div class="flex flex-col space-y-4">
    <!-- Mobile filters -->
    <div class="md:hidden">
      <div class="flex flex-wrap justify-between items-center gap-2">
        <UButton label="Фильтры" icon="i-heroicons-funnel" size="sm" color="neutral" variant="outline" @click="showMobileFilters = !showMobileFilters" />
        <div class="flex space-x-2">
          <UButton :label="sortMethod === 'desc' ? 'A-Z' : 'Z-A'" icon="i-heroicons-arrows-up-down" size="xs" color="primary" square variant="solid" @click="sort" />
          <UButton label="Добавить" icon="i-heroicons-plus-circle" size="xs" color="primary" square variant="solid" @click="openCreateModal" />
        </div>
      </div>
      <div v-if="showMobileFilters" class="mt-3 p-4 bg-muted rounded-lg border border-default space-y-4">
        <USelect v-model="statusFilter" :items="statusSelectOptions" size="sm" class="w-full" @update:model-value="applyFilters" />
        <USelect v-model="sortBy" :items="sortFieldOptions" size="sm" class="w-full" @update:model-value="applyFilters" />
      </div>
    </div>

    <!-- Desktop filters -->
    <div class="hidden md:block">
      <div class="flex flex-wrap justify-between items-center gap-2">
        <div class="flex flex-wrap space-x-2 gap-y-2">
          <USelect v-model="statusFilter" :items="statusSelectOptions" size="sm" class="min-w-[160px]" @update:model-value="applyFilters" />
          <USelect v-model="sortBy" :items="sortFieldOptions" size="sm" class="min-w-[190px]" @update:model-value="applyFilters" />
        </div>
        <div class="flex flex-wrap space-x-2 gap-y-2">
          <UButton :label="sortMethod === 'desc' ? 'По названию A-Z' : 'По названию Z-A'" icon="i-heroicons-arrows-up-down" size="xs" color="primary" square variant="solid" @click="sort" />
          <UButton label="Добавить новую запись" icon="i-heroicons-plus-circle" size="xs" color="primary" square variant="solid" @click="openCreateModal" />
        </div>
      </div>
    </div>

    <div class="flex space-x-2">
      <UInput v-model="searchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск мероприятий по наименованию" @input="debouncedSearch" @keyup.enter.prevent="search" />
      <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" class="shrink-0" @click="search" />
    </div>

    <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
      <p class="text-muted">Загрузка мероприятий...</p>
    </div>

    <div v-if="status === 'success'">
      <div v-if="activities.length !== 0" class="flex flex-col gap-2">
        <ul>
          <li v-for="item in activities" :key="item.id">
            <hr role="presentation" class="w-full border-t border-default">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 md:p-6">
              <div class="flex flex-col sm:flex-row gap-4 md:gap-6">
                <div
                  :style="{ backgroundImage: item.photo_path ? `url('${config.public.API_PATH_STORAGE + item.photo_path}')` : `url('${staticImg.bgCard}')` }"
                  class="h-20 w-28 md:h-24 md:w-32 shrink-0 bg-cover rounded self-center sm:self-start cursor-pointer hover:opacity-90 transition-opacity"
                  @click="openImagePreview(item.photo_path)"
                />
                <div class="space-y-2 md:space-y-1.5 flex-1">
                  <div class="text-base md:text-base/6 font-semibold text-center sm:text-left text-default">
                    {{ item.title }}
                  </div>
                  <div class="text-xs text-muted text-center sm:text-left">с {{ item.date_start }} по {{ item.date_end }}</div>
                  <div v-if="item.description" class="w-full text-xs text-muted mt-1 max-w-md mx-auto text-center sm:mx-0 sm:text-left">{{ truncuteText(item.description, 100) }}</div>
                  <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-xs">
                    <span class="text-muted text-center sm:text-left">{{ item.score }} балл(ов)</span>
                    <div class="flex items-center justify-center sm:justify-start space-x-3">
                      <UTooltip text="Всего участников">
                        <div class="flex items-center space-x-1">
                          <UIcon name="i-heroicons-users" class="w-4 h-4 text-primary" />
                          <span class="text-primary font-medium tabular-nums">{{ item.participants_count ?? '0' }}</span>
                        </div>
                      </UTooltip>
                    </div>
                  </div>
                  <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2">
                    <span class="text-xs text-muted text-center sm:text-left">{{ item.planet_name }}</span>
                  </div>
                </div>
              </div>
              <div class="flex flex-col sm:flex-row items-center gap-2 mt-4 md:mt-0">
                <UBadge v-if="item.status_eng === 'draft'" color="warning" variant="solid" class="md:max-sm:hidden">{{ item.status }}</UBadge>
                <UBadge v-if="item.status_eng === 'scheduled'" color="info" variant="solid" class="md:max-sm:hidden">{{ item.status }}</UBadge>
                <UBadge v-if="item.status_eng === 'started'" color="primary" variant="solid" class="md:max-sm:hidden">{{ item.status }}</UBadge>
                <UBadge v-if="item.status_eng === 'completed'" color="success" variant="solid" class="md:max-sm:hidden">{{ item.status }}</UBadge>
                <UBadge v-if="item.status_eng === 'canceled'" color="error" variant="solid" class="md:max-sm:hidden">{{ item.status }}</UBadge>
                <div class="flex items-center justify-center gap-2">
                  <UButton icon="i-heroicons-pencil-square" color="warning" variant="outline" size="sm" square @click="edit(item.id)" />
                  <UButton icon="i-heroicons-trash" color="error" variant="outline" size="sm" square @click="destroy(item.id)" />
                </div>
              </div>
            </div>
          </li>
        </ul>
        <div class="flex justify-center md:justify-end">
          <ListPagination :pagination="pagination" @update:page="onPageChange" />
        </div>
      </div>
      <UAlert v-if="activities.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Информация" description="Список мероприятий пуст. Для добавления нового мероприятия нажмите на кнопку «Добавить новую запись»" />
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model:open="showModal" :ui="{ content: 'w-full sm:max-w-3xl max-h-[90vh] overflow-y-auto' }">
      <template #header>
        <h3 class="text-lg font-semibold leading-6 text-default">{{ editingId ? 'Редактировать мероприятие' : 'Создать мероприятие' }}</h3>
      </template>
      <template #body>
        <form class="space-y-0" @submit.prevent="saveActivity">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Наименование<span class="ml-1 text-error">*</span></h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Максимум 255 символов.</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <UInput v-model="form.title" class="w-full" placeholder="Название мероприятия" />
              <ul v-for="(error, idx) in fieldErrors(errors.title)" :key="idx">
                <li class="text-xs text-error">{{ error }}</li>
              </ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Описание<span class="ml-1 text-error">*</span></h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Максимум 5000 символов.</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <UTextarea v-model="form.description" class="w-full" :rows="6" autoresize />
              <ul v-for="(error, idx) in fieldErrors(errors.description)" :key="idx">
                <li class="text-xs text-error">{{ error }}</li>
              </ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Дата старта<span class="ml-1 text-error">*</span></h2>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <RuDateInput v-model="form.date_start" />
              <ul v-for="(error, idx) in fieldErrors(errors.date_start)" :key="idx">
                <li class="text-xs text-error">{{ error }}</li>
              </ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Дата завершения<span class="ml-1 text-error">*</span></h2>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <RuDateInput v-model="form.date_end" />
              <ul v-for="(error, idx) in fieldErrors(errors.date_end)" :key="idx">
                <li class="text-xs text-error">{{ error }}</li>
              </ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Баллы</h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Количество баллов за участие.</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <UInput v-model="form.score" type="number" class="w-full" />
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Направление<span class="ml-1 text-error">*</span></h2>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <USelect v-model="form.planet_id" :items="planetSelectItems" placeholder="Выберите направление" class="w-full" />
              <ul v-for="(error, idx) in fieldErrors(errors.planet_id)" :key="idx">
                <li class="text-xs text-error">{{ error }}</li>
              </ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Категория<span class="ml-1 text-error">*</span></h2>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <USelect v-model="form.category_id" :items="categorySelectItems" placeholder="Выберите категорию" class="w-full" />
              <ul v-for="(error, idx) in fieldErrors(errors.category_id)" :key="idx">
                <li class="text-xs text-error">{{ error }}</li>
              </ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Изображение</h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Обложка мероприятия (jpeg, png).</p>
            </div>
            <div class="flex flex-col space-y-3 justify-center">
              <UFileUpload v-model="uploadFile" accept="image/*" :multiple="false" :preview="false" variant="area" label="Выберите файл" description="Изображение до 2 Мб" class="w-full" />
              <div v-if="form.photo_path && !uploadFile" class="space-y-2">
                <p class="text-sm font-medium">Текущее изображение:</p>
                <img :src="config.public.API_PATH_STORAGE + form.photo_path" class="w-20 h-20 object-cover rounded border border-default">
              </div>
            </div>
          </section>
          <div class="flex justify-end py-4">
            <UButton type="submit" color="success" :loading="saving">{{ editingId ? 'Сохранить изменения' : 'Добавить запись' }}</UButton>
          </div>
        </form>
      </template>
    </UModal>

    <ImagePreviewModal v-model:open="showImagePreview" :src="previewImageSrc" :fallback-src="staticImg.bgCard" />
  </div>
</template>

<script setup lang="ts">
import { staticImg } from '~/assets/img'
import { apiDateToRuDate, isValidRuDate, ruDateToApiDate } from '~/utils/ruDate'
import type { paths } from '~/types/api'

type PlanetsResponse = paths['/api/v1/admin/planets']['get']['responses'][200]['content']['application/json']
type PlanetItem = PlanetsResponse[number]

type CategoriesResponse = paths['/api/v1/categories']['get']['responses'][200]['content']['application/json']
type CategoryItem = CategoriesResponse[number]

type ModeratorActivity = {
  id: number
  title: string
  description?: string
  date_start: string
  date_end: string
  score?: number
  status?: string
  status_eng?: string
  planet_name?: string
  planet_id?: number
  category_id?: number
  photo_path?: string | null
  participants_count?: number
}

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Мероприятия',
  description: 'Управление мероприятиями учреждения',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Мероприятия' }],
})

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const { get, post, delete: del } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const activities = ref<ModeratorActivity[]>([])
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle')
const sortMethod = ref<'asc' | 'desc'>('desc')
const sortBy = ref<'created_at' | 'title' | 'date_start' | 'score'>('created_at')
const statusFilter = ref('all')
const searchTxt = ref('')
const isSearchApplied = ref(false)
const showMobileFilters = ref(false)
const saving = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const uploadFile = ref<File | null>(null)
const planets = ref<PlanetItem[]>([])
const categories = ref<CategoryItem[]>([])

const showImagePreview = ref(false)
const previewImageSrc = ref('')

function openImagePreview(photoPath?: string | null) {
  previewImageSrc.value = photoPath ? config.public.API_PATH_STORAGE + photoPath : ''
  showImagePreview.value = true
}

const statusSelectOptions = [
  { label: 'Все статусы', value: 'all' },
  { label: 'Черновик', value: 'draft' },
  { label: 'Запланировано', value: 'scheduled' },
  { label: 'Начато', value: 'started' },
  { label: 'Завершено', value: 'completed' },
  { label: 'Отменено', value: 'canceled' },
]

const sortFieldOptions = [
  { label: 'По дате создания', value: 'created_at' },
  { label: 'По названию', value: 'title' },
  { label: 'По дате начала', value: 'date_start' },
  { label: 'По баллам', value: 'score' },
]

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  prevPageUrl: null as string | null,
  nextPageUrl: null as string | null,
  perPage: 20,
  totalItems: 0,
})

const form = ref({
  title: '',
  description: '',
  date_start: '',
  date_end: '',
  score: 0,
  planet_id: 0,
  category_id: 0,
  photo_path: null as string | null,
})

const errors = ref({
  title: '',
  description: '',
  date_start: '',
  date_end: '',
  score: '',
  planet_id: '',
  category_id: '',
})

function fieldErrors(value: string | string[] | null | undefined) {
  if (value == null || value === '') return []
  if (Array.isArray(value)) return value
  return [String(value)]
}

function clearErrors() {
  errors.value = { title: '', description: '', date_start: '', date_end: '', score: '', planet_id: '', category_id: '' }
}

const planetSelectItems = computed(() => {
  return [
    { label: 'Выберите направление', value: 0 },
    ...planets.value.map(p => ({ label: p.name ?? '', value: p.id ?? 0 })),
  ]
})

const categorySelectItems = computed(() => {
  return [
    { label: 'Выберите категорию', value: 0 },
    ...categories.value.map(c => ({ label: c.name ?? '', value: c.id ?? 0 })),
  ]
})

function resetForm() {
  form.value = { title: '', description: '', date_start: '', date_end: '', score: 0, planet_id: 0, category_id: 0, photo_path: null }
  uploadFile.value = null
  editingId.value = null
  clearErrors()
}

function openCreateModal() {
  resetForm()
  showModal.value = true
}

async function loadData() {
  status.value = 'pending'
  try {
    const params: Record<string, any> = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
    }
    if (statusFilter.value !== 'all') params.status = statusFilter.value
    if (sortBy.value) {
      params.sort_by = sortBy.value
      params.sort_direction = sortMethod.value
    }
    if (isSearchApplied.value && searchTxt.value.trim()) {
      params.search = searchTxt.value.trim()
    }

    const data = await get<any>('/api/v1/moderator/activities', { query: params })
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    activities.value = list
    pagination.value.currentPage = data?.current_page ?? 1
    pagination.value.lastPage = data?.last_page ?? 1
    pagination.value.totalItems = data?.total ?? list.length
    pagination.value.prevPageUrl = data?.prev_page_url ?? null
    pagination.value.nextPageUrl = data?.next_page_url ?? null
    status.value = 'success'
  } catch (error) {
    status.value = 'error'
    showApiError(error, 'Ошибка загрузки мероприятий')
  }
}

function applyFilters() {
  isSearchApplied.value = false
  pagination.value.currentPage = 1
  loadData()
}

function sort() {
  sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc'
  applyFilters()
}

function search() {
  if (!searchTxt.value.trim()) {
    isSearchApplied.value = false
    applyFilters()
    return
  }
  isSearchApplied.value = true
  pagination.value.currentPage = 1
  loadData()
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    search()
  }, 400)
}

function onPageChange(page: number) {
  pagination.value.currentPage = page
  loadData()
}

async function edit(id: number) {
  const item = activities.value.find(a => a.id === id)
  if (!item) return
  resetForm()
  form.value.title = item.title ?? ''
  form.value.description = item.description ?? ''
  form.value.date_start = formatDateForInput(item.date_start)
  form.value.date_end = formatDateForInput(item.date_end)
  form.value.score = item.score ?? 0
  form.value.planet_id = item.planet_id ?? 0
  form.value.category_id = item.category_id ?? 0
  form.value.photo_path = item.photo_path ?? null
  editingId.value = id
  showModal.value = true
}

async function saveActivity() {
  saving.value = true
  clearErrors()

  if (!form.value.title.trim()) { errors.value.title = 'Название обязательно'; saving.value = false; return }
  if (!form.value.description.trim()) { errors.value.description = 'Описание обязательно'; saving.value = false; return }
  if (!form.value.date_start) { errors.value.date_start = 'Дата начала обязательна'; saving.value = false; return }
  if (!form.value.date_end) { errors.value.date_end = 'Дата окончания обязательна'; saving.value = false; return }
  if (!isValidRuDate(form.value.date_start)) { errors.value.date_start = 'Введите корректную дату'; saving.value = false; return }
  if (!isValidRuDate(form.value.date_end)) { errors.value.date_end = 'Введите корректную дату'; saving.value = false; return }
  if (!form.value.planet_id || form.value.planet_id <= 0) { errors.value.planet_id = 'Выберите направление'; saving.value = false; return }
  if (!form.value.category_id || form.value.category_id <= 0) { errors.value.category_id = 'Выберите категорию'; saving.value = false; return }

  try {
    const submitData = new FormData()
    submitData.append('title', form.value.title)
    submitData.append('description', form.value.description)
    submitData.append('date_start', ruDateToApiDate(form.value.date_start))
    submitData.append('date_end', ruDateToApiDate(form.value.date_end))
    submitData.append('score', String(form.value.score))
    submitData.append('planet_id', String(form.value.planet_id))
    submitData.append('category_id', String(form.value.category_id))
    if (uploadFile.value) submitData.append('photo', uploadFile.value)

    if (editingId.value) {
      submitData.append('_method', 'PUT')
      await post(`/api/v1/moderator/activities/${editingId.value}`, submitData)
      toast.add({ title: 'Мероприятие обновлено', color: 'success' })
    } else {
      await post('/api/v1/moderator/activities', submitData)
      toast.add({ title: 'Мероприятие создано', color: 'success' })
    }
    showModal.value = false
    loadData()
  } catch (error: any) {
    if (error?.response?._data?.errors) {
      Object.assign(errors.value, error.response._data.errors)
    }
    showApiError(error, 'Ошибка сохранения')
  } finally {
    saving.value = false
  }
}

async function destroy(id: number) {
  if (!confirm('Вы уверены, что хотите удалить данную запись?')) return
  try {
    await del(`/api/v1/moderator/activities/${id}`)
    toast.add({ title: 'Мероприятие удалено', color: 'success' })
    loadData()
  } catch (error) {
    showApiError(error, 'Ошибка удаления')
  }
}

function formatDateForInput(value?: string | null): string {
  return apiDateToRuDate(value)
}

function truncuteText(text: string, length: number) {
  if (text && text.length > length) return text.substring(0, length) + '...'
  return text || ''
}

async function loadPlanets() {
  try {
    const data = await get<PlanetsResponse>('/api/v1/planets')
    planets.value = data
  } catch (error) {
    console.error('Ошибка загрузки направлений:', error)
  }
}

async function loadCategories() {
  try {
    const data = await get<CategoriesResponse>('/api/v1/categories')
    categories.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Ошибка загрузки категорий:', error)
  }
}

onMounted(() => {
  loadData()
  loadPlanets()
  loadCategories()
})
</script>
