<template>
    <DashboardContent endpoint="/api/v1/mentor/statistics" role="mentor" />
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['mentor'],
    layout: 'mentor',
    title: 'Главная',
    breadcrumb: [{ label: 'Главная' }],
});
</script>
