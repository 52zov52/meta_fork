<template>
  <div class="flex flex-col space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
      <div class="flex flex-wrap gap-2">
        <USelect v-model="statusFilter" :items="statusOptions" size="sm" class="min-w-[150px]" @update:model-value="applyFilters" />
      </div>
      <UButton label="Добавить работодателя" icon="i-heroicons-plus-circle" size="xs" color="primary" square variant="solid" @click="openCreateModal" />
    </div>

    <div class="flex space-x-2">
      <UInput v-model="searchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск по названию, email, контактному лицу" @input="debouncedSearch" @keyup.enter.prevent="applyFilters" />
      <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" class="shrink-0" @click="applyFilters" />
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
      <p class="text-muted">Загрузка...</p>
    </div>

    <div v-if="!loading">
      <div v-if="employers.length > 0" class="flex flex-col gap-2">
        <div class="hidden lg:block">
          <table class="w-full text-sm">
            <thead>
              <tr>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Компания</th>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Контакты</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Статус</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Дата рег.</th>
                <th class="w-32 border-b border-default px-4 py-2 font-semibold">Учреждение</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in employers" :key="item.id" class="hover:bg-muted/50">
                <td class="border-b border-muted px-4 py-2 font-medium">{{ item.company_name }}</td>
                <td class="border-b border-muted px-4 py-2">
                  <div class="text-xs text-muted">{{ item.contact_email }}</div>
                  <div class="text-xs text-muted">{{ item.contact_person }}</div>
                </td>
                <td class="border-b border-muted px-4 py-2 text-center">
                  <UBadge :color="item.status === 'active' ? 'success' : 'error'" variant="solid">{{ item.status === 'active' ? 'Активен' : 'Заблокирован' }}</UBadge>
                </td>
                <td class="border-b border-muted px-4 py-2 text-center text-xs">{{ fmtDate(item.created_at) }}</td>
                <td class="border-b border-muted px-4 py-2 text-center text-xs">{{ item.institution?.name || '—' }}</td>
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
          <div v-for="item in employers" :key="item.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
            <div class="font-medium">{{ item.company_name }}</div>
            <div class="text-xs text-muted">{{ item.contact_email }} | {{ item.contact_person }}</div>
            <div class="flex items-center gap-2">
              <UBadge :color="item.status === 'active' ? 'success' : 'error'" variant="solid" class="w-fit">{{ item.status === 'active' ? 'Активен' : 'Заблокирован' }}</UBadge>
              <span class="text-xs text-muted">{{ fmtDate(item.created_at) }}</span>
            </div>
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
      <UAlert v-if="employers.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Нет работодателей" description="Список пуст. Нажмите «Добавить работодателя» для создания." />
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model:open="showModal" :ui="{ content: 'w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto' }">
      <template #header>
        <h3 class="text-lg font-semibold text-default">{{ editingId ? 'Редактировать' : 'Добавить' }} работодателя</h3>
      </template>
      <template #body>
        <form @submit.prevent="save" class="space-y-0">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Название компании<span class="ml-1 text-error">*</span></h2>
            </div>
            <div class="flex flex-col space-y-1">
              <UInput v-model="form.company_name" placeholder="ООО «Ромашка»" />
              <ul v-for="(err, idx) in fieldErrors(errors.company_name)" :key="idx"><li class="text-xs text-error">{{ err }}</li></ul>
            </div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Контактное лицо</h2>
            </div>
            <div class="flex flex-col space-y-1">
              <UInput v-model="form.contact_person" placeholder="Иванов Иван" />
            </div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Email</h2>
            </div>
            <div class="flex flex-col space-y-1">
              <UInput v-model="form.contact_email" type="email" placeholder="company@mail.ru" />
              <ul v-for="(err, idx) in fieldErrors(errors.contact_email)" :key="idx"><li class="text-xs text-error">{{ err }}</li></ul>
            </div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Телефон</h2>
            </div>
            <div class="flex flex-col space-y-1">
              <UInput v-model="form.contact_phone" placeholder="+7 (999) 123-45-67" />
            </div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Сайт</h2>
            </div>
            <div class="flex flex-col space-y-1">
              <UInput v-model="form.website" placeholder="https://example.com" />
            </div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Учреждение-куратор<span class="ml-1 text-error">*</span></h2>
            </div>
            <div class="flex flex-col space-y-1">
              <USelect v-model="form.institution_id" :items="institutionOptions" placeholder="Выберите учреждение" />
              <ul v-for="(err, idx) in fieldErrors(errors.institution_id)" :key="idx"><li class="text-xs text-error">{{ err }}</li></ul>
            </div>
          </section>
          <hr class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-sm font-semibold">Описание</h2>
            </div>
            <div class="flex flex-col space-y-1">
              <UTextarea v-model="form.description" :rows="3" />
            </div>
          </section>
          <div class="flex justify-end py-4">
            <UButton type="submit" color="success" :loading="saving">{{ editingId ? 'Сохранить' : 'Добавить' }}</UButton>
          </div>
        </form>
      </template>
    </UModal>

    <!-- View Modal -->
    <UModal v-model:open="showViewModal" :ui="{ content: 'w-full sm:max-w-2xl' }">
      <template #header>
        <h3 class="text-lg font-semibold text-default">{{ viewData?.company_name }}</h3>
      </template>
      <template #body>
        <div v-if="viewData" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-xs text-muted">Статус</label><p><UBadge :color="viewData.status === 'active' ? 'success' : 'error'" variant="solid">{{ viewData.status === 'active' ? 'Активен' : 'Заблокирован' }}</UBadge></p></div>
            <div><label class="text-xs text-muted">Email</label><p>{{ viewData.contact_email || '—' }}</p></div>
            <div><label class="text-xs text-muted">Контактное лицо</label><p>{{ viewData.contact_person || '—' }}</p></div>
            <div><label class="text-xs text-muted">Телефон</label><p>{{ viewData.contact_phone || '—' }}</p></div>
            <div><label class="text-xs text-muted">Сайт</label><p>{{ viewData.website || '—' }}</p></div>
            <div><label class="text-xs text-muted">Учреждение</label><p>{{ viewData.institution?.name || '—' }}</p></div>
          </div>
          <div v-if="viewData.description"><label class="text-xs text-muted">Описание</label><p class="text-sm mt-1">{{ viewData.description }}</p></div>
          <div v-if="viewData.jobs?.length"><label class="text-xs text-muted">Вакансии ({{ viewData.jobs.length }})</label>
            <ul class="list-disc list-inside text-sm mt-1">
              <li v-for="j in viewData.jobs" :key="j.id">{{ j.title }} — <UBadge :color="j.status === 'active' ? 'success' : 'neutral'" variant="soft" size="xs">{{ j.status === 'active' ? 'Активна' : 'Заморожена' }}</UBadge></li>
            </ul>
          </div>
        </div>
      </template>
      <template #footer>
        <UButton color="neutral" variant="outline" @click="showViewModal = false">Закрыть</UButton>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Работодатели',
  description: 'Модерация и управление компаниями',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Работодатели' }],
})

const { get, post, put, delete: del, patch } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const employers = ref<any[]>([])
const loading = ref(false)
const searchTxt = ref('')
const statusFilter = ref('all')
const showModal = ref(false)
const showViewModal = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const form = ref({ company_name: '', contact_person: '', contact_email: '', contact_phone: '', website: '', description: '', institution_id: 0 })
const errors = ref<Record<string, string>>({})
const viewData = ref<any>(null)
const pagination = ref({ currentPage: 1, lastPage: 1, perPage: 20, totalItems: 0 })
const institutions = ref<any[]>([])

const statusOptions = [
  { label: 'Все статусы', value: 'all' },
  { label: 'Активные', value: 'active' },
  { label: 'Заблокированные', value: 'blocked' },
]

const institutionOptions = computed(() => [
  { label: 'Выберите учреждение', value: 0 },
  ...institutions.value.map((i: any) => ({ label: i.name, value: i.id })),
])

function fieldErrors(value: string | string[] | null | undefined) {
  if (value == null || value === '') return []
  if (Array.isArray(value)) return value
  return [String(value)]
}

function resetForm() {
  form.value = { company_name: '', contact_person: '', contact_email: '', contact_phone: '', website: '', description: '', institution_id: 0 }
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
  const q = String(searchTxt.value || '').trim()
  if (q) p.set('search', q)
  return `/api/v1/moderator/employers?${p.toString()}`
}

function loadData() {
  loading.value = true
  get<any>(buildUrl(pagination.value.currentPage)).then(data => {
    employers.value = Array.isArray(data.data) ? data.data : []
    pagination.value.currentPage = data.current_page ?? 1
    pagination.value.lastPage = data.last_page ?? 1
    pagination.value.totalItems = data.total ?? 0
  }).catch(e => showApiError(e, 'Ошибка')).finally(() => { loading.value = false })
}

function applyFilters() {
  pagination.value.currentPage = 1
  loadData()
}

let st: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
  if (st) clearTimeout(st)
  st = setTimeout(() => applyFilters(), 500)
}

function onPageChange(page: number) {
  pagination.value.currentPage = page
  loadData()
}

function edit(id: number) {
  resetForm()
  get<any>(`/api/v1/moderator/employers/${id}`).then(data => {
    form.value.company_name = data.company_name ?? ''
    form.value.contact_person = data.contact_person ?? ''
    form.value.contact_email = data.contact_email ?? ''
    form.value.contact_phone = data.contact_phone ?? ''
    form.value.website = data.website ?? ''
    form.value.description = data.description ?? ''
    form.value.institution_id = data.institution_id ?? 0
    editingId.value = id
    showModal.value = true
  }).catch(e => showApiError(e, 'Ошибка'))
}

function view(id: number) {
  get<any>(`/api/v1/moderator/employers/${id}`).then(data => {
    viewData.value = data
    showViewModal.value = true
  }).catch(e => showApiError(e, 'Ошибка'))
}

function save() {
  saving.value = true
  errors.value = {}
  if (!form.value.company_name.trim()) { errors.value.company_name = 'Обязательное поле'; saving.value = false; return }
  if (!form.value.institution_id) { errors.value.institution_id = 'Выберите учреждение'; saving.value = false; return }

  const body = { ...form.value }
  const apiCall = editingId.value
    ? put<any>(`/api/v1/moderator/employers/${editingId.value}`, body)
    : post<any>('/api/v1/moderator/employers', body)

  apiCall.then(data => {
    toast.add({ title: data.message || 'Сохранено', color: 'success' })
    showModal.value = false
    loadData()
  }).catch(error => {
    if (error?.response?._data?.errors) Object.assign(errors.value, error.response._data.errors)
    showApiError(error, 'Ошибка')
  }).finally(() => { saving.value = false })
}

function toggleStatus(item: any) {
  const action = item.status === 'active' ? 'заблокировать' : 'разблокировать'
  if (!confirm(`Вы уверены, что хотите ${action} компанию «${item.company_name}»?`)) return
  patch<any>(`/api/v1/moderator/employers/${item.id}/toggle-status`).then(data => {
    toast.add({ title: data.message || 'Статус изменен', color: 'success' })
    loadData()
  }).catch(e => showApiError(e, 'Ошибка'))
}

function fmtDate(d: string | undefined) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', { year: 'numeric', month: 'short', day: 'numeric' })
}

function loadInstitutions() {
  get<any>('/api/v1/institutions').then(data => {
    institutions.value = Array.isArray(data) ? data : []
  }).catch(() => {})
}

onMounted(() => {
  loadData()
  loadInstitutions()
})
</script>
