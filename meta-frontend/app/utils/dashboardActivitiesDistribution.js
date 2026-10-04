function parseAcademicYearStart(year) {
    const value = String(year ?? '').trim()

    if (/^\d{4}$/.test(value)) {
        return Number(value)
    }

    const match = value.match(/^(\d{4})\s*(?:\/|-|–|—)/)
    return match ? Number(match[1]) : null
}

export function formatAcademicYearLabel(year) {
    const value = String(year ?? '').trim()

    if (!value) {
        return ''
    }

    if (/^\d{4}$/.test(value)) {
        const startYear = Number(value)
        return `${startYear}/${startYear + 1}`
    }

    return value
}

export function getAvailableAcademicYears(items) {
    const yearsMap = new Map()

    for (const item of items ?? []) {
        const value = String(item?.year ?? item?.label ?? item?.name ?? '').trim()

        if (!value) {
            continue
        }

        const existing = yearsMap.get(value)
        const count = Number(item?.count ?? item?.value ?? 0)

        yearsMap.set(value, {
            value,
            label: formatAcademicYearLabel(value),
            count: (existing?.count ?? 0) + count,
            sortValue: parseAcademicYearStart(value),
        })
    }

    return Array.from(yearsMap.values()).sort((a, b) => {
        if (a.sortValue !== null && b.sortValue !== null) {
            return b.sortValue - a.sortValue
        }

        if (a.sortValue !== null) {
            return -1
        }

        if (b.sortValue !== null) {
            return 1
        }

        return b.label.localeCompare(a.label, 'ru')
    })
}

export function syncSelectedAcademicYears(years, currentYearA, currentYearB) {
    const values = years.map(year => year.value)

    if (!values.length) {
        return {
            selectedYearA: null,
            selectedYearB: null,
        }
    }

    let selectedYearA = values.includes(currentYearA) ? currentYearA : values[0]
    let selectedYearB = values.includes(currentYearB) && currentYearB !== selectedYearA ? currentYearB : null

    if (!selectedYearB) {
        selectedYearB = values.find(value => value !== selectedYearA) ?? null
    }

    if (!selectedYearA && selectedYearB) {
        selectedYearA = values.find(value => value !== selectedYearB) ?? values[0]
    }

    return {
        selectedYearA,
        selectedYearB,
    }
}

export function buildActivitiesDistributionForYears(years, selectedYearA, selectedYearB) {
    const selectedValues = new Set([selectedYearA, selectedYearB].filter(Boolean))

    if (!selectedValues.size) {
        return []
    }

    return years
        .filter(year => selectedValues.has(year.value))
        .map(year => ({
            label: year.label,
            value: year.count,
        }))
}
