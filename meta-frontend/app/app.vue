<script setup>
const colorMode = useColorMode()
const themeColor = computed(() => colorMode.value === 'dark' ? '#18181b' : '#ffffff')
useHead({
  meta: [
    { charset: 'utf-8' },
    { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1' },
    { key: 'theme-color', name: 'theme-color', content: themeColor }
  ],
  htmlAttrs: {
    lang: 'ru'
  }
})

const toaster = {
  position: 'top-right',
  duration: 5000,
  max: 5,
  expand: true
}
</script>

<template>
  <UApp :toaster="toaster">
    <NuxtLoadingIndicator color="var(--ui-primary)" :height="3" :throttle="0" />

    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>

    <CookieNotice />
  </UApp>
</template>
