<template>
  <div class="flex flex-col space-y-4">
    <UTabs v-model="activeTab" :items="tabItems" :default-value="0" class="w-auto px-4" :ui="{ list: 'gap-1', trigger: 'px-3 py-1.5 text-sm' }">
      <template #content>
        <section v-if="activeTab === 0" class="flex flex-col space-y-4 mt-4">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-base font-semibold text-default">Покупки студентов</h2>
              <p class="text-sm text-muted">Очередь выдачи и история решений модератора.</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <UButton label="Ожидают выдачи" size="xs" :color="orderStatusFilter === 'pending' ? 'primary' : 'neutral'" variant="solid" @click="setOrderStatusFilter('pending')" />
              <UButton label="Выданы" size="xs" :color="orderStatusFilter === 'issued' ? 'primary' : 'neutral'" variant="solid" @click="setOrderStatusFilter('issued')" />
              <UButton label="Отменены" size="xs" :color="orderStatusFilter === 'cancelled' ? 'primary' : 'neutral'" variant="solid" @click="setOrderStatusFilter('cancelled')" />
            </div>
          </div>

          <div class="flex space-x-2">
            <UInput v-model="orderSearchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск по ФИО, группе, email или товару" @input="debouncedOrderSearch" @keyup.enter.prevent="searchOrders" />
            <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" class="shrink-0" @click="searchOrders" />
          </div>

          <div v-if="ordersStatus === 'pending'" class="flex justify-center items-center py-12">
            <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
            <p class="text-muted">Загрузка заявок...</p>
          </div>

          <div v-if="ordersStatus === 'success'">
            <div v-if="orders.length > 0" class="flex flex-col gap-2">
              <div class="hidden lg:block">
                <table class="w-full text-sm">
                  <thead>
                    <tr>
                      <th class="border-b border-default px-4 py-2 text-left font-semibold">Студент</th>
                      <th class="border-b border-default px-4 py-2 text-left font-semibold">Товар</th>
                      <th class="w-20 border-b border-default px-4 py-2 font-semibold">Баллы</th>
                      <th class="w-28 border-b border-default px-4 py-2 font-semibold">Статус</th>
                      <th class="w-36 border-b border-default px-4 py-2 font-semibold">Дата покупки</th>
                      <th class="w-36 border-b border-default px-4 py-2 font-semibold">Дата обработки</th>
                      <th v-if="orderStatusFilter === 'pending'" class="w-48 border-b border-default px-4 py-2 font-semibold">Действия</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-muted/50">
                      <td class="border-b border-muted px-4 py-2">{{ formatStudent(order) }}</td>
                      <td class="border-b border-muted px-4 py-2">{{ order.item_name_snapshot }}</td>
                      <td class="w-20 border-b border-muted px-4 py-2 text-center">{{ order.item_score_snapshot }}</td>
                      <td class="w-28 border-b border-muted px-4 py-2 text-center">
                        <UBadge :color="orderStatusColor(order.status)" variant="solid">{{ orderStatusLabel(order.status) }}</UBadge>
                      </td>
                      <td class="w-36 border-b border-muted px-4 py-2 text-center">{{ formatDate(order.created_at) }}</td>
                      <td class="w-36 border-b border-muted px-4 py-2 text-center">{{ formatOrderDecision(order) }}</td>
                      <td v-if="orderStatusFilter === 'pending'" class="w-48 border-b border-muted px-4 py-2 text-center">
                        <div class="flex items-center justify-center gap-2">
                          <UButton label="Выдано" color="primary" variant="outline" size="xs" :loading="isOrderUpdating(order.id, 'issued')" @click="updateOrderStatus(order.id!, 'issued')" />
                          <UButton label="Отменить" color="error" variant="outline" size="xs" :loading="isOrderUpdating(order.id, 'cancelled')" @click="updateOrderStatus(order.id!, 'cancelled')" />
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="lg:hidden grid gap-2">
                <div v-for="order in orders" :key="order.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
                  <div class="grid gap-1"><span class="font-semibold">Студент</span><span>{{ formatStudent(order) }}</span></div>
                  <div class="grid gap-1"><span class="font-semibold">Товар</span><span>{{ order.item_name_snapshot }}</span></div>
                  <div class="grid gap-1"><span class="font-semibold">Баллы</span><span>{{ order.item_score_snapshot }}</span></div>
                  <div class="grid gap-1">
                    <span class="font-semibold">Статус</span>
                    <UBadge :color="orderStatusColor(order.status)" variant="solid" class="w-fit">{{ orderStatusLabel(order.status) }}</UBadge>
                  </div>
                  <div class="grid gap-1"><span class="font-semibold">Дата покупки</span><span>{{ formatDate(order.created_at) }}</span></div>
                  <div class="grid gap-1"><span class="font-semibold">Дата обработки</span><span>{{ formatOrderDecision(order) }}</span></div>
                  <div v-if="orderStatusFilter === 'pending'" class="grid grid-cols-2 gap-2">
                    <UButton label="Выдано" color="primary" variant="outline" size="sm" block :loading="isOrderUpdating(order.id, 'issued')" @click="updateOrderStatus(order.id!, 'issued')" />
                    <UButton label="Отменить" color="error" variant="outline" size="sm" block :loading="isOrderUpdating(order.id, 'cancelled')" @click="updateOrderStatus(order.id!, 'cancelled')" />
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <ListPagination :pagination="ordersPagination" @update:page="onOrdersPageChange" />
              </div>
            </div>
            <UAlert v-if="orders.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Информация" description="Покупки по выбранному фильтру не найдены" />
          </div>
        </section>

        <section v-if="activeTab === 1" class="flex flex-col space-y-4 mt-4">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-base font-semibold text-default">Товары</h2>
              <p class="text-sm text-muted">Управление каталогом товаров маркетплейса.</p>
            </div>
            <div class="flex gap-2">
              <UButton :label="sortMethod === 'asc' ? 'От старых к новым' : 'От новых к старым'" icon="i-heroicons-arrows-up-down" size="xs" color="primary" square variant="solid" @click="sort" />
              <UButton label="Добавить товар" icon="i-heroicons-plus-circle" size="xs" color="primary" square variant="solid" @click="openCreateModal" />
            </div>
          </div>

          <div class="flex space-x-2">
            <UInput v-model="searchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск товаров по наименованию" @input="debouncedSearch" @keyup.enter.prevent="search" />
            <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" class="shrink-0" @click="search" />
          </div>

          <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
            <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
            <p class="text-muted">Загрузка товаров...</p>
          </div>

          <div v-if="status === 'success'">
            <div v-if="products.length != 0" class="flex flex-col gap-2">
              <div class="hidden lg:block">
                <table class="w-full text-sm">
                  <thead>
                    <tr>
                      <th class="w-20 border-b border-default px-4 py-2 font-semibold">Фото</th>
                      <th class="w-16 border-b border-default px-4 py-2 font-semibold">№</th>
                      <th class="w-auto border-b border-default px-4 py-2 text-left font-semibold">Наименование</th>
                      <th class="w-20 border-b border-default px-4 py-2 font-semibold">Стоимость</th>
                      <th class="w-24 border-b border-default px-4 py-2 font-semibold">Статус</th>
                      <th class="w-20 border-b border-default px-4 py-2 font-semibold">Ограничения</th>
                      <th class="w-28 border-b border-default px-4 py-2 font-semibold">Действия</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in products" :key="item.id" class="hover:bg-muted/50">
                      <td class="w-20 border-b border-muted px-4 py-2 text-center">
                        <img v-if="item.photo_path" :src="productPhotoUrl(item.photo_path)" class="mx-auto h-12 w-12 rounded object-cover border border-default cursor-zoom-in" @click="openImagePreview(productPhotoUrl(item.photo_path), item.name)">
                        <UIcon v-else name="i-heroicons-photo" class="mx-auto h-8 w-8 text-muted" />
                      </td>
                      <td class="w-16 border-b border-muted px-4 py-2 text-center">{{ (pagination.currentPage - 1) * pagination.perPage + index + 1 }}</td>
                      <td class="w-auto border-b border-muted px-4 py-2">{{ item.name }}</td>
                      <td class="w-20 border-b border-muted px-4 py-2 text-center">{{ item.score }}</td>
                      <td class="w-24 border-b border-muted px-4 py-2 text-center">
                        <UBadge :color="Number(item.is_archive) === 0 ? 'success' : 'neutral'" variant="solid">{{ Number(item.is_archive) === 0 ? 'Активный' : 'В архиве' }}</UBadge>
                      </td>
                      <td class="w-20 border-b border-muted px-4 py-2 text-center">
                        <UBadge color="info" variant="soft" class="cursor-pointer" @click="openRestrictions(item)">{{ item.restrictions_count || 0 }}</UBadge>
                      </td>
                      <td class="w-28 border-b border-muted px-4 py-2 text-center">
                        <div class="flex items-center justify-center gap-2">
                          <UButton icon="i-heroicons-pencil-square" color="warning" variant="outline" size="sm" square @click="edit(item.id!)" />
                          <UButton icon="i-heroicons-trash" color="error" variant="outline" size="sm" square @click="destroy(item.id!)" />
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="lg:hidden grid gap-2">
                <div v-for="(item, index) in products" :key="item.id" class="grid gap-2 rounded-md ring-1 shadow-xs ring-zinc-950/5 p-2 text-sm">
                  <img v-if="item.photo_path" :src="productPhotoUrl(item.photo_path)" class="h-32 w-full rounded object-cover border border-default cursor-zoom-in" @click="openImagePreview(productPhotoUrl(item.photo_path), item.name)">
                  <div v-else class="flex h-32 w-full items-center justify-center rounded border border-default bg-muted"><UIcon name="i-heroicons-photo" class="h-10 w-10 text-muted" /></div>
                  <div class="grid gap-1"><span class="font-semibold">Наименование</span><span>{{ item.name }}</span></div>
                  <div class="grid gap-1"><span class="font-semibold">Стоимость</span><span>{{ item.score }} баллов</span></div>
                  <div class="grid gap-1">
                    <span class="font-semibold">Статус</span>
                    <UBadge :color="Number(item.is_archive) === 0 ? 'success' : 'neutral'" variant="solid" class="w-fit">{{ Number(item.is_archive) === 0 ? 'Активный' : 'В архиве' }}</UBadge>
                  </div>
                  <div class="grid grid-cols-3 gap-2">
                    <UButton icon="i-heroicons-adjustments-horizontal" color="info" variant="outline" size="sm" @click="openRestrictions(item)" />
                    <UButton icon="i-heroicons-pencil-square" color="warning" variant="outline" size="sm" @click="edit(item.id!)" />
                    <UButton icon="i-heroicons-trash" color="error" variant="outline" size="sm" @click="destroy(item.id!)" />
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <ListPagination :pagination="pagination" @update:page="onPageChange" />
              </div>
            </div>
            <UAlert v-if="products.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Информация" description="Список товаров пуст. Для добавления нового товара нажмите на кнопку «Добавить товар»" />
          </div>
        </section>
      </template>
    </UTabs>

    <!-- Create/Edit Product Modal -->
    <UModal v-model:open="showProductModal" :ui="{ content: 'w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto' }">
      <template #header>
        <h3 class="text-lg font-semibold leading-6 text-default">{{ editingId ? 'Редактировать товар' : 'Создать товар' }}</h3>
      </template>
      <template #body>
        <form @submit.prevent="saveProduct">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Наименование<span class="ml-1 text-error">*</span></h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Название товара.</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <UInput v-model="productForm.name" class="w-full" placeholder="Наименование товара" />
              <ul v-for="(error, idx) in fieldErrors(productErrors.name)" :key="idx"><li class="text-xs text-error">{{ error }}</li></ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Стоимость (баллы)<span class="ml-1 text-error">*</span></h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Количество баллов для обмена.</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <UInput v-model="productForm.score" type="number" class="w-full" placeholder="Стоимость" />
              <ul v-for="(error, idx) in fieldErrors(productErrors.score)" :key="idx"><li class="text-xs text-error">{{ error }}</li></ul>
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">В архиве</h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Скрыть товар из каталога студентов.</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <USwitch v-model="productForm.is_archive" />
            </div>
          </section>
          <hr role="presentation" class="w-full border-t border-default">
          <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2 py-4">
            <div class="space-y-1">
              <h2 class="text-base/7 font-semibold sm:text-sm/6">Фото товара</h2>
              <p class="text-base/6 sm:text-sm/6 text-muted">Изображение (jpg, png, до 2 Мб).</p>
            </div>
            <div class="flex flex-col space-y-1 justify-center">
              <UFileUpload v-model="productPhotoFile" accept="image/*" :multiple="false" :preview="false" variant="area" label="Выберите файл" description="Изображение до 2 Мб" class="w-full" />
              <div v-if="productForm.photo_path && !productPhotoFile" class="space-y-2">
                <p class="text-sm font-medium">Текущее изображение:</p>
                <img :src="config.public.API_PATH_STORAGE + productForm.photo_path" class="w-20 h-20 object-cover rounded border border-default">
              </div>
            </div>
          </section>
          <div class="flex justify-end py-4">
            <UButton type="submit" color="success" :loading="savingProduct">{{ editingId ? 'Сохранить изменения' : 'Добавить запись' }}</UButton>
          </div>
        </form>
      </template>
    </UModal>

    <!-- Restrictions Modal (Visibility rules constructor) -->
    <UModal v-model:open="showRestrictionsModal" :ui="{ content: 'w-full sm:max-w-3xl max-h-[90vh] overflow-y-auto' }">
      <template #header>
        <h3 class="text-lg font-semibold leading-6 text-default">Конструктор ограничений видимости</h3>
        <p class="text-sm text-muted mt-1">Настройка правил видимости товара для студентов</p>
      </template>
      <template #body>
        <div v-if="restrictionProduct" class="space-y-6">
          <div class="bg-muted rounded-lg p-4">
            <p class="text-sm font-medium">Товар: <span class="text-default">{{ restrictionProduct.name }}</span></p>
          </div>

          <!-- Logic operator toggle -->
          <div class="flex items-center gap-3">
            <span class="text-sm font-medium">Студент должен соответствовать:</span>
            <USelect v-model="restrictionForm.logic_operator" :items="logicOperatorOptions" size="sm" class="min-w-[180px]" />
          </div>

          <!-- Conditions list -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-muted">Условия ({{ restrictionForm.conditions.length }})</span>
              <UButton label="Добавить условие" icon="i-heroicons-plus-circle" size="xs" color="primary" variant="outline" @click="addCondition" />
            </div>

            <div v-for="(cond, idx) in restrictionForm.conditions" :key="idx" class="border border-default rounded-lg p-4 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-muted uppercase">Условие {{ idx + 1 }}</span>
                <UButton icon="i-heroicons-x-mark" color="error" variant="ghost" size="xs" square @click="removeCondition(idx)" />
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <USelect v-model="cond.field" :items="conditionFieldOptions" placeholder="Поле" size="sm" @update:model-value="onConditionFieldChange(cond)" />
                <USelect v-model="cond.operator" :items="getOperatorOptions(cond.field)" placeholder="Оператор" size="sm" />
                <div>
                  <USelect v-if="isSelectField(cond.field)" v-model="cond.value" :items="getFieldValueOptions(cond.field)" placeholder="Значение" size="sm" />
                  <UInput v-else v-model="cond.value" type="text" placeholder="Значение" size="sm" />
                </div>
              </div>
            </div>

            <UAlert v-if="restrictionForm.conditions.length === 0" icon="i-heroicons-information-circle" color="info" variant="soft" title="Нет условий" description="Добавьте хотя бы одно условие, чтобы настроить видимость товара." />
          </div>

          <!-- Preview -->
          <div v-if="restrictionForm.conditions.length > 0" class="bg-muted rounded-lg p-4">
            <p class="text-sm font-medium mb-2">Предпросмотр текста ограничений:</p>
            <p class="text-sm text-muted">{{ previewRestrictionText }}</p>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-default">
            <UButton color="neutral" variant="outline" @click="deleteRestrictions" :disabled="!hasRestrictions">Удалить все</UButton>
            <UButton color="primary" :loading="savingRestrictions" @click="saveRestrictions">Сохранить ограничения</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <ImagePreviewModal :open="showImagePreview" :src="previewImageSrc" @update:open="showImagePreview = $event" />
  </div>
</template>

<script setup lang="ts">
import { staticImg } from '~/assets/img'
import type { paths } from '~/types/api'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Товары маркетплейса',
  description: 'Управление каталогом товаров и заявками на обмен',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Товары' }],
})

type OrderStatus = 'pending' | 'issued' | 'cancelled'

interface Condition {
  field: string
  operator: string
  value: string
}

interface RestrictionForm {
  logic_operator: 'all' | 'any'
  conditions: Condition[]
}

const config = useRuntimeConfig()
const { get, post, patch, delete: del, put } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const activeTab = ref(0)
const tabItems = [
  { label: 'Заявки на товары', value: 0 },
  { label: 'Товары', value: 1 },
]

// ---- Orders ----
const orders = ref<any[]>([])
const ordersStatus = ref<'idle' | 'pending' | 'success' | 'error'>('idle')
const orderStatusFilter = ref<OrderStatus>('pending')
const orderSearchTxt = ref('')
const updatingOrder = ref<{ id: number; status: OrderStatus } | null>(null)
const ordersPagination = ref({ currentPage: 1, lastPage: 1, perPage: 20, totalItems: 0 })

function buildOrdersUrl(page = 1) {
  const params = new URLSearchParams({ page: String(page), status: orderStatusFilter.value, sort: 'created_at', direction: 'desc' })
  const query = String(orderSearchTxt.value || '').trim()
  if (query) params.set('search', query)
  return `/api/v1/moderator/marketplace/orders?${params.toString()}`
}

function loadOrders(url: string) {
  ordersStatus.value = 'pending'
  get<any>(url).then(data => {
    orders.value = Array.isArray(data.data) ? data.data : []
    ordersPagination.value.currentPage = data.current_page ?? 1
    ordersPagination.value.lastPage = data.last_page ?? 1
    ordersPagination.value.perPage = data.per_page ?? ordersPagination.value.perPage
    ordersPagination.value.totalItems = data.total ?? 0
    ordersStatus.value = 'success'
  }).catch(error => {
    ordersStatus.value = 'error'
    showApiError(error, 'Ошибка загрузки заявок')
  })
}

function setOrderStatusFilter(status: OrderStatus) {
  orderStatusFilter.value = status
  ordersPagination.value.currentPage = 1
  loadOrders(buildOrdersUrl(1))
}

let orderSearchTimeout: ReturnType<typeof setTimeout> | null = null
function debouncedOrderSearch() {
  if (orderSearchTimeout) clearTimeout(orderSearchTimeout)
  orderSearchTimeout = setTimeout(() => searchOrders(), 500)
}

function searchOrders() {
  ordersPagination.value.currentPage = 1
  loadOrders(buildOrdersUrl(1))
}

function onOrdersPageChange(page: number) {
  ordersPagination.value.currentPage = page
  loadOrders(buildOrdersUrl(page))
}

function updateOrderStatus(id: number, status: Exclude<OrderStatus, 'pending'>) {
  const msg = status === 'issued' ? 'Отметить покупку как выданную?' : 'Отменить покупку и вернуть баллы?'
  if (!confirm(msg)) return
  updatingOrder.value = { id, status }
  patch<any>(`/api/v1/moderator/marketplace/orders/${id}`, { status }).then(data => {
    toast.add({ title: data.message, color: 'success' })
    loadOrders(buildOrdersUrl(ordersPagination.value.currentPage))
  }).catch(error => {
    showApiError(error, 'Ошибка')
  }).finally(() => { updatingOrder.value = null })
}

function isOrderUpdating(id: number | undefined, status: OrderStatus) {
  return updatingOrder.value?.id === id && updatingOrder.value?.status === status
}

function formatStudent(order: any) {
  const name = order.student_full_name || order.student_email || `ID ${order.user_id}`
  return order.student_group ? `${name} (${order.student_group})` : name
}

function orderStatusLabel(status?: string) {
  if (status === 'issued') return 'Выдано'
  if (status === 'cancelled') return 'Отменено'
  return 'Ожидает'
}

function orderStatusColor(status?: string) {
  if (status === 'issued') return 'primary'
  if (status === 'cancelled') return 'error'
  return 'warning'
}

function formatOrderDecision(order: any) {
  if (order.status === 'issued') return formatDate(order.issued_at)
  if (order.status === 'cancelled') return formatDate(order.cancelled_at)
  return '—'
}

function formatDate(date: string | undefined) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('ru-RU', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// ---- Products ----
const products = ref<any[]>([])
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle')
const sortMethod = ref<'asc' | 'desc'>('asc')
const searchTxt = ref('')
const pagination = ref({ currentPage: 1, lastPage: 1, perPage: 20, totalItems: 0 })
const showProductModal = ref(false)
const editingId = ref<number | null>(null)
const savingProduct = ref(false)
const productForm = ref({ name: '', score: 0, is_archive: false, photo_path: null as string | null })
const productPhotoFile = ref<File | null>(null)
const productErrors = ref({ name: '', score: '', photo: '' })
const showImagePreview = ref(false)
const previewImageSrc = ref('')

function productPhotoUrl(photoPath: string) {
  return `${config.public.API_PATH_STORAGE}${photoPath}`
}

function openImagePreview(src: string, alt?: string) {
  previewImageSrc.value = src
  showImagePreview.value = true
}

function fieldErrors(value: string | string[] | null | undefined) {
  if (value == null || value === '') return []
  if (Array.isArray(value)) return value
  return [String(value)]
}

function clearProductErrors() {
  productErrors.value = { name: '', score: '', photo: '' }
}

function resetProductForm() {
  productForm.value = { name: '', score: 0, is_archive: false, photo_path: null }
  productPhotoFile.value = null
  editingId.value = null
  clearProductErrors()
}

function openCreateModal() {
  resetProductForm()
  showProductModal.value = true
}

function buildProductsUrl(page = 1) {
  const params = new URLSearchParams({ page: String(page), sort: 'id', direction: sortMethod.value })
  const query = String(searchTxt.value || '').trim()
  if (query) params.set('search', query)
  return `/api/v1/moderator/marketplace/products?${params.toString()}`
}

function loadProducts(url: string) {
  status.value = 'pending'
  get<any>(url).then(data => {
    products.value = Array.isArray(data.data) ? data.data : []
    pagination.value.currentPage = data.current_page ?? 1
    pagination.value.lastPage = data.last_page ?? 1
    pagination.value.perPage = data.per_page ?? pagination.value.perPage
    pagination.value.totalItems = data.total ?? 0
    // Fetch restrictions count for each product
    products.value.forEach(p => { p.restrictions_count = 0 })
    status.value = 'success'
  }).catch(error => {
    status.value = 'error'
    showApiError(error, 'Ошибка загрузки товаров')
  })
}

function sort() {
  sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc'
  pagination.value.currentPage = 1
  loadProducts(buildProductsUrl(1))
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => search(), 500)
}

function search() {
  pagination.value.currentPage = 1
  loadProducts(buildProductsUrl(1))
}

function onPageChange(page: number) {
  pagination.value.currentPage = page
  loadProducts(buildProductsUrl(page))
}

function edit(id: number) {
  get<any>(`/api/v1/moderator/marketplace/products/${id}`).then(data => {
    productForm.value.name = data.name ?? ''
    productForm.value.score = data.score ?? 0
    productForm.value.photo_path = data.photo_path ?? null
    productForm.value.is_archive = Number(data.is_archive) === 1
    editingId.value = id
    showProductModal.value = true
  }).catch(error => showApiError(error, 'Ошибка загрузки товара'))
}

function saveProduct() {
  savingProduct.value = true
  clearProductErrors()

  if (!productForm.value.name.trim()) { productErrors.value.name = 'Название обязательно'; savingProduct.value = false; return }
  if (!productForm.value.score || productForm.value.score <= 0) { productErrors.value.score = 'Укажите стоимость'; savingProduct.value = false; return }

  const formData = new FormData()
  formData.append('name', productForm.value.name)
  formData.append('score', String(productForm.value.score))
  formData.append('is_archive', productForm.value.is_archive ? '1' : '0')
  if (productPhotoFile.value) formData.append('photo', productPhotoFile.value)
  if (editingId.value) formData.append('_method', 'PATCH')

  const apiCall = editingId.value
    ? post<any>(`/api/v1/moderator/marketplace/products/${editingId.value}`, formData)
    : post<any>('/api/v1/moderator/marketplace/products', formData)

  apiCall.then(data => {
    toast.add({ title: data?.message || (editingId.value ? 'Товар обновлен' : 'Товар создан'), color: 'success' })
    showProductModal.value = false
    loadProducts(buildProductsUrl(pagination.value.currentPage))
  }).catch(error => {
    if (error?.response?._data?.errors) Object.assign(productErrors.value, error.response._data.errors)
    showApiError(error, 'Ошибка сохранения')
  }).finally(() => { savingProduct.value = false })
}

function destroy(id: number) {
  if (!confirm('Вы уверены, что хотите удалить этот товар?')) return
  del<any>(`/api/v1/moderator/marketplace/products/${id}`).then(data => {
    toast.add({ title: data.message || 'Товар удален', color: 'success' })
    loadProducts(buildProductsUrl(pagination.value.currentPage))
  }).catch(error => showApiError(error, 'Ошибка удаления'))
}

// ---- Restrictions / Visibility Rules ----
const showRestrictionsModal = ref(false)
const restrictionProduct = ref<any>(null)
const savingRestrictions = ref(false)
const hasRestrictions = ref(false)
const restrictionId = ref<number | null>(null)
const restrictionForm = ref<RestrictionForm>({ logic_operator: 'all', conditions: [] })

const logicOperatorOptions = [
  { label: 'ВСЕМ из нижеследующих условий', value: 'all' },
  { label: 'ЛЮБОМУ из нижеследующих условий', value: 'any' },
]

const conditionFieldOptions = [
  { label: 'Учреждение', value: 'institution_id' },
  { label: 'Уровень обучения', value: 'education_level' },
  { label: 'Семестр обучения', value: 'semester' },
  { label: 'Группа', value: 'student_group' },
  { label: 'Курс', value: 'course' },
  { label: 'Участник хаба/лаборатории', value: 'is_hub_member' },
  { label: 'Успеваемость', value: 'academic_performance' },
]

const operatorOptionsByField: Record<string, { label: string; value: string }[]> = {
  institution_id: [
    { label: 'совпадает с', value: 'eq' },
    { label: 'не совпадает с', value: 'neq' },
  ],
  education_level: [
    { label: 'совпадает с', value: 'eq' },
    { label: 'не совпадает с', value: 'neq' },
    { label: 'входит в список', value: 'in' },
  ],
  semester: [
    { label: 'совпадает с', value: 'eq' },
    { label: 'не совпадает с', value: 'neq' },
    { label: 'больше чем', value: 'gt' },
    { label: 'меньше чем', value: 'lt' },
  ],
  student_group: [
    { label: 'совпадает с', value: 'eq' },
    { label: 'не совпадает с', value: 'neq' },
  ],
  course: [
    { label: 'совпадает с', value: 'eq' },
    { label: 'не совпадает с', value: 'neq' },
    { label: 'больше чем', value: 'gt' },
    { label: 'меньше чем', value: 'lt' },
  ],
  is_hub_member: [
    { label: 'совпадает с', value: 'eq' },
  ],
  academic_performance: [
    { label: 'совпадает с', value: 'eq' },
    { label: 'не совпадает с', value: 'neq' },
    { label: 'больше чем', value: 'gt' },
    { label: 'меньше чем', value: 'lt' },
  ],
}

const fieldValueOptions: Record<string, { label: string; value: string }[]> = {
  institution_id: [],
  education_level: [],
  is_hub_member: [
    { label: 'Да', value: '1' },
    { label: 'Нет', value: '0' },
  ],
  academic_performance: [
    { label: 'Отличник', value: 'excellent' },
    { label: 'Хорошист', value: 'good' },
    { label: 'Удовлетворительно', value: 'satisfactory' },
    { label: 'Неудовлетворительно', value: 'unsatisfactory' },
  ],
}

function getOperatorOptions(field: string) {
  return operatorOptionsByField[field] ?? [{ label: 'совпадает с', value: 'eq' }]
}

function isSelectField(field: string) {
  return ['institution_id', 'education_level', 'is_hub_member', 'academic_performance'].includes(field)
}

function getFieldValueOptions(field: string) {
  return fieldValueOptions[field] ?? []
}

function onConditionFieldChange(cond: Condition) {
  cond.operator = getOperatorOptions(cond.field)[0]?.value ?? 'eq'
  cond.value = ''
}

function addCondition() {
  restrictionForm.value.conditions.push({ field: 'institution_id', operator: 'eq', value: '' })
}

function removeCondition(idx: number) {
  restrictionForm.value.conditions.splice(idx, 1)
}

const previewRestrictionText = computed(() => {
  const { conditions, logic_operator } = restrictionForm.value
  if (conditions.length === 0) return 'Нет ограничений — товар виден всем студентам.'

  const parts = conditions.map(c => {
    const fieldLabel = conditionFieldOptions.find(f => f.value === c.field)?.label || c.field
    const opLabel = getOperatorOptions(c.field).find(o => o.value === c.operator)?.label || c.operator
    return `${fieldLabel} ${opLabel} "${c.value}"`
  })

  const joiner = logic_operator === 'all' ? ' И ' : ' ИЛИ '
  return `Доступно для: ${parts.join(joiner)}.`
})

function openRestrictions(product: any) {
  restrictionProduct.value = product
  restrictionForm.value = { logic_operator: 'all', conditions: [] }
  hasRestrictions.value = false
  restrictionId.value = null
  get<any[]>(`/api/v1/moderator/marketplace/products/${product.id}/restrictions`).then(data => {
    const restrictions = Array.isArray(data) ? data : []
    if (restrictions.length > 0) {
      const r = restrictions[0]
      restrictionForm.value.logic_operator = r.logic_operator || 'all'
      restrictionForm.value.conditions = Array.isArray(r.conditions) ? r.conditions : []
      hasRestrictions.value = true
      restrictionId.value = r.id
    }
    showRestrictionsModal.value = true
  }).catch(() => {
    showRestrictionsModal.value = true
  })
}

function saveRestrictions() {
  if (!restrictionProduct.value) return
  if (restrictionForm.value.conditions.length === 0) {
    toast.add({ title: 'Добавьте хотя бы одно условие', color: 'warning' })
    return
  }
  savingRestrictions.value = true
  const payload = {
    marketplace_id: restrictionProduct.value.id,
    logic_operator: restrictionForm.value.logic_operator,
    conditions: restrictionForm.value.conditions,
  }

  const apiCall = hasRestrictions.value && restrictionId.value
    ? put<any>(`/api/v1/moderator/marketplace/restrictions/${restrictionId.value}`, payload)
    : post<any>('/api/v1/moderator/marketplace/restrictions', payload)

  apiCall.then(data => {
    toast.add({ title: data.message || 'Ограничения сохранены', color: 'success' })
    showRestrictionsModal.value = false
  }).catch(error => {
    showApiError(error, 'Ошибка сохранения ограничений')
  }).finally(() => { savingRestrictions.value = false })
}

function deleteRestrictions() {
  if (!restrictionId.value) return
  if (!confirm('Удалить все ограничения для этого товара?')) return
  del<any>(`/api/v1/moderator/marketplace/restrictions/${restrictionId.value}`).then(data => {
    toast.add({ title: data.message || 'Ограничения удалены', color: 'success' })
    showRestrictionsModal.value = false
  }).catch(error => showApiError(error, 'Ошибка удаления ограничений'))
}

// ---- Watchers ----
watch(activeTab, (value) => {
  if (value === 0 && ordersStatus.value === 'idle') loadOrders(buildOrdersUrl(1))
  if (value === 1 && status.value === 'idle') loadProducts(buildProductsUrl(1))
})

onMounted(() => {
  loadOrders(buildOrdersUrl(1))
})
</script>
