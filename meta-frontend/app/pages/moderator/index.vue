<template>
    <DashboardContent endpoint="/api/v1/moderator/statistics" role="moderator" />
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['moderator'],
    layout: 'moderator',
    title: 'Главная',
    breadcrumb: [{ label: 'Главная' }],
});
</script>
