<?php

namespace App\Http\Controllers\Activity;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Support\PublicUploadPath;
use App\Models\ActivityParameter;
use App\Services\ScoreCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @group Activities
 */
class ActivityController extends Controller
{
    /**
     * Получить мероприятия ментора
     *
     * Получить список мероприятий, созданных конкретным ментором.
     * Агрегированные данные с JOIN. (ResponseCalls broken)
     *
     * @group Activities (Mentor)
     *
     * @urlParam user_id integer required ID ментора. Example: 5
     *
     * @queryParam status string Фильтр по статусу. Example: scheduled
     * @queryParam per_page integer Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page": 1, "data": [{"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "is_dynamic_score": false, "status": "Запланировано", "status_eng": "scheduled", "planet_name": "IT", "category_name": "Проектная", "photo_path": null, "participants_count": 5}], "first_page_url": "...", "from": 1, "last_page": 1, "last_page_url": "...", "next_page_url": null, "path": "...", "per_page": 20, "prev_page_url": null, "to": 1, "total": 1}
     */
    public function mentorActivities(Request $request, int $user_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'string', Rule::in(ActivityStatus::values())],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Activity::join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->where('activities.creator_id', $user_id)
            ->select(
                'activities.id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                'activities.is_dynamic_score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activity_categories.name as category_name',
                'activities.photo_path',
                DB::raw('(SELECT COUNT(*) FROM activity_user WHERE activity_user.activity_id = activities.id) as participants_count')
            );

        if ($request->filled('status')) {
            $query->where('activities.status', $request->status);
        }

        $perPage = $request->input('per_page', 20);
        $activities = $query->orderBy('activities.created_at', 'desc')->paginate($perPage);

        return response()->json($activities);
    }

    /**
     * Получить данные для редактирования (для ментора)
     *
     * Ментор может получить данные только своего мероприятия.
     * Возвращает мероприятие с параметрами КСУ. (ResponseCalls broken)
     *
     * @group Activities (Mentor)
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     *
     * @queryParam user_id integer required ID ментора. Example: 5
     *
     * @response 200 {"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "is_dynamic_score": false, "status": "scheduled", "planet_id": 7, "academy_id": 0, "category_id": 1, "creator_id": 1, "photo_path": null, "parameters": null}
     * @response 403 scenario="Not owner" {"message": "У вас нет прав для просмотра этого мероприятия."}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     */
    public function mentorEdit(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $activity = Activity::with('parameters')->findOrFail($id);

        // Проверка, что ментор получает данные только своего мероприятия
        if ($activity->creator_id != $request->user_id) {
            return response()->json([
                'message' => 'У вас нет прав для просмотра этого мероприятия.',
            ], 403);
        }

        return response()->json($activity);
    }

    /**
     * Обновить мероприятие (для ментора)
     *
     * Ментор может редактировать только свои мероприятия (где он creator_id).
     * При is_dynamic_score=true поле score игнорируется, баллы пересчитываются автоматически.
     *
     * @group Activities (Mentor)
     *
     * @urlParam id integer required ID мероприятия для обновления. Example: 1
     *
     * @bodyParam title string required Название активности. Example: "Обновленный конкурс"
     * @bodyParam description string required Описание активности. Example: "Новое описание"
     * @bodyParam date_start string required Дата начала (формат: Y-m-d). Example: 2025-12-01
     * @bodyParam date_end string required Дата окончания (формат: Y-m-d). Example: 2025-12-02
     * @bodyParam score integer Количество очков (обязательно при is_dynamic_score=false). Example: 30
     * @bodyParam is_dynamic_score boolean required Режим автоматического расчета баллов. Example: false
     * @bodyParam parameters object Параметры КСУ (обязательно при is_dynamic_score=true). Example: {"activity_type":"external","has_places":true,"target_education_level_ids":[1,2]}
     * @bodyParam parameters.activity_type string Тип активности. Example: "external"
     * @bodyParam parameters.has_places boolean Призовые места. Example: true
     * @bodyParam parameters.has_implementation boolean Внедрение (для проектной). Example: true
     * @bodyParam parameters.is_leader boolean Лидер студ. лаборатории (для общественной). Example: false
     * @bodyParam parameters.target_education_level_ids integer[] nullable Один или два соседних по NUO уровня (например, [1,2] или [2]). Без него KSU=1.00 для всех. Example: [1, 2]
     * @bodyParam parameters.target_education_level_ids.* integer ID уровня обучения. Example: 1
     * @bodyParam status string required Статус. Example: started
     * @bodyParam planet_id integer required ID планеты. Example: 7
     * @bodyParam category_id integer required ID категории баллов. Example: 2
     * @bodyParam user_id integer required ID ментора (creator). Example: 5
     * @bodyParam file file optional Новое изображение (jpeg, jpg, png, максимум 2MB)
     *
     * @response 200 {"message": "Запись успешно обновлена!"}
     * @response 403 scenario="Not owner" {"message": "У вас нет прав для редактирования этого мероприятия."}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function mentorUpdate(ActivityRequest $activityRequest, int $id): JsonResponse
    {
        $activityRequest->validated();

        $activity = Activity::findOrFail($id);

        // Проверка, что ментор редактирует только своё мероприятие
        if ($activity->creator_id != $activityRequest->user_id) {
            return response()->json([
                'message' => 'У вас нет прав для редактирования этого мероприятия.',
            ], 403);
        }

        $activity->title = $activityRequest->title;
        $activity->description = $activityRequest->description;
        $activity->date_start = Carbon::parse($activityRequest->date_start)->format('Y-m-d');
        $activity->date_end = Carbon::parse($activityRequest->date_end)->format('Y-m-d');
        $activity->score = $activityRequest->score ?? 0;
        $activity->status = $activityRequest->status;
        $activity->planet_id = $activityRequest->planet_id;
        $activity->academy_id = $activityRequest->academy_id;
        $activity->category_id = $activityRequest->category_id;
        $activity->creator_id = $activityRequest->user_id;

        if (! is_null($activityRequest->file)) {
            $pathToFile = $activityRequest->file('file')->store(PublicUploadPath::activityCover(), 'public');
            $activity->photo_path = $pathToFile;
        }

        $activity->save();

        $this->handleDynamicScore($activity, $activityRequest);

        return response()->json(['message' => 'Запись успешно обновлена!']);
    }

    /**
     * Удаление мероприятия (для ментора)
     *
     * Ментор может удалять только свои мероприятия (где он creator_id)
     *
     * @group Activities (Mentor)
     *
     * @urlParam id integer required ID мероприятия для удаления. Example: 1
     *
     * @bodyParam user_id integer required ID ментора. Example: 5
     */
    public function mentorDestroy(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $activity = Activity::findOrFail($id);

        // Проверка, что ментор удаляет только своё мероприятие
        if ($activity->creator_id != $request->user_id) {
            return response()->json([
                'message' => 'У вас нет прав для удаления этого мероприятия.',
            ], 403);
        }

        $activity->delete();

        return response()->json(['message' => 'Запись успешно удалена!']);
    }

    /**
     * Получить список мероприятий
     *
     * Агрегированные данные с подзапросами. (ResponseCalls broken)
     *
     * @group Activities (Admin)
     *
     * @queryParam status string Фильтр по статусу. Допустимые значения: `draft`, `scheduled`, `started`, `completed`, `canceled`. Example: started
     * @queryParam category_id integer Фильтр по категории. Должен существовать в таблице activity_categories. Example: 3
     * @queryParam has_confirmed_participants boolean Фильтр по наличию подтвержденных участников. `1` - есть подтвержденные, `0` - нет. Example: true
     * @queryParam sort_by string Поле сортировки. Допустимые значения: `created_at`, `title`, `date_start`, `score`, `status`, `participants`. Example: date_start
     * @queryParam sort_direction string Направление сортировки. Допустимые значения: `asc`, `desc`. Example: desc
     * @queryParam per_page integer Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page": 1, "data": [{"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "is_dynamic_score": false, "status": "Запланировано", "status_eng": "scheduled", "planet_name": "IT", "category_name": "Проектная", "photo_path": null, "participants_count": 5, "confirmed_participants_count": 2}], "first_page_url": "...", "from": 1, "last_page": 1, "last_page_url": "...", "next_page_url": null, "path": "...", "per_page": 20, "prev_page_url": null, "to": 1, "total": 1}
     */
    public function index(Request $request): JsonResponse
    {
        // Валидация параметров
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'string', Rule::in(ActivityStatus::values())],
            'category_id' => 'sometimes|integer|exists:activity_categories,id',
            'has_confirmed_participants' => 'sometimes|in:true,false,1,0',
            'sort_by' => 'sometimes|string|in:created_at,title,date_start,score,status,participants',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Базовый запрос
        $query = Activity::query()
            ->join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->select(
                'activities.id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                'activities.is_dynamic_score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activity_categories.name as category_name',
                'activities.photo_path',
                DB::raw('(SELECT COUNT(*) FROM activity_user WHERE activity_user.activity_id = activities.id) as participants_count'),
                DB::raw('(SELECT COUNT(*) FROM activity_user WHERE activity_user.activity_id = activities.id AND activity_user.is_confirmed = true) as confirmed_participants_count')
            );

        // === ФИЛЬТРЫ ===

        // 1. Фильтр по статусу мероприятия
        if ($request->filled('status')) {
            $query->where('activities.status', $request->status);
        }

        // 2. Фильтр по категории
        if ($request->filled('category_id')) {
            $query->where('activities.category_id', $request->category_id);
        }

        // 3. Фильтр по наличию подтвержденных участников
        if ($request->has('has_confirmed_participants')) {
            $value = $request->input('has_confirmed_participants');
            $hasConfirmed = in_array($value, ['true', '1', 1, true], true);

            if ($hasConfirmed) {
                // Есть хотя бы один подтвержденный участник
                $query->whereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('activity_user')
                        ->whereColumn('activity_user.activity_id', 'activities.id')
                        ->where('activity_user.is_confirmed', true);
                });
            } else {
                // Нет подтвержденных участников
                $query->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('activity_user')
                        ->whereColumn('activity_user.activity_id', 'activities.id')
                        ->where('activity_user.is_confirmed', true);
                });
            }
        }

        // === СОРТИРОВКА ===

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'asc');

        if ($sortBy === 'participants') {
            $query->orderBy('participants_count', $sortDirection);
        } elseif ($sortBy === 'status') {
            // Сортировка по статусу: используем английское поле, чтобы избежать проблем с русским текстом из CASE
            $query->orderBy('activities.status', $sortDirection);

        } else {
            // Сортировка по прямым полям таблицы (created_at, title, date_start, score)
            $query->orderBy("activities.$sortBy", $sortDirection);
        }

        // Пагинация
        $perPage = $request->input('per_page', 20);
        $activities = $query->paginate($perPage);

        return response()->json($activities);
    }

    /**
     * Поиск мероприятий
     *
     * Агрегированные данные с JOIN. (ResponseCalls broken)
     *
     * @group Activities (Admin)
     *
     * @urlParam sortMethod string required Метод сортировки. Example: asc
     * @urlParam searchTxt string required Текст для поиска. Example: кон
     *
     * @response 200 {"current_page": 1, "data": [{"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "is_dynamic_score": false, "status": "Запланировано", "status_eng": "scheduled", "planet_name": "IT", "category_name": "Проектная", "photo_path": null, "participants_count": 5, "confirmed_participants_count": 2}], "first_page_url": "...", "from": 1, "last_page": 1, "last_page_url": "...", "next_page_url": null, "path": "...", "per_page": 20, "prev_page_url": null, "to": 1, "total": 1}
     */
    public function search(string $sortMethod, string $searchTxt): JsonResponse
    {
        $sortMethod = in_array(strtolower($sortMethod), ['asc', 'desc']) ? $sortMethod : 'asc';

        $activities = Activity::join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->where('activities.title', 'like', '%'.$searchTxt.'%')
            ->select(
                'activities.id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                'activities.is_dynamic_score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activity_categories.name as category_name',
                'activities.photo_path',
                DB::raw('(SELECT COUNT(*) FROM activity_user WHERE activity_user.activity_id = activities.id) as participants_count'),
                DB::raw('(SELECT COUNT(*) FROM activity_user WHERE activity_user.activity_id = activities.id AND activity_user.is_confirmed = true) as confirmed_participants_count')
            )
            ->orderBy('activities.id', $sortMethod)
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Получить мероприятия для студента
     *
     * Агрегированные данные с подзапросами. (ResponseCalls broken)
     *
     * @group Activities (Student)
     *
     * @queryParam status string optional Фильтр по статусу. Допустимые значения: `draft`, `scheduled`, `started`, `completed`, `canceled`. Example: started
     * @queryParam category_ids integer[] optional Фильтр по нескольким категориям. Example: [1, 2]
     * @queryParam category_ids.* integer ID категории. Example: 1
     * @queryParam search string optional Поиск по названию мероприятия. Example: олимпиада
     * @queryParam sort_by string optional Поле сортировки: `id`, `title`, `date_start`, `score`, `created_at`. По умолчанию: `id`. Example: date_start
     * @queryParam sort_method string optional Направление сортировки: `asc` или `desc`. По умолчанию: `desc`. Example: desc
     * @queryParam academy string optional Фильтр по академии: `all` или ID академии. По умолчанию: `all`. Example: all
     * @queryParam per_page integer optional Количество элементов на странице (1–100). Example: 20
     *
     * @response 200 {"current_page": 1, "data": [{"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "status": "Запланировано", "status_eng": "scheduled", "planet_name": "IT", "category_name": "Проектная", "photo_path": null, "is_registered": 0, "is_wishlist": 0}], "first_page_url": "...", "from": 1, "last_page": 1, "last_page_url": "...", "next_page_url": null, "path": "...", "per_page": 20, "prev_page_url": null, "to": 1, "total": 1}
     */
    public function getActivities(Request $request): JsonResponse
    {
        $academyFilter = (string) $request->query('academy', 'all');
        $sortMethod = strtolower((string) $request->query('sort_method', 'desc'));

        $validator = Validator::make(
            $request->all(),
            [
                'status' => ['sometimes', 'string', Rule::in(ActivityStatus::values())],
                'category_ids' => ['sometimes', 'array', 'min:1'],
                'category_ids.*' => ['integer', 'distinct', 'exists:activity_categories,id'],
                'search' => ['sometimes', 'string', 'max:255'],
                'sort_by' => ['sometimes', 'string', Rule::in(['id', 'title', 'date_start', 'score', 'created_at'])],
                'sort_method' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
                'academy' => ['sometimes', 'string', Rule::when(
                    $academyFilter !== 'all',
                    ['integer', 'exists:academies,id']
                )],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = $this->buildStudentActivitiesQuery((int) $request->user()->id);
        $this->applyStudentActivityFilters($query, [
            'status' => $request->query('status'),
            'category_ids' => $request->query('category_ids', []),
            'search' => $request->query('search'),
            'academy' => $academyFilter,
        ]);
        $this->applyStudentActivitiesSorting(
            $query,
            (string) $request->query('sort_by', 'id'),
            $sortMethod
        );

        $activities = $query->paginate((int) $request->query('per_page', 20));

        return response()->json($activities);
    }

    private function buildStudentActivitiesQuery(int $userId)
    {
        $userLevelId = \App\Models\Member::where('user_id', $userId)->value('education_level_id') ?? 0;

        return Activity::join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->leftJoin('activity_scores', function ($join) use ($userLevelId) {
                $join->on('activity_scores.activity_id', '=', 'activities.id')
                    ->where('activity_scores.education_level_id', '=', $userLevelId)
                    ->where('activity_scores.place', '=', 'participation');
            })
            ->select(
                'activities.id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                'activities.is_dynamic_score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activity_categories.name as category_name',
                'activities.photo_path',
                'activity_scores.final_score as user_personal_score'
            )
            ->selectRaw(
                'IF(EXISTS (
                    SELECT 1
                    FROM activity_user
                    WHERE activity_user.activity_id = activities.id
                    AND activity_user.user_id = ?
                ), 1, 0) AS is_registered',
                [$userId]
            )
            ->selectRaw(
                'IF(EXISTS (
                    SELECT 1
                    FROM wishlist_activity
                    WHERE wishlist_activity.activity_id = activities.id
                    AND wishlist_activity.user_id = ?
                ), 1, 0) AS is_wishlist',
                [$userId]
            );
    }

    private function applyStudentActivityFilters($query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('activities.status', $filters['status']);
        }

        if (! empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $query->whereIn('activities.category_id', array_map('intval', $filters['category_ids']));
        }

        if (! empty($filters['search'])) {
            $query->where('activities.title', 'like', '%'.$filters['search'].'%');
        }

        if (($filters['academy'] ?? 'all') !== 'all') {
            $query->where('activities.academy_id', (int) $filters['academy']);
        }
    }

    private function applyStudentActivitiesSorting($query, string $sortBy, string $sortMethod): void
    {
        $sortByMap = [
            'id' => 'activities.id',
            'title' => 'activities.title',
            'date_start' => 'activities.date_start',
            'score' => 'activities.score',
            'created_at' => 'activities.created_at',
        ];

        $query->orderBy($sortByMap[$sortBy] ?? 'activities.id', $sortMethod);
    }

    /**
     * Получить мероприятия
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     *
     * @urlParam filter string required Фильтр по статусу. Example: scheduled
     * @urlParam sortMethod string required Метод сортировки. Example: asc
     */
    public function getPublicActivities(string $filter, string $sortMethod): JsonResponse
    {
        $sortMethod = in_array(strtolower($sortMethod), ['asc', 'desc']) ? $sortMethod : 'asc';

        $activities = Activity::join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->where('activities.status', '=', $filter)
            ->select(
                'activities.id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activity_categories.name as category_name',
                'activities.photo_path'
            )
            ->orderBy('activities.id', $sortMethod)
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Создать новую активность
     *
     * Этот эндпоинт позволяет создать новую активность с возможностью загрузки изображения.
     * При is_dynamic_score=true поле score игнорируется, баллы рассчитываются автоматически.
     *
     * @group Activities (Admin)
     *
     * @bodyParam title string required Название активности. Example: "Конкурс по программированию"
     * @bodyParam description string required Описание активности. Example: "Соревнование по алгоритмам"
     * @bodyParam date_start string required Дата начала активности (формат: Y-m-d). Example: 2025-11-05
     * @bodyParam date_end string required Дата окончания активности (формат: Y-m-d). Example: 2025-11-06
     * @bodyParam score integer Количество очков (обязательно при is_dynamic_score=false). Example: 26
     * @bodyParam is_dynamic_score boolean required Режим автоматического расчета баллов. Example: false
     * @bodyParam parameters object Параметры КСУ (обязательно при is_dynamic_score=true). Example: {"activity_type":"external","has_places":true,"target_education_level_ids":[1,2]}
     * @bodyParam parameters.activity_type string Тип активности. Example: "external"
     * @bodyParam parameters.has_places boolean Призовые места. Example: true
     * @bodyParam parameters.has_implementation boolean Внедрение (для проектной). Example: true
     * @bodyParam parameters.is_leader boolean Лидер студ. лаборатории (для общественной). Example: false
     * @bodyParam parameters.target_education_level_ids integer[] nullable Один или два соседних по NUO уровня (например, [1,2] или [2]). Без него KSU=1.00 для всех. Example: [1, 2]
     * @bodyParam parameters.target_education_level_ids.* integer ID уровня обучения. Example: 1
     * @bodyParam status string required Статус активности. Example: scheduled
     * @bodyParam planet_id integer required ID планеты. Example: 7
     * @bodyParam category_id integer required ID категории баллов. Example: 1
     * @bodyParam user_id integer required ID создателя активности. Example: 1
     * @bodyParam file file optional Изображение для активности (jpeg, jpg, png, максимум 2MB)
     *
     * @response 200 {"message": "Запись успешно создана!", "activity_id": 123}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     * @response 500 {"message": "Произошла ошибка в процессе добавления новой записи. Пожалуйста, попробуйте позже."}
     */
    public function store(ActivityRequest $activityRequest): JsonResponse
    {
        $activityRequest->validated();

        $activity = new Activity;
        $activity->title = $activityRequest->title;
        $activity->description = $activityRequest->description;
        $activity->date_start = Carbon::parse($activityRequest->date_start)->format('Y-m-d');
        $activity->date_end = Carbon::parse($activityRequest->date_end)->format('Y-m-d');
        $activity->score = $activityRequest->score ?? 0;
        $activity->status = $activityRequest->status;
        $activity->planet_id = $activityRequest->planet_id;
        $activity->academy_id = $activityRequest->academy_id;
        $activity->category_id = $activityRequest->category_id;
        $activity->creator_id = $activityRequest->user_id;

        if (! is_null($activityRequest->file)) {
            $pathToFile = $activityRequest->file('file')->store(PublicUploadPath::activityCover(), 'public');
            $activity->photo_path = $pathToFile;
        }

        $activity->save();

        $this->handleDynamicScore($activity, $activityRequest);

        return response()->json([
            'message' => 'Запись успешно создана!',
            'activity_id' => $activity->id,
        ]);
    }

    /**
     * Просмотреть мероприятие
     *
     * Агрегированные данные с подзапросами. (ResponseCalls broken)
     *
     * @group Activities (Admin)
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     *
     * @response 200 {"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "is_dynamic_score": false, "status": "Запланировано", "status_eng": "scheduled", "planet_name": "IT", "category_name": "Проектная", "category_description": "", "photo_path": null}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     */
    public function show(int $id): JsonResponse
    {
        $activity = Activity::join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->with('files')
            ->select(
                'activities.id',
                'activities.creator_id',
                'activities.title',
                'activities.description',
                'activities.date_start',
                'activities.date_end',
                'activities.score',
                'activities.is_dynamic_score',
                DB::raw(ActivityStatus::caseSql('activities.status', 'status')),
                'activities.status as status_eng',
                'planets.name as planet_name',
                'activity_categories.name as category_name',
                'activity_categories.description as category_description',
                'activities.photo_path'
            )
            ->where('activities.id', '=', $id)
            ->first();

        return response()->json($activity);
    }

    /**
     * Получить данные для редактирования
     *
     * Возвращает мероприятие со связанными файлами и параметрами КСУ. (ResponseCalls broken)
     *
     * @group Activities (Admin)
     *
     * @urlParam id integer required ID мероприятия. Example: 1
     *
     * @response 200 {"id": 1, "title": "Конкурс", "description": "Описание", "date_start": "2025-11-05", "date_end": "2025-11-06", "score": 26, "is_dynamic_score": false, "status": "scheduled", "planet_id": 7, "academy_id": 0, "category_id": 1, "creator_id": 1, "photo_path": null, "files": [], "parameters": null}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     */
    public function edit(int $id): JsonResponse
    {
        $activity = Activity::with(['files', 'parameters'])->findOrFail($id);

        return response()->json($activity);
    }

    /**
     * Обновить мероприятие
     *
     * При is_dynamic_score=true поле score игнорируется, баллы пересчитываются автоматически.
     *
     * @group Activities (Admin)
     *
     * @urlParam id integer required ID мероприятия для обновления. Example: 1
     *
     * @bodyParam title string required Название активности. Example: "Обновленный конкурс"
     * @bodyParam description string required Описание активности. Example: "Новое описание"
     * @bodyParam date_start string required Дата начала (формат: Y-m-d). Example: 2025-12-01
     * @bodyParam date_end string required Дата окончания (формат: Y-m-d). Example: 2025-12-02
     * @bodyParam score integer Количество очков (обязательно при is_dynamic_score=false). Example: 30
     * @bodyParam is_dynamic_score boolean required Режим автоматического расчета баллов. Example: false
     * @bodyParam parameters object Параметры КСУ (обязательно при is_dynamic_score=true). Example: {"activity_type":"external","has_places":true,"target_education_level_ids":[1,2]}
     * @bodyParam parameters.activity_type string Тип активности. Example: "external"
     * @bodyParam parameters.has_places boolean Призовые места. Example: true
     * @bodyParam parameters.has_implementation boolean Внедрение (для проектной). Example: true
     * @bodyParam parameters.is_leader boolean Лидер студ. лаборатории (для общественной). Example: false
     * @bodyParam parameters.target_education_level_ids integer[] nullable Один или два соседних по NUO уровня (например, [1,2] или [2]). Без него KSU=1.00 для всех. Example: [1, 2]
     * @bodyParam parameters.target_education_level_ids.* integer ID уровня обучения. Example: 1
     * @bodyParam status string required Статус. Example: started
     * @bodyParam planet_id integer required ID планеты. Example: 7
     * @bodyParam category_id integer required ID категории баллов. Example: 2
     * @bodyParam user_id integer required ID создателя. Example: 1
     * @bodyParam file file optional Новое изображение (jpeg, jpg, png, максимум 2MB)
     *
     * @response 200 {"message": "Запись успешно обновлена!"}
     * @response 404 scenario="Not found" {"message": "Запись не найдена."}
     * @response 422 scenario="Validation error" {"message": "The given data was invalid."}
     */
    public function update(ActivityRequest $activityRequest, int $id): JsonResponse
    {
        $activityRequest->validated();

        $activity = Activity::findOrFail($id);
        $activity->title = $activityRequest->title;
        $activity->description = $activityRequest->description;
        $activity->date_start = Carbon::parse($activityRequest->date_start)->format('Y-m-d');
        $activity->date_end = Carbon::parse($activityRequest->date_end)->format('Y-m-d');
        $activity->score = $activityRequest->score ?? 0;
        $activity->status = $activityRequest->status;
        $activity->planet_id = $activityRequest->planet_id;
        $activity->academy_id = $activityRequest->academy_id;
        $activity->category_id = $activityRequest->category_id;
        $activity->creator_id = $activityRequest->user_id;

        if (! is_null($activityRequest->file)) {
            $pathToFile = $activityRequest->file('file')->store(PublicUploadPath::activityCover(), 'public');
            $activity->photo_path = $pathToFile;
        }

        $activity->save();

        $this->handleDynamicScore($activity, $activityRequest);

        return response()->json(['message' => 'Запись успешно обновлена!']);
    }

    /**
     * Удаление мероприятия
     *
     * Безвозвратно удаляет мероприятие по ID.
     *
     * @group Activities (Admin)
     *
     * @urlParam id integer required ID мероприятия для удаления. Example: 1
     *
     * @response 200 scenario="success" {"message": "Запись успешно удалена!"}
     * @response 404 scenario="not found" {"message": "Запись не найдена"}
     */
    public function destroy(int $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return response()->json(['message' => 'Запись успешно удалена!']);
    }

    /**
     * Нормализует список target ids из произвольного входа (массив, скаляр, строка).
     */
    private function normalizeTargetIds(mixed $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (! is_array($input)) {
            $input = [$input];
        }
        $ids = [];
        foreach ($input as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $ids[] = (int) $value;
        }
        return array_values(array_unique($ids));
    }

    /**
     * Обработать динамические параметры и пересчитать баллы при необходимости.
     */
    private function handleDynamicScore(Activity $activity, ActivityRequest $request): void
    {
        $isDynamic = (bool) $request->input('is_dynamic_score', false);
        $activity->is_dynamic_score = $isDynamic;

        if ($isDynamic) {
            $activity->score = 0;

            $params = $request->input('parameters', []);
            $targetIds = $this->normalizeTargetIds($params['target_education_level_ids'] ?? null);

            ActivityParameter::updateOrCreate(
                ['activity_id' => $activity->id],
                [
                    'activity_type' => $params['activity_type'] ?? null,
                    'has_places' => $params['has_places'] ?? false,
                    'has_implementation' => $params['has_implementation'] ?? null,
                    'is_leader' => $params['is_leader'] ?? false,
                    'target_education_level_ids' => $targetIds,
                ]
            );

            ScoreCalculationService::calculateAndStore($activity, $params, $targetIds);
        } else {
            ActivityParameter::where('activity_id', $activity->id)->delete();
            $activity->activityScores()->delete();
        }

        $activity->save();
    }
}
