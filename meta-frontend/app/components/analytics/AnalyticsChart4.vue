<template>
  <div class="overflow-hidden">
    <div class="p-6">
    
      <div v-if="loading" class="flex justify-center py-8">
        <UIcon name="i-heroicons-arrow-path" class="animate-spin w-5 h-5 text-primary" />
      </div>
      <div v-else-if="chartData.length" ref="chartRef" class="relative w-full">
        <!-- Legend -->
        <div class="flex justify-center gap-4 mb-4">
          <div v-for="(legend, i) in legends" :key="i" class="flex items-center gap-2">
            <div class="w-4 h-4" :style="{ backgroundColor: colors[i] }" />
            <span class="text-sm text-slate-600">{{ legend }} ({{ legendTotals[i] }})</span>
          </div>
        </div>

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
            <g v-for="(val, si) in item.values" :key="si">
              <rect
                :x="getBarX(i, si)"
                :y="getBarY(val)"
                :width="barWidth"
                :height="getBarHeight(val)"
                :fill="colors[si]"
              />
              <text
                :x="getBarX(i, si) + barWidth / 2"
                :y="getBarHeight(val) >= 30 
                  ? getBarY(val) + getBarHeight(val) / 2 - 5 
                  : getBarY(val) - 15"
                text-anchor="middle"
                :fill="getBarHeight(val) >= 30 ? getSegmentTextColor(si) : '#1e293b'"
                font-size="11"
                font-weight="700"
              >
                {{ val }}
              </text>
              <text
                :x="getBarX(i, si) + barWidth / 2"
                :y="getBarHeight(val) >= 30 
                  ? getBarY(val) + getBarHeight(val) / 2 + 8 
                  : getBarY(val) - 4"
                text-anchor="middle"
                :fill="getBarHeight(val) >= 30 ? getSegmentTextColor(si) : '#6b7280'"
                font-size="9"
                font-weight="600"
              >
                {{ getPercent(val, si) }}
              </text>
            </g>
          </g>

          <!-- X axis labels -->
          <g v-for="(item, i) in chartData" :key="`x-${i}`">
            <text
              :x="getGroupCenterX(i)"
              :y="svgHeight - marginBottom + 15"
              text-anchor="middle"
              fill="#374151"
              font-size="10"
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
  colors: { type: Array, default: () => ['#7c3aed', '#1e40af', '#ea580c', '#eab308'] },
  legends: { type: Array, default: () => [] },
  legendTotals: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const svgHeight = 380
const marginLeft = 60
const marginRight = 20
const marginTop = 20
const marginBottom = 60
const seriesCount = props.colors.length

const chartRef = ref(null)
const containerWidth = ref(800)

const chartData = computed(() => props.data)

const svgWidth = computed(() => {
  return Math.max(containerWidth.value, 400)
})

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
      y: svgHeight - marginBottom - ((value / max) * (svgHeight - marginTop - marginBottom)),
    })
  }
  return ticks
})

const chartWidth = computed(() => svgWidth.value - marginLeft - marginRight)
const barWidth = computed(() => {
  const count = chartData.value.length || 1
  return Math.min(60, (chartWidth.value / count) * 0.6 / seriesCount)
})
const barGap = computed(() => chartWidth.value / (chartData.value.length || 1))

function getBarX(groupIndex, seriesIndex) {
  const groupStart = marginLeft + groupIndex * barGap.value
  const totalBarsWidth = barWidth.value * seriesCount + (seriesCount - 1) * 4
  const startOffset = (barGap.value - totalBarsWidth) / 2
  return groupStart + startOffset + seriesIndex * (barWidth.value + 4)
}

function getGroupCenterX(groupIndex) {
  return marginLeft + groupIndex * barGap.value + barGap.value / 2
}

function getBarY(value) {
  const chartHeight = svgHeight - marginTop - marginBottom
  return marginTop + chartHeight - (value / maxValue.value) * chartHeight
}

function getBarHeight(value) {
  const chartHeight = svgHeight - marginTop - marginBottom
  return (value / maxValue.value) * chartHeight
}

function getPercent(value, seriesIndex) {
  const total = props.legendTotals[seriesIndex]
  if (!total) return '0%'
  return Math.round((value / total) * 100) + '%'
}

function getSegmentTextColor(segmentIndex) {
  if (segmentIndex <= 1) return '#ffffff'
  return '#1e293b'
}

onMounted(() => {
  if (chartRef.value) {
    containerWidth.value = chartRef.value.clientWidth
    const observer = new ResizeObserver((entries) => {
      for (const entry of entries) {
        containerWidth.value = entry.contentRect.width
      }
    })
    observer.observe(chartRef.value)
    onUnmounted(() => observer.disconnect())
  }
})
</script>
