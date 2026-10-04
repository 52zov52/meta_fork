export const adminActivityStatusOptions = [
  { label: 'Черновик', value: 'draft', chip: { color: 'warning' } },
  { label: 'Запланировано', value: 'scheduled', chip: { color: 'info' } },
  { label: 'Начато', value: 'started', chip: { color: 'primary' } },
  { label: 'Завершено', value: 'completed', chip: { color: 'success' } },
  { label: 'Отменено', value: 'canceled', chip: { color: 'error' } },
]

export function getAdminActivityStatusChip(value) {
  return adminActivityStatusOptions.find(item => item.value === value)?.chip
}

export function buildAdminActivityUpdateFormData(activity, overrides = {}) {
  if (!activity) throw new Error('Activity data is required')

  const status = overrides.status ?? activity.status ?? ''
  const formData = new FormData()

  formData.append('title', activity.title ?? '')
  formData.append('description', activity.description ?? '')
  formData.append('date_start', activity.date_start ?? '')
  formData.append('date_end', activity.date_end ?? '')
  formData.append('status', status)
  formData.append('planet_id', String(activity.planet_id ?? 0))

  if (Number(activity.academy_id ?? 0) > 0) {
    formData.append('academy_id', String(activity.academy_id))
  }

  formData.append('user_id', String(activity.user_id ?? 0))
  formData.append('category_id', String(activity.category_id ?? 0))
  formData.append('is_dynamic_score', activity.is_dynamic_score ? '1' : '0')

  if (activity.is_dynamic_score) {
    const parameters = normalizeParameters(activity.parameters)

    if (parameters.activity_type) {
      formData.append('parameters[activity_type]', parameters.activity_type)
    }

    if ('has_implementation' in parameters) {
      formData.append('parameters[has_implementation]', parameters.has_implementation ? '1' : '0')
    }

    if ('has_places' in parameters) {
      formData.append('parameters[has_places]', parameters.has_places ? '1' : '0')
    }

    if ('is_leader' in parameters) {
      formData.append('parameters[is_leader]', parameters.is_leader ? '1' : '0')
    }

    for (const id of ensureNumberIds(parameters.target_education_level_ids)) {
      formData.append('parameters[target_education_level_ids][]', String(id))
    }
  } else if (activity.score !== undefined && activity.score !== null) {
    formData.append('score', String(activity.score))
  }

  return formData
}

function ensureNumberIds(value) {
  if (!Array.isArray(value)) return []
  return value.map(Number).filter(Number.isFinite)
}

function normalizeParameters(value) {
  if (!value) return {}
  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value)
      return parsed && typeof parsed === 'object' ? parsed : {}
    } catch {
      return {}
    }
  }
  return typeof value === 'object' ? value : {}
}
