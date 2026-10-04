<template>
    <div>
        employer activate
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['employer']
});
</script>
