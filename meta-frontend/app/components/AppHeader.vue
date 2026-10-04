<template>
  <header class="flex flex-col">
    <div 
    :class="[
        'fixed top-0 left-0 right-0 z-20 flex items-center backdrop-blur-md px-4 lg:px-8 py-4 border-b border-primary/20 transition-colors duration-300',
        isScrolledPastHero
          ? 'bg-slate-950/80'
          : 'bg-slate-950/40'
      ]"
    >

      <div class="shrink-0 flex items-center gap-3 w-auto lg:w-52">
        <img :src="staticImg.logoIubipDark" class="w-16 drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]" alt="logo_iubip">
        <img :src="staticImg.logoCi" class="w-28 drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]" alt="logo_ci">
      </div>

      <nav class="hidden lg:flex flex-1 items-center justify-center gap-1 text-slate-100 min-w-0">
        <slot name="nav" />
      </nav>

      <!-- Пустая распорка на мобилке, чтобы actions прижался вправо -->
      <div class="flex-1 lg:hidden" />

      <div class="shrink-0 flex items-center justify-end gap-2 lg:w-32">
        <slot name="actions" />
      </div>
    </div>

    <div
      ref="heroRef"
      :class="[height, 'flex flex-col bg-cover bg-center relative']"
      :style="{ backgroundImage: `url(${staticImg.bg})` }"
    >
      <div class="absolute inset-0 bg-linear-to-r from-indigo-950/70 via-purple-950/50 to-sky-950/40" />
    </div>

    <div>
      <slot name="subheader" />
    </div>
  </header>
</template>

<script setup lang="ts">
  import { staticImg } from '~/assets/img'
  import { ref, onMounted, onUnmounted } from 'vue'

  withDefaults(defineProps<{
    height?: string
  }>(), {
    height: 'h-20'
  })

  const isScrolledPastHero = ref(false)
  const heroRef = ref<HTMLElement | null>(null)

  let observer: IntersectionObserver | null = null

  onMounted(() => {
    if (!heroRef.value) return

    observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0]
        if (!entry) return

        isScrolledPastHero.value = !entry.isIntersecting
      },
      {
        root: null,
        threshold: 0,
        rootMargin: '-80px 0px 0px 0px'
      }
    )

    observer.observe(heroRef.value)
  })

  onUnmounted(() => {
    observer?.disconnect()
    observer = null
  })
</script>
