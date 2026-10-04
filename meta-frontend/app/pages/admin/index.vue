<template>
    <DashboardContent endpoint="/api/v1/admin/statistics" role="admin" />
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['admin'],
    layout: 'admin',
    title: 'Главная',
    breadcrumb: [{ label: 'Главная' }],
});
</script>
