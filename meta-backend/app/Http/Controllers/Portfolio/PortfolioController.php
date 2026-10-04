<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioRequest;
use App\Models\Activity;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\SemesterService;
use App\Support\PublicUploadPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Portfolio
 */
class PortfolioController extends Controller
{
    /**
     * Привязать портфолио к мероприятию
     *
     * @group Portfolio (Student)
     *
     * @bodyParam portfolio_id integer required ID портфолио Example: 1
     * @bodyParam activity_id integer required ID мероприятия Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Портфолио успешно привязано к мероприятию",
     *   "data": {
     *     "portfolio_id": 1,
     *     "activity_id": 5,
     *     "activity_title": "Конференция IT-специалистов"
     *   }
     * }
     */
    public function attachPortfolioToActivity(Request $request): JsonResponse
    {
        if (SemesterService::isScoringBlackout(now())) {
            return response()->json([
                'success' => false,
                'message' => 'Привязка портфолио временно недоступна в период подсчета семестра.',
            ], 422);
        }

        $validated = $request->validate([
            'portfolio_id' => 'required|integer|exists:portfolio,id',
            'activity_id' => 'required|integer|exists:activities,id',
        ]);

        DB::beginTransaction();

        try {
            $portfolio = Portfolio::findOrFail($validated['portfolio_id']);
            $activity = Activity::findOrFail($validated['activity_id']);

            //  БЕЗОПАСНОСТЬ: Проверка владельца портфолио
            if ($portfolio->user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не можете привязать чужое портфолио',
                ], 403);
            }

            //  БЕЗОПАСНОСТЬ: Проверка существующей привязки
            $alreadyAttached = $portfolio->activities()
                ->where('activity_id', $activity->id)
                ->exists();

            if ($alreadyAttached) {
                return response()->json([
                    'success' => false,
                    'message' => 'Это портфолио уже привязано к данному мероприятию',
                ], 422);
            }

            // Привязываем портфолио к мероприятию
            $portfolio->activities()->attach($activity->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Портфолио успешно привязано к мероприятию',
                'data' => [
                    'portfolio_id' => $portfolio->id,
                    'activity_id' => $activity->id,
                    'activity_title' => $activity->title,
                ],
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Отвязать портфолио от мероприятия
     *
     * @group Portfolio (Student)
     *
     * @bodyParam portfolio_id integer required ID портфолио Example: 1
     * @bodyParam activity_id integer required ID мероприятия Example: 5
     */
    public function detachPortfolioFromActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'portfolio_id' => 'required|integer|exists:portfolio,id',
            'activity_id' => 'required|integer|exists:activities,id',
        ]);

        $portfolio = Portfolio::findOrFail($validated['portfolio_id']);

        //  БЕЗОПАСНОСТЬ: Проверка владельца портфолио
        if ($portfolio->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не можете отвязать чужое портфолио',
            ], 403);
        }

        $detached = $portfolio->activities()->detach($validated['activity_id']);

        if ($detached === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Портфолио не было привязано к этому мероприятию',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Портфолио успешно отвязано от мероприятия',
        ], 200);
    }

    /**
     * Получить публичное портфолио
     *
     * @group Portfolio (Student)
     *
     * @unauthenticated
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     *
     * @queryParam sort_by string Поле для сортировки (created_at, title). По умолчанию created_at. Example: title
     * @queryParam sort_order string Порядок сортировки (asc, desc). По умолчанию desc. Example: desc
     * @queryParam month integer Месяц проведения мероприятия. Example: 9
     * @queryParam category_id integer Категория мероприятия. Example: 1
     * @queryParam semester_number integer Номер семестра (1 или 2). Example: 1
     * @queryParam semester_year integer Год мероприятия. С номером семестра фильтрует конкретный семестр, без номера — весь год. Example: 2024
     */
    public function publicIndex(int $user_id, Request $request): JsonResponse
    {
        if (! User::whereKey($user_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 404);
        }

        $validated = $request->validate([
            'sort_by' => ['nullable', 'string', 'in:created_at,title'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'category_id' => ['nullable', 'integer', 'exists:activity_categories,id'],
            'semester_number' => ['nullable', 'integer', 'in:1,2'],
            'semester_year' => ['required_with:semester_number', 'nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $month = $validated['month'] ?? null;
        $categoryId = $validated['category_id'] ?? null;
        $semesterNumber = $validated['semester_number'] ?? null;
        $semesterYear = $validated['semester_year'] ?? null;

        $query = Portfolio::where('user_id', $user_id);

        if ($categoryId !== null) {
            $query->whereHas('activities', function ($q) use ($categoryId) {
                $q->where('activities.category_id', $categoryId);
            });
        }

        if ($month !== null) {
            $query->whereMonth('portfolio.created_at', $month);
        }

        if ($semesterYear !== null) {
            if ($semesterNumber !== null) {
                $semester = SemesterService::getSemester((int) $semesterNumber, (int) $semesterYear);
                $query->whereBetween('portfolio.created_at', [$semester['start'], $semester['end']]);
            } else {
                $query->whereYear('portfolio.created_at', (int) $semesterYear);
            }
        }

        $activityWithFilter = function ($q) use ($categoryId) {
            $q->select('activities.id', 'activities.title', 'activities.date_start', 'activities.date_end', 'activities.category_id');
            if ($categoryId !== null) {
                $q->where('activities.category_id', $categoryId);
            }
        };

        $query->with(['activities' => $activityWithFilter]);

        $query->orderBy("portfolio.{$sortBy}", $sortOrder)
            ->orderBy('portfolio.id');

        $portfolios = $query->paginate(20);

        $formattedPortfolios = $portfolios->through(function ($portfolio) {
            return [
                'id' => $portfolio->id,
                'user_id' => $portfolio->user_id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'original_name' => $portfolio->original_name,
                'extension' => $portfolio->extension,
                'file_url' => $portfolio->file_url,
                'created_at' => $portfolio->created_at,
                'has_activities' => $portfolio->activities->count() > 0,
                'activities_count' => $portfolio->activities->count(),
                'activities' => $portfolio->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'title' => $activity->title,
                        'date_start' => $activity->date_start,
                        'date_end' => $activity->date_end,
                        'category_id' => $activity->category_id,
                    ];
                }),
            ];
        });

        return response()->json($formattedPortfolios);
    }

    /**
     * Создать новое портфолио
     *
     * @group Portfolio (Student)
     *
     * @bodyParam file file nullable Файл для загрузки в портфолио. Либо файл, либо ссылка (или оба) обязательны.
     * @bodyParam link string nullable Ссылка на работу/достижение. Либо файл, либо ссылка (или оба) обязательны.
     * @bodyParam title string required Название портфолио. Example: Сайт-визитка
     * @bodyParam description string nullable Описание файла или ссылки. Будет объединено со ссылкой, если она предоставлена. Example: Мое лучшее портфолио
     */
    public function store(PortfolioRequest $portfolioRequest): JsonResponse
    {
        if (SemesterService::isScoringBlackout(now())) {
            return response()->json([
                'success' => false,
                'message' => 'Загрузка работ в портфолио доступна только во время учебного семестра.',
            ], 422);
        }

        $validated = $portfolioRequest->validated();

        //  БЕЗОПАСНОСТЬ: user_id берется из токена авторизации
        $userId = Auth::id();

        $pathToFile = null;
        $originalName = null;
        $extension = null;

        if ($portfolioRequest->hasFile('file')) {
            $pathToFile = $portfolioRequest->file('file')->store(PublicUploadPath::portfolioFile(), 'public');
            $originalName = $portfolioRequest->file('file')->getClientOriginalName();
            $extension = $portfolioRequest->file('file')->getClientOriginalExtension();
        }

        $combinedDescription = null;
        if (! empty($validated['link'])) {
            $combinedDescription = 'LINK:'.$validated['link'];
            if (! empty($validated['description'])) {
                $combinedDescription .= "\n\n".$validated['description'];
            }
        } elseif (! empty($validated['description'])) {
            $combinedDescription = $validated['description'];
        }

        $portfolio = Portfolio::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'path_to_file' => $pathToFile,
            'original_name' => $originalName,
            'extension' => $extension,
            'description' => $combinedDescription, // Используем объединенное описание/ссылку
        ]);

        $responseData = [
            'portfolio_id' => $portfolio->id,
            'title' => $portfolio->title,
        ];

        if ($portfolio->original_name) {
            $responseData['original_name'] = $portfolio->original_name;
        }

        return response()->json([
            'success' => true,
            'message' => 'Файл успешно загружен!',
            'data' => $responseData,
        ], 201);
    }

    /**
     * Удалить портфолио
     *
     * @group Portfolio (Student)
     *
     * @urlParam id integer required ID портфолио Example: 1
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $portfolio = Portfolio::findOrFail($id);

            //  БЕЗОПАСНОСТЬ: Проверка владельца портфолио
            if ($portfolio->user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не можете удалить чужое портфолио',
                ], 403);
            }

            // Удаляем запись из БД
            $portfolio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно удален!',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Портфолио не найдено',
            ], 404);
        }
    }

    //  АДМИНИСТРАТИВНЫЕ ФУНКЦИИ (только для админов)

    /**
     * Получить все портфолио с фильтрацией и поиском
     *
     * @group Portfolio (Admin)
     *
     * @bodyParam user_id integer Фильтр по ID пользователя. Example: 2
     * @bodyParam search string Поиск по названию или описанию. Example: Сайт
     * @bodyParam extension string Фильтр по типу файла. Example: pdf
     * @bodyParam date_from string Фильтр по дате создания (от). Example: 2024-01-01
     * @bodyParam date_to string Фильтр по дате создания (до). Example: 2024-12-31
     * @bodyParam per_page integer Количество записей на странице. Example: 20
     * @bodyParam sort_by string Поле для сортировки. Example: created_at
     * @bodyParam sort_order string Порядок сортировки. Example: desc
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:created_at,title,user_id,id',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        $query = Portfolio::query();

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['search']) && $validated['search'] !== '') {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        if (isset($validated['extension'])) {
            $query->where('extension', $validated['extension']);
        }

        if (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $validated['per_page'] ?? 20;

        $portfolios = $query->with([
            'user:id,name,email',
            'activities:id,title,date_start,date_end',
        ])->paginate($perPage);

        $formattedData = $portfolios->through(function ($portfolio) {
            return [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'original_name' => $portfolio->original_name,
                'extension' => $portfolio->extension,
                'file_url' => $portfolio->file_url,
                'created_at' => $portfolio->created_at,
                'user' => [
                    'id' => $portfolio->user->id,
                    'name' => $portfolio->user->name ?? 'Без имени',
                    'email' => $portfolio->user->email,
                ],
                'activities_count' => $portfolio->activities->count(),
                'activities' => $portfolio->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'title' => $activity->title,
                        'date_start' => $activity->date_start,
                        'date_end' => $activity->date_end,
                    ];
                }),
            ];
        });

        return response()->json($formattedData);
    }

    /**
     * Просмотр конкретного портфолио
     *
     * @group Portfolio (Admin)
     *
     * @urlParam id integer required ID портфолио Example: 1
     */
    public function adminShow(int $id): JsonResponse
    {
        try {

            $portfolio = Portfolio::with([
                'user:id,name,email',
                'activities',
            ])->findOrFail($id);

            $formattedPortfolio = [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'original_name' => $portfolio->original_name,
                'extension' => $portfolio->extension,
                'file_url' => $portfolio->file_url,
                'file_path' => $portfolio->path_to_file,
                'created_at' => $portfolio->created_at,
                'updated_at' => $portfolio->updated_at,
                'user' => [
                    'id' => $portfolio->user->id,
                    'name' => $portfolio->user->name ?? 'Без имени',
                    'email' => $portfolio->user->email,
                ],
                'activities' => $portfolio->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'title' => $activity->title,
                        'date_start' => $activity->date_start,
                        'date_end' => $activity->date_end,
                        'location' => $activity->location ?? null,
                    ];
                }),
                'activities_count' => $portfolio->activities->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedPortfolio,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Портфолио не найдено',
            ], 404);
        }
    }

    /**
     * Удалить портфолио администратором
     *
     * @group Portfolio (Admin)
     *
     * @urlParam id integer required ID портфолио Example: 1
     */
    public function adminDestroy(int $id): JsonResponse
    {
        try {
            $portfolio = Portfolio::with('user:id,name')->findOrFail($id);

            $portfolioInfo = [
                'id' => $portfolio->id,
                'user_id' => $portfolio->user_id,
                'user_name' => $portfolio->user->name ?? 'Неизвестный пользователь',
                'title' => $portfolio->title,
                'path' => $portfolio->path_to_file,
            ];

            $portfolio->delete();

            \Log::info('Admin deleted portfolio', [
                'admin_id' => Auth::id(),
                'portfolio' => $portfolioInfo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Портфолио успешно удалено администратором',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Портфолио не найдено',
            ], 404);
        }
    }
}
