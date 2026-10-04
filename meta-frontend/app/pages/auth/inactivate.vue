<template>
  <div
    class="h-screen w-full bg-cover bg-center flex flex-col relative"
    :style="{ backgroundImage: `url(${staticImg.bg})` }"
  >
    <!-- Градиентный оверлей слева (фиолетовый) к справа (синий) -->
    <div class="absolute inset-0 bg-linear-to-br from-indigo-950/85 via-purple-950/70 to-sky-950/60" />

    <!-- Header -->
    <div class="relative flex justify-end p-4 z-10">
      <UButton
        color="secondary"
        variant="soft"
        trailing-icon="i-heroicons-arrow-right-start-on-rectangle"
        @click="handleLogout"
      >
        Выход
      </UButton>
    </div>

    <!-- Main content -->
    <div class="relative flex-1 flex items-center justify-center p-4 z-10">
      <UCard
        class="max-w-2xl text-center bg-slate-950/50 backdrop-blur-xl border border-primary/30"
        :ui="{
          body: { padding: 'px-8 py-10' },
          ring: 'ring-1 ring-primary/20'
        }"
      >
        <template #default>
          <!-- Иконка с свечением -->
          <div class="relative mb-6">
            <div class="absolute inset-0 bg-indigo-500/30 blur-2xl rounded-full scale-150" />
            <UIcon
              name="i-heroicons-clock"
              class="relative w-20 h-20 text-indigo-400 mx-auto drop-shadow-[0_0_20px_rgba(99,102,241,0.8)]"
            />
          </div>

          <h2 class="text-2xl font-bold text-slate-100 mb-4">
            Ваш аккаунт находится на модерации
          </h2>

          <p class="text-slate-400 leading-relaxed max-w-md mx-auto">
            После проверки он будет активирован. В случае нарушения правил аккаунт будет удален.
          </p>

          <!-- Декоративные звёзды -->
          <div class="mt-8 flex justify-center gap-3">
            <span class="w-2 h-2 rounded-full bg-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.8)] animate-pulse" />
            <span class="w-2 h-2 rounded-full bg-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.8)] animate-pulse delay-100" />
            <span class="w-2 h-2 rounded-full bg-violet-400 shadow-[0_0_10px_rgba(167,139,250,0.8)] animate-pulse delay-200" />
          </div>
        </template>
      </UCard>
    </div>
  </div>
</template>

<script setup>
import { staticImg } from '~/assets/img'

definePageMeta({
  layout: 'default',
  middleware: ['sanctum:auth']
});

const { logout: sanctumLogout } = useSanctumAuth();
const logoutStatus = ref<'idle' | 'success' | 'error'>('idle');

async function handleLogout() {
  try {
    await sanctumLogout();
    logoutStatus.value = 'success';
  } catch {
    logoutStatus.value = 'error';
  }
}
</script>
