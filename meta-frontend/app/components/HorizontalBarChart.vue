<template>
  <div class="min-w-0 w-full">
    <div
      v-if="chartData.length"
      class="min-w-0 w-full"
      role="img"
      :aria-label="ariaLabel"
    >
      <!-- Axis ticks -->
      <div
        class="grid h-5 items-end text-[10px] md:text-xs text-muted"
        :style="rowStyle"
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
      </div>

      <!-- Bars -->
      <div
        v-for="row in chartData"
        :key="row.ix"
        class="grid min-h-9 md:min-h-10 items-center"
        :style="rowStyle"
      >
        <div :class="labelClass">
          {{ row.x }}
        </div>
        <div :class="trackClass">
          <span
            v-for="tick in gridTicks"
            :key="tick.value"
            class="pointer-events-none absolute inset-y-0 w-px"
            :style="[tick.gridStyle, { backgroundColor: 'var(--ui-border-muted)' }]"
            aria-hidden="true"
          />
          <div
            :class="valueClass"
            :style="getBarStyle(row.y, row.ix)"
            role="meter"
            :aria-label="`${row.x}: ${formatValue(row.y)}`"
            :aria-valuemin="0"
            :aria-valuemax="axisMax"
            :aria-valuenow="Number(row.y) || 0"
          >
            <span v-if="Number(row.y) > 0" class="whitespace-nowrap">
              {{ formatValue(row.y) }}
              <span class="text-xs opacity-80 ml-1">{{ getPercent(row.y) }}</span>
            </span>
          </div>
          <span
            v-if="Number(row.y) <= 0"
            class="ml-2 text-xs md:text-sm font-semibold text-muted tabular-nums"
          >
            {{ formatValue(row.y) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  data: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  height: { type: Number, default: 320 },
  color: { type: String, default: '#5B8DEF' },
  colors: { type: Array, default: () => [] },
  accentColor: { type: String, default: '#a8e06c' },
  accentThreshold: { type: Number, default: 0.7 },
  ariaLabel: { type: String, default: 'Горизонтальная диаграмма' },
  valueFormat: { type: String, default: 'count' },
})

const chartData = computed(() => props.data)

const axisMax = computed(() => {
  return Math.max(...chartData.value.map(d => d.y ?? 0), 1)
})

const rowStyle = computed(() => ({
  gridTemplateColumns: '120px 1fr',
}))

const labelClass = 'text-xs md:text-sm font-medium text-default truncate pr-2'
const trackClass = 'relative min-w-0 h-6 md:h-7 rounded-sm bg-muted/30 overflow-hidden'
const valueClass = 'absolute inset-y-0 left-0 flex items-center justify-start px-2 text-xs md:text-sm font-semibold text-white transition-all duration-300'

const axisTicks = computed(() => {
  const max = axisMax.value
  const steps = 5
  const step = Math.ceil(max / steps)
  const ticks = []
  for (let i = 0; i <= steps; i++) {
    const value = step * i
    if (value > max) break
    const percent = (value / max) * 100
    ticks.push({
      value,
      label: formatAxisValue(value),
      style: { left: `${percent}%` },
      isCenter: i > 0 && i < steps,
    })
  }
  return ticks
})

const gridTicks = computed(() => {
  const max = axisMax.value
  const steps = 5
  const step = Math.ceil(max / steps)
  const ticks = []
  for (let i = 1; i <= steps; i++) {
    const value = step * i
    if (value > max) break
    const percent = (value / max) * 100
    ticks.push({
      value,
      gridStyle: { left: `${percent}%` },
    })
  }
  return ticks
})

function getBarStyle(value, index) {
  const percent = axisMax.value ? (value / axisMax.value) * 100 : 0
  const ratio = axisMax.value ? value / axisMax.value : 0
  
  let bgColor
  if (props.colors.length > 0) {
    bgColor = props.colors[index % props.colors.length]
  } else if (ratio >= props.accentThreshold) {
    bgColor = props.accentColor
  } else {
    bgColor = props.color
  }
  
  return {
    width: `${percent}%`,
    backgroundColor: bgColor,
  }
}

function formatAxisValue(y) {
  if (!Number.isFinite(y)) return '0'
  if (props.valueFormat === 'score') {
    return Number.isInteger(y) ? String(y) : y.toLocaleString('ru-RU', { maximumFractionDigits: 2 })
  }
  return String(Math.trunc(y))
}

function formatValue(y) {
  if (!Number.isFinite(y)) return '0'
  if (props.valueFormat === 'score') {
    return Number.isInteger(y) ? String(y) : y.toLocaleString('ru-RU', { maximumFractionDigits: 2 })
  }
  return String(Math.trunc(y))
}

function getPercent(value) {
  if (!props.total) return ''
  return `(${((value / props.total) * 100).toFixed(1)}%)`
}
</script>
