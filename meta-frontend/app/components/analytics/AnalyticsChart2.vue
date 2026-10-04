<template>
  <div class="overflow-hidden">
    <div class="p-3 md:p-6">

      <div v-if="loading" class="flex justify-center py-8">
        <UIcon name="i-heroicons-arrow-path" class="animate-spin w-5 h-5 text-primary" />
      </div>
      <div v-else-if="chartData.length" class="relative w-full">

        <template v-if="isMdUp">
          <div class="flex justify-center gap-4 mb-4">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-[#6366f1]" />
              <span class="text-sm text-slate-600">С баллами или сертификатами</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-[#f43f5e]" />
              <span class="text-sm text-slate-600">Общее</span>
            </div>
          </div>

          <svg :viewBox="`0 0 ${svgWidth} ${svgHeight}`" class="w-full" :style="{ height: svgHeight + 'px' }">
            <g v-for="tick in yTicks" :key="tick.value">
              <line :x1="marginLeft" :y1="tick.y" :x2="svgWidth - marginRight" :y2="tick.y" stroke="#e5e7eb" stroke-width="1" />
              <text :x="marginLeft - 8" :y="tick.y + 4" text-anchor="end" fill="#6b7280" :font-size="labelFontSize">{{ tick.label }}</text>
            </g>
            <g v-for="(item, i) in chartData" :key="i">
              <rect :x="getBarX(i, 0)" :y="getBarY(item.values[0])" :width="barWidth" :height="getBarHeight(item.values[0])" fill="#6366f1" />
              <text v-if="item.values[0] > 0" :x="getBarX(i, 0) + barWidth / 2" :y="getBarHeight(item.values[0]) >= 40 ? getBarY(item.values[0]) + getBarHeight(item.values[0]) / 2 - 6 : getBarY(item.values[0]) - 18"
                text-anchor="middle" :fill="getBarHeight(item.values[0]) >= 40 ? 'white' : '#1e293b'" :font-size="valueFontSize" font-weight="700">
                {{ item.values[0] }}
              </text>
              <text :x="getBarX(i, 0) + barWidth / 2" :y="getBarHeight(item.values[0]) >= 40 ? getBarY(item.values[0]) + getBarHeight(item.values[0]) / 2 + 10 : getBarY(item.values[0]) - 4"
                text-anchor="middle" :fill="getBarHeight(item.values[0]) >= 40 ? 'white' : '#6b7280'" :font-size="percentFontSize" font-weight="600">
                {{ getPercent(item.values[0], item.values[1]) }}
              </text>
              <rect :x="getBarX(i, 1)" :y="getBarY(item.values[1])" :width="barWidth" :height="getBarHeight(item.values[1])" fill="#f43f5e" />
              <text v-if="item.values[1] > 0" :x="getBarX(i, 1) + barWidth / 2" :y="getBarHeight(item.values[1]) >= 25 ? getBarY(item.values[1]) + getBarHeight(item.values[1]) / 2 + 5 : getBarY(item.values[1]) - 5"
                text-anchor="middle" :fill="getBarHeight(item.values[1]) >= 25 ? 'white' : '#1e293b'" :font-size="valueFontSize" font-weight="700">
                {{ item.values[1] }}
              </text>
            </g>
            <g v-for="(item, i) in chartData" :key="`x-${i}`">
              <text :x="marginLeft + i * barGap + barGap / 2" :y="svgHeight - marginBottom + 15"
                text-anchor="middle" fill="#374151" :font-size="labelFontSize" font-weight="600">{{ item.x }}</text>
            </g>
            <line :x1="marginLeft" :y1="svgHeight - marginBottom" :x2="svgWidth - marginRight" :y2="svgHeight - marginBottom" stroke="#374151" stroke-width="2" />
          </svg>
        </template>

        <template v-else>
          <div class="flex justify-center gap-3 mb-3">
            <div class="flex items-center gap-1">
              <div class="w-3 h-3 bg-[#6366f1]" />
              <span class="text-[10px] text-slate-600">Активные</span>
            </div>
            <div class="flex items-center gap-1">
              <div class="w-3 h-3 bg-[#f43f5e]" />
              <span class="text-[10px] text-slate-600">Всего</span>
            </div>
          </div>

          <svg :viewBox="`0 0 480 ${Math.max(200, chartData.length * 100 + 60)}`" class="w-full h-auto max-w-full" preserveAspectRatio="xMidYMid meet">
            <g v-for="tick in hXTicks" :key="tick.value">
              <line :x1="tick.x" :y1="hMarginTop" :x2="tick.x" :y2="svgH" stroke="#e5e7eb" stroke-width="1" />
              <text :x="tick.x" :y="hMarginTop - 6" text-anchor="middle" fill="#6b7280" font-size="12">{{ tick.label }}</text>
            </g>
            <g v-for="(item, i) in chartData" :key="i">
              <rect :x="hMarginLeft" :y="getHBarY(i, 0)" :width="getHBarWidth(item.values[0])" :height="hBarHeight" fill="#6366f1" rx="2" />
              <rect :x="hMarginLeft" :y="getHBarY(i, 1)" :width="getHBarWidth(item.values[1])" :height="hBarHeight" fill="#f43f5e" rx="2" />
              <template v-if="item.values[0] > 0">
                <text :x="hMarginLeft + getHBarWidth(item.values[0]) - 6" :y="getHBarY(i, 0) + hBarHeight / 2 + 5"
                  text-anchor="end" :fill="getHBarWidth(item.values[0]) >= 50 ? 'white' : '#6366f1'" font-size="12" font-weight="700">
                  {{ item.values[0] }} ({{ getPercent(item.values[0], item.values[1]) }})
                </text>
              </template>
              <text v-else :x="hMarginLeft" :y="getHBarY(i, 0) + hBarHeight / 2 + 5"
                text-anchor="start" fill="#9ca3af" font-size="11" font-weight="600">0%</text>
              <text v-if="item.values[1] > 0" :x="hMarginLeft + getHBarWidth(item.values[1]) - 4" :y="getHBarY(i, 1) + hBarHeight / 2 + 5"
                text-anchor="end" :fill="getHBarWidth(item.values[1]) >= 50 ? 'white' : '#f43f5e'" font-size="12" font-weight="700">
                {{ item.values[1] }}
              </text>
              <text v-else :x="hMarginLeft" :y="getHBarY(i, 1) + hBarHeight / 2 + 5"
                text-anchor="start" fill="#9ca3af" font-size="11" font-weight="600">0</text>
            </g>
            <g v-for="(item, i) in chartData" :key="`hl-${i}`">
              <text :x="hMarginLeft - 6" :y="getHBarY(i, 0) + hBarHeight * 2 + 8" text-anchor="end" fill="#374151" font-size="11" font-weight="600">{{ item.x }}</text>
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
  loading: { type: Boolean, default: false },
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
  let max = 0
  for (const item of chartData.value) {
    max = Math.max(max, ...item.values)
  }
  return Math.max(max, 1)
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
const barWidth = computed(() => Math.min(36, (chartWidth.value / chartData.value.length) * 0.35))
const barGap = computed(() => chartWidth.value / chartData.value.length)

const labelFontSize = computed(() => isMdUp.value ? 12 : 9)
const valueFontSize = computed(() => isMdUp.value ? 13 : 11)
const percentFontSize = computed(() => isMdUp.value ? 11 : 9)

function getBarX(groupIndex, seriesIndex) {
  const groupStart = marginLeft.value + groupIndex * barGap.value
  const totalBarsWidth = barWidth.value * 2 + 6
  const startOffset = (barGap.value - totalBarsWidth) / 2
  return groupStart + startOffset + seriesIndex * (barWidth.value + 6)
}

function getBarY(value) {
  const chartHeight = svgHeight.value - marginTop - marginBottom.value
  return marginTop + chartHeight - (value / maxValue.value) * chartHeight
}

function getBarHeight(value) {
  const chartHeight = svgHeight.value - marginTop - marginBottom.value
  return (value / maxValue.value) * chartHeight
}

function getPercent(active, total) {
  if (!total) return '0%'
  return Math.round((active / total) * 100) + '%'
}

const hSvgWidth = 480
const hMarginLeft = 90
const hMarginTop = 22
const hMarginBottom = 10

const svgH = computed(() => Math.max(200, chartData.value.length * 100 + 60) - hMarginBottom)

const hMaxValue = computed(() => maxValue.value)

const hChartWidth = computed(() => hSvgWidth - hMarginLeft - 10)

const hBarHeight = computed(() => Math.min(40, (svgH.value - hMarginTop) / chartData.value.length * 0.7))
const hBarGap = computed(() => (svgH.value - hMarginTop) / chartData.value.length / 2)

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

function getHBarY(index, seriesIndex) {
  return hMarginTop + index * (hBarHeight.value * 2 + 8) + seriesIndex * hBarHeight.value
}

function getHBarWidth(value) {
  return Math.max(0, (value / hMaxValue.value) * hChartWidth.value)
}
</script>
