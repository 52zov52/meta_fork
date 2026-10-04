<template>
  <div class="flex flex-col space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
      <div class="flex flex-wrap gap-2">
        <USelect v-model="statusFilter" :items="statusOptions" size="sm" class="min-w-[140px]" @update:model-value="applyFilters" />
        <USelect v-model="typeFilter" :items="typeOptions" size="sm" class="min-w-[140px]" @update:model-value="applyFilters" />
        <USelect v-model="employerFilter" :items="employerOptions" size="sm" class="min-w-[180px]" @update:model-value="applyFilters" />
      </div>
      <UButton label="Добавить вакансию" icon="i-heroicons-plus-circle" size="xs" color="primary" square variant="solid" @click="openCreateModal" />
    </div>

    <div class="flex space-x-2">
      <UInput v-model="searchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск вакансий по названию" @input="debouncedSearch" @keyup.enter.prevent="applyFilters" />
      <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" class="shrink-0" @click="applyFilters" />
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
    </div>

    <div v-if="!loading">
      <div v-if="jobs.length > 0" class="flex flex-col gap-2">
        <div class="hidden lg:block">
          <table class="w-full text-sm">
            <thead>
              <tr>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Название</th>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Компания</th>
                <th class="w-24 border-b border-default px-4 py-2 font-semibold">Тип</th>
                <th class="w-24 border-b border-default px-4 py-2 font-semibold">Статус</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Уровень</th>
                <th class="w-20 border-b border-default px-4 py-2 font-semibold">З/п</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in jobs" :key="item.id" class="hover:bg-muted/50">
                <td class="border-b border-muted px-4 py-2 font-medium">{{ item.title }}</td>
                <td class="border-b border-muted px-4 py-2">{{ item.employer?.company_name || '—' }}</td>
                <td class="border-b border-muted px-4 py-2 text-center">
                  <UBadge :color="item.type === 'vacancy' ? 'primary' : 'info'" variant="soft">{{ item.type === 'vacancy' ? 'Вакансия' : 'Стажировка' }}</UBadge>
                </td>
                <td class="border-b border-muted px-4 py-2 text-center">
                  <UBadge :color="item.status === 'active' ? 'success' : 'neutral'" variant="solid">{{ item.status === 'active' ? 'Активна' : 'Заморожена' }}</UBadge>
                </td>
                <td class="border-b border-muted px-4 py-2 text-center text-xs">{{ item.education_level || '—' }}</td>
                <td class="border-b border-muted px-4 py-2 text-center">{{ item.salary ?? '—' }}</td>
                <td class="border-b border-muted px-4 py-2 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <UButton icon="i-heroicons-eye" color="primary" variant="ghost" size="xs" square @click="view(item.id)" />
                    <UButton icon="i-heroicons-pencil-square" color="warning" variant="ghost" size="xs" square @click="edit(item.id)" />
                    <UButton icon="i-heroicons-lock-closed" color="error" variant="ghost" size="xs" square @click="toggleStatus(item)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="lg:hidden grid gap-2">
          <div v-for="item in jobs" :key="item.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
            <div class="font-medium">{{ item.title }}</div>
            <div class="text-xs text-muted">{{ item.employer?.company_name || '—' }}</div>
            <div class="flex flex-wrap gap-2">
              <UBadge :color="item.type === 'vacancy' ? 'primary' : 'info'" variant="soft">{{ item.type === 'vacancy' ? 'Вакансия' : 'Стажировка' }}</UBadge>
              <UBadge :color="item.status === 'active' ? 'success' : 'neutral'" variant="solid">{{ item.status === 'active' ? 'Активна' : 'Заморожена' }}</UBadge>
            </div>
            <div class="text-xs">Уровень: {{ item.education_level || '—' }} | З/п: {{ item.salary ?? '—' }}</div>
            <div class="grid grid-cols-3 gap-2">
              <UButton icon="i-heroicons-eye" color="primary" variant="outline" size="sm" @click="view(item.id)" />
              <UButton icon="i-heroicons-pencil-square" color="warning" variant="outline" size="sm" @click="edit(item.id)" />
              <UButton icon="i-heroicons-lock-closed" color="error" variant="outline" size="sm" @click="toggleStatus(item)" />
            </div>
          </div>
        </div>
        <div class="flex justify-end">
          <ListPagination :pagination="pagination" @update:page="onPageChange" />
        </div>
      </div>
      <UAlert v-if="jobs.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Нет вакансий" description="Список пуст." />
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model:open="showModal" :ui="{ content: 'w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto' }">
      <template #header>
        <h3 class="text-lg font-semibold text-default">{{ editingId ? 'Редактировать' : 'Добавить' }} вакансию</h3>
      </template>
      <template #body>
        <form @submit.prevent="save" class="space-y-0">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Название<span class="ml-1 text-error">*</span></h2></div>
            <div class="flex flex-col"><UInput v-model="form.title" placeholder="Название вакансии" /><ul v-for="(err, idx) in fieldErrors(errors.title)" :key="idx"><li class="text-xs text-error">{{ err }}</li></ul></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Работодатель<span class="ml-1 text-error">*</span></h2></div>
            <div class="flex flex-col"><USelect v-model="form.employer_id" :items="employerSelectOptions" placeholder="Выберите компанию" /><ul v-for="(err, idx) in fieldErrors(errors.employer_id)" :key="idx"><li class="text-xs text-error">{{ err }}</li></ul></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Тип</h2></div>
            <div><USelect v-model="form.type" :items="[{ label: 'Вакансия', value: 'vacancy' }, { label: 'Стажировка', value: 'internship' }]" /></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Статус</h2></div>
            <div><USelect v-model="form.status" :items="[{ label: 'Активна', value: 'active' }, { label: 'Заморожена', value: 'frozen' }]" /></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Уровень обучения</h2></div>
            <div><UInput v-model="form.education_level" placeholder="Бакалавриат, Магистратура..." /></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Специальность</h2></div>
            <div><UInput v-model="form.specialty" placeholder="Специальность" /></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Локация</h2></div>
            <div><UInput v-model="form.location" placeholder="Город" /></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Зарплата (баллы)</h2></div>
            <div><UInput v-model="form.salary" type="number" placeholder="0" /></div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div><h2 class="text-sm font-semibold">Описание</h2></div>
            <div><UTextarea v-model="form.description" :rows="4" /></div>
          </section>
          <div class="flex justify-end py-4">
            <UButton type="submit" color="success" :loading="saving">{{ editingId ? 'Сохранить' : 'Добавить' }}</UButton>
          </div>
        </form>
      </template>
    </UModal>

    <!-- View Modal -->
    <UModal v-model:open="showViewModal" :ui="{ content: 'w-full sm:max-w-2xl' }">
      <template #header><h3 class="text-lg font-semibold text-default">{{ viewData?.title }}</h3></template>
      <template #body>
        <div v-if="viewData" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-xs text-muted">Компания</label><p>{{ viewData.employer?.company_name || '—' }}</p></div>
            <div><label class="text-xs text-muted">Тип</label><p><UBadge :color="viewData.type === 'vacancy' ? 'primary' : 'info'" variant="soft">{{ viewData.type === 'vacancy' ? 'Вакансия' : 'Стажировка' }}</UBadge></p></div>
            <div><label class="text-xs text-muted">Статус</label><p><UBadge :color="viewData.status === 'active' ? 'success' : 'neutral'" variant="solid">{{ viewData.status === 'active' ? 'Активна' : 'Заморожена' }}</UBadge></p></div>
            <div><label class="text-xs text-muted">Уровень обучения</label><p>{{ viewData.education_level || '—' }}</p></div>
            <div><label class="text-xs text-muted">Специальность</label><p>{{ viewData.specialty || '—' }}</p></div>
            <div><label class="text-xs text-muted">Локация</label><p>{{ viewData.location || '—' }}</p></div>
            <div><label class="text-xs text-muted">Зарплата</label><p>{{ viewData.salary ?? '—' }}</p></div>
          </div>
          <div v-if="viewData.employer?.contact_email || viewData.employer?.contact_phone">
            <label class="text-xs text-muted">Контакты работодателя</label>
            <p class="text-sm">{{ viewData.employer?.contact_email }} {{ viewData.employer?.contact_phone ? '| ' + viewData.employer?.contact_phone : '' }}</p>
          </div>
          <div v-if="viewData.description"><label class="text-xs text-muted">Описание</label><p class="text-sm mt-1">{{ viewData.description }}</p></div>
        </div>
      </template>
      <template #footer><UButton color="neutral" variant="outline" @click="showViewModal = false">Закрыть</UButton></template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Вакансии и стажировки',
  description: 'Модерация вакансий',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Вакансии' }],
})

const { get, post, put, patch, delete: del } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const jobs = ref<any[]>([])
const loading = ref(false)
const searchTxt = ref('')
const statusFilter = ref('all')
const typeFilter = ref('all')
const employerFilter = ref('all')
const showModal = ref(false)
const showViewModal = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const form = ref({ title: '', employer_id: 0, type: 'vacancy', status: 'active', education_level: '', specialty: '', location: '', salary: 0, description: '' })
const errors = ref<Record<string, string>>({})
const viewData = ref<any>(null)
const pagination = ref({ currentPage: 1, lastPage: 1, perPage: 20, totalItems: 0 })
const employers = ref<any[]>([])

const statusOptions = [
  { label: 'Все статусы', value: 'all' },
  { label: 'Активные', value: 'active' },
  { label: 'Замороженные', value: 'frozen' },
]
const typeOptions = [
  { label: 'Все типы', value: 'all' },
  { label: 'Вакансии', value: 'vacancy' },
  { label: 'Стажировки', value: 'internship' },
]
const employerOptions = computed(() => [
  { label: 'Все работодатели', value: 'all' },
  ...employers.value.map((e: any) => ({ label: e.company_name, value: e.id })),
])
const employerSelectOptions = computed(() => [
  { label: 'Выберите работодателя', value: 0 },
  ...employers.value.map((e: any) => ({ label: e.company_name, value: e.id })),
])

function fieldErrors(value: string | string[] | null | undefined) {
  if (value == null || value === '') return []
  if (Array.isArray(value)) return value
  return [String(value)]
}

function resetForm() {
  form.value = { title: '', employer_id: 0, type: 'vacancy', status: 'active', education_level: '', specialty: '', location: '', salary: 0, description: '' }
  errors.value = {}
  editingId.value = null
}

function openCreateModal() {
  resetForm()
  showModal.value = true
}

function buildUrl(page = 1) {
  const p = new URLSearchParams({ page: String(page) })
  if (statusFilter.value !== 'all') p.set('status', statusFilter.value)
  if (typeFilter.value !== 'all') p.set('type', typeFilter.value)
  if (employerFilter.value !== 'all') p.set('employer_id', employerFilter.value)
  const q = String(searchTxt.value || '').trim()
  if (q) p.set('search', q)
  return `/api/v1/moderator/jobs?${p.toString()}`
}

function loadData() {
  loading.value = true
  get<any>(buildUrl(pagination.value.currentPage)).then(data => {
    jobs.value = Array.isArray(data.data) ? data.data : []
    pagination.value.currentPage = data.current_page ?? 1
    pagination.value.lastPage = data.last_page ?? 1
    pagination.value.totalItems = data.total ?? 0
  }).catch(e => showApiError(e, 'Ошибка')).finally(() => { loading.value = false })
}

function applyFilters() { pagination.value.currentPage = 1; loadData() }

let st: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() { if (st) clearTimeout(st); st = setTimeout(() => applyFilters(), 500) }

function onPageChange(page: number) { pagination.value.currentPage = page; loadData() }

function edit(id: number) {
  resetForm()
  get<any>(`/api/v1/moderator/jobs/${id}`).then(data => {
    form.value.title = data.title ?? ''
    form.value.employer_id = data.employer_id ?? 0
    form.value.type = data.type ?? 'vacancy'
    form.value.status = data.status ?? 'active'
    form.value.education_level = data.education_level ?? ''
    form.value.specialty = data.specialty ?? ''
    form.value.location = data.location ?? ''
    form.value.salary = data.salary ?? 0
    form.value.description = data.description ?? ''
    editingId.value = id
    showModal.value = true
  }).catch(e => showApiError(e, 'Ошибка'))
}

function view(id: number) {
  get<any>(`/api/v1/moderator/jobs/${id}`).then(data => { viewData.value = data; showViewModal.value = true }).catch(e => showApiError(e, 'Ошибка'))
}

function save() {
  saving.value = true; errors.value = {}
  if (!form.value.title.trim()) { errors.value.title = 'Обязательное поле'; saving.value = false; return }
  if (!form.value.employer_id) { errors.value.employer_id = 'Выберите работодателя'; saving.value = false; return }

  const body = { ...form.value, salary: Number(form.value.salary) || 0 }
  const apiCall = editingId.value ? put<any>(`/api/v1/moderator/jobs/${editingId.value}`, body) : post<any>('/api/v1/moderator/jobs', body)

  apiCall.then(data => {
    toast.add({ title: data.message || 'Сохранено', color: 'success' })
    showModal.value = false; loadData()
  }).catch(error => {
    if (error?.response?._data?.errors) Object.assign(errors.value, error.response._data.errors)
    showApiError(error, 'Ошибка')
  }).finally(() => { saving.value = false })
}

function toggleStatus(item: any) {
  const action = item.status === 'active' ? 'заморозить' : 'активировать'
  if (!confirm(`Вы уверены, что хотите ${action} вакансию «${item.title}»?`)) return
  patch<any>(`/api/v1/moderator/jobs/${item.id}/toggle-status`).then(data => {
    toast.add({ title: data.message || 'Статус изменен', color: 'success' }); loadData()
  }).catch(e => showApiError(e, 'Ошибка'))
}

function fmtDate(d: string | undefined) { if (!d) return '—'; return new Date(d).toLocaleDateString('ru-RU', { year: 'numeric', month: 'short', day: 'numeric' }) }

function loadEmployers() {
  get<any>('/api/v1/moderator/employers?per_page=100').then(data => {
    employers.value = Array.isArray(data.data) ? data.data : []
  }).catch(() => {})
}

onMounted(() => { loadData(); loadEmployers() })
</script>
