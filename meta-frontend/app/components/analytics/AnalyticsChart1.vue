<template>
  <div class="overflow-hidden">
    <div class="p-6">
      <div v-if="loading" class="flex justify-center py-8">
        <UIcon name="i-heroicons-arrow-path" class="animate-spin w-5 h-5 text-primary" />
      </div>
      <div v-else-if="chartData.length" class="relative w-full">
        <svg :viewBox="`0 0 ${svgWidth} ${svgHeight}`" class="w-full" :style="{ height: svgHeight + 'px' }">
          <!-- Grid lines -->
          <g v-for="tick in yTicks" :key="tick.value">
            <line
              :x1="marginLeft"
              :y1="tick.y"
              :x2="svgWidth - marginRight"
              :y2="tick.y"
              stroke="#e5e7eb"
              stroke-width="1"
            />
            <text
              :x="marginLeft - 8"
              :y="tick.y + 4"
              text-anchor="end"
              fill="#6b7280"
              font-size="12"
            >
              {{ tick.label }}
            </text>
          </g>

          <!-- Bars -->
          <g v-for="(item, i) in chartData" :key="i">
            <rect
              :x="getBarX(i)"
              :y="getBarY(item.y)"
              :width="barWidth"
              :height="getBarHeight(item.y)"
              :fill="getBarColor(i)"
            />
            <!-- Labels inside bars -->
            <text
              :x="getBarX(i) + barWidth / 2"
              :y="getBarY(item.y) + getBarHeight(item.y) / 2 - 8"
              text-anchor="middle"
              fill="#111827"
              font-size="14"
              font-weight="700"
            >
              {{ formatValue(item.y) }}
            </text>
            <text
              :x="getBarX(i) + barWidth / 2"
              :y="getBarY(item.y) + getBarHeight(item.y) / 2 + 10"
              text-anchor="middle"
              fill="#111827"
              font-size="12"
              font-weight="600"
            >
              {{ getPercent(item.y) }}
            </text>
          </g>

          <!-- X axis labels -->
          <g v-for="(item, i) in chartData" :key="`x-${i}`">
            <text
              :x="getBarX(i) + barWidth / 2"
              :y="svgHeight - marginBottom + 15"
              text-anchor="middle"
              fill="#374151"
              font-size="11"
              font-weight="600"
            >{{ item.x }}</text>
          </g>

          <!-- X axis line -->
          <line
            :x1="marginLeft"
            :y1="svgHeight - marginBottom"
            :x2="svgWidth - marginRight"
            :y2="svgHeight - marginBottom"
            stroke="#374151"
            stroke-width="2"
          />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  data: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  colors: { type: Array, default: () => [] },
  accentColor: { type: String, default: '#c8e6a0' },
  defaultColor: { type: String, default: '#cde4ff' },
  accentThreshold: { type: Number, default: 0.7 },
})

const svgWidth = 800
const svgHeight = 320
const marginLeft = 60
const marginRight = 20
const marginTop = 20
const marginBottom = 60

const chartData = computed(() => props.data)

const maxValue = computed(() => {
  return Math.max(...chartData.value.map(d => d.y), 1)
})

const yTicks = computed(() => {
  const max = maxValue.value
  const step = Math.ceil(max / 5)
  const ticks = []
  for (let i = 0; i <= 5; i++) {
    const value = step * i
    if (value > max) break
    ticks.push({
      value,
      label: formatAxisValue(value),
      y: svgHeight - marginBottom - ((value / max) * (svgHeight - marginTop - marginBottom)),
    })
  }
  return ticks
})

const chartWidth = svgWidth - marginLeft - marginRight
const barWidth = Math.min(80, (chartWidth / chartData.value.length) * 0.7)
const barGap = chartWidth / chartData.value.length

function getBarX(index) {
  return marginLeft + index * barGap + (barGap - barWidth) / 2
}

function getBarY(value) {
  const chartHeight = svgHeight - marginTop - marginBottom
  return marginTop + chartHeight - (value / maxValue.value) * chartHeight
}

function getBarHeight(value) {
  const chartHeight = svgHeight - marginTop - marginBottom
  return (value / maxValue.value) * chartHeight
}

function getBarColor(index) {
  if (props.colors.length > 0) {
    return props.colors[index % props.colors.length]
  }
  const ratio = chartData.value[index]?.y / maxValue.value
  return ratio >= props.accentThreshold ? props.accentColor : props.defaultColor
}

function formatAxisValue(y) {
  if (!Number.isFinite(y)) return '0'
  return String(Math.trunc(y))
}

function formatValue(y) {
  if (!Number.isFinite(y)) return '0'
  return String(Math.trunc(y))
}

function getPercent(value) {
  if (!props.total) return ''
  return Math.round((value / props.total) * 100) + '%'
}
</script>
