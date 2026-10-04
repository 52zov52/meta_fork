/**
 * Возвращает вариант шапки и данные для nav/sidebar.
 * Без опций — вариант по auth (для layout public).
 * С variant: 'student' — принудительно student (для layout student).
 *
 * Варианты:
 * - guest: не авторизован — «Метаплатформа» + «Войти», без бургера.
 * - student: полное меню кабинета студента (Главная, Мероприятия, Избранное, …) + «Выход» и бургер с тем же меню.
 *    Используется на layout student и на layout public для роли student.
 * - прочие авторизованные роли (admin, mentor и др.): упрощённая шапка только для layout public.
 *    Содержит ссылку «Вернуться в кабинет» (на роут роли) и «Выход». Основное меню роли доступно только внутри её собственного layout.
 */
import type { User } from '~/types';

export type HeaderVariant = 'guest' | 'student' | 'admin' | 'mentor';

export interface NavItem {
  to: string
  label: string
  target?: string
}

export interface SidebarItem extends NavItem {
  icon: string
}

const STUDENT_NAV_ITEMS: NavItem[] = [
  { to: '/student/my-activities', label: 'Главная' },
  { to: '/student/activities', label: 'Мероприятия' },
  { to: '/student/wishlist', label: 'Избранное' },
  { to: '/student/marketplace', label: 'Маркетплейс' },
  { to: '/student/portfolio', label: 'Портфолио' },
  { to: '/student/ratings', label: 'Рейтинги' },
  { to: 'https://web.virtonex.com/events/view/pvUYcmnO_gf6ARnbiIISPAo5lZp3VR-f', label: 'Мета в 3D', target: '_blank' },
];

const STUDENT_SIDEBAR_ITEMS: SidebarItem[] = [
  { to: '/student/my-activities', label: 'Главная', icon: 'i-heroicons-home' },
  { to: '/student/activities', label: 'Мероприятия', icon: 'i-heroicons-calendar-days' },
  { to: '/student/wishlist', label: 'Избранное', icon: 'i-heroicons-heart' },
  { to: '/student/marketplace', label: 'Маркетплейс', icon: 'i-heroicons-shopping-bag' },
  { to: '/student/portfolio', label: 'Портфолио', icon: 'i-heroicons-square-3-stack-3d' },
  { to: '/student/ratings', label: 'Рейтинги', icon: 'i-heroicons-chart-bar-square' },
  { to: 'https://web.virtonex.com/events/view/pvUYcmnO_gf6ARnbiIISPAo5lZp3VR-f', label: 'Мета в 3D', icon: 'i-heroicons-link', target: '_blank' },
];

export function useHeaderVariant(options?: { variant?: HeaderVariant }) {
  const { isAuthenticated, user } = useSanctumAuth();
  const { getPath } = useRoleRedirect();

  const variant = computed<HeaderVariant>(() => {
    if (options?.variant) return options.variant;
    if (!isAuthenticated.value) return 'guest';
    const role = (user.value as User | undefined)?.role;
    if (role === 'admin') return 'admin';
    if (role === 'mentor') return 'mentor';
    return 'student';
  });

  const navItems = computed<NavItem[]>(() => {
    const v = variant.value;
    if (v === 'guest') return [];
    if (v === 'student') return STUDENT_NAV_ITEMS;
    const mainPath = getPath(v === 'admin' ? 'admin' : 'mentor');
    return [{ to: mainPath, label: 'Вернуться на главную' }];
  });

  const sidebarItems = computed<SidebarItem[]>(() => {
    const v = variant.value;
    if (v === 'guest') return [];
    if (v === 'student') return STUDENT_SIDEBAR_ITEMS;
    const mainPath = getPath(v === 'admin' ? 'admin' : 'mentor');
    return [{ to: mainPath, label: 'Вернуться на главную', icon: 'i-heroicons-home' }];
  });

  const showBurger = computed(() => variant.value !== 'guest');
  const isGuest = computed(() => variant.value === 'guest');

  return {
    variant,
    navItems,
    sidebarItems,
    showBurger,
    isGuest,
  };
}
