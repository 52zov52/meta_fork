<template>
  <div class="relative h-screen flex flex-col overflow-hidden">
    <div class="flex-1 min-h-0 overflow-y-auto">
      <AppHeader height="h-44">
        <template #nav>
          <template v-if="isGuest">
            <h2 class="text-base md:text-xl font-semibold text-slate-100">Метаплатформа</h2>
          </template>
          <template v-else>
            <UButton
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              :target="item.target"
              variant="ghost"
              :class="[
                'text-slate-100 hover:bg-indigo-500/20 hover:text-white',
                isNavActive(item) && 'bg-indigo-500/30 text-white ring-1 ring-indigo-400/50'
              ]"
            >
              {{ item.label }}
            </UButton>
          </template>
        </template>

        <template #actions>
          <UButton
            v-if="showBurger"
            icon="i-heroicons-bars-3"
            size="sm"
            variant="ghost"
            class="block lg:hidden text-slate-100 hover:bg-indigo-500/20 absolute right-4 top-1/2 -translate-y-1/2"
            @click="toggleSidebar"
          />
          <UButton
            v-if="isGuest"
            to="/auth/login"
            icon="i-heroicons-arrow-left-start-on-rectangle"
            size="sm"
            variant="ghost"
            class="text-slate-100 hover:bg-indigo-500/20 hover:text-white"
          >
            Войти
          </UButton>
          <UButton
            v-else
            icon="i-heroicons-arrow-right-start-on-rectangle"
            size="sm"
            color="error"
            variant="ghost"
            class="hidden lg:flex items-center gap-2"
            @click="handleLogout"
          >
            Выход
          </UButton>
        </template>

        <template #subheader>
          <slot name="header" />
        </template>
      </AppHeader>

      <main class="px-4 lg:px-8 xl:px-16 py-4 min-w-0 max-w-full overflow-x-hidden">
        <slot />
      </main>
    </div>

    <USlideover
      v-if="showBurger"
      v-model:open="isSidebarOpen"
      side="left"
      title="Меню навигации"
      description="Меню навигации по кабинету"
      class="lg:hidden w-full max-w-[16rem]"
    >
      <template #content>
        <div class="h-full flex flex-col p-4 bg-[#0f1033] border-r border-primary/20">
          <div class="flex items-center justify-between gap-2 mb-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-col gap-0.5">
                <UTooltip
                  :text="profileData.fullName"
                  :disabled="!profileData.fullName"
                  :content="{ side: 'top' }"
                  class="block min-w-0 w-full"
                >
                  <span class="text-slate-200 text-sm font-medium truncate block text-left w-full">
                    {{ profileData.shortName || 'Загрузка...' }}
                  </span>
                </UTooltip>
                <span class="text-slate-400 text-xs truncate">
                  {{ profileData.role }}
                </span>
              </div>
            </div>
            <UButton
              icon="i-heroicons-x-mark"
              variant="ghost"
              class="text-slate-100 hover:bg-indigo-500/20 shrink-0"
              @click="toggleSidebar"
            />
          </div>

          <div class="flex-1 min-h-0 flex flex-col">
            <nav class="flex-1 flex flex-col gap-1 overflow-y-auto">
              <UButton
                v-for="item in sidebarItems"
                :key="item.to"
                :to="item.to"
                :target="item.target"
                variant="ghost"
                :class="[
                  'justify-start text-slate-200 hover:bg-indigo-500/20 hover:text-white',
                  isNavActive(item) && 'bg-indigo-500/30 text-white'
                ]"
                @click="isSidebarOpen = false"
              >
                <UIcon :name="item.icon" class="w-5 h-5 mr-3 text-indigo-400" />
                {{ item.label }}
              </UButton>
            </nav>

            <div class="pt-3 mt-3 border-t border-primary/30">
              <UButton
                icon="i-heroicons-arrow-right-start-on-rectangle"
                label="Выход"
                variant="ghost"
                color="error"
                size="sm"
                block
                class="justify-start rounded-lg"
                @click="handleLogout"
              />
            </div>
          </div>
        </div>
      </template>
    </USlideover>
  </div>
</template>

<script setup lang="ts">
import type { HeaderVariant } from '~/composables/useHeaderVariant';
import type { User } from '~/types';

type ProfileResponse = {
  shortName?: string;
  fullName?: string;
};

const props = withDefaults(
  defineProps<{
    /** Принудительный вариант шапки (для layout student); без него — по auth */
    forceVariant?: HeaderVariant
  }>(),
  { forceVariant: undefined }
);

const route = useRoute();
const { logout: sanctumLogout, user } = useSanctumAuth();
const logoutStatus = ref<'idle' | 'success' | 'error'>('idle');

async function handleLogout() {
  try {
    await sanctumLogout();
    logoutStatus.value = 'success';
  } catch {
    logoutStatus.value = 'error';
  }
}

const { navItems, sidebarItems, showBurger, isGuest, variant } = useHeaderVariant(
  props.forceVariant ? { variant: props.forceVariant } : undefined
);

const { get } = useApi();

const isSidebarOpen = ref(false);
const profileData = ref({ shortName: '', fullName: '', role: '' });

async function loadProfileData() {
  const userId = (user.value as User | undefined)?.user_id;
  if (!userId) return;

  const v = variant.value;
  if (v === 'guest') return;

  const path =
    v === 'student'
      ? `/api/v1/student/users/${userId}`
      : v === 'admin'
        ? `/api/v1/admin/users/${userId}`
        : v === 'mentor'
          ? `/api/v1/mentor/users/${userId}`
          : null;

  if (!path) return;

  try {
    const data = await get<ProfileResponse>(path);

    profileData.value.shortName = data.shortName ?? '';
    profileData.value.fullName = data.fullName ?? '';
    profileData.value.role =
      v === 'student'
        ? 'Студент'
        : v === 'admin'
          ? 'Администратор'
          : v === 'mentor'
            ? 'Наставник'
            : '';
  } catch {
    // 
  }
}

onMounted(() => {
  if (!isGuest.value) loadProfileData();
});

function isNavActive(item: { to: string; target?: string }) {
  if (item.target) return false;
  return route.path === item.to || route.path.startsWith(item.to + '/');
}

function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value;
}
</script>
