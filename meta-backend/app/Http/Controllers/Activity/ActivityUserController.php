<?php

namespace App\Http\Controllers\Activity;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityScore;
use App\Models\ActivityUser;
use App\Models\Member;
use App\Models\User;
use App\Services\SemesterService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @group Activities
 */
class ActivityUserController extends Controller
{
    /**
     * Найти студентов для ручного добавления в мероприятие.
     *
     * @group Activities (Admin)
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     * @queryParam search string Поиск по ФИО, группе или email. Example: Иванов
     */
    public function participantCandidates(Request $request, int $activity_id): JsonResponse
    {
        Activity::findOrFail($activity_id);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = User::query()
            ->join('members', 'users.id', '=', 'members.user_id')
            ->leftJoin('activity_user as selected_activity_user', function ($join) use ($activity_id) {
                $join->on('selected_activity_user.user_id', '=', 'users.id')
                    ->where('selected_activity_user.activity_id', '=', $activity_id);
            })
            ->where('users.role', 'student')
            ->where('users.is_activated', 1)
            ->select(
                'users.id as user_id',
                'users.email',
                'users.is_activated',
                DB::raw("CONCAT_WS(' ', members.last_name, members.first_name, members.middle_name) as fio"),
                'members.student_group as group',
                DB::raw('selected_activity_user.id IS NOT NULL as is_participant')
            );

        $query->searchByKeyword($validated['search'] ?? null);

        $students = $query
            ->orderBy('members.last_name')
            ->orderBy('members.first_name')
            ->limit((int) ($validated['limit'] ?? 20))
            ->get()
            ->map(fn ($student) => [
                'user_id' => (int) $student->user_id,
                'fio' => $student->fio,
                'group' => $student->group,
                'email' => $student->email,
                'is_activated' => (bool) $student->is_activated,
                'is_participant' => (bool) $student->is_participant,
            ]);

        return response()->json(['data' => $students]);
    }

    /**
     * Добавить студента в участники мероприятия.
     *
     * Повторное добавление возвращает успешный ответ без создания дубля.
     *
     * @group Activities (Admin)
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     * @bodyParam user_id integer required ID студента. Example: 2
     */
    public function storeParticipant(Request $request, int $activity_id): JsonResponse
    {
        Activity::findOrFail($activity_id);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::where('id', $validated['user_id'])
            ->where('role', 'student')
            ->first();

        if (! $student) {
            return response()->json([
                'message' => 'В участники можно добавить только студента.',
            ], 422);
        }

        if (! $student->is_activated) {
            return response()->json([
                'message' => 'Студент не активирован.',
            ], 422);
        }

        $participant = ActivityUser::where('activity_id', $activity_id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($participant) {
            return response()->json([
                'message' => 'Студент уже добавлен в участники мероприятия.',
                'added' => false,
                'participant_id' => $participant->id,
            ]);
        }

        $participant = ActivityUser::create([
            'activity_id' => $activity_id,
            'user_id' => $validated['user_id'],
        ]);

        return response()->json([
            'message' => 'Студент добавлен в участники мероприятия.',
            'added' => true,
            'participant_id' => $participant->id,
        ]);
    }

    /**
     * Скачать шаблон Excel для массового добавления участников.
     *
     * @group Activities (Admin)
     */
    public function participantsImportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Участники');
        $sheet->fromArray(['ФИО', 'Группа'], null, 'A1');
        $sheet->fromArray(['Иванов Иван Иванович', 'К1И'], null, 'A2');
        $sheet->fromArray(['Петрова Мария', ''], null, 'A3');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(16);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'activity-participants-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Проверить Excel перед массовым добавлением участников.
     *
     * @group Activities (Admin)
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     * @bodyParam file file required Excel-файл по шаблону.
     */
    public function previewParticipantsImport(Request $request, int $activity_id): JsonResponse
    {
        Activity::findOrFail($activity_id);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $rows = $this->readParticipantsImportRows($request->file('file'));
        $results = collect($rows)
            ->map(fn (array $row) => $this->resolveParticipantsImportRow($row, $activity_id))
            ->values();

        return response()->json([
            'summary' => [
                'total_rows' => $results->count(),
                'ready_count' => $results->where('status', 'ready')->count(),
                'already_exists_count' => $results->where('status', 'already_exists')->count(),
                'ambiguous_count' => $results->where('status', 'ambiguous')->count(),
                'error_count' => $results->where('status', 'error')->count(),
            ],
            'rows' => $results,
        ]);
    }

    /**
     * Добавить выбранных после проверки Excel пользователей в участники.
     *
     * @group Activities (Admin)
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     * @bodyParam user_ids int[] required ID студентов из результата проверки.
     */
    public function importParticipants(Request $request, int $activity_id): JsonResponse
    {
        Activity::findOrFail($activity_id);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = collect($validated['user_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $studentIds = User::whereIn('id', $userIds)
            ->where('role', 'student')
            ->where('is_activated', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($studentIds->count() !== $userIds->count()) {
            return response()->json([
                'message' => 'В участники можно добавить только активированных студентов.',
            ], 422);
        }

        $addedCount = 0;
        $skippedCount = 0;

        foreach ($studentIds as $userId) {
            $participant = ActivityUser::firstOrCreate([
                'activity_id' => $activity_id,
                'user_id' => $userId,
            ]);

            if ($participant->wasRecentlyCreated) {
                $addedCount++;
            } else {
                $skippedCount++;
            }
        }

        return response()->json([
            'message' => "Добавлено: {$addedCount}, уже были участниками: {$skippedCount}.",
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * @return list<array{row:int,fio:string,group:string}>
     */
    private function readParticipantsImportRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        for ($rowNumber = 2; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
            $fio = trim((string) $sheet->getCell("A{$rowNumber}")->getFormattedValue());
            $group = trim((string) $sheet->getCell("B{$rowNumber}")->getFormattedValue());

            if ($fio === '' && $group === '') {
                continue;
            }

            $rows[] = [
                'row' => $rowNumber,
                'fio' => $fio,
                'group' => $group,
            ];
        }

        return $rows;
    }

    /**
     * @param  array{row:int,fio:string,group:string}  $row
     * @return array<string, mixed>
     */
    private function resolveParticipantsImportRow(array $row, int $activity_id): array
    {
        $base = [
            'row' => $row['row'],
            'fio' => $row['fio'],
            'group' => $row['group'],
            'user' => null,
        ];

        $nameParts = preg_split('/\s+/u', $row['fio']) ?: [];
        $nameParts = array_values(array_filter($nameParts, fn ($p) => $p !== ''));

        if (count($nameParts) < 2) {
            return $base + [
                'status' => 'error',
                'message' => 'Укажите ФИО минимум из фамилии и имени.',
            ];
        }

        $lastName = $nameParts[0];
        $firstName = $nameParts[1];
        $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 2)) : null;

        // Шаг 1: ищем по фамилии + имя
        $matches = $this->studentImportQuery()
            ->where('members.last_name', $lastName)
            ->where('members.first_name', $firstName)
            ->get();

        // Шаг 2: если несколько и есть отчество — уточняем
        if ($matches->count() > 1 && $middleName !== null) {
            $narrowed = $matches->filter(
                fn ($s) => mb_strtolower(trim((string) $s->middle_name)) === mb_strtolower($middleName)
            );
            if ($narrowed->count() >= 1) {
                $matches = $narrowed;
            }
        }

        // Шаг 3: если всё ещё несколько и есть группа — уточняем
        if ($matches->count() > 1 && $row['group'] !== '') {
            $narrowed = $matches->filter(
                fn ($s) => mb_strtolower(trim((string) $s->group)) === mb_strtolower($row['group'])
            );
            if ($narrowed->count() >= 1) {
                $matches = $narrowed;
            }
        }

        if ($matches->isEmpty()) {
            $hint = $row['group'] !== '' ? ", группа {$row['group']}" : '';

            return $base + [
                'status' => 'error',
                'message' => "Студент не найден: {$row['fio']}{$hint}.",
            ];
        }

        if ($matches->count() > 1) {
            $count = $matches->count();
            $groups = $matches->pluck('group')->toArray();

            // Все группы различны → можно выбрать конкретного
            if (count(array_unique($groups)) === $count) {
                return array_merge($base, [
                    'status' => 'ambiguous',
                    'message' => "Найдено {$count} студента с таким ФИО, группы разные. Выберите нужного.",
                    'candidates' => $matches->map(fn ($s) => [
                        'user_id' => (int) $s->user_id,
                        'fio' => $s->fio,
                        'group' => $s->group,
                    ])->values()->all(),
                ]);
            }

            return $base + [
                'status' => 'error',
                'message' => "Найдено {$count} студента с такими данными: {$row['fio']}. Уточните данные или добавьте вручную через поиск.",
            ];
        }

        $student = $matches->first();
        $user = [
            'user_id' => (int) $student->user_id,
            'fio' => $student->fio,
            'group' => $student->group,
            'email' => $student->email,
        ];

        $alreadyExists = ActivityUser::where('activity_id', $activity_id)
            ->where('user_id', $student->user_id)
            ->exists();

        return array_merge($base, [
            'status' => $alreadyExists ? 'already_exists' : 'ready',
            'message' => $alreadyExists
                ? 'Студент уже есть в участниках мероприятия.'
                : 'Готов к добавлению.',
            'user' => $user,
        ]);
    }

    private function studentImportQuery()
    {
        return User::query()
            ->join('members', 'users.id', '=', 'members.user_id')
            ->where('users.role', 'student')
            ->where('users.is_activated', 1)
            ->select(
                'users.id as user_id',
                'users.email',
                DB::raw("CONCAT_WS(' ', members.last_name, members.first_name, members.middle_name) as fio"),
                'members.student_group as group',
                'members.middle_name'
            );
    }


    /**
     * Получить список участников мероприятия
     *
     * Возвращает список пользователей, зарегистрированных на конкретное мероприятие.
     *
     * @group Activities (Admin)
     *
     * @urlParam activity_id integer required ID мероприятия. Example: 1
     */
    public function index(Request $request, int $activity_id)
    {
        Activity::findOrFail($activity_id);

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = User::join('activity_user', 'users.id', '=', 'activity_user.user_id')
            ->join('members', 'users.id', '=', 'members.user_id')
            ->where('activity_user.activity_id', '=', $activity_id)
            ->select(
                'activity_user.id',
                'activity_user.user_id',
                DB::raw("CONCAT_WS(' ', members.last_name, members.first_name, members.middle_name) as fio"),
                'members.student_group as group',
                'activity_user.is_confirmed',
                'activity_user.level',
                'activity_user.place',
                'activity_user.earned_score',
                DB::raw("IF(EXISTS (
                    SELECT 1
                    FROM activity_portfolio
                    JOIN portfolio ON activity_portfolio.portfolio_id = portfolio.id
                    WHERE activity_portfolio.activity_id = {$activity_id}
                    AND portfolio.user_id = activity_user.user_id
                ), 'yes', 'no') AS attached_portfolio")
            );

        $query->searchByKeyword($validated['search'] ?? null);

        $listOfUsers = $query->get();

        return response()->json($listOfUsers);
    }

    /**
     * Получить мои мероприятия
     *
     * Возвращает список мероприятий, на которые зарегистрирован пользователь.
     *
     * @group Activities (Student)
     *
     * @urlParam user_id integer required ID пользователя. Example: 2
     */
    public function getMyActivities(int $user_id)
    {
        $userLevelId = \App\Models\Member::where('user_id', $user_id)->value('education_level_id') ?? 0;

        $activities = ActivityUser::join('activities', 'activity_user.activity_id', '=', 'activities.id')
            ->join('planets', 'activities.planet_id', '=', 'planets.id')
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->leftJoin('activity_scores', function ($join) use ($userLevelId) {
                $join->on('activity_scores.activity_id', '=', 'activities.id')
                    ->where('activity_scores.education_level_id', '=', $userLevelId)
                    ->where('activity_scores.place', '=', 'participation');
            })
            ->where('activity_user.user_id', '=', $user_id)
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
                'activity_scores.final_score as user_personal_score',
                'activity_user.earned_score',
                'activity_user.place',
                DB::raw("IF(EXISTS (
                    SELECT 1
                    FROM wishlist_activity
                    WHERE wishlist_activity.activity_id = activities.id
                    AND wishlist_activity.user_id = $user_id
                ), 1, 0) AS is_wishlist")
            )
            ->orderBy('activities.date_start', 'desc')
            ->orderBy('activities.id', 'desc')
            ->paginate(20);

        return response()->json($activities);
    }

    /**
     * Регистрация на мероприятие
     *
     * Позволяет пользователю зарегистрироваться на мероприятие.
     *
     * @group Activities (Student)
     *
     * @bodyParam user_id integer required ID пользователя. Example: 2
     * @bodyParam activity_id integer required ID мероприятия. Example: 1
     *
     * @response 200 {
     *   "message": "Вы успешно зарегистрировались на мероприятие!"
     * }
     * @response 409 {
     *   "message": "Вы уже зарегистрированы на это мероприятие."
     * }
     * @response 500 {
     *   "message": "Произошла ошибка в процессе регистрации на мероприятие. Пожалуйста, попробуйте позже."
     * }
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'activity_id' => 'required|integer|exists:activities,id',
        ]);

        try {
            ActivityUser::create([
                'user_id' => $validated['user_id'],
                'activity_id' => $validated['activity_id'],
            ]);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? '';
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            if ($sqlState === '23000' && ($driverCode === 1062 || $driverCode === 19)) {
                return response()->json([
                    'message' => 'Вы уже зарегистрированы на это мероприятие.',
                ], 409);
            }

            throw $e;
        }

        return response()->json(['message' => 'Вы успешно зарегистрировались на мероприятие!']);
    }

    /**
     * Отмена регистрации на мероприятие
     *
     * Позволяет пользователю отменить регистрацию на мероприятие.
     *
     * @group Activities (Student)
     *
     * @bodyParam user_id integer required ID пользователя. Example: 2
     * @bodyParam activity_id integer required ID мероприятия. Example: 1
     *
     * @response 200 {
     *   "message": "Вы успешно отменили регистрацию на мероприятие!"
     * }
     * @response 500 {
     *   "message": "Произошла ошибка в процессе отмены регистрации на мероприятие. Пожалуйста, попробуйте позже."
     * }
     */
    public function undo(Request $request)
    {
        $activityUser = ActivityUser::where('user_id', '=', $request->user_id)
            ->where('activity_id', '=', $request->activity_id);
        $activityUser->delete();

        return response()->json(['message' => 'Вы успешно отменили регистрацию на мероприятие!']);
    }

    /**
     * Подтверждение участия в мероприятии
     *
     * Этот эндпоинт позволяет подтвердить участие пользователя в мероприятии и установить уровень участия.
     *
     * @group Activities (Admin)
     *
     * @bodyParam id integer required ID записи участия (activity_user). Example: 1
     * @bodyParam level numeric required Уровень участия (коэффициент). Example: 0.4
     *
     * @response 200 {
     *   "message": "Участие участника успешно подтверждено!"
     * }
     * @response 200 {
     *   "message": "Вы уже подтвердили участие участника!"
     * }
     */
    public function confirmation(Request $request)
    {
        if (SemesterService::isScoringBlackout(now())) {
            return response()->json([
                'message' => 'Подтверждение баллов временно недоступно в период подсчета семестра.',
            ], 422);
        }

        $validated = $request->validate([
            'id' => 'required|integer|exists:activity_user,id',
            'level' => 'required|in:0.4,0.6,0.8,1.0',
        ]);

        $activityUser = ActivityUser::with('activity')->findOrFail($validated['id']);



        if ($activityUser->is_confirmed) {
            return response()->json(['message' => 'Вы уже подтвердили участие участника!']);
        }

        $activityUser->level = $validated['level'];
        $activityUser->is_confirmed = true;
        $activityUser->save();

        return response()->json(['message' => 'Участие участника успешно подтверждено!']);
    }

    /**
     * Массовое подтверждение участия в мероприятии
     *
     * Позволяет массово подтвердить участие всех неподтверждённых студентов мероприятия.
     *
     * @group Activities (Admin)
     *
     * @bodyParam activity_id integer required ID мероприятия. Example: 1
     * @bodyParam level numeric required Уровень участия (коэффициент). Example: 0.4
     * @bodyParam only_with_portfolio boolean required Только студенты с прикреплённым портфолио. Example: true
     *
     * @response 200 {
     *   "message": "Участие 12 студентов успешно подтверждено!"
     * }
     */
    public function bulkConfirmation(Request $request)
    {
        if (SemesterService::isScoringBlackout(now())) {
            return response()->json([
                'message' => 'Подтверждение баллов временно недоступно в период подсчета семестра.',
            ], 422);
        }

        $validated = $request->validate([
            'activity_id' => 'required|integer|exists:activities,id',
            'level' => 'required|in:0.4,0.6,0.8,1.0',
            'only_with_portfolio' => 'boolean',
        ]);

        $activity = Activity::findOrFail($validated['activity_id']);

        $query = ActivityUser::where('activity_id', $activity->id)
            ->where('is_confirmed', false);

        if ($validated['only_with_portfolio'] ?? false) {
            $query->whereExists(function ($query) use ($activity) {
                $query->select(DB::raw(1))
                    ->from('activity_portfolio')
                    ->join('portfolio', 'activity_portfolio.portfolio_id', '=', 'portfolio.id')
                    ->whereColumn('portfolio.user_id', 'activity_user.user_id')
                    ->where('activity_portfolio.activity_id', $activity->id);
            });
        }

        if (! $activity->is_dynamic_score) {
            $count = $query->update([
                'level' => $validated['level'],
                'is_confirmed' => true,
            ]);

            return response()->json([
                'message' => "Участие {$count} студентов успешно подтверждено!",
            ]);
        }

        $place = $this->bulkConfirmationPlace($activity, $validated['level']);

        $activityUsers = $query->with('user.member')->get();

        if ($activityUsers->isEmpty()) {
            return response()->json([
                'message' => 'Участие 0 студентов успешно подтверждено!',
            ]);
        }

        $scores = ActivityScore::where('activity_id', $activity->id)
            ->where('place', $place)
            ->get()
            ->keyBy('education_level_id');

        if ($scores->isEmpty()) {
            return response()->json([
                'message' => 'Баллы для данного места не найдены. Проверьте расчет КСУ для мероприятия.',
            ], 422);
        }

        $count = 0;
        $confirmedLevel = $this->levelForPlace($place);

        foreach ($activityUsers as $activityUser) {
            $member = $activityUser->user->member ?? null;

            if (! $member || ! $member->education_level_id) {
                continue;
            }

            $activityScore = $scores->get($member->education_level_id);

            if (! $activityScore) {
                continue;
            }

            $activityUser->place = $place;
            $activityUser->earned_score = $activityScore->final_score;
            $activityUser->level = $confirmedLevel;
            $activityUser->is_confirmed = true;
            $activityUser->save();

            $count++;
        }

        if ($count === 0) {
            return response()->json([
                'message' => 'Не удалось подтвердить участников: отсутствуют уровни обучения или баллы КСУ для их уровней.',
            ], 422);
        }

        return response()->json([
            'message' => "Участие {$count} студентов успешно подтверждено!",
        ]);
    }

    private function bulkConfirmationPlace(Activity $activity, string $level): string
    {
        if (! (bool) ($activity->parameters?->has_places ?? false)) {
            return 'participation';
        }

        return match ((string) $level) {
            '1.0' => '1st',
            '0.8' => '2nd',
            '0.6' => '3rd',
            default => 'participation',
        };
    }

    private function levelForPlace(string $place): float
    {
        return match ($place) {
            '1st' => 1.0,
            '2nd' => 0.8,
            '3rd' => 0.6,
            default => 0.4,
        };
    }

    /**
     * Подтвердить участие в мероприятии с динамическими баллами (КСУ).
     *
     * @group Activities
     *
     * @bodyParam id integer required ID записи участия (activity_user). Example: 1
     * @bodyParam place string required Место (1st, 2nd, 3rd, participation). Example: "1st"
     *
     * @response 200 {"message": "Участие участника успешно подтверждено!"}
     * @response 422 scenario="Already confirmed" {"message": "Вы уже подтвердили участие участника!"}
     * @response 422 scenario="Not KSU" {"message": "Мероприятие не использует КСУ. Используйте обычное подтверждение."}
     * @response 422 scenario="No education level" {"message": "У участника не указан уровень обучения."}
     * @response 422 scenario="Score not found" {"message": "Баллы для данного уровня обучения и места не найдены."}
     * @response 422 scenario="Blackout" {"message": "Подтверждение баллов временно недоступно в период подсчета семестра."}
     */
    public function confirmationKsu(Request $request)
    {
        if (SemesterService::isScoringBlackout(now())) {
            return response()->json([
                'message' => 'Подтверждение баллов временно недоступно в период подсчета семестра.',
            ], 422);
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:activity_user,id'],
            'place' => ['required', 'string', 'in:1st,2nd,3rd,participation'],
        ]);

        $activityUser = ActivityUser::with(['activity', 'user.member'])->findOrFail($validated['id']);



        if ($activityUser->is_confirmed) {
            return response()->json(['message' => 'Вы уже подтвердили участие участника!']);
        }

        $activity = $activityUser->activity;

        if (! $activity->is_dynamic_score) {
            return response()->json([
                'message' => 'Мероприятие не использует КСУ. Используйте обычное подтверждение.',
            ], 422);
        }

        $member = $activityUser->user->member;

        if (! $member || ! $member->education_level_id) {
            return response()->json([
                'message' => 'У участника не указан уровень обучения.',
            ], 422);
        }

        $activityScore = ActivityScore::where('activity_id', $activity->id)
            ->where('education_level_id', $member->education_level_id)
            ->where('place', $validated['place'])
            ->first();

        if (! $activityScore) {
            return response()->json([
                'message' => 'Баллы для данного уровня обучения и места не найдены.',
            ], 422);
        }

        $activityUser->place = $validated['place'];
        $activityUser->earned_score = $activityScore->final_score;
        $activityUser->level = match ($validated['place']) {
            '1st' => 1.0,
            '2nd' => 0.8,
            '3rd' => 0.6,
            'participation' => 0.4,
        };
        $activityUser->is_confirmed = true;
        $activityUser->save();

        return response()->json(['message' => 'Участие участника успешно подтверждено!']);
    }
}
