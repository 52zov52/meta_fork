import type { NavigationMenuItem } from '@nuxt/ui'

const ADMIN_NAV_ITEMS: NavigationMenuItem[] = [
  { label: 'Главная', icon: 'i-heroicons-home', to: '/admin' },
  { label: 'Мероприятия', icon: 'i-heroicons-calendar-days', to: '/admin/activities' },
  { label: 'Пользователи', icon: 'i-heroicons-users', to: '/admin/users' },
  { label: 'Уведомления', icon: 'i-heroicons-bell', to: '/admin/notifications' },
  { label: 'Маркетплейс', icon: 'i-heroicons-building-storefront', to: '/admin/marketplace' },
  { label: 'Настройки', icon: 'i-heroicons-cog-6-tooth', to: '/admin/settings' },
  { label: 'Рейтинги', icon: 'i-heroicons-trophy', to: '/student/ratings' },
]

export function useAdminNav() {
  const { logout: sanctumLogout } = useSanctumAuth()
  const status = ref<'idle' | 'success' | 'error'>('idle')

  const items = computed<NavigationMenuItem[]>(() => ADMIN_NAV_ITEMS)

  async function logout() {
    try {
      await sanctumLogout()
      status.value = 'success'
    } catch {
      status.value = 'error'
    }
  }

  return { items, logout, status }
}
