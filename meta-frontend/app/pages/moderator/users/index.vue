<template>
  <div class="flex flex-col space-y-4">
    <div class="flex flex-wrap justify-between items-center gap-2">
      <div class="flex flex-wrap space-x-2 gap-y-2">
        <USelect v-model="statusFilter" :items="statusOptions" size="sm" class="min-w-[160px]" @update:model-value="applyFilters" />
      </div>
      <div class="flex space-x-2">
        <UButton :label="sortMethod === 'asc' ? 'По возрастанию' : 'По убыванию'" icon="i-heroicons-arrows-up-down" size="xs" color="primary" square variant="solid" @click="toggleSort" />
      </div>
    </div>

    <div class="flex space-x-2">
      <UInput v-model="searchTxt" type="text" icon="i-heroicons-magnifying-glass" size="sm" class="w-full" placeholder="Поиск студентов по ФИО или email" @input="debouncedSearch" @keyup.enter.prevent="search" />
      <UButton icon="i-heroicons-magnifying-glass" size="xs" color="primary" variant="solid" @click="search" />
    </div>

    <div v-if="status === 'pending'" class="flex justify-center items-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin w-8 h-8 text-primary mx-auto mb-2" />
      <p class="text-muted">Загрузка студентов...</p>
    </div>

    <div v-if="status === 'success'">
      <div v-if="students.length > 0" class="flex flex-col gap-2">
        <ul>
          <li v-for="(item, index) in students" :key="index">
            <hr role="presentation" class="w-full border-t border-default dark:border-inverted">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 md:p-6">
              <div class="flex flex-col sm:flex-row gap-4 md:gap-6 flex-1">
                <div class="flex-1 space-y-1">
                  <div class="text-base font-semibold text-default">{{ item.name }}</div>
                  <div class="text-sm text-muted">{{ item.email }}</div>
                  <div class="flex flex-wrap gap-2 text-xs mt-2">
                    <UBadge color="primary" variant="soft">{{ item.academy?.short_name || item.academy?.name || '—' }}</UBadge>
                    <UBadge v-if="item.education_level" color="neutral" variant="soft">{{ item.education_level }}</UBadge>
                    <UBadge :color="item.is_activated ? 'success' : 'error'" variant="soft">{{ item.is_activated ? 'Активен' : 'Заблокирован' }}</UBadge>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2 mt-2 md:mt-0">
                <UButton label="Подробнее" icon="i-heroicons-eye" size="xs" color="primary" variant="outline" @click="showStudent(item.id)" />
                <UDropdownMenu :items="getStudentActions(item)">
                  <UButton color="neutral" variant="ghost" icon="i-heroicons-ellipsis-vertical-20-solid" />
                </UDropdownMenu>
              </div>
            </div>
          </li>
        </ul>
        <div class="flex justify-end">
          <p class="text-sm text-muted">Всего: {{ students.length }} студент(ов)</p>
        </div>
      </div>
      <UAlert v-if="students.length === 0" icon="i-heroicons-information-circle" color="info" variant="solid" title="Информация" description="Список студентов пуст." />
    </div>

    <!-- Student modal -->
    <UModal v-model:open="showStudentModal" :ui="{ content: 'w-full sm:max-w-2xl' }">
      <template #header>
        <h3 class="text-lg font-semibold leading-6 text-default">Информация о студенте</h3>
      </template>
      <template #body>
        <div v-if="selectedStudent" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-muted">ФИО</label>
              <p class="text-base text-default">{{ selectedStudent.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-muted">Email</label>
              <p class="text-base text-default">{{ selectedStudent.email }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-muted">Академия</label>
              <p class="text-base text-default">{{ selectedStudent.academy?.name || '—' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-muted">Уровень образования</label>
              <p class="text-base text-default">{{ selectedStudent.education_level || '—' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-muted">Статус</label>
              <p class="text-base">
                <UBadge :color="selectedStudent.is_activated ? 'success' : 'error'" variant="soft">{{ selectedStudent.is_activated ? 'Активен' : 'Заблокирован' }}</UBadge>
              </p>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end">
          <UButton color="neutral" variant="outline" @click="showStudentModal = false">Закрыть</UButton>
        </div>
      </template>
    </UModal>

    <!-- Reset password modal -->
    <UModal v-model:open="showResetPasswordModal" :ui="{ content: 'w-full sm:max-w-md' }">
      <template #header>
        <h3 class="text-lg font-semibold leading-6 text-default">Сброс пароля</h3>
      </template>
      <template #body>
        <div class="space-y-4">
          <p class="text-sm text-muted">Введите новый пароль для студента <strong>{{ selectedStudent?.name }}</strong></p>
          <UFormField label="Новый пароль" required>
            <UInput v-model="resetPasswordForm.password" type="password" placeholder="Введите пароль" />
          </UFormField>
          <UFormField label="Подтвердите пароль" required>
            <UInput v-model="resetPasswordForm.password_confirmation" type="password" placeholder="Повторите пароль" />
          </UFormField>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end space-x-3">
          <UButton color="neutral" variant="outline" @click="showResetPasswordModal = false">Отмена</UButton>
          <UButton color="primary" :loading="resettingPassword" @click="resetPassword">Сбросить пароль</UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { paths } from '~/types/api'

type ModeratorStudentsResponse = paths['/api/v1/moderator/students']['get']['responses']['200']['content']['application/json']
type ModeratorStudentItem = ArrayElement<NonNullable<ModeratorStudentsResponse>>

type ModeratorStudentDetail = paths['/api/v1/moderator/students/{id}']['get']['responses']['200']['content']['application/json']

type ArrayElement<ArrayType extends readonly unknown[]> = ArrayType extends readonly (infer ElementType)[] ? ElementType : never

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Пользователи',
  description: 'Управление студентами учреждения',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Пользователи' }],
})

const { get, patch, post } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const students = ref<ModeratorStudentItem[]>([])
const status = ref<'idle' | 'pending' | 'success' | 'error'>('idle')
const searchTxt = ref('')
const statusFilter = ref('all')
const sortMethod = ref<'asc' | 'desc'>('asc')
const showStudentModal = ref(false)
const showResetPasswordModal = ref(false)
const selectedStudent = ref<ModeratorStudentItem | ModeratorStudentDetail | null>(null)
const resettingPassword = ref(false)

const resetPasswordForm = ref({ password: '', password_confirmation: '' })

const statusOptions = [
  { label: 'Все статусы', value: 'all' },
  { label: 'Активные', value: 'active' },
  { label: 'Заблокированные', value: 'blocked' },
]

async function loadStudents() {
  status.value = 'pending'
  try {
    const data = await get<ModeratorStudentsResponse>('/api/v1/moderator/students')
    let list = Array.isArray(data) ? data : (data?.data || [])
    if (statusFilter.value === 'active') list = list.filter((s: any) => s.is_activated)
    else if (statusFilter.value === 'blocked') list = list.filter((s: any) => !s.is_activated)
    if (searchTxt.value) {
      const q = searchTxt.value.toLowerCase()
      list = list.filter((s: any) => (s.name?.toLowerCase().includes(q) || s.email?.toLowerCase().includes(q)))
    }
    list.sort((a: any, b: any) => {
      const cmp = (a.name || '').localeCompare(b.name || '', 'ru')
      return sortMethod.value === 'asc' ? cmp : -cmp
    })
    students.value = list
    status.value = 'success'
  } catch (error) {
    status.value = 'error'
    showApiError(error, 'Ошибка загрузки списка студентов')
  }
}

function applyFilters() {
  loadStudents()
}
function toggleSort() {
  sortMethod.value = sortMethod.value === 'asc' ? 'desc' : 'asc'
  loadStudents()
}
function search() {
  loadStudents()
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => search(), 400)
}

function getStudentActions(student: ModeratorStudentItem) {
  return [
    [{
      label: student.is_activated ? 'Заблокировать' : 'Разблокировать',
      icon: student.is_activated ? 'i-heroicons-lock-closed' : 'i-heroicons-lock-open',
      onSelect: () => toggleBlock(student),
    }],
    [{
      label: 'Сбросить пароль',
      icon: 'i-heroicons-key',
      onSelect: () => openResetPassword(student),
    }],
  ]
}

function showStudent(id: number) {
  const student = students.value.find(s => s.id === id)
  if (!student) return
  get<ModeratorStudentDetail>(`/api/v1/moderator/students/${id}`).then((data: any) => {
    selectedStudent.value = data
    showStudentModal.value = true
  }).catch((error: any) => {
    showApiError(error, 'Ошибка загрузки данных студента')
  })
}

function openResetPassword(student: ModeratorStudentItem) {
  selectedStudent.value = student
  resetPasswordForm.value = { password: '', password_confirmation: '' }
  showResetPasswordModal.value = true
}

async function resetPassword() {
  if (!selectedStudent.value) return
  if (resetPasswordForm.value.password !== resetPasswordForm.value.password_confirmation) {
    toast.add({ title: 'Пароли не совпадают', color: 'error' })
    return
  }
  if (resetPasswordForm.value.password.length < 8) {
    toast.add({ title: 'Пароль должен содержать минимум 8 символов', color: 'error' })
    return
  }
  resettingPassword.value = true
  try {
    const data = await post<any>(`/api/v1/moderator/students/${selectedStudent.value.id}/reset-password`, {
      password: resetPasswordForm.value.password,
    })
    toast.add({ title: data?.message || 'Пароль успешно сброшен', color: 'success' })
    showResetPasswordModal.value = false
  } catch (error: any) {
    showApiError(error, 'Ошибка сброса пароля')
  } finally {
    resettingPassword.value = false
  }
}

async function toggleBlock(student: ModeratorStudentItem) {
  const action = student.is_activated ? 'заблокировать' : 'разблокировать'
  if (!confirm(`Вы уверены, что хотите ${action} студента ${student.name}?`)) return
  try {
    const data = await patch<any>(`/api/v1/moderator/students/${student.id}/block`)
    toast.add({ title: data?.message || `Студент успешно ${action}`, color: 'success' })
    await loadStudents()
  } catch (error: any) {
    showApiError(error, `Ошибка при попытке ${action} студента`)
  }
}

onMounted(() => {
  loadStudents()
})
</script>
