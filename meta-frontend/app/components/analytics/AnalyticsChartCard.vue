<template>
  <div ref="cardRef" :class="['min-w-0 flex flex-col space-y-2 bg-elevated border border-default border-t-2 border-t-primary rounded-lg shadow-sm p-3 md:p-4 text-center overflow-hidden', heightClass]">
    <div class="relative mb-1 min-h-6 flex items-center gap-2">
      <div
        class="min-w-0 flex-1"
        :class="titleAlignment === 'center' ? 'flex justify-center text-center' : 'flex justify-start text-left'"
      >
        <h3 ref="titleRef" class="max-w-full font-medium text-xs md:text-base text-balance leading-snug">
          {{ title }}
        </h3>
      </div>
      <div data-snapshot-exclude class="ml-auto flex items-center gap-1 md:gap-2 shrink-0">
        <span v-if="total != null" class="text-xs md:text-sm font-semibold text-muted whitespace-nowrap">Всего: {{ total }}</span>
        <slot name="header-actions" />
        <CardSnapshotButton
          :target-el="cardRef"
          :filename="snapshotFilename"
          :aria-label="`Скопировать карточку «${title}» как изображение`"
          :tooltip="`Скопировать карточку «${title}» как изображение`"
        />
      </div>
    </div>
    <div class="min-w-0 w-full flex-1 min-h-0">
      <slot />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  total: {
    type: Number,
    default: null,
  },
  heightClass: {
    type: String,
    default: 'md:h-[30rem]',
  },
  snapshotFilename: {
    type: String,
    default: 'card.png',
  },
});

const cardRef = ref(null);
const titleRef = ref(null);
const titleAlignment = ref('center');

let titleResizeObserver;

function updateTitleAlignment() {
  const el = titleRef.value;
  if (!el) return;

  const lineHeight = Number.parseFloat(getComputedStyle(el).lineHeight) || 16;
  const isWrapped = el.scrollHeight > lineHeight * 1.35;
  titleAlignment.value = isWrapped ? 'left' : 'center';
}

function scheduleTitleAlignmentUpdate() {
  requestAnimationFrame(updateTitleAlignment);
}

onMounted(() => {
  scheduleTitleAlignmentUpdate();

  if (typeof ResizeObserver === 'undefined') return;

  titleResizeObserver = new ResizeObserver(scheduleTitleAlignmentUpdate);
  if (cardRef.value) titleResizeObserver.observe(cardRef.value);
  if (titleRef.value) titleResizeObserver.observe(titleRef.value);
});

watch(() => props.title, scheduleTitleAlignmentUpdate);

onBeforeUnmount(() => {
  titleResizeObserver?.disconnect();
});
</script>
