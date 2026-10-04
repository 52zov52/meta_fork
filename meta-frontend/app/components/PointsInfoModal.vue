<template>
    <UModal
        v-model:open="isOpen"
        title="Приём сертификатов и начисление баллов"
        description="Информация о сроках подачи сертификатов и системе начисления баллов за внеучебную активность"
        :ui="{
            content: 'w-full sm:max-w-5xl max-h-[90dvh]'
        }"
    >
        <template #body>
            <div class="space-y-6">
                <!-- Сроки подачи -->
                <div class="text-center pb-4 border-b border-default">
                    <UIcon
                        name="i-heroicons-calendar"
                        class="w-12 h-12 text-primary mx-auto mb-3"
                    />
                    <p class="text-base text-gray-700">
                        <template v-if="semesterLinesPending">
                            <span class="text-muted">Загрузка сроков…</span>
                        </template>
                        <template v-else-if="semesterLinesError">
                            <span class="text-error">Не удалось загрузить сроки подачи.</span>
                        </template>
                        <template v-else-if="semesterLines">
                            <span class="font-medium">Первый семестр:</span> {{ semesterLines.first }}<br >
                            <span class="font-medium">Второй семестр:</span> {{ semesterLines.second }}
                        </template>
                    </p>
                </div>

                <!-- Таблица с баллами -->
                <div>
                    <h4 class="text-base font-semibold text-gray-900 mb-3 text-center">
                        Начисление баллов за активности (до применения КСУ)
                    </h4>
                    <div class="overflow-x-auto -mx-4 px-4">
                        <table class="w-full text-xs border-collapse border border-default">
                            <colgroup>
                                <col class="w-[16%]" >
                                <col class="w-[49%]" >
                                <col class="w-[33%]" >
                            </colgroup>
                            <thead>
                                <tr class="bg-primary/10">
                                    <th class="border border-default px-2 py-2 text-left font-semibold text-gray-900">Направление</th>
                                    <th class="border border-default px-2 py-2 text-left font-semibold text-gray-900">Вид деятельности и количество баллов</th>
                                    <th class="border border-default px-2 py-2 text-left font-semibold text-gray-900">Примечания</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <!-- Проектная деятельность -->
                                <tr class="bg-white">
                                    <td rowspan="2" class="border border-default px-2 py-2 font-medium align-top">Проектная деятельность</td>
                                    <td class="border border-default px-2 py-2 align-top">
                                        <strong>Работа над проектом в составе команды – 25 баллов:</strong><br>
                                        • внутренняя проектная разработка (работа в Лабораториях, IT-песочнице, Бизнес-инкубаторе и др.)<br>
                                        • работа над проектом в рамках участия по внешних конкурсах (Проектный интенсив 2035, защита проектов от Опоры России и др.)<br><br>
                                        В случае успешного внедрения разработанного проекта или продукта в реальное окружение, практического достижения поставленных целей и измеримый результат – <strong>до 50 баллов</strong>
                                    </td>
                                    <td class="border border-default px-2 py-2 align-top">Баллы начисляются при условии успешной защиты проекта и предоставлении сертификата</td>
                                </tr>
                                <tr class="bg-white">
                                    <td class="border border-default px-2 py-2 align-top">
                                        <strong>Участие в Кейс-чемпионатах, хакатонах:</strong><br>
                                        • сертификат участника – <strong>10 баллов</strong><br>
                                        • первое место – <strong>25 баллов</strong><br>
                                        • второе место – <strong>20 баллов</strong><br>
                                        • третье место – <strong>15 баллов</strong>
                                    </td>
                                    <td class="border border-default px-2 py-2 align-top">При выборе рекомендуется учитывать соответствие профиля мероприятия с профилем подготовки</td>
                                </tr>

                                <!-- Научная деятельность -->
                                <tr class="bg-gray-50">
                                    <td rowspan="2" class="border border-default px-2 py-2 font-medium align-top">Научная деятельность</td>
                                    <td class="border border-default px-2 py-2 align-top">
                                        <strong>Публикационная активность:</strong><br>
                                        • публикация научной статьи с размещением в сборник РИНЦ (и внешний, и внутренний) – <strong>15 баллов</strong><br>
                                        • Выступление на конференции РИНЦ с публикацией – <strong>20 баллов</strong><br>
                                        • публикация научной статьи с размещением в журнале РИНЦ – <strong>25 баллов</strong><br>
                                        • публикация научной статьи в сборники из списка ВАК – <strong>50 баллов</strong>
                                    </td>
                                    <td class="border border-default px-2 py-2 align-top">Баллы начисляются при подтверждении публикации статьи (ссылка на данные о работе в elibrary)</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="border border-default px-2 py-2 align-top">
                                        <strong>Участие в реализации НИР:</strong><br>
                                        • выполнение хоздоговорной НИР – <strong>50 баллов</strong><br>
                                        • выполнение НИР в рамках конкурса<br>
                                        &nbsp;&nbsp;– подготовка НИР – <strong>10 баллов</strong><br>
                                        &nbsp;&nbsp;– первое место – <strong>25 баллов</strong><br>
                                        &nbsp;&nbsp;– второе место – <strong>20 баллов</strong><br>
                                        &nbsp;&nbsp;– третье место – <strong>15 баллов</strong>
                                    </td>
                                    <td class="border border-default px-2 py-2 align-top">Баллы начисляются при наличии договора, где исполнителем является ЧОУ ВО ЮУ (ИУБиП) для хоздоговорной НИР, или предоставлении сертификата/диплома</td>
                                </tr>

                                <!-- Общественная деятельность -->
                                <tr class="bg-white">
                                    <td class="border border-default px-2 py-2 font-medium align-top">Общественная деятельность</td>
                                    <td class="border border-default px-2 py-2 align-top">
                                        Волонтёрство, реализация социальных инициатив, участие в деятельности студенческих объединений и организаций, профориентационная работа и д.р.<br><br>
                                        За каждую активность – <strong>10 баллов</strong><br>
                                        Лидер движения – <strong>15 баллов</strong>
                                    </td>
                                    <td class="border border-default px-2 py-2 align-top">Количество начисленных баллов может быть увеличено по заявлению куратора программы, в котором обосновывается значимость и сложность мероприятия</td>
                                </tr>

                                <!-- Культурно-творческая деятельность -->
                                <tr class="bg-gray-50">
                                    <td rowspan="2" class="border border-default px-2 py-2 font-medium align-top">Культурно-творческая деятельность</td>
                                    <td class="border border-default px-2 py-2 align-top">
                                        Участие в культурных, спортивных, творческих мероприятиях, конкурсах, фестивалях<br>
                                        За каждую активность – <strong>10 баллов</strong><br><br>
                                        В случае участия во внешних соревнованиях/олимпиадах:<br>
                                        • первое место – <strong>25 баллов</strong><br>
                                        • второе место – <strong>20 баллов</strong><br>
                                        • третье место – <strong>15 баллов</strong><br>
                                        • участие – <strong>10 баллов</strong>
                                    </td>
                                    <td class="border border-default px-2 py-2 align-top border-b-0">
                                        Количество начисленных баллов может быть увеличено по заявлению куратора программы, в котором обосновывается значимость и сложность мероприятия
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-600 leading-relaxed">
                <p>
                    <strong>КСУ</strong> — коэффициент соответствия уровня требований активности уровню обучения студента.
                    Он применяется к указанным выше базовым баллам.
                </p>
                <p class="mt-1">
                    Итоговый балл рассчитывается по формуле:
                    <strong>базовые баллы × КСУ</strong> с округлением до целого значения.
                    Если уровень активности соответствует уровню обучения, КСУ равен <strong>1,00</strong>;
                    для более сложных активностей он повышается до <strong>1,10</strong> или <strong>1,20</strong>,
                    а для более простых снижается до <strong>0,90</strong> или <strong>0,80</strong>.
                </p>
            </div>
        </template>
    </UModal>
</template>

<script setup lang="ts">
import type { paths } from '~/types/api';
import {
    computeScoringSemestersFromBlackout,
    formatScoringSemesterSummaryLines,
} from '~/utils/scoringSemesterRanges';

type ScoringBlackoutGetJson =
    paths['/api/v1/scoring-blackout']['get']['responses'][200]['content']['application/json'];

/** Имя `open` обязательно: родитель вешает `v-model:open`. */
const isOpen = defineModel<boolean>('open', { default: false });

const { get } = useApi();

type SemesterLinePair = { first: string; second: string };

const semesterLines = shallowRef<SemesterLinePair | null>(null);
const semesterLinesPending = ref(false);
const semesterLinesError = ref(false);

async function loadScoringSemesterLines() {
    if (!isOpen.value) return;
    semesterLinesPending.value = true;
    semesterLinesError.value = false;
    try {
        const response = await get<ScoringBlackoutGetJson>('/api/v1/scoring-blackout');
        const ranges = computeScoringSemestersFromBlackout(response?.data ?? {});
        if (ranges) {
            const { firstLine, secondLine } = formatScoringSemesterSummaryLines(ranges);
            semesterLines.value = { first: firstLine, second: secondLine };
        } else {
            semesterLines.value = null;
            semesterLinesError.value = true;
        }
    } catch {
        semesterLines.value = null;
        semesterLinesError.value = true;
    } finally {
        semesterLinesPending.value = false;
    }
}

watch(
    isOpen,
    (open) => {
        if (open) {
            semesterLines.value = null;
            void loadScoringSemesterLines();
        } else {
            semesterLinesError.value = false;
        }
    },
    { immediate: true },
);
</script>
