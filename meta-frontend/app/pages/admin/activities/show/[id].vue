<template>
    <div class="flex flex-col gap-4">
        <ImagePreviewModal
            v-model:open="showImagePreview"
            :src="previewImageSrc"
            :fallback-src="staticImg.bgCard"
        />
        <!-- Основная информация о мероприятии -->
        <div class="bg-white rounded-lg shadow-sm border border-default p-4 md:p-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <div
                    :style="{ backgroundImage: activity.photo_path
                        ? `url('${config.public.API_PATH_STORAGE + activity.photo_path}')`
                        : `url('${staticImg.bgCard}')` }"
                    class="h-48 w-full lg:w-64 shrink-0 bg-cover bg-center rounded-lg shadow-inner border border-black/5 cursor-pointer hover:opacity-90 transition-opacity"
                    @click="openImagePreview(activity.photo_path)"
                />

                <div class="flex-1 space-y-4">
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <h2 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight">{{ activity.title }}</h2>
                            <div class="shrink-0">
                                <UBadge v-if="activity.status_eng === 'draft'" color="warning" variant="subtle" size="md">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'scheduled'" color="primary" variant="subtle" size="md">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'started'" color="primary" variant="subtle" size="md">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'completed'" color="primary" variant="subtle" size="md">{{ activity.status }}</UBadge>
                                <UBadge v-if="activity.status_eng === 'canceled'" color="error" variant="subtle" size="md">{{ activity.status }}</UBadge>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm text-gray-600">
                            <div class="flex items-center space-x-2 bg-gray-50 p-2 rounded-md">
                                <UIcon name="i-heroicons-calendar-days" class="w-4 h-4 text-primary shrink-0" />
                                <span class="truncate">с {{ formatDate(activity.date_start) }} по {{ formatDate(activity.date_end) }}</span>
                            </div>
                            <div class="flex items-center space-x-2 bg-gray-50 p-2 rounded-md">
                                <UIcon name="i-heroicons-star" class="w-4 h-4 text-yellow-500 shrink-0" />
                                <span v-if="activity.is_dynamic_score">Автоматический расчёт баллов</span>
                                <span v-else>{{ activity.score }} баллов за участие</span>
                            </div>
                            <div class="flex items-center space-x-2 bg-gray-50 p-2 rounded-md">
                                <UIcon name="i-heroicons-tag" class="w-4 h-4 text-green-500 shrink-0" />
                                <span class="truncate">{{ activity.planet_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="activity.description" class="mt-4">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">Описание мероприятия</h3>
                        <div class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 md:p-4 border border-default/50">
                            <p class="whitespace-pre-line leading-relaxed">{{ activity.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Заголовок и управление списком -->
        <div class="flex flex-col gap-3 mt-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    Список участников
                    <UBadge color="neutral" variant="soft" size="sm">{{ listOfUsers.length }}</UBadge>
                </h2>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <div class="lg:hidden shrink-0">
                    <USelect
                        v-model="sortColumn"
                        :items="sortOptions"
                        size="sm"
                        class="w-32"
                        placeholder="Сортировка"
                        @update:model-value="onMobileSortChange"
                    />
                </div>
                <div v-if="listOfUsers.length > 0 && status !== 'pending'" class="flex flex-row gap-2">
                    <UButton
                        color="primary"
                        variant="outline"
                        size="xs"
                        icon="i-heroicons-document-check"
                        class="shrink-0"
                        @click="bulkConfirmation(true)"
                    >
                        <span class="sm:hidden">С портфолио</span>
                        <span class="hidden sm:inline">Подтвердить (с портфолио)</span>
                    </UButton>
                    <UButton
                        color="primary"
                        variant="solid"
                        size="xs"
                        icon="i-heroicons-check-badge"
                        class="shrink-0"
                        @click="bulkConfirmation(false)"
                    >
                        <span class="sm:hidden">Всех</span>
                        <span class="hidden sm:inline">Подтвердить всех</span>
                    </UButton>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-2">
                <div class="bg-white rounded-lg border border-default shadow-sm p-4 space-y-3">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-gray-900">Добавить участника вручную</h3>
                        <p class="text-xs text-gray-500">Поиск идёт по всем студентам: ФИО, группа или email.</p>
                    </div>
                    <div class="flex gap-2">
                        <UInput
                            v-model="candidateSearchTxt"
                            icon="i-heroicons-magnifying-glass"
                            size="sm"
                            class="w-full"
                            placeholder="ФИО, группа или email"
                            @input="debouncedCandidateSearch"
                            @keyup.enter.prevent="searchParticipantCandidates"
                        />
                        <UButton
                            size="xs"
                            color="primary"
                            variant="solid"
                            icon="i-heroicons-magnifying-glass"
                            :disabled="!candidateSearchTxt.trim()"
                            :loading="candidateStatus === 'pending'"
                            @click="searchParticipantCandidates"
                        />
                    </div>
                    <p v-if="candidateSearchTxt.trim() && candidateStatus === 'success' && participantCandidates.length === 20" class="text-xs text-gray-500">
                        Показаны первые 20 результатов. Уточните запрос, чтобы сузить список.
                    </p>
                    <div v-if="candidateStatus === 'pending'" class="flex justify-center py-4">
                        <UIcon name="i-heroicons-arrow-path" class="w-5 h-5 animate-spin text-primary" />
                    </div>
                    <div v-else-if="candidateSearchTxt.trim() && participantCandidates.length > 0" class="max-h-64 overflow-y-auto divide-y divide-muted rounded-md border border-default">
                        <div v-for="candidate in participantCandidates" :key="candidate.user_id" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 bg-white">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ candidate.fio }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ candidate.group || '—' }} · {{ candidate.email }}</p>
                            </div>
                            <UButton
                                size="xs"
                                :color="candidate.is_participant ? 'neutral' : 'primary'"
                                :variant="candidate.is_participant ? 'outline' : 'solid'"
                                :disabled="candidate.is_participant || addingParticipantId === candidate.user_id"
                                :loading="addingParticipantId === candidate.user_id"
                                @click="addParticipant(candidate)"
                            >
                                {{ candidate.is_participant ? 'Уже участник' : 'Добавить' }}
                            </UButton>
                        </div>
                    </div>
                    <UAlert
                        v-else-if="candidateSearchTxt.trim() && candidateStatus === 'success'"
                        color="info"
                        variant="soft"
                        icon="i-heroicons-information-circle"
                        title="Ничего не найдено"
                        description="Попробуйте ФИО, группу или email."
                    />
                </div>

                <div class="bg-white rounded-lg border border-default shadow-sm p-4 space-y-3">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-gray-900">Массовое добавление из Excel</h3>
                        <p class="text-xs text-gray-500">Заполните шаблон: ФИО обязательно (фамилия + имя), группа помогает при неоднозначности.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <UButton
                            size="xs"
                            color="neutral"
                            variant="outline"
                            icon="i-heroicons-arrow-down-tray"
                            :loading="downloadingTemplate"
                            @click="downloadParticipantsImportTemplate"
                        >
                            Скачать шаблон
                        </UButton>
                        <input
                            ref="participantsImportFileInput"
                            type="file"
                            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                            class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-white hover:file:bg-primary/90"
                            @change="onParticipantsImportFileChange"
                        >
                        <UButton
                            size="xs"
                            color="primary"
                            variant="solid"
                            icon="i-heroicons-document-magnifying-glass"
                            :disabled="!participantsImportFile"
                            :loading="importPreviewStatus === 'pending'"
                            @click="previewParticipantsImport"
                        >
                            Проверить
                        </UButton>
                    </div>
                    <p v-if="participantsImportFile" class="text-xs text-gray-500 truncate">
                        Файл: {{ participantsImportFile.name }}
                    </p>
                    <div v-if="participantsImportPreview" class="space-y-3">
                        <div class="flex flex-wrap gap-2 text-xs">
                            <UBadge color="primary" variant="soft">Готово: {{ participantsImportPreview.summary.ready_count }}</UBadge>
                            <UBadge color="neutral" variant="soft">Уже были: {{ participantsImportPreview.summary.already_exists_count }}</UBadge>
                            <UBadge v-if="participantsImportPreview.summary.ambiguous_count" color="warning" variant="soft">Уточните: {{ participantsImportPreview.summary.ambiguous_count }}</UBadge>
                            <UBadge color="error" variant="soft">Ошибки: {{ participantsImportPreview.summary.error_count }}</UBadge>
                        </div>
                        <div class="max-h-56 overflow-auto rounded-md border border-default divide-y divide-muted">
                            <div v-for="row in participantsImportPreview.rows" :key="row.row" class="p-2 text-xs bg-white">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900">Строка {{ row.row }}: {{ row.fio || '—' }}</p>
                                        <p class="text-gray-500">{{ row.group || '—' }} · {{ row.message }}</p>
                                    </div>
                                    <UBadge :color="importRowBadgeColor(row.status)" variant="soft" class="shrink-0">
                                        {{ importRowStatusLabel(row.status) }}
                                    </UBadge>
                                </div>
                                <div v-if="row.status === 'ambiguous'" class="mt-2 flex flex-wrap gap-1">
                                    <button
                                        v-for="candidate in row.candidates"
                                        :key="candidate.user_id"
                                        type="button"
                                        class="px-2 py-1 rounded border text-xs transition-colors"
                                        :class="ambiguousSelections[row.row] === candidate.user_id
                                            ? 'bg-primary border-primary text-white'
                                            : 'bg-white border-gray-300 text-gray-700 hover:border-primary hover:text-primary'"
                                        @click="ambiguousSelections[row.row] = candidate.user_id"
                                    >
                                        {{ candidate.group || 'без группы' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <UButton
                            size="xs"
                            color="primary"
                            variant="solid"
                            icon="i-heroicons-user-plus"
                            :disabled="readyImportUserIds.length === 0"
                            :loading="importCommitStatus === 'pending'"
                            @click="commitParticipantsImport"
                        >
                            Добавить найденных: {{ readyImportUserIds.length }}
                        </UButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Контент списка участников -->
        <div v-if="status === 'pending'" class="flex justify-center py-12">
            <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary" />
        </div>

        <div v-else-if="listOfUsers.length > 0" class="flex flex-col gap-4">
            <!-- Табличный вид (Десктоп) -->
            <div class="hidden lg:block overflow-x-auto bg-white rounded-lg border border-default shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="w-16 border-b border-default px-4 py-3 font-semibold text-gray-700">№</th>
                            <th class="border-b border-default px-4 py-3 text-left font-semibold text-gray-700">
                                <button class="flex items-center space-x-1 hover:text-primary transition-colors group" @click="toggleSort('fio')">
                                    <span>ФИО</span>
                                    <UIcon :name="getSortIcon('fio') || 'i-heroicons-arrows-up-down'"
                                           :class="[getSortIcon('fio') ? 'text-primary' : 'text-gray-300 group-hover:text-gray-400']"
                                           class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-32 border-b border-default px-4 py-3 font-semibold text-gray-700 text-left">
                                <button class="flex items-center space-x-1 hover:text-primary transition-colors group" @click="toggleSort('group')">
                                    <span>Группа</span>
                                    <UIcon :name="getSortIcon('group') || 'i-heroicons-arrows-up-down'"
                                           :class="[getSortIcon('group') ? 'text-primary' : 'text-gray-300 group-hover:text-gray-400']"
                                           class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-40 border-b border-default px-4 py-3 font-semibold text-gray-700">
                                <button class="flex items-center justify-center space-x-1 hover:text-primary transition-colors group" @click="toggleSort('level')">
                                    <span>{{ activity.is_dynamic_score ? 'Место' : 'Уровень' }}</span>
                                    <UIcon :name="getSortIcon('level') || 'i-heroicons-arrows-up-down'"
                                           :class="[getSortIcon('level') ? 'text-primary' : 'text-gray-300 group-hover:text-gray-400']"
                                           class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-24 border-b border-default px-4 py-3 font-semibold text-gray-700 text-center">
                                <button class="flex items-center justify-center space-x-1 hover:text-primary transition-colors group" @click="toggleSort('status')">
                                    <span>Статус</span>
                                    <UIcon :name="getSortIcon('status') || 'i-heroicons-arrows-up-down'"
                                           :class="[getSortIcon('status') ? 'text-primary' : 'text-gray-300 group-hover:text-gray-400']"
                                           class="w-4 h-4" />
                                </button>
                            </th>
                            <th v-if="activity.is_dynamic_score" class="w-24 border-b border-default px-4 py-3 font-semibold text-gray-700 text-center">
                                Баллы
                            </th>
                            <th class="w-32 border-b border-default px-4 py-3 font-semibold text-gray-700 text-center">
                                <button class="flex items-center justify-center space-x-1 hover:text-primary transition-colors group" @click="toggleSort('portfolio')">
                                    <span>Портфолио</span>
                                    <UIcon :name="getSortIcon('portfolio') || 'i-heroicons-arrows-up-down'"
                                           :class="[getSortIcon('portfolio') ? 'text-primary' : 'text-gray-300 group-hover:text-gray-400']"
                                           class="w-4 h-4" />
                                </button>
                            </th>
                            <th class="w-24 border-b border-default px-4 py-3 font-semibold text-gray-700">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted">
                        <tr v-for="(item, index) in sortedListOfUsers" :key="item.id || index" class="hover:bg-zinc-950/2.5 transition-colors">
                            <td class="px-4 py-3 text-center text-gray-500">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <UButton
                                    :to="`/student/profile/${item.user_id || item.id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    variant="link"
                                    color="primary"
                                    class="p-0 h-auto font-medium text-left justify-start"
                                >
                                    {{ item.fio }}
                                </UButton>
                            </td>
                            <td class="px-4 py-3 text-left text-gray-600">
                                {{ item.group || '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="activity.is_dynamic_score"
                                      class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                      :class="{
                                        'bg-primary/10 text-primary/80': item.place === '1st',
                                        'bg-green-100 text-green-800': item.place === '2nd',
                                        'bg-amber-100 text-amber-800': item.place === '3rd',
                                        'bg-gray-100 text-gray-700': item.place === 'participation',
                                        'text-gray-500 bg-gray-50': !item.place
                                      }">
                                    {{ getPlaceLabel(item.place) }}
                                </span>
                                <span v-else
                                      class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                      :class="getLevelStyles(item.level)">
                                    {{ getLevelLabel(item.level) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <UIcon :name="item.is_confirmed == 1 ? 'i-heroicons-check-circle' : 'i-heroicons-clock'"
                                       :class="item.is_confirmed == 1 ? 'text-green-500' : 'text-amber-500'"
                                       class="w-5 h-5 mx-auto" />
                            </td>
                            <td v-if="activity.is_dynamic_score" class="px-4 py-3 text-center">
                                <span v-if="item.is_confirmed && item.earned_score" class="text-sm font-semibold text-primary">{{ item.earned_score }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center">
                                    <UTooltip :text="item.attached_portfolio === 'yes' ? 'Загружено' : 'Не загружено'">
                                        <UIcon :name="item.attached_portfolio === 'yes' ? 'i-heroicons-document-check' : 'i-heroicons-document-minus'"
                                               :class="item.attached_portfolio === 'yes' ? 'text-green-600' : 'text-gray-400'"
                                               class="w-6 h-6" />
                                    </UTooltip>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <UDropdownMenu :items="items(item)" :content="{ align: 'end' }">
                                    <UButton color="neutral" variant="ghost" icon="i-heroicons-ellipsis-vertical-20-solid" />
                                </UDropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Карточный вид (Мобильный) -->
            <div class="lg:hidden grid grid-cols-1 gap-2">
                <div v-for="(item, index) in sortedListOfUsers" :key="item.id || index" class="bg-white rounded-lg border border-default shadow-sm p-3 space-y-2 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full" :class="item.is_confirmed == 1 ? 'bg-green-500' : 'bg-amber-500'"></div>

                    <div class="flex justify-between items-start pl-2">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">ФИО участника</span>
                            <UButton
                                :to="`/student/profile/${item.user_id || item.id}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                variant="link"
                                color="primary"
                                class="p-0 h-auto font-bold text-base text-left justify-start leading-tight"
                            >
                                {{ item.fio }}
                            </UButton>
                            <span class="text-xs text-gray-500">{{ item.group || '—' }}</span>
                        </div>
                        <span class="text-xs font-mono text-gray-300">#{{ index + 1 }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pl-2">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Уровень</span>
                            <div>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="getLevelStyles(item.level)">
                                    {{ getLevelLabel(item.level) }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Статус</span>
                            <div class="flex items-center gap-1.5">
                                <UIcon :name="item.is_confirmed == 1 ? 'i-heroicons-check-circle' : 'i-heroicons-clock'"
                                       :class="item.is_confirmed == 1 ? 'text-green-500' : 'text-amber-500'"
                                       class="w-4 h-4" />
                                <span class="text-xs font-medium" :class="item.is_confirmed == 1 ? 'text-green-700' : 'text-amber-700'">
                                    {{ item.is_confirmed == 1 ? 'Подтвержден' : 'Ожидает' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pl-2 pt-0.5">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Портфолио</span>
                            <div class="flex items-center gap-1.5">
                                <UIcon :name="item.attached_portfolio === 'yes' ? 'i-heroicons-document-check' : 'i-heroicons-document-minus'"
                                       :class="item.attached_portfolio === 'yes' ? 'text-green-600' : 'text-gray-400'"
                                       class="w-4 h-4" />
                                <span class="text-xs text-gray-600">
                                    {{ item.attached_portfolio === 'yes' ? 'Загружено' : 'Нет файла' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col justify-end">
                            <UDropdownMenu :items="items(item)" :content="{ align: 'end', side: 'top' }">
                                <UButton color="primary" variant="outline" size="xs" icon="i-heroicons-check-badge" label="Действие" block class="h-8" />
                            </UDropdownMenu>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UAlert
            v-else
            icon="i-heroicons-information-circle"
            color="info"
            variant="solid"
            title="Информация"
            description="Список участников мероприятия пуст"
        />

    </div>
</template>

<script setup>
import { staticImg } from '~/assets/img'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Мероприятия',
  description: 'Управляйте мероприятиями и их настройками',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Мероприятия', to: '/admin/activities' }, { label: 'Просмотр' }],
});

onMounted(() => {
    loadData();
    fetchListOfUsers(apiUrl.value);
})

const config = useRuntimeConfig();
const route = useRoute()
const { get, post, getBlob } = useApi();
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
}

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

const apiUrl = ref('api/v1/admin/activities/' + route.params.id + '/participants');

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
    photo_path: null
});

const showImagePreview = ref(false)
const previewImageSrc = ref('')

function openImagePreview(photoPath) {
    previewImageSrc.value = photoPath ? config.public.API_PATH_STORAGE + photoPath : ''
    showImagePreview.value = true
}

const listOfUsers = ref([]);
const status = ref('idle');
const candidateSearchTxt = ref('');
const participantCandidates = ref([]);
const candidateStatus = ref('idle');
const addingParticipantId = ref(null);
const downloadingTemplate = ref(false);
const participantsImportFile = ref(null);
const participantsImportFileInput = ref(null);
const participantsImportPreview = ref(null);
const importPreviewStatus = ref('idle');
const importCommitStatus = ref('idle');
const ambiguousSelections = ref({});

// Sorting state
const sortColumn = ref('');
const sortDirection = ref('asc'); // 'asc' or 'desc'

const sortOptions = [
    { value: 'fio', label: 'По ФИО' },
    { value: 'group', label: 'По группе' },
    { value: 'level', label: 'По уровню' },
    { value: 'status', label: 'По статусу' },
    { value: 'portfolio', label: 'По портфолио' },
];

const readyImportUserIds = computed(() => {
    if (!participantsImportPreview.value?.rows) return [];

    const ids = new Set();
    for (const row of participantsImportPreview.value.rows) {
        if (row.status === 'ready' && row.user?.user_id) {
            ids.add(row.user.user_id);
        } else if (row.status === 'ambiguous' && ambiguousSelections.value[row.row]) {
            ids.add(ambiguousSelections.value[row.row]);
        }
    }
    return [...ids];
});

function onMobileSortChange(value) {
    sortColumn.value = value;
    sortDirection.value = 'asc';
}

function getLevelStyles(level) {
    const num = Number(level);
    if (Math.abs(num - 1) < 0.01) return 'bg-primary/10 text-primary/80';
    if (Math.abs(num - 0.8) < 0.01) return 'bg-green-100 text-green-800';
    if (Math.abs(num - 0.6) < 0.01) return 'bg-amber-100 text-amber-800';
    if (Math.abs(num - 0.4) < 0.01) return 'bg-gray-100 text-gray-700';
    return 'text-gray-500 bg-gray-50';
}

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
            case 'group':
                aVal = (a.group || '').toLowerCase();
                bVal = (b.group || '').toLowerCase();
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
    get('/api/v1/admin/activity/show/' + route.params.id).then((data) => {
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

        if (activity.value.is_dynamic_score) {
            loadKsuAvailablePlaces(route.params.id);
        }
    }).catch(_error => {
        showApiError(_error, 'Произошла ошибка в процессе получения данных');
    });
}

function formatDate(value) {
  if (!value) return '—'

  const parts = String(value).split('T');
  const datePart = parts[0];
  if (!datePart) return String(value);

  const dateSegments = datePart.split('-');
  const [year, month, day] = dateSegments;

  if (!year || !month || !day) return String(value);

  return `${day.padStart(2, '0')}.${month.padStart(2, '0')}.${year}`;
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

    post('/api/v1/admin/user-confirmation-ksu', { id, place })
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
    status.value = 'pending';
    get(url).then((data) => {
        listOfUsers.value = data;
        status.value = 'success';
    }).catch(_error => {
        status.value = 'error';
        showApiError(_error, 'Произошла ошибка в процессе получения данных');
    });
}

let candidateSearchTimeout = null;
function debouncedCandidateSearch() {
    if (candidateSearchTimeout) {
        clearTimeout(candidateSearchTimeout);
    }

    if (!candidateSearchTxt.value.trim()) {
        clearParticipantCandidates();
        return;
    }

    candidateSearchTimeout = setTimeout(() => {
        searchParticipantCandidates();
    }, 400);
}

function searchParticipantCandidates() {
    const search = candidateSearchTxt.value.trim();
    if (!search) {
        clearParticipantCandidates();
        return;
    }

    candidateStatus.value = 'pending';

    const query = { limit: 20, search };

    get(`/api/v1/admin/activities/${route.params.id}/participant-candidates`, { query })
        .then((data) => {
            participantCandidates.value = Array.isArray(data?.data) ? data.data : [];
            candidateStatus.value = 'success';
        })
        .catch((error) => {
            candidateStatus.value = 'error';
            showApiError(error, 'Ошибка поиска студентов');
        });
}

function clearParticipantCandidates() {
    participantCandidates.value = [];
    candidateStatus.value = 'idle';
}

function addParticipant(candidate) {
    addingParticipantId.value = candidate.user_id;

    post(`/api/v1/admin/activities/${route.params.id}/participants`, {
        user_id: candidate.user_id,
    })
        .then((data) => {
            toast.add({ title: data?.message ?? 'Студент добавлен' });
            fetchListOfUsers(apiUrl.value);
            searchParticipantCandidates();
        })
        .catch((error) => {
            showApiError(error, 'Ошибка добавления участника');
        })
        .finally(() => {
            addingParticipantId.value = null;
        });
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

function downloadParticipantsImportTemplate() {
    downloadingTemplate.value = true;

    getBlob('/api/v1/admin/activities/participants-import-template')
        .then(({ data }) => {
            downloadBlob(data, 'activity-participants-template.xlsx');
        })
        .catch((error) => {
            showApiError(error, 'Ошибка скачивания шаблона');
        })
        .finally(() => {
            downloadingTemplate.value = false;
        });
}

function onParticipantsImportFileChange(event) {
    const file = event.target.files?.[0] || null;
    participantsImportFile.value = file;
    participantsImportPreview.value = null;
    ambiguousSelections.value = {};
}

function previewParticipantsImport() {
    if (!participantsImportFile.value) {
        toast.add({ title: 'Выберите Excel-файл', color: 'error' });
        return;
    }

    importPreviewStatus.value = 'pending';
    participantsImportPreview.value = null;

    const formData = new FormData();
    formData.append('file', participantsImportFile.value);

    post(`/api/v1/admin/activities/${route.params.id}/participants-import/preview`, formData)
        .then((data) => {
            participantsImportPreview.value = data;
            ambiguousSelections.value = {};
            importPreviewStatus.value = 'success';
        })
        .catch((error) => {
            importPreviewStatus.value = 'error';
            showApiError(error, 'Ошибка проверки Excel');
        });
}

function commitParticipantsImport() {
    if (readyImportUserIds.value.length === 0) {
        toast.add({ title: 'Нет найденных студентов для добавления', color: 'error' });
        return;
    }

    importCommitStatus.value = 'pending';

    post(`/api/v1/admin/activities/${route.params.id}/participants-import`, {
        user_ids: readyImportUserIds.value,
    })
        .then((data) => {
            toast.add({ title: data?.message ?? 'Участники добавлены' });
            participantsImportPreview.value = null;
            participantsImportFile.value = null;
            ambiguousSelections.value = {};
            if (participantsImportFileInput.value) {
                participantsImportFileInput.value.value = '';
            }
            fetchListOfUsers(apiUrl.value);
            searchParticipantCandidates();
        })
        .catch((error) => {
            showApiError(error, 'Ошибка добавления участников');
        })
        .finally(() => {
            importCommitStatus.value = 'idle';
        });
}

function importRowStatusLabel(status) {
    if (status === 'ready') return 'Готово';
    if (status === 'already_exists') return 'Уже есть';
    if (status === 'ambiguous') return 'Уточните';
    return 'Ошибка';
}

function importRowBadgeColor(status) {
    if (status === 'ready') return 'primary';
    if (status === 'already_exists') return 'neutral';
    if (status === 'ambiguous') return 'warning';
    return 'error';
}

function confirmation(id, level) {
  if (!confirm('Вы уверены, что хотите подтвердить участие участника в мероприятии и начислить баллы?')) {
return;
  }

  post('/api/v1/admin/user-confirmation', {
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

function bulkConfirmation(onlyWithPortfolio = false) {
    const label = onlyWithPortfolio ? 'студентов с прикреплённым портфолио' : 'всех студентов';
    if (!confirm(`Вы уверены, что хотите подтвердить участие ${label} в мероприятии как "Участник"?`)) {
        return;
    }

    post('/api/v1/admin/activities/bulk-confirmation', {
        activity_id: Number(route.params.id),
        level: LEVEL_CONFIRMED_PARTICIPANT,
        only_with_portfolio: onlyWithPortfolio,
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
