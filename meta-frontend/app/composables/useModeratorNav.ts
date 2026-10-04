import type { NavigationMenuItem } from '@nuxt/ui'

const MODERATOR_NAV_ITEMS: NavigationMenuItem[] = [
  { label: 'Главная', icon: 'i-heroicons-home', to: '/moderator' },
  { label: 'Мероприятия', icon: 'i-heroicons-calendar-days', to: '/moderator/activities' },
  { label: 'Пользователи', icon: 'i-heroicons-users', to: '/moderator/users' },
  { label: 'Товары', icon: 'i-heroicons-building-storefront', to: '/moderator/marketplace' },
  { label: 'Заявки на товары', icon: 'i-heroicons-clipboard-document-list', to: '/moderator/marketplace/orders' },
  { label: 'Работодатели', icon: 'i-heroicons-briefcase', to: '/moderator/employers' },
  { label: 'Вакансии', icon: 'i-heroicons-briefcase', to: '/moderator/jobs' },
  { label: 'Отклики', icon: 'i-heroicons-inbox', to: '/moderator/responses' },
  { label: 'Рейтинги', icon: 'i-heroicons-trophy', to: '/student/ratings' },
  { label: 'Настройки', icon: 'i-heroicons-cog-6-tooth', to: '/moderator/settings' },
]

export function useModeratorNav() {
  const { logout: sanctumLogout } = useSanctumAuth()
  const status = ref<'idle' | 'success' | 'error'>('idle')

  const items = computed<NavigationMenuItem[]>(() => MODERATOR_NAV_ITEMS)

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
