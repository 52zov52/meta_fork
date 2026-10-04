<template>
    <NuxtLayout name="student">
        <template #header>
            <StudentHeader />
        </template>

    </NuxtLayout>
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['student'],
    layout: false
});
</script>
