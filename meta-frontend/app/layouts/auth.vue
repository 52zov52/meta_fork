<script setup lang="ts">
import { staticImg } from '~/assets/img'
</script>

<template>
    <div class="relative h-dvh lg:max-w-none grid lg:grid-cols-2 items-center justify-center px-8 sm:px-0 lg:px-0">
        <!-- Десктопный фон -->
        <div
            class="hidden lg:flex h-full flex-col items-center justify-center bg-cover p-10 text-white relative"
            :style="{ backgroundImage: `url(${staticImg.bg})` }"
        >
            <div class="absolute inset-0 bg-black/40 pointer-events-none"/>

                <NuxtLink to="/auth/login" class="relative z-10 flex flex-col items-center justify-center space-y-4">
                    <img :src="staticImg.logoIubipDark" alt="logo" class="w-72">
                </NuxtLink>
                <div class="flex flex-col items-center justify-center text-white text-3xl relative z-10">
                    <p class="font-semibold">Первый инновационный</p>
                    <p>Университет будущего</p>
                </div>
            </div>

        <!-- Мобильный фон -->
        <div
            class="lg:hidden absolute inset-0 bg-cover bg-center"
            :style="{ backgroundImage: `url(${staticImg.bg})` }"
        >
            <div class="absolute inset-0 bg-black/40"/>
        </div>

        <!-- Логотип для мобильного -->
        <div class="lg:hidden absolute top-6 left-1/2 -translate-x-1/2 z-10">
            <NuxtLink to="/auth/login">
                <img :src="staticImg.logoIubipDark" alt="logo" class="w-40">
            </NuxtLink>
        </div>

        <!-- Форма -->
        <div class="relative z-10 lg:p-8 w-full">
            <div class="w-full sm:w-152 mx-auto flex flex-col justify-center space-y-6 bg-white/90 lg:bg-transparent backdrop-blur-sm lg:backdrop-blur-none rounded-xl p-6 lg:p-0 shadow-lg lg:shadow-none">
                <div class="flex flex-col space-y-2 text-center">
                    <slot name="title" />
                    <slot name="description" />
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
