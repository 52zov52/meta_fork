import type { NavigationMenuItem } from '@nuxt/ui'

const MENTOR_NAV_ITEMS: NavigationMenuItem[] = [
  { label: 'Главная', icon: 'i-heroicons-home', to: '/mentor' },
  { label: 'Мероприятия', icon: 'i-heroicons-calendar-days', to: '/mentor/activities' },
  { label: 'Пользователи', icon: 'i-heroicons-users', to: '/mentor/users' },
  { label: 'Рейтинги', icon: 'i-heroicons-trophy', to: '/student/ratings' },
]

export function useMentorNav() {
  const { logout: sanctumLogout } = useSanctumAuth()
  const status = ref<'idle' | 'success' | 'error'>('idle')

  const items = computed<NavigationMenuItem[]>(() => MENTOR_NAV_ITEMS)

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

