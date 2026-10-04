<template>
    <UModal
        :open="open"
        fullscreen
        title="Просмотр изображения"
        description="Полноэкранный просмотр изображения"
        :ui="{
            overlay: 'bg-black/90 backdrop-blur-sm z-[9999]',
            content: 'w-full h-full p-0 bg-transparent shadow-none z-[10000]'
        }"
        :dismissible="true"
        :close="false"
        @update:open="$emit('update:open', $event)"
        @close="$emit('close')"
    >
        <template v-if="effectiveSrc" #content>
            <div
                class="relative grid place-items-center w-full h-full p-8 md:p-16 cursor-zoom-out"
                @click.self="close"
            >
                <UButton
                    color="neutral"
                    variant="solid"
                    icon="i-heroicons-x-mark"
                    class="absolute right-4 top-4 z-[10001] rounded-full shadow-lg"
                    aria-label="Закрыть изображение"
                    @click.stop="close"
                />
                <img
                    :src="effectiveSrc"
                    :alt="alt || ''"
                    class="max-w-[90vw] max-h-[90vh] object-contain cursor-default"
                    @error="handleError"
                >
            </div>
        </template>
    </UModal>
</template>

<script setup>
const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    src: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: null,
    },
    fallbackSrc: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:open', 'close']);

const hasError = ref(false);

const effectiveSrc = computed(() => {
    if (hasError.value && props.fallbackSrc) {
        return props.fallbackSrc;
    }
    return props.src || props.fallbackSrc || '';
});

function handleError() {
    hasError.value = true;
}

function close() {
    emit('update:open', false);
    emit('close');
}

watch(() => props.open, (val) => {
    if (!val) {
        hasError.value = false;
    }
});
</script>
