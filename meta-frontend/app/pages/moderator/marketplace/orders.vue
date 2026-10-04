<template>
  <div class="flex flex-col space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-base font-semibold text-default">Заявки на товары</h2>
        <p class="text-sm text-muted">Очередь выдачи и история решений.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <UButton label="Ожидают" size="xs" :color="statusFilter === 'pending' ? 'primary' : 'neutral'" variant="solid" @click="setFilter('pending')" />
        <UButton label="Выданы" size="xs" :color="statusFilter === 'issued' ? 'primary' : 'neutral'" variant="solid" @click="setFilter('issued')" />
        <UButton label="Отменены" size="xs" :color="statusFilter === 'cancelled' ? 'primary' : 'neutral'" variant="solid" @click="setFilter('cancelled')" />
      </div>
    </div>

    <div class="flex space-x-2">
      <UInput v-model="searchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск по ФИО, группе, email или товару" @input="debouncedSearch" @keyup.enter.prevent="loadData" />
      <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" class="shrink-0" @click="loadData" />
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
    </div>

    <div v-if="!loading">
      <div v-if="orders.length > 0" class="flex flex-col gap-2">
        <div class="hidden lg:block">
          <table class="w-full text-sm">
            <thead>
              <tr>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Студент</th>
                <th class="border-b border-default px-4 py-2 text-left font-semibold">Товар</th>
                <th class="w-20 border-b border-default px-4 py-2 font-semibold">Баллы</th>
                <th class="w-28 border-b border-default px-4 py-2 font-semibold">Статус</th>
                <th class="w-36 border-b border-default px-4 py-2 font-semibold">Создана</th>
                <th class="w-36 border-b border-default px-4 py-2 font-semibold">Обработана</th>
                <th v-if="statusFilter === 'pending'" class="w-48 border-b border-default px-4 py-2 font-semibold">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id" class="hover:bg-muted/50">
                <td class="border-b border-muted px-4 py-2">{{ formatStudent(order) }}</td>
                <td class="border-b border-muted px-4 py-2">{{ order.item_name_snapshot }}</td>
                <td class="w-20 border-b border-muted px-4 py-2 text-center">{{ order.item_score_snapshot }}</td>
                <td class="w-28 border-b border-muted px-4 py-2 text-center">
                  <UBadge :color="statusColor(order.status)" variant="solid">{{ statusLabel(order.status) }}</UBadge>
                </td>
                <td class="w-36 border-b border-muted px-4 py-2 text-center">{{ fmtDate(order.created_at) }}</td>
                <td class="w-36 border-b border-muted px-4 py-2 text-center">{{ fmtDecision(order) }}</td>
                <td v-if="statusFilter === 'pending'" class="w-48 border-b border-muted px-4 py-2 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <UButton label="Выдано" color="primary" variant="outline" size="xs" :loading="isUpdating(order.id, 'issued')" @click="updateStatus(order.id!, 'issued')" />
                    <UButton label="Отменить" color="error" variant="outline" size="xs" :loading="isUpdating(order.id, 'cancelled')" @click="updateStatus(order.id!, 'cancelled')" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="lg:hidden grid gap-2">
          <div v-for="order in orders" :key="order.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
            <div><span class="font-semibold">Студент:</span> {{ formatStudent(order) }}</div>
            <div><span class="font-semibold">Товар:</span> {{ order.item_name_snapshot }}</div>
            <div><span class="font-semibold">Баллы:</span> {{ order.item_score_snapshot }}</div>
            <div><span class="font-semibold">Статус:</span> <UBadge :color="statusColor(order.status)" variant="solid">{{ statusLabel(order.status) }}</UBadge></div>
            <div><span class="font-semibold">Создана:</span> {{ fmtDate(order.created_at) }}</div>
            <div><span class="font-semibold">Обработана:</span> {{ fmtDecision(order) }}</div>
            <div v-if="statusFilter === 'pending'" class="grid grid-cols-2 gap-2">
              <UButton label="Выдано" color="primary" variant="outline" size="sm" block :loading="isUpdating(order.id, 'issued')" @click="updateStatus(order.id!, 'issued')" />
              <UButton label="Отменить" color="error" variant="outline" size="sm" block :loading="isUpdating(order.id, 'cancelled')" @click="updateStatus(order.id!, 'cancelled')" />
            </div>
          </div>
        </div>
        <div class="flex justify-end">
          <ListPagination :pagination="pagination" @update:page="onPageChange" />
        </div>
      </div>
      <UAlert v-if="orders.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Информация" description="Заявки не найдены" />
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Заявки на товары',
  description: 'Модерация заявок на обмен',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Заявки на товары' }],
})

type OStatus = 'pending' | 'issued' | 'cancelled'

const { get, patch } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const orders = ref<any[]>([])
const loading = ref(false)
const statusFilter = ref<OStatus>('pending')
const searchTxt = ref('')
const updating = ref<{ id: number; status: OStatus } | null>(null)
const pagination = ref({ currentPage: 1, lastPage: 1, perPage: 20, totalItems: 0 })

function buildUrl(page = 1) {
  const p = new URLSearchParams({ page: String(page), status: statusFilter.value, sort: 'created_at', direction: 'desc' })
  const q = String(searchTxt.value || '').trim()
  if (q) p.set('search', q)
  return `/api/v1/moderator/marketplace/orders?${p.toString()}`
}

function loadData() {
  loading.value = true
  get<any>(buildUrl(pagination.value.currentPage)).then(data => {
    orders.value = Array.isArray(data.data) ? data.data : []
    pagination.value.currentPage = data.current_page ?? 1
    pagination.value.lastPage = data.last_page ?? 1
    pagination.value.perPage = data.per_page ?? pagination.value.perPage
    pagination.value.totalItems = data.total ?? 0
  }).catch(e => showApiError(e, 'Ошибка')).finally(() => { loading.value = false })
}

function setFilter(s: OStatus) {
  statusFilter.value = s
  pagination.value.currentPage = 1
  loadData()
}

let st: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
  if (st) clearTimeout(st)
  st = setTimeout(() => loadData(), 500)
}

function onPageChange(page: number) {
  pagination.value.currentPage = page
  loadData()
}

function updateStatus(id: number, status: Exclude<OStatus, 'pending'>) {
  if (!confirm(status === 'issued' ? 'Отметить как выданную?' : 'Отменить и вернуть баллы?')) return
  updating.value = { id, status }
  patch<any>(`/api/v1/moderator/marketplace/orders/${id}`, { status }).then(d => {
    toast.add({ title: d.message, color: 'success' })
    loadData()
  }).catch(e => showApiError(e, 'Ошибка')).finally(() => { updating.value = null })
}

function isUpdating(id: number | undefined, status: OStatus) {
  return updating.value?.id === id && updating.value?.status === status
}

function formatStudent(o: any) {
  const n = o.student_full_name || o.student_email || `ID ${o.user_id}`
  return o.student_group ? `${n} (${o.student_group})` : n
}

function statusLabel(s?: string) {
  if (s === 'issued') return 'Выдано'
  if (s === 'cancelled') return 'Отменено'
  return 'Ожидает'
}

function statusColor(s?: string) {
  if (s === 'issued') return 'primary'
  if (s === 'cancelled') return 'error'
  return 'warning'
}

function fmtDecision(o: any) {
  if (o.status === 'issued') return fmtDate(o.issued_at)
  if (o.status === 'cancelled') return fmtDate(o.cancelled_at)
  return '—'
}

function fmtDate(d: string | undefined) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ru-RU', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(loadData)
</script>
