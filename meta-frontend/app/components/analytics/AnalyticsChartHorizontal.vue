<template>
  <div class="h-full overflow-hidden">
    <div class="h-full p-3 md:p-6 flex flex-col min-h-0">
    
      <div v-if="loading" class="flex justify-center py-8">
        <UIcon name="i-heroicons-arrow-path" class="animate-spin w-5 h-5 text-primary" />
      </div>
      <div v-else-if="chartData.length" class="min-w-0 w-full overflow-x-auto overflow-y-auto flex-1 min-h-0">
        <!-- Legend -->
        <div v-if="legends.length" class="flex flex-wrap justify-center gap-4 mb-4">
          <div v-for="(legend, i) in legends" :key="i" class="flex items-center gap-2">
            <div class="w-4 h-4" :style="{ backgroundColor: colors[i] }" />
            <span class="text-sm text-slate-600">{{ legend }} ({{ legendTotals[i] }})</span>
          </div>
        </div>

        <div class="min-w-0 w-full py-1 md:py-2 flex-1 min-h-0" role="img" :aria-label="title">
          <!-- Axis -->
          <div
            class="grid h-5 items-end text-[10px] md:text-xs text-muted"
            :style="horizontalBarRowStyle"
            aria-hidden="true"
          >
            <div />
            <div class="relative min-w-0 h-full">
              <span
                v-for="tick in axisTicks"
                :key="tick.value"
                :class="['absolute bottom-0 tabular-nums', tick.isCenter ? '-translate-x-1/2' : '']"
                :style="tick.style"
              >
                {{ tick.label }}
              </span>
            </div>
            <div v-if="props.showRowTotals" />
          </div>

          <!-- Bars -->
          <div
            v-for="row in chartData"
            :key="row.ix"
            class="grid min-h-9 md:min-h-10 items-center"
            :style="horizontalBarRowStyle"
          >
            <div :class="horizontalBarLabelClass">
              {{ row.x }}
              <div class="flex flex-wrap gap-x-1.5 gap-y-0 mt-0.5">
                <span
                  v-for="(val, si) in row.values"
                  :key="si"
                  v-show="Number(val) > 0"
                  class="inline-flex items-center gap-0.5 text-[8px] md:text-[9px] text-muted tabular-nums leading-none"
                >
                  <span
                    class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                    :style="{ backgroundColor: colors[si] }"
                  />
                  {{ Math.trunc(val) }}
                </span>
              </div>
            </div>
            <div :class="horizontalBarTrackClass">
              <span
                v-for="tick in gridTicks"
                :key="tick.value"
                class="pointer-events-none absolute inset-y-0 w-px"
                :style="[tick.gridStyle, { backgroundColor: 'var(--ui-border-muted)' }]"
                aria-hidden="true"
              />
              <div class="relative z-10 w-full flex flex-col">
                <!-- Stacked bar segments -->
                <div class="flex h-7 md:h-8 min-w-0 w-full">
                  <template v-for="(val, si) in row.values" :key="si">
                    <div
                      v-if="Number(val) > 0"
                      :style="getSegmentStyle(val, si)"
                      class="min-w-0 overflow-hidden flex items-center justify-center"
                    >
                      <UTooltip
                        :text="`${legends[si] || 'Значение'}: ${Math.trunc(val)}`"
                        :prevent="!legends[si]"
                        class="w-full h-full flex items-center justify-center"
                        :content="{ side: 'top' }"
                      >
                        <div
                          :style="{ color: getSegmentTextColor(si) }"
                          class="w-full h-full flex items-center justify-center text-[10px] md:text-xs font-bold tabular-nums leading-none overflow-hidden whitespace-nowrap px-1 transition-all hover:brightness-95 cursor-pointer"
                        >
                          <span v-if="shouldShowLabel(val)">{{ Math.trunc(val) }}</span>
                        </div>
                      </UTooltip>
                    </div>
                  </template>
                </div>

              </div>
            </div>
            <div v-if="props.showRowTotals" class="flex justify-end pl-1 md:pl-3">
              <span class="min-w-max rounded-md border border-default bg-default px-2 py-1 text-xs md:text-sm font-semibold text-default tabular-nums shadow-sm">
                {{ formatValue(row.total) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useMediaQuery } from '@vueuse/core'

const props = defineProps({
  title: { type: String, default: '' },
  data: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  colors: { type: Array, default: () => ['#6366f1'] },
  legends: { type: Array, default: () => [] },
  legendTotals: { type: Array, default: () => [] },
  showRowTotals: { type: Boolean, default: false },
})

const isMobile = useMediaQuery('(max-width: 767px)')

const horizontalBarRowStyle = computed(() => {
  const labelWidth = isMobile.value ? 'minmax(3rem, 6rem)' : 'minmax(5rem, 10rem)'
  const totalWidth = isMobile.value ? 'minmax(2.5rem, 2.5rem)' : 'minmax(4.5rem, 4.5rem)'
  return {
    gridTemplateColumns: props.showRowTotals
      ? `${labelWidth} minmax(0, 1fr) ${totalWidth}`
      : `${labelWidth} minmax(0, 1fr)`,
  }
})
const horizontalBarLabelClass = 'min-w-0 pr-1 text-left text-[10px] md:text-xs font-semibold text-muted whitespace-normal break-words leading-tight'
const horizontalBarTrackClass = 'relative min-w-0 h-full border-l border-default flex items-center'

const chartData = computed(() => props.data.map(d => ({
  ...d,
  total: d.values ? d.values.reduce((sum, v) => sum + (Number(v) || 0), 0) : (d.y ?? 0),
})))

const maxRaw = computed(() =>
  Math.max(0, ...chartData.value.map(d => d.total))
)

const axisMax = computed(() => {
  const max = maxRaw.value
  if (max <= 0) return 1
  return Math.ceil(max / 100) * 100
})

const axisTicks = computed(() => {
  const max = axisMax.value
  const step = getNiceAxisStep(max)
  const values = []
  for (let value = 0; value < max; value += step) {
    if (max - value < step * 0.5) break
    values.push(value)
  }
  values.push(max)
  return values.map((value, index) => {
    const isFirst = index === 0
    const isLast = index === values.length - 1
    return {
      value,
      label: String(Math.trunc(value)),
      isFirst,
      isLast,
      isCenter: !isFirst && !isLast,
      style: isFirst ? { left: '0' } : isLast ? { right: '0' } : { left: `${(value / max) * 100}%` },
      gridStyle: isLast ? { right: '0' } : { left: `${(value / max) * 100}%` },
    }
  })
})

const gridTicks = computed(() =>
  axisTicks.value.filter(tick => !tick.isFirst)
)

function getNiceAxisStep(max) {
  if (max <= 0) return 1
  const targetIntervalCount = 5
  const rawStep = max / targetIntervalCount
  const magnitude = 10 ** Math.floor(Math.log10(rawStep))
  const normalized = rawStep / magnitude
  if (normalized <= 1) return magnitude
  if (normalized <= 2) return 2 * magnitude
  if (normalized <= 5) return 5 * magnitude
  return 10 * magnitude
}

function getSegmentTextColor(segmentIndex) {
  if (segmentIndex <= 1) return '#ffffff'
  return '#1e293b'
}

function getSegmentStyle(value, segmentIndex) {
  const numberValue = Math.max(0, Number(value) || 0)
  const max = Number(axisMax.value) || 0
  const width = max > 0 ? (numberValue / max) * 100 : 0
  const color = props.colors[segmentIndex % props.colors.length] || props.colors[0] || 'var(--ui-primary)'
  return {
    width: `${width}%`,
    backgroundColor: color,
  }
}

function shouldShowLabel(value) {
  const numberValue = Math.max(0, Number(value) || 0)
  const max = Number(axisMax.value) || 0
  if (max <= 0 || numberValue <= 0) return false
  const chars = String(Math.trunc(numberValue)).length
  const minWidthPercent = chars * 1.5
  return (numberValue / max) * 100 >= minWidthPercent
}

function getHiddenSegments(row) {
  const result = []
  for (let i = 0; i < row.values.length; i++) {
    const val = Number(row.values[i]) || 0
    if (val > 0 && !shouldShowLabel(val)) {
      result.push({ index: i, value: Math.trunc(val) })
    }
  }
  return result
}

function formatValue(y) {
  if (!Number.isFinite(y)) return '0'
  return String(Math.trunc(y))
}
</script>
