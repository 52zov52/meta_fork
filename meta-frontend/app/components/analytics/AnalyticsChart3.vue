<template>
  <div class="overflow-hidden">
    <div class="p-3 md:p-6">

      <div v-if="loading" class="flex justify-center py-8">
        <UIcon name="i-heroicons-arrow-path" class="animate-spin w-5 h-5 text-primary" />
      </div>
      <div v-else-if="chartData.length" class="relative w-full">

        <template v-if="isMdUp">
          <svg :viewBox="`0 0 ${svgWidth} ${svgHeight}`" class="w-full" :style="{ height: svgHeight + 'px' }">
            <g v-for="tick in yTicks" :key="tick.value">
              <line :x1="marginLeft" :y1="tick.y" :x2="svgWidth - marginRight" :y2="tick.y" stroke="#e5e7eb" stroke-width="1" />
              <text :x="marginLeft - 8" :y="tick.y + 4" text-anchor="end" fill="#6b7280" :font-size="labelFontSize">{{ tick.label }}</text>
            </g>
            <g v-for="(item, i) in chartData" :key="i">
              <rect :x="getBarX(i)" :y="getBarY(item.y)" :width="barWidth" :height="getBarHeight(item.y)" :fill="colors[i % colors.length]" />
              <text v-if="item.y > 0" :x="getBarX(i) + barWidth / 2" :y="getBarHeight(item.y) >= 45 ? getBarY(item.y) + getBarHeight(item.y) / 2 - 6 : getBarY(item.y) - 20"
                text-anchor="middle" :fill="getBarHeight(item.y) >= 45 ? (i <= 1 ? '#ffffff' : '#1e293b') : '#1e293b'" :font-size="valueFontSize" font-weight="700">
                {{ item.y }}
              </text>
              <text :x="getBarX(i) + barWidth / 2" :y="getBarHeight(item.y) >= 45 ? getBarY(item.y) + getBarHeight(item.y) / 2 + 12 : getBarY(item.y) - 5"
                text-anchor="middle" :fill="getBarHeight(item.y) >= 45 ? (i <= 1 ? '#ffffff' : '#1e293b') : '#6b7280'" :font-size="percentFontSize" font-weight="600">
                {{ getPercent(item.y) }}
              </text>
            </g>
            <g v-for="(item, i) in chartData" :key="`x-${i}`">
              <text :x="getBarX(i) + barWidth / 2" :y="svgHeight - marginBottom + 15" text-anchor="middle" fill="#374151" :font-size="labelFontSize" font-weight="600">{{ item.x }}</text>
            </g>
            <line :x1="marginLeft" :y1="svgHeight - marginBottom" :x2="svgWidth - marginRight" :y2="svgHeight - marginBottom" stroke="#374151" stroke-width="2" />
          </svg>
        </template>

        <template v-else>
          <svg :viewBox="`0 0 480 ${Math.max(200, chartData.length * 68 + 60)}`" class="w-full h-auto max-w-full" preserveAspectRatio="xMidYMid meet">
            <g v-for="tick in hXTicks" :key="tick.value">
              <line :x1="tick.x" :y1="hMarginTop" :x2="tick.x" :y2="svgH" stroke="#e5e7eb" stroke-width="1" />
              <text :x="tick.x" :y="hMarginTop - 6" text-anchor="middle" fill="#6b7280" font-size="12">{{ tick.label }}</text>
            </g>
            <g v-for="(item, i) in chartData" :key="i">
              <rect :x="hMarginLeft" :y="getHBarY(i)" :width="getHBarWidth(item.y)" :height="hBarHeight" :fill="colors[i % colors.length]" rx="2" />
              <template v-if="item.y > 0">
                <text :x="hMarginLeft + getHBarWidth(item.y) - 6" :y="getHBarY(i) + hBarHeight / 2 + 4"
                  text-anchor="end" :fill="getHBarWidth(item.y) >= 50 ? (i <= 1 ? '#ffffff' : '#1e293b') : '#1e293b'" font-size="11" font-weight="700">
                  {{ item.y }} ({{ getPercent(item.y) }})
                </text>
              </template>
              <text v-else :x="hMarginLeft" :y="getHBarY(i) + hBarHeight / 2 + 4"
                text-anchor="start" fill="#9ca3af" font-size="10" font-weight="600">0%</text>
            </g>
            <g v-for="(item, i) in chartData" :key="`hl-${i}`">
              <text :x="hMarginLeft - 6" :y="getHBarY(i) + hBarHeight / 2 + 5" text-anchor="end" fill="#374151" font-size="11" font-weight="600">{{ item.x }}</text>
            </g>
            <line :x1="hMarginLeft" :y1="hMarginTop" :x2="hMarginLeft" :y2="svgH" stroke="#374151" stroke-width="1" />
          </svg>
        </template>

      </div>
    </div>
  </div>
</template>

<script setup>
import { useMediaQuery } from '@vueuse/core'

const props = defineProps({
  data: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  colors: { type: Array, default: () => ['#7c3aed', '#1e40af', '#ea580c', '#eab308'] },
})

const isMdUp = useMediaQuery('(min-width: 768px)')

const svgWidth = computed(() => isMdUp.value ? 800 : 480)
const svgHeight = computed(() => isMdUp.value ? 320 : 260)
const marginLeft = computed(() => isMdUp.value ? 60 : 44)
const marginRight = 20
const marginTop = 20
const marginBottom = computed(() => isMdUp.value ? 60 : 50)

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
      label: value.toString(),
      y: svgHeight.value - marginBottom.value - ((value / max) * (svgHeight.value - marginTop - marginBottom.value)),
    })
  }
  return ticks
})

const chartWidth = computed(() => svgWidth.value - marginLeft.value - marginRight)
const barWidth = computed(() => Math.min(72, (chartWidth.value / chartData.value.length) * 0.65))
const barGap = computed(() => chartWidth.value / chartData.value.length)

const labelFontSize = computed(() => isMdUp.value ? 11 : 9)
const valueFontSize = computed(() => isMdUp.value ? 16 : 12)
const percentFontSize = computed(() => isMdUp.value ? 13 : 10)

function getBarX(index) {
  return marginLeft.value + index * barGap.value + (barGap.value - barWidth.value) / 2
}

function getBarY(value) {
  const chartHeight = svgHeight.value - marginTop - marginBottom.value
  return marginTop + chartHeight - (value / maxValue.value) * chartHeight
}

function getBarHeight(value) {
  const chartHeight = svgHeight.value - marginTop - marginBottom.value
  return (value / maxValue.value) * chartHeight
}

function getPercent(value) {
  if (!props.total) return '0%'
  return Math.round((value / props.total) * 100) + '%'
}

const hSvgWidth = 480
const hMarginLeft = 130
const hMarginTop = 22
const hMarginBottom = 10

const svgH = computed(() => Math.max(200, chartData.value.length * 68 + 60) - hMarginBottom)

const hMaxValue = computed(() => maxValue.value)

const hChartWidth = computed(() => hSvgWidth - hMarginLeft - 10)

const hBarHeight = computed(() => Math.min(40, (svgH.value - hMarginTop) / chartData.value.length * 0.7))
const hBarGap = computed(() => (svgH.value - hMarginTop) / chartData.value.length)

const hXTicks = computed(() => {
  const max = hMaxValue.value
  const step = Math.ceil(max / 4)
  const ticks = []
  for (let i = 0; i <= 4; i++) {
    const value = step * i
    if (value > max) break
    ticks.push({
      value,
      label: value.toString(),
      x: hMarginLeft + (value / max) * hChartWidth.value,
    })
  }
  return ticks
})

function getHBarY(index) {
  return hMarginTop + index * hBarGap.value + (hBarGap.value - hBarHeight.value) / 2
}

function getHBarWidth(value) {
  return Math.max(0, (value / hMaxValue.value) * hChartWidth.value)
}
</script>
