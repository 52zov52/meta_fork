<template>
  <div class="flex flex-col gap-1">
    <NuxtLink
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-primary-200 text-sm transition-colors cursor-pointer"
      :class="isActive(item.to) ? 'bg-primary-700 text-white ring-1 ring-primary/30 ring-inset' : 'hover:bg-primary-800 hover:text-white'"
    >
      <UIcon :name="item.icon" class="shrink-0 w-5 h-5" />
      <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
const { items } = useModeratorNav()
const route = useRoute()

const { collapsed = false } = defineProps<{
  collapsed?: boolean
}>()

function isActive(to: string) {
  if (to === '/moderator') {
    return route.path === '/moderator'
  }
  return route.path.startsWith(to)
}
</script>
