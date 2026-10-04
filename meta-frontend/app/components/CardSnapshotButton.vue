<template>
  <ClientOnly>
    <UTooltip :text="tooltip" :content="{ side: 'top' }">
      <button
        type="button"
        class="p-1 rounded-md hover:bg-muted text-muted hover:text-default disabled:opacity-50 disabled:cursor-not-allowed"
        :aria-label="ariaLabel"
        :disabled="loading || !targetEl"
        @click="captureSnapshot"
      >
        <UIcon
          :name="loading ? 'i-heroicons-arrow-path' : 'i-heroicons-camera'"
          :class="['w-4 h-4 md:w-5 md:h-5', loading ? 'animate-spin' : '']"
        />
      </button>
    </UTooltip>
  </ClientOnly>
</template>

<script setup>
import { copyElementSnapshotToClipboard } from '~/utils/cardSnapshot';

const props = defineProps({
  targetEl: {
    type: Object,
    default: null,
  },
  filename: {
    type: String,
    required: true,
  },
  tooltip: {
    type: String,
    default: 'Скопировать карточку как изображение',
  },
  ariaLabel: {
    type: String,
    default: 'Скопировать карточку как изображение',
  },
});

const toast = useToast();
const loading = ref(false);

async function captureSnapshot() {
  if (!props.targetEl || loading.value) return;

  loading.value = true;
  try {
    const result = await copyElementSnapshotToClipboard(props.targetEl, props.filename);
    toast.add({
      title: result.mode === 'clipboard' ? 'Скрин карточки скопирован' : 'Скрин карточки скачан',
      color: 'success',
    });
  } catch (error) {
    console.error('Card snapshot error:', error);
    toast.add({
      title: 'Не удалось сделать скрин карточки',
      color: 'error',
    });
  } finally {
    loading.value = false;
  }
}
</script>
