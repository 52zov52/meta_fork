<template>
  <div
    v-if="!dismissed"
    class="fixed bottom-4 left-4 right-4 z-50 flex justify-center px-4"
  >
    <div
      class="max-w-xl w-full rounded-xl border border-default bg-elevated px-4 py-3 shadow-lg flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-start gap-3">
        <UIcon name="i-lucide-cookie" class="size-5 shrink-0 mt-0.5 text-primary" />
        <p class="text-sm text-default">
          Мы используем только технические cookie для работы авторизации и обеспечения безопасности сервиса.
          Подробнее в
          <ULink
            to="https://iubip.ru/privacy.php"
            target="_blank"
            class="text-primary hover:underline"
          >
            Политике конфиденциальности
          </ULink>.
        </p>
      </div>
      <div class="shrink-0 self-center">
        <UButton color="primary" size="sm" @click="dismiss">
          Понятно
        </UButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { dismissed, dismiss } = useCookieNotice()
</script>
