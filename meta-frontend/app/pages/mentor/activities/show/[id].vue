<template>
    <div>
        <div class="bg-elevated rounded-lg shadow-sm border border-default p-6 mb-6">
            <div class="flex max-sm:flex-col gap-6">
                <div
                    :style="{ backgroundImage: activity.photo_path
                    ? `url('${config.public.API_PATH_STORAGE + activity.photo_path}')`
                    : `url('${staticImg.bgCard}')` }"
                    class="h-48 w-64 max-sm:w-full shrink-0 bg-cover bg-center rounded-lg"/>

                <div class="flex-1 space-y-4">
                    <div class="space-y-2">
                        <div class="flex max-sm:flex-wrap items-center gap-x-4 gap-y-2">
                            <h2 class="text-2xl font-bold text-default">{{ activity.title }}</h2>
                            <div>
                                <UBadge v-if="activity.status_eng === 'draft'" color="warning" variant="solid" size="lg">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'scheduled'" color="primary" variant="solid" size="lg">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'started'" color="primary" variant="solid" size="lg">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'completed'" color="primary" variant="solid" size="lg">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'canceled'" color="error" variant="solid" size="lg">{{ activity.status }}</UBadge>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-muted">
                            <div class="flex items-center space-x-2">
                                <UIcon name="i-heroicons-calendar-days" class="w-4 h-4 text-primary" />
                                <span>{{ activity.date_start }} - {{ activity.date_end }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <UIcon name="i-heroicons-star" class="w-4 h-4 text-yellow-500" />
                                <span v-if="activity.is_dynamic_score">Автоматический расчёт баллов</span>
                                <span v-else>{{ activity.score }} баллов за участие</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <UIcon name="i-heroicons-tag" class="w-4 h-4 text-success" />
                                <span>{{ activity.planet_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="activity.description" class="mt-4">
                        <h3 class="text-lg font-semibold text-default mb-2">Описание мероприятия</h3>
                        <div class="prose max-w-none text-default bg-muted rounded-lg p-4">
                            <p class="whitespace-pre-line">{{ activity.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-12 text-base/7 font-semibold text-default sm:text-sm/6">Список участников</h2>

        <div class="">
            <div v-if="listOfUsers.length != 0" class="flex flex-col gap-2">
                <table class="w-full text-sm">
                    <thead class="">
                        <tr class="">
                            <th class="w-16 border-b border-default px-4 py-2 font-semibold">
                                № п/п
                            </th>
                            <th class="w-auto border-b border-default px-4 py-2 text-left font-semibold">
                                <button class="flex items-center space-x-1 hover:text-primary transition-colors" @click="toggleSort('fio')">
                                    <span>ФИО</span>
                                    <UIcon v-if="getSortIcon('fio')" :name="getSortIcon('fio')" class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">
                                <button class="flex items-center justify-center space-x-1 hover:text-primary transition-colors" @click="toggleSort('level')">
                                    <span>{{ activity.is_dynamic_score ? 'Место' : 'Уровень участия' }}</span>
                                    <UIcon v-if="getSortIcon('level')" :name="getSortIcon('level')" class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">
                                <button class="flex items-center justify-center space-x-1 hover:text-primary transition-colors" @click="toggleSort('status')">
                                    <span>Статус</span>
                                    <UIcon v-if="getSortIcon('status')" :name="getSortIcon('status')" class="w-4 h-4" />
                                </button>
                            </th>
                            <th v-if="activity.is_dynamic_score" class="w-20 border-b border-default px-4 py-2 font-semibold">
                                Баллы
                            </th>
                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">
                                <button class="flex items-center justify-center space-x-1 hover:text-primary transition-colors" @click="toggleSort('portfolio')">
                                    <UIcon name="i-heroicons-document-text" class="w-4 h-4" />
                                    <span>Портфолио</span>
                                    <UIcon v-if="getSortIcon('portfolio')" :name="getSortIcon('portfolio')" class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-20 border-b border-default px-4 py-2 font-semibold">
                                Действия
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedListOfUsers" :key="item.id || index" class="hover:bg-muted">
                            <td class="w-16 border-b border-muted px-4 py-2 text-center">{{ index + 1 }}</td>
                            <td class="border-b border-muted px-4 py-2">
                                <UButton
                                    :to="`/student/profile/${item.user_id || item.id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    variant="ghost"
                                    color="primary"
                                    class="p-0 h-auto font-medium text-left justify-start text-primary"
                                >
                                    {{ item.fio }}
                                </UButton>
                            </td>
                            <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                <span v-if="activity.is_dynamic_score"
                                      class="px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                      :class="{
                                        'bg-primary/10 text-primary/80': item.place === '1st',
                                        'bg-green-100 text-green-800': item.place === '2nd',
                                        'bg-amber-100 text-amber-800': item.place === '3rd',
                                        'bg-gray-100 text-gray-700': item.place === 'participation',
                                        'text-gray-500': !item.place
                                      }">
                                    {{ getPlaceLabel(item.place) }}
                                </span>
                                <span v-else
                                      class="px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                      :class="{
                                        'bg-primary/10 text-primary/80': item.level == 1,
                                        'bg-success/10 text-success': item.level == 0.8,
                                        'bg-warning/10 text-warning': item.level == 0.6,
                                        'bg-muted text-default': item.level == 0.4,
                                        'text-muted': !item.level || ![1, 0.8, 0.6, 0.4].includes(Number(item.level))
                                      }">
                                    {{ getLevelLabel(item.level) }}
                                </span>
                            </td>
                            <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                <UIcon v-if="item.is_confirmed == 0" name="i-heroicons-x-circle" class="w-5 h-5 text-error" />
                                <UIcon v-if="item.is_confirmed == 1" name="i-heroicons-check-circle" class="w-5 h-5 text-success" />
                            </td>
                            <td v-if="activity.is_dynamic_score" class="w-20 border-b border-muted px-4 py-2 text-center">
                                <span v-if="item.is_confirmed && item.earned_score" class="text-sm font-semibold text-primary">{{ item.earned_score }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                <div class="flex items-center justify-center">
                                    <UTooltip
                                        v-if="item.attached_portfolio === 'yes'"
                                        text="Портфолио по мероприятию загружено">
                                        <UIcon name="i-heroicons-document-check" class="w-6 h-6 text-success" />
                                    </UTooltip>
                                    <UTooltip
                                        v-else
                                        text="Портфолио по мероприятию не загружено">
                                        <UIcon name="i-heroicons-document-minus" class="w-6 h-6 text-muted" />
                                    </UTooltip>
                                </div>
                            </td>
                            <td class="w-20 border-b border-muted px-4 py-2 text-center">
                                <UDropdownMenu :items="items(item)">
                                    <UButton color="neutral" variant="ghost" icon="i-heroicons-ellipsis-vertical-20-solid" />
                                </UDropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UAlert
                v-if="listOfUsers.length == 0"
                icon="i-heroicons-information-circle"
                color="info"
                variant="solid"
                title="Информация"
                description="Список участников мероприятия пуст" />
        </div>

    </div>
</template>

<script setup>
import { staticImg } from '~/assets/img'

definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['mentor'],
    layout: 'mentor',
    title: 'Мероприятия',
    description: 'Просмотр мероприятия и участников',
    breadcrumb: [
      { label: 'Главная', to: '/mentor' },
      { label: 'Мероприятия', to: '/mentor/activities' },
      { label: 'Просмотр' },
    ],
});

onMounted(() => {
    loadData();
    fetchListOfUsers(apiUrl.value);
})

const config = useRuntimeConfig();
const { user } = useSanctumAuth();
const route = useRoute()
const { get, post } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();

const LEVEL_CONFIRMED_1ST_PLACE = 1;
const LEVEL_CONFIRMED_2ND_PLACE = 0.8;
const LEVEL_CONFIRMED_3RD_PLACE = 0.6;
const LEVEL_CONFIRMED_PARTICIPANT = 0.4;

const availableKsuPlaces = ref([]);

const items = row => {
    if (activity.value.is_dynamic_score) {
        return availableKsuPlaces.value.map(p => ([{
            label: p.label,
            onSelect: () => confirmKsuPlace(row.id, p.value)
        }]));
    }
    return [
        [{
            label: 'Подтвердить 1 место',
            onSelect: () => confirmation(row.id, LEVEL_CONFIRMED_1ST_PLACE)
        }],
        [{
            label: 'Подтвердить 2 место',
            onSelect: () => confirmation(row.id, LEVEL_CONFIRMED_2ND_PLACE)
        }],
        [{
            label: 'Подтвердить 3 место',
            onSelect: () => confirmation(row.id, LEVEL_CONFIRMED_3RD_PLACE)
        }],
        [{
            label: 'Подтвердить участие',
            onSelect: () => confirmation(row.id, LEVEL_CONFIRMED_PARTICIPANT)
        }]
    ];
};

function loadKsuAvailablePlaces(activityId) {
    if (!activityId) {
        availableKsuPlaces.value = [];
        return;
    }
    get(`/api/v1/activities/${activityId}/scores`)
        .then((data) => {
            availableKsuPlaces.value = Array.isArray(data?.available_places) ? data.available_places : [];
        })
        .catch(() => {
            availableKsuPlaces.value = [];
        });
}

const apiUrl = ref('/api/v1/mentor/activities/' + route.params.id + '/participants');

const activity = ref({
    id: 0,
    title: "",
    description: "",
    date_start: "",
    date_end: "",
    score: 0,
    is_dynamic_score: false,
    status: "",
    status_eng: "",
    planet_name: "",
    photo_path: null,
    user_id: null
});

const listOfUsers = ref([]);

// Sorting state
const sortColumn = ref('');
const sortDirection = ref('asc'); // 'asc' or 'desc'

// Computed sorted list
const sortedListOfUsers = computed(() => {
    if (!sortColumn.value) return listOfUsers.value;

    return [...listOfUsers.value].sort((a, b) => {
        let aVal, bVal;

        switch (sortColumn.value) {
            case 'fio':
                aVal = a.fio.toLowerCase();
                bVal = b.fio.toLowerCase();
                break;
            case 'level':
                aVal = a.level || 0;
                bVal = b.level || 0;
                break;
            case 'status':
                aVal = a.is_confirmed;
                bVal = b.is_confirmed;
                break;
            case 'portfolio':
                aVal = a.attached_portfolio === 'yes' ? 1 : 0;
                bVal = b.attached_portfolio === 'yes' ? 1 : 0;
                break;
            default:
                return 0;
        }

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

function loadData() {
    get('/api/v1/mentor/activity/show-mentor/' + route.params.id).then((data) => {
        activity.value.id = data.id;
        activity.value.title = data.title;
        activity.value.description = data.description;
        activity.value.date_start = data.date_start;
        activity.value.date_end = data.date_end;
        activity.value.score = data.score;
        activity.value.is_dynamic_score = !!data.is_dynamic_score;
        activity.value.status = data.status;
        activity.value.status_eng = data.status_eng;
        activity.value.planet_name = data.planet_name;
        activity.value.photo_path = data.photo_path;
        activity.value.user_id = data.creator_id ?? data.user_id;

        if (activity.value.is_dynamic_score) {
            loadKsuAvailablePlaces(route.params.id);
        }
    }).catch(error => {
        showApiError(error, 'Произошла ошибка в процессе получения данных');
    });
}


function getLevelLabel(level) {
const num = typeof level === 'string' ? parseFloat(level) : level;

if (Math.abs(num - 1) < 0.01) return '1 место';
if (Math.abs(num - 0.8) < 0.01) return '2 место';
if (Math.abs(num - 0.6) < 0.01) return '3 место';
if (Math.abs(num - 0.4) < 0.01) return 'Участник';
return '—';
}

function getPlaceLabel(place) {
    const labels = { '1st': '1 место', '2nd': '2 место', '3rd': '3 место', 'participation': 'Участие' };
    return labels[place] || '—';
}

function confirmKsuPlace(id, place) {
    if (!confirm('Вы уверены, что хотите подтвердить участие и начислить баллы?')) {
        return;
    }

    post('/api/v1/mentor/user-confirmation-ksu-mentor', { id, place })
        .then((data) => {
            fetchListOfUsers(apiUrl.value);
            toast.add({ title: data?.message ?? 'Успешно' });
        })
        .catch((error) => {
            showApiError(error, 'Произошла ошибка');
        });
}

// Sorting function
function toggleSort(column) {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
}

// Get sort icon for column
function getSortIcon(column) {
    if (sortColumn.value !== column) return '';
    return sortDirection.value === 'asc' ? 'i-heroicons-chevron-up' : 'i-heroicons-chevron-down';
}

function fetchListOfUsers(url) {
    get(url).then((data) => {
        listOfUsers.value = data;
    }).catch(error => {
        showApiError(error, 'Произошла ошибка в процессе получения данных');
    });
}

async function checkOwnershipAndConfirm(participantId, level) {
  try {
// Проверяем, есть ли мероприятие в списке мероприятий ментора
const data = await get(`/api/v1/mentor/activity/mentor/activities/${user.value.user_id}`, {
  query: { per_page: 100 }
});

const activities = data || [];
const isOwner = activities.some(act => act.id == route.params.id);

if (!isOwner) {
  toast.add({
    title: 'У вас нет прав для подтверждения участия в этом мероприятии',
    color: 'error'
  });
  return;
}

// Если ментор владеет мероприятием, подтверждаем участие
if (!confirm('Вы уверены, что хотите подтвердить участие участника в мероприятии и начислить баллы?')) {
  return;
}

const response = await post('/api/v1/mentor/user-confirmation-mentor', {
  id: participantId,
  level: Number(level),
});

fetchListOfUsers(apiUrl.value);
toast.add({ title: response?.message ?? 'Успешно' });
  } catch (error) {
console.error('Error checking ownership:', error);
toast.add({ title: 'Произошла ошибка при проверке прав', color: 'error' });
  }
}

function confirmation(id, level) {
  // Проверяем, что ментор является создателем мероприятия
  console.log('Activity user_id:', activity.value.user_id, 'Auth user_id:', user.value.user_id, 'Types:', typeof activity.value.user_id, typeof user.value.user_id);

  // Если user_id не загружен, проверяем через API списка мероприятий ментора
  if (!activity.value.user_id) {
console.log('user_id not loaded, checking via mentor activities API');
checkOwnershipAndConfirm(id, level);
return;
  }

  if (activity.value.user_id !== user.value.user_id) {
toast.add({
  title: 'У вас нет прав для подтверждения участия в этом мероприятии',
  color: 'error'
});
return;
  }

  if (!confirm('Вы уверены, что хотите подтвердить участие участника в мероприятии и начислить баллы?')) {
return;
  }

  post('/api/v1/mentor/user-confirmation-mentor', {
id,
level: Number(level),
  })
.then((data) => {
  fetchListOfUsers(apiUrl.value);
  toast.add({ title: data?.message ?? 'Успешно' });
})
.catch((error) => {
  showApiError(error, 'Произошла ошибка');
});
}
</script>



