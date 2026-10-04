<template>
  <div class="flex flex-col space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
      <div class="flex flex-wrap gap-2">
        <USelect v-model="statusFilter" :items="statusOptions" size="sm" class="min-w-[150px]" @update:model-value="applyFilters" />
        <USelect v-model="employerFilter" :items="employerOptions" size="sm" class="min-w-[180px]" @update:model-value="applyFilters" />
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
    </div>

    <div v-if="!loading">
      <div v-if="responses.length > 0" class="flex flex-col gap-2">
        <div class="hidden lg:block">
          <table class="w-full text-sm">
            <thead>
              <tr>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Дата</th>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Студент</th>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Вакансия</th>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Компания</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Статус</th>
                <th class="w-32 border-b border-default px-4 py-2 font-semibold">Контакты</th>
                <th class="w-20 border-b border-default px-4 py-2 font-semibold">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in responses" :key="item.id" class="hover:bg-muted/50">
                <td class="border-b border-muted px-4 py-2 text-xs">{{ fmtDate(item.created_at) }}</td>
                <td class="border-b border-muted px-4 py-2">
                  <span class="font-medium">{{ formatStudent(item) }}</span>
                </td>
                <td class="border-b border-muted px-4 py-2">{{ item.job?.title || '—' }}</td>
                <td class="border-b border-muted px-4 py-2">{{ item.job?.employer?.company_name || '—' }}</td>
                <td class="border-b border-muted px-4 py-2 text-center">
                  <UBadge :color="statusColor(item.status)" variant="solid">{{ statusLabel(item.status) }}</UBadge>
                </td>
                <td class="border-b border-muted px-4 py-2 text-xs">
                  <div>{{ item.student_email || item.user?.email || '—' }}</div>
                  <div>{{ item.student_phone || '—' }}</div>
                </td>
                <td class="border-b border-muted px-4 py-2 text-center">
                  <UButton icon="i-heroicons-eye" color="primary" variant="ghost" size="xs" square @click="view(item.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="lg:hidden grid gap-2">
          <div v-for="item in responses" :key="item.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="font-medium">{{ formatStudent(item) }}</span>
              <UBadge :color="statusColor(item.status)" variant="solid">{{ statusLabel(item.status) }}</UBadge>
            </div>
            <div class="text-xs text-muted">{{ item.job?.title }} — {{ item.job?.employer?.company_name }}</div>
            <div class="text-xs text-muted">{{ fmtDate(item.created_at) }}</div>
            <div class="text-xs">Контакты: {{ item.student_email || item.user?.email || '—' }} {{ item.student_phone || '' }}</div>
            <UButton icon="i-heroicons-eye" color="primary" variant="outline" size="sm" @click="view(item.id)">Подробнее</UButton>
          </div>
        </div>
        <div class="flex justify-end">
          <ListPagination :pagination="pagination" @update:page="onPageChange" />
        </div>
      </div>
      <UAlert v-if="responses.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Нет откликов" description="Список пуст." />
    </div>

    <!-- View Modal -->
    <UModal v-model:open="showViewModal" :ui="{ content: 'w-full sm:max-w-2xl' }">
      <template #header><h3 class="text-lg font-semibold text-default">Отклик на вакансию</h3></template>
      <template #body>
        <div v-if="viewData" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="text-xs text-muted">Студент</label><p class="text-sm font-medium">{{ viewData.user?.member ? formatFullName(viewData.user.member) : viewData.user?.name || '—' }}</p></div>
            <div><label class="text-xs text-muted">Email студента</label><p>{{ viewData.student_email || viewData.user?.email || '—' }}</p></div>
            <div><label class="text-xs text-muted">Телефон студента</label><p>{{ viewData.student_phone || '—' }}</p></div>
            <div><label class="text-xs text-muted">Статус</label><p><UBadge :color="statusColor(viewData.status)" variant="solid">{{ statusLabel(viewData.status) }}</UBadge></p></div>
            <div><label class="text-xs text-muted">Вакансия</label><p>{{ viewData.job?.title || '—' }}</p></div>
            <div><label class="text-xs text-muted">Компания</label><p>{{ viewData.job?.employer?.company_name || '—' }}</p></div>
            <div><label class="text-xs text-muted">Дата отклика</label><p>{{ fmtDate(viewData.created_at) }}</p></div>
          </div>
          <div v-if="viewData.cover_letter">
            <label class="text-xs text-muted">Сопроводительное письмо</label>
            <p class="text-sm mt-1 whitespace-pre-wrap">{{ viewData.cover_letter }}</p>
          </div>
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
  title: 'Отклики',
  description: 'Отклики студентов на вакансии',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Отклики' }],
})

const { get } = useApi()
const { show: showApiError } = useApiError()

const responses = ref<any[]>([])
const loading = ref(false)
const statusFilter = ref('all')
const employerFilter = ref('all')
const showViewModal = ref(false)
const viewData = ref<any>(null)
const pagination = ref({ currentPage: 1, lastPage: 1, perPage: 20, totalItems: 0 })
const employers = ref<any[]>([])

const statusOptions = [
  { label: 'Все статусы', value: 'all' },
  { label: 'Отправлен', value: 'sent' },
  { label: 'Просмотрен', value: 'viewed' },
  { label: 'Принят', value: 'accepted' },
  { label: 'Отклонен', value: 'rejected' },
]

const employerOptions = computed(() => [
  { label: 'Все компании', value: 'all' },
  ...employers.value.map((e: any) => ({ label: e.company_name, value: e.id })),
])

function buildUrl(page = 1) {
  const p = new URLSearchParams({ page: String(page), sort: 'created_at', direction: 'desc' })
  if (statusFilter.value !== 'all') p.set('status', statusFilter.value)
  if (employerFilter.value !== 'all') p.set('employer_id', employerFilter.value)
  return `/api/v1/moderator/responses?${p.toString()}`
}

function loadData() {
  loading.value = true
  get<any>(buildUrl(pagination.value.currentPage)).then(data => {
    responses.value = Array.isArray(data.data) ? data.data : []
    pagination.value.currentPage = data.current_page ?? 1
    pagination.value.lastPage = data.last_page ?? 1
    pagination.value.totalItems = data.total ?? 0
  }).catch(e => showApiError(e, 'Ошибка')).finally(() => { loading.value = false })
}

function applyFilters() { pagination.value.currentPage = 1; loadData() }
function onPageChange(page: number) { pagination.value.currentPage = page; loadData() }

function view(id: number) {
  get<any>(`/api/v1/moderator/responses/${id}`).then(data => { viewData.value = data; showViewModal.value = true }).catch(e => showApiError(e, 'Ошибка'))
}

function formatStudent(item: any) {
  const m = item.user?.member
  if (m) return `${m.last_name || ''} ${m.first_name || ''} ${m.middle_name || ''}`.trim() || item.user?.name || '—'
  return item.user?.name || '—'
}

function formatFullName(m: any) {
  return `${m.last_name || ''} ${m.first_name || ''} ${m.middle_name || ''}`.trim() || '—'
}

function statusLabel(s?: string) {
  const map: Record<string, string> = { sent: 'Отправлен', viewed: 'Просмотрен', accepted: 'Принят', rejected: 'Отклонен' }
  return map[s || ''] || s || '—'
}

function statusColor(s?: string) {
  const map: Record<string, any> = { sent: 'info', viewed: 'warning', accepted: 'success', rejected: 'error' }
  return map[s || ''] || 'neutral' as any
}

function fmtDate(d: string | undefined) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function loadEmployers() {
  get<any>('/api/v1/moderator/employers?per_page=100').then(data => {
    employers.value = Array.isArray(data.data) ? data.data : []
  }).catch(() => {})
}

onMounted(() => { loadData(); loadEmployers() })
</script>
