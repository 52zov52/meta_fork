<script setup lang="ts">
import type { NuxtError } from '#app'
import { staticImg } from '~/assets/img'

const props = defineProps<{
  error: NuxtError
}>()

const is404 = computed(() => props.error?.statusCode === 404)
const displayError = computed(() => {
  if (is404.value) {
    return {
      ...props.error,
      statusMessage: 'Страница не найдена',
      message: 'Извините, запрашиваемая страница не существует или была перемещена.'
    }
  }
  return {
    ...props.error,
    statusMessage: 'Ошибка сервера',
    message: ''
  }
})
</script>

<template>
  <UApp>
    <div
      class="min-h-screen bg-cover bg-center relative"
      :style="{ backgroundImage: `url(${staticImg.bg})` }"
    >
      <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/85 via-purple-950/70 to-sky-950/60" />

      <UError
        :error="displayError"
        :ui="{
          statusCode: 'text-primary-400',
          statusMessage: 'text-neutral-100',
          message: 'text-lg text-neutral-400 text-balance'
        }"
        :clear="{ label: 'Вернуться на главную' }"
        class="relative z-10 min-h-screen"
      >
        <template v-if="!is404" #message>
          Непредвиденная ошибка. Сообщите в
          <a
            href="https://t.me/metaIubipProblems"
            target="_blank"
            rel="noopener noreferrer"
            class="text-secondary hover:text-secondary/300 underline"
          >
            Telegram канал
          </a>
        </template>
      </UError>
    </div>
  </UApp>
</template>
