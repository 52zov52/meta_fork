<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\EducationLevel;
use App\Services\ScoreCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Activities
 */
class DynamicScoreController extends Controller
{
    /**
     * Предварительный расчет баллов (живое превью).
     *
     * Возвращает таблицу баллов для ВСЕХ уровней обучения. Если переданы target_education_level_ids
     * (1 или 2 соседних по NUO), применяется КСУ-коэффициент по ближайшему таргету.
     * Без target — KSU=1.00 для всех уровней.
     *
     * @group Activities
     *
     * @bodyParam category_id integer required ID категории. Example: 1
     * @bodyParam parameters object required Параметры КСУ. Example: {"activity_type":"external","has_places":true}
     * @bodyParam parameters.activity_type string nullable Тип активности. Example: "external"
     * @bodyParam parameters.has_places boolean Призовые места. Example: true
     * @bodyParam parameters.has_implementation boolean Внедрение (для проектной внутренней). Example: true
     * @bodyParam parameters.is_leader boolean Лидер студ. лаборатории (для общественной). Example: false
     * @bodyParam target_education_level_ids integer[] nullable ID целевых уровней (1 или 2 соседних NUO). Example: [1,2]
     *
     * @response 200 {"preview": [{"education_level_id": 1, "education_level_name": "Колледж", "is_target": true, "ksu": 1.0, "delta": 0, "scores": {"1st_place": 25, "participation": 10}}]}
     * @response 422 scenario="Base score not found" {"message": "Не удалось найти базовые баллы для указанных параметров."}
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'category_id' => ['required', 'integer', 'exists:activity_categories,id'],
            'parameters' => ['required', 'array'],
            'parameters.activity_type' => ['nullable', 'string'],
            'parameters.has_places' => ['sometimes', 'boolean'],
            'parameters.has_implementation' => ['sometimes', 'boolean', 'nullable'],
            'parameters.is_leader' => ['sometimes', 'boolean'],
            'target_education_level_ids' => ['nullable', 'array', 'max:2'],
            'target_education_level_ids.*' => ['integer', 'exists:education_levels,id'],
        ])->validate();

        $targetIds = array_values(array_unique(array_map('intval', $validated['target_education_level_ids'] ?? [])));

        // Проверка адъяцентности для 2 элементов (для UX preview не блокируем — только в Request при сохранении)
        if (count($targetIds) === 2) {
            $nuos = EducationLevel::whereIn('id', $targetIds)->pluck('nuo_value')->map(fn ($v) => (int) $v)->all();
            sort($nuos);
            if (count($nuos) !== 2 || $nuos[1] - $nuos[0] !== 1) {
                return response()->json([
                    'message' => 'Можно выбрать только соседние уровни обучения.',
                ], 422);
            }
        }

        $preview = ScoreCalculationService::calculatePreview(
            (int) $validated['category_id'],
            $validated['parameters'],
            $targetIds === [] ? null : $targetIds,
        );

        if ($preview === []) {
            return response()->json([
                'message' => 'Не удалось найти базовые баллы для указанных параметров.',
            ], 422);
        }

        return response()->json(['preview' => $preview]);
    }

    /**
     * Получить рассчитанные баллы мероприятия.
     *
     * Для КСУ-мероприятий возвращает таблицу баллов по уровням обучения.
     * Для legacy возвращает статический score.
     *
     * @group Activities
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     *
     * @response 200 scenario="KSU" {"activity_id": 1, "is_dynamic_score": true, "has_places": true, "target_education_level_ids": [1,2], "target_education_level_names": ["Колледж", "Бакалавриат"], "scores": [{"education_level_id": 1, "education_level_name": "Колледж", "1st_place": 25, "participation": 10}], "user_level_id": 2, "user_level_name": "Бакалавриат", "user_scores": {"1st_place": 25, "participation": 10}, "user_personal_score": 10}
     * @response 200 scenario="Legacy" {"activity_id": 1, "is_dynamic_score": false, "score": 30}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     */
    public function showScores(int $id): JsonResponse
    {
        $activity = Activity::with(['parameters', 'activityScores.educationLevel'])->findOrFail($id);

        if (! $activity->is_dynamic_score) {
            return response()->json([
                'activity_id' => $activity->id,
                'is_dynamic_score' => false,
                'score' => $activity->score,
            ]);
        }

        $scores = [];
        foreach ($activity->activityScores as $activityScore) {
            $levelId = $activityScore->education_level_id;
            if (! isset($scores[$levelId])) {
                $scores[$levelId] = [
                    'education_level_id' => $levelId,
                    'education_level_name' => $activityScore->educationLevel->name,
                    'nuo_value' => $activityScore->nuo_value,
                ];
            }
            $key = $activityScore->place === 'participation' ? 'participation' : $activityScore->place.'_place';
            $scores[$levelId][$key] = $activityScore->final_score;
        }

        $scoresList = array_values($scores);
        usort($scoresList, fn ($a, $b) => ($a['nuo_value'] ?? 0) <=> ($b['nuo_value'] ?? 0));

        $user = Auth::user();
        $userLevelId = null;
        $userLevelName = null;
        $userScores = null;
        $userPersonalScore = null;

        if ($user && $user->member) {
            $userLevelId = $user->member->education_level_id;
            $userLevel = EducationLevel::find($userLevelId);
            $userLevelName = $userLevel?->name;

            $userRow = collect($scoresList)->firstWhere('education_level_id', $userLevelId);
            if ($userRow) {
                $userScores = collect($userRow)->except(['education_level_id', 'education_level_name', 'nuo_value'])->all();
                $userPersonalScore = $userScores['participation'] ?? null;
            }
        }

        $targetIds = (array) ($activity->parameters?->target_education_level_ids ?? []);
        $targetIds = array_map('intval', $targetIds);
        $targetNames = EducationLevel::whereIn('id', $targetIds)->orderBy('nuo_value')->pluck('name')->all();
        $targetIdsSorted = EducationLevel::whereIn('id', $targetIds)->orderBy('nuo_value')->pluck('id')->map(fn ($v) => (int) $v)->all();

        return response()->json([
            'activity_id' => $activity->id,
            'is_dynamic_score' => true,
            'has_places' => $activity->parameters?->has_places ?? false,
            'is_leader' => $activity->parameters?->is_leader ?? false,
            'target_education_level_ids' => $targetIdsSorted,
            'target_education_level_names' => $targetNames,
            'scores' => $scoresList,
            'available_places' => $this->availablePlaces($activity),
            'user_level_id' => $userLevelId,
            'user_level_name' => $userLevelName,
            'user_scores' => $userScores,
            'user_personal_score' => $userPersonalScore,
        ]);
    }

    /**
     * Какие действия подтверждения участия доступны для данного мероприятия.
     * Лейблы зависят от параметров — лидеру движения «Подтвердить (лидер движения)»
     * вместо обычного «Подтвердить участие».
     *
     * @return list<array{value: string, label: string}>
     */
    private function availablePlaces(Activity $activity): array
    {
        $hasPlaces = (bool) ($activity->parameters?->has_places ?? false);
        $isLeader = (bool) ($activity->parameters?->is_leader ?? false);

        $items = [];
        if ($hasPlaces) {
            $items[] = ['value' => '1st', 'label' => 'Подтвердить 1 место'];
            $items[] = ['value' => '2nd', 'label' => 'Подтвердить 2 место'];
            $items[] = ['value' => '3rd', 'label' => 'Подтвердить 3 место'];
        }
        $items[] = [
            'value' => 'participation',
            'label' => $isLeader ? 'Подтвердить (лидер движения)' : 'Подтвердить участие',
        ];

        return $items;
    }
}
