import test from 'node:test'
import assert from 'node:assert/strict'
import { adminActivityStatusOptions, buildAdminActivityUpdateFormData, getAdminActivityStatusChip } from '../app/utils/adminActivityStatus.js'

test('exposes the admin activity status dropdown options', () => {
  assert.deepEqual(adminActivityStatusOptions, [
    { label: 'Черновик', value: 'draft', chip: { color: 'warning' } },
    { label: 'Запланировано', value: 'scheduled', chip: { color: 'info' } },
    { label: 'Начато', value: 'started', chip: { color: 'primary' } },
    { label: 'Завершено', value: 'completed', chip: { color: 'success' } },
    { label: 'Отменено', value: 'canceled', chip: { color: 'error' } },
  ])
})

test('maps status to chip color', () => {
  assert.deepEqual(getAdminActivityStatusChip('completed'), { color: 'success' })
})

test('builds an update payload with an overridden status', () => {
  const formData = buildAdminActivityUpdateFormData({
    title: 'Конкурс',
    description: 'Описание',
    date_start: '2025-11-05',
    date_end: '2025-11-06',
    status: 'scheduled',
    planet_id: 7,
    academy_id: 0,
    user_id: 42,
    category_id: 3,
    is_dynamic_score: false,
    score: 26,
  }, { status: 'completed' })

  assert.deepEqual(Object.fromEntries(formData.entries()), {
    title: 'Конкурс',
    description: 'Описание',
    date_start: '2025-11-05',
    date_end: '2025-11-06',
    status: 'completed',
    planet_id: '7',
    user_id: '42',
    category_id: '3',
    is_dynamic_score: '0',
    score: '26',
  })
})

test('parses dynamic score parameters before building the update payload', () => {
  const formData = buildAdminActivityUpdateFormData({
    title: 'Проект',
    description: 'Описание',
    date_start: '2025-11-05',
    date_end: '2025-11-06',
    status: 'scheduled',
    planet_id: 7,
    user_id: 42,
    category_id: 1,
    is_dynamic_score: true,
    parameters: JSON.stringify({
      activity_type: 'external',
      has_places: true,
      has_implementation: false,
      is_leader: false,
      target_education_level_ids: [1, '2'],
    }),
  })

  assert.deepEqual(Object.fromEntries(formData.entries()), {
    title: 'Проект',
    description: 'Описание',
    date_start: '2025-11-05',
    date_end: '2025-11-06',
    status: 'scheduled',
    planet_id: '7',
    user_id: '42',
    category_id: '1',
    is_dynamic_score: '1',
    'parameters[activity_type]': 'external',
    'parameters[has_implementation]': '0',
    'parameters[has_places]': '1',
    'parameters[is_leader]': '0',
    'parameters[target_education_level_ids][]': '2',
  })

  assert.deepEqual(formData.getAll('parameters[target_education_level_ids][]'), ['1', '2'])
})
