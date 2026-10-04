<template>
  <div class="relative h-screen flex flex-col overflow-hidden">
    <div class="flex-1 min-h-0 overflow-y-auto">
      <AppHeader height="h-44">
        <template #nav>
          <h2 class="text-base md:text-xl font-semibold text-slate-100">Метаплатформа</h2>
        </template>
        <template #actions>
          <UButton
            to="/auth/login"
            icon="i-heroicons-arrow-left-start-on-rectangle"
            size="sm"
            variant="ghost"
            class="text-slate-100 hover:bg-indigo-500/20 hover:text-white"
          >
            Войти
          </UButton>
        </template>
        <template #subheader>
          <slot name="header" />
        </template>
      </AppHeader>

      <main class="px-4 lg:px-8 xl:px-16 py-4">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
</script>
