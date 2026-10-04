<template>
    <div class="space-y-1">
        <div class="flex gap-2">
            <UInput
                :model-value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                inputmode="numeric"
                maxlength="10"
                class="w-full"
                @keydown="onKeydown"
                @beforeinput="onBeforeInput"
                @paste.prevent="onPaste"
                @update:model-value="onTextInput"
            />
            <div ref="anchorRef" class="shrink-0">
                <UButton
                    type="button"
                    color="neutral"
                    variant="outline"
                    icon="i-heroicons-calendar-days"
                    :disabled="disabled"
                    aria-label="Выбрать дату"
                    aria-haspopup="dialog"
                    :aria-expanded="isOpen"
                    @click="toggle"
                />
            </div>
        </div>
        <p v-if="errorMessage" class="text-xs text-error">
            {{ errorMessage }}
        </p>

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="panelRef"
                class="fixed z-50 w-68 rounded-md bg-default p-3 shadow-lg ring ring-default"
                :style="panelStyle"
            >
                <div class="mb-2 flex items-center justify-between gap-1">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        icon="i-heroicons-chevron-double-left"
                        aria-label="Предыдущий год"
                        @click="shiftView(-12)"
                    />
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        icon="i-heroicons-chevron-left"
                        aria-label="Предыдущий месяц"
                        @click="shiftView(-1)"
                    />
                    <span class="text-sm font-medium text-default">{{ viewMonthLabel }}</span>
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        icon="i-heroicons-chevron-right"
                        aria-label="Следующий месяц"
                        @click="shiftView(1)"
                    />
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        icon="i-heroicons-chevron-double-right"
                        aria-label="Следующий год"
                        @click="shiftView(12)"
                    />
                </div>

                <div class="grid grid-cols-7 gap-0.5">
                    <span
                        v-for="label in weekdayLabels"
                        :key="label"
                        class="flex h-8 items-center justify-center text-xs text-muted"
                    >
                        {{ label }}
                    </span>

                    <button
                        v-for="day in calendarDays"
                        :key="day.key"
                        type="button"
                        class="h-8 w-full rounded-md text-sm transition-colors"
                        :class="dayClass(day)"
                        :disabled="day.isOutOfRange"
                        :aria-label="day.date"
                        :aria-current="day.isToday ? 'date' : undefined"
                        @click="selectDay(day)"
                    >
                        {{ day.day }}
                    </button>
                </div>

                <div class="mt-2 flex justify-end border-t border-default pt-2">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        label="Сегодня"
                        @click="selectToday()"
                    />
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { onClickOutside, useEventListener } from '@vueuse/core'
import { formatRuDateInput, isAllowedRuDateKey, isValidRuDate, ruDateFromParts, ruDateToParts } from '~/utils/ruDate'

const MIN_YEAR = 1900
const MAX_YEAR = 2100
const MS_IN_DAY = 86400000
const GAP = 6
const EDGE = 8
const weekdayLabels = ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс']

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'дд.мм.гггг',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const now = new Date()
const today = { day: now.getDate(), month: now.getMonth(), year: now.getFullYear() }
const viewYear = ref(today.year)
const viewMonth = ref(today.month)

const anchorRef = ref(null)
const panelRef = ref(null)
const panelSize = ref({ width: 0, height: 0 })
const panelBox = ref({ top: 0, left: 0, placed: false })

const panelStyle = computed(() => ({
    top: `${panelBox.value.top}px`,
    left: `${panelBox.value.left}px`,
    visibility: panelBox.value.placed ? 'visible' : 'hidden',
}))

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), Math.max(min, max))
}

function place() {
    const anchor = anchorRef.value

    if (!anchor) return

    const { top, right, bottom } = anchor.getBoundingClientRect()
    const { width, height } = panelSize.value
    const left = clamp(right - width, EDGE, window.innerWidth - width - EDGE)
    const below = bottom + GAP
    const above = top - GAP
    const flipUp = below + height > window.innerHeight - EDGE && below > above

    panelBox.value = {
        top: flipUp ? Math.max(EDGE, above - height) : below,
        left,
        placed: true,
    }
}

function open() {
    isOpen.value = true
    panelBox.value = { ...panelBox.value, placed: false }

    nextTick(() => {
        panelSize.value = {
            width: panelRef.value?.offsetWidth ?? 0,
            height: panelRef.value?.offsetHeight ?? 0,
        }
        place()
    })
}

function close() {
    if (!isOpen.value) return

    isOpen.value = false
    panelBox.value = { ...panelBox.value, placed: false }
}

function toggle() {
    if (isOpen.value) {
        close()
    } else {
        open()
    }
}

onClickOutside(panelRef, close, { ignore: [anchorRef] })

useEventListener('keydown', (event) => {
    if (event.key === 'Escape') close()
})

useEventListener('scroll', () => {
    if (isOpen.value) place()
}, { capture: true, passive: true })

useEventListener('resize', () => {
    if (isOpen.value) place()
})

const errorMessage = computed(() => {
    if (!props.modelValue || props.modelValue.length < 10) return ''

    return isValidRuDate(props.modelValue)
        ? ''
        : 'Введите корректную дату в формате дд.мм.гггг'
})

const viewMonthLabel = computed(() => {
    const label = new Date(Date.UTC(viewYear.value, viewMonth.value, 1))
        .toLocaleDateString('ru-RU', { month: 'long' })

    return `${label.charAt(0).toUpperCase()}${label.slice(1)} ${viewYear.value}`
})

const selectedDate = computed(() => ruDateToParts(props.modelValue))

const calendarDays = computed(() => {
    const leadingDays = (new Date(Date.UTC(viewYear.value, viewMonth.value, 1)).getUTCDay() + 6) % 7
    const gridStart = Date.UTC(viewYear.value, viewMonth.value, 1 - leadingDays)

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(gridStart + index * MS_IN_DAY)
        const year = date.getUTCFullYear()
        const month = date.getUTCMonth()
        const day = date.getUTCDate()

        return {
            key: `${year}-${month + 1}-${day}`,
            date: ruDateFromParts(day, month, year),
            day,
            inMonth: month === viewMonth.value,
            isOutOfRange: year < MIN_YEAR || year > MAX_YEAR,
            isSelected: selectedDate.value?.day === day
                && selectedDate.value?.month === month
                && selectedDate.value?.year === year,
            isToday: today.day === day && today.month === month && today.year === year,
        }
    })
})

function dayClass(day) {
    if (day.isSelected) return 'bg-primary text-inverted hover:bg-primary'
    if (day.isOutOfRange) return 'text-muted opacity-40'
    if (day.isToday) return 'font-semibold text-primary ring-1 ring-inset ring-primary hover:bg-accented'
    if (!day.inMonth) return 'text-muted opacity-60 hover:bg-accented'

    return 'text-default hover:bg-accented'
}

function onTextInput(value) {
    emit('update:modelValue', formatRuDateInput(value))
}

function onKeydown(event) {
    if (!isAllowedRuDateKey(event)) {
        event.preventDefault()
    }
}

function onBeforeInput(event) {
    if (event.data && /\D/.test(event.data)) {
        event.preventDefault()
    }
}

function onPaste(event) {
    const text = event.clipboardData?.getData('text') || ''
    emit('update:modelValue', formatRuDateInput(text))
}

function shiftView(months) {
    const shifted = new Date(Date.UTC(viewYear.value, viewMonth.value + months, 1))

    viewYear.value = shifted.getUTCFullYear()
    viewMonth.value = shifted.getUTCMonth()
}

function selectDay(day) {
    if (day.isOutOfRange) return

    emit('update:modelValue', day.date)
    close()
}

function selectToday() {
    emit('update:modelValue', ruDateFromParts(today.day, today.month, today.year))
    close()
}

watch(isOpen, (open) => {
    if (!open) return

    const target = selectedDate.value || today

    viewYear.value = target.year
    viewMonth.value = target.month
})
</script>
