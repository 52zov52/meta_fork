<template>
    <div class="flex justify-center pt-4">
        <UPagination
            :page="pagination.currentPage ?? 1"
            :total="totalComputed"
            :items-per-page="pagination.perPage ?? 10"
            :sibling-count="1"
            show-edges
            @update:page="$emit('update:page', $event)"
        />
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    pagination: {
        currentPage?: number | null;
        totalItems?: number;
        total?: number;
        perPage?: number;
    };
}>();

defineEmits<{
    'update:page': [page: number];
}>();

const totalComputed = computed(() =>
    props.pagination.totalItems ?? props.pagination.total ?? 0,
);
</script>
