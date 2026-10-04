<template>
  <div class="min-w-0 w-full flex flex-col gap-4">
    <h2 class="font-medium text-default">Настройки маркетплейса</h2>

    <div class="grid gap-6 max-w-2xl">
      <div class="bg-elevated border border-default rounded-lg p-6 space-y-4">
        <div>
          <h3 class="text-base font-semibold text-default">Адреса выдачи товаров</h3>
          <p class="text-sm text-muted mt-1">Информация о местах получения товаров для студентов.</p>
        </div>

        <div class="space-y-2">
          <label class="block text-sm font-medium">Текст подсказки</label>
          <UTextarea v-model="pickupHint" :rows="5" placeholder="Укажите адреса выдачи товаров для каждого учреждения..." class="w-full" />
          <p class="text-xs text-muted">Этот текст будет показан студентам при оформлении заявки на обмен.</p>
        </div>

        <div class="flex justify-end">
          <UButton color="primary" :loading="saving" @click="saveSettings">Сохранить настройки</UButton>
        </div>
      </div>

      <div class="bg-elevated border border-default rounded-lg p-6 space-y-4">
        <div>
          <h3 class="text-base font-semibold text-default">Учреждения</h3>
          <p class="text-sm text-muted mt-1">Список учреждений, для которых доступен маркетплейс.</p>
        </div>
        <div v-if="institutions.length > 0" class="space-y-2">
          <div v-for="inst in institutions" :key="inst.id" class="flex items-center gap-2 p-2 bg-muted rounded text-sm">
            <UIcon name="i-heroicons-building-office" class="w-4 h-4 text-primary shrink-0" />
            <span>{{ inst.name }}</span>
          </div>
        </div>
        <UAlert v-else icon="i-heroicons-information-circle" color="info" variant="soft" title="" description="Загрузка учреждений..." />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['moderator'],
  layout: 'moderator',
  title: 'Настройки',
  description: 'Настройки маркетплейса',
  breadcrumb: [{ label: 'Главная', to: '/moderator' }, { label: 'Настройки' }],
})

const { get, put } = useApi()
const { show: showApiError } = useApiError()
const toast = useToast()

const pickupHint = ref('')
const institutions = ref<any[]>([])
const saving = ref(false)

function loadSettings() {
  get<any>('/api/v1/moderator/marketplace/settings').then(data => {
    pickupHint.value = data?.data?.pickup_hint ?? ''
  }).catch(e => showApiError(e, 'Ошибка загрузки настроек'))
}

function loadInstitutions() {
  get<any>('/api/v1/institutions').then(data => {
    institutions.value = Array.isArray(data) ? data : []
  }).catch(() => {})
}

async function saveSettings() {
  saving.value = true
  try {
    const data = await put<any>('/api/v1/moderator/marketplace/settings', { pickup_hint: pickupHint.value })
    toast.add({ title: data.message || 'Настройки сохранены', color: 'success' })
  } catch (error) {
    showApiError(error, 'Ошибка сохранения')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSettings()
  loadInstitutions()
})
</script>
