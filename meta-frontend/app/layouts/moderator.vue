<template>
  <UDashboardGroup>
    <UDashboardSidebar
      v-model:open="sidebarOpen"
      v-model:collapsed="isSidebarCollapsed"
      collapsible
      resizable
      mode="slideover"
      toggle-side="right"
      :default-size="12"
      :max-size="18"
      class="bg-primary-800 border-r border-primary-700"
      :ui="{
        content: 'bg-primary-800 border-r border-primary-700 py-3 px-2 w-64 max-w-[16rem]',
        header: 'px-4 pt-5 pb-3 border-b border-primary-700 flex items-center justify-between gap-2',
        footer: isSidebarCollapsed ? '!border-t-0 py-0 min-h-0 overflow-hidden' : undefined
      }"
    >
      <template #header="{ collapsed }">
        <div v-if="!collapsed" class="flex-1 min-w-0">
          <div class="flex flex-col gap-0.5">
            <UTooltip
              :text="profileData.fullName"
              :disabled="!profileData.fullName"
              :content="{ side: 'top' }"
              class="block min-w-0 w-full"
            >
              <span class="text-sm font-medium truncate block text-left w-full text-primary-100">
                {{ profileData.shortName || 'Загрузка...' }}
              </span>
            </UTooltip>
            <span class="text-xs text-primary-300 truncate">
              Модератор
            </span>
          </div>
        </div>
      </template>

      <template #default="{ collapsed }">
        <ModeratorNavMenu :collapsed="collapsed" />
      </template>

      <template #footer="{ collapsed }">
        <div
          class="w-full px-2 pt-3 pb-2"
          :class="{ 'border-t border-primary-800': !sidebarOpen }"
        >
          <UButton
            icon="i-heroicons-arrow-right-start-on-rectangle"
            :label="collapsed ? undefined : 'Выход'"
            color="error"
            variant="ghost"
            size="sm"
            block
            class="justify-start rounded-lg"
            @click="logout"
          />
        </div>
      </template>
    </UDashboardSidebar>

    <UDashboardPanel>
      <template #header>
        <UDashboardNavbar :title="pageTitle" :ui="{ root: 'border-b-2 border-b-primary/30' }">
          <template #left>
            <UBreadcrumb divider="/" :items="breadcrumbItems" />
          </template>
        </UDashboardNavbar>
        <div v-if="pageDescription" class="border-b border-primary/20 px-4 py-3 bg-primary/5">
          <p class="text-sm text-muted mt-0.5">{{ pageDescription }}</p>
        </div>
      </template>

      <template #body>
        <div class="min-w-0 w-full max-w-full flex flex-col space-y-4 mx-auto pr-4 overflow-x-hidden">
          <slot />
        </div>
      </template>
    </UDashboardPanel>
  </UDashboardGroup>
</template>

<script setup>
const { user } = useSanctumAuth()
const { logout } = useModeratorNav()

const profileData = computed(() => {
  const u = user.value
  return {
    shortName: u?.name || u?.email || 'Модератор',
    fullName: u?.name || u?.email || '',
  }
})

const route = useRoute()
const sidebarOpen = ref(false)
const isSidebarCollapsed = ref(false)

const pageTitle = computed(() => route.meta.title ?? 'Кабинет модератора')
const pageDescription = computed(() => route.meta.description ?? '')
const breadcrumbItems = computed(() => {
  const items = route.meta.breadcrumb
  return Array.isArray(items) && items.length ? items : [{ label: pageTitle.value }]
})
</script>
