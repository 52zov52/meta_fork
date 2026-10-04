<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Models\EducationLevel;
use App\Models\Member;
use App\Models\User;
use App\Services\AcademyService;
use App\Support\PublicUploadPath;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * @group Users
 */
class MemberController extends Controller
{
    /**
     * Получить список участников
     *
     * @group Users (Admin)
     *
     * @queryParam sortBy string Поле сортировки.
     *  `date_reg`, `fio`, `role`, `status`. By default: `date_reg` Example: fio
     * @queryParam sortMethod string Направление сортировки.
     *  `asc`, `desc`. By default: `desc`. Example: asc
     * @queryParam role string Фильтр по роли.
     *  `student`, `mentor`, `moderator`, `admin`. Example: student
     * @queryParam status boolean Фильтр по статусу аккаунта.
     *  `1` - активирован, `0` - не активирован. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $sortBy = $request->input('sortBy', 'date_reg');
        $sortMethod = $request->input('sortMethod', 'desc');
        $role = $request->input('role');
        $status = $request->input('status');

        $query = Member::join('users', 'members.user_id', '=', 'users.id')
            ->select(
                'members.id',
                'members.user_id',
                'members.profile_photo_path',
                'users.is_activated',
                'users.role',
                'users.created_at as registration_date',
                DB::raw("CONCAT_WS(' ', members.last_name, members.first_name, members.middle_name) as fio"),
                DB::raw("CASE
                    WHEN users.role = 'admin' THEN 'Админ'
                    WHEN users.role = 'moderator' THEN 'Модератор'
                    WHEN users.role = 'mentor' THEN 'Наставник'
                    ELSE 'Студент' END as role_label")
            )
            ->when($role, fn ($q) => $q->where('users.role', $role))
            ->when(! is_null($status), fn ($q) => $q->where('users.is_activated', $status));

        match ($sortBy) {
            'fio' => $query->orderBy('members.last_name', $sortMethod)
                ->orderBy('members.first_name', $sortMethod),
            'role' => $query->orderBy('users.role', $sortMethod),
            'status' => $query->orderBy('users.is_activated', $sortMethod),
            default => $query->orderBy('users.created_at', $sortMethod),
        };

        return response()->json($query->paginate(20));
    }

    /**
     * Поиск участников по ФИО
     *
     * Ищет участников по строке в ФИО (last_name/first_name/middle_name) и возвращает пагинированный список.
     *
     * @group Users (Admin)
     *
     * @urlParam sortMethod string required Направление сортировки по id: asc|desc. Example: desc
     * @urlParam searchTxt string required Строка поиска по ФИО. Example: Иван
     */
    public function search(string $sortMethod, string $searchTxt): JsonResponse
    {
        $members = Member::join('users', 'members.user_id', '=', 'users.id')
            ->having('fio', 'like', '%'.$searchTxt.'%')
            ->select(
                'members.id',
                DB::raw("CONCAT_WS(' ', members.last_name, members.first_name, members.middle_name) as fio"),
                'members.gender',
                'members.profile_photo_path',
                'members.user_id',
                DB::raw("CASE
                    WHEN users.role = 'admin' THEN 'Админ'
                    WHEN users.role = 'moderator' THEN 'Модератор'
                    WHEN users.role = 'mentor' THEN 'Наставник'
                    ELSE 'Студент' END as role"),
                'users.is_activated'
            )
            ->orderBy('id', $sortMethod)
            ->paginate(20);

        return response()->json($members);
    }

    /**
     * Создать участника (пользователя)
     *
     * Создаёт пользователя (users) и связанного участника (members).
     * Опционально загружает фото профиля.
     * Для role=student и заполненной группы автоматически определяет academy_id и education_level_id.
     *
     * @group Users (Admin)
     *
     * @bodyParam last_name string required Фамилия. Example: Иванов
     * @bodyParam first_name string required Имя. Example: Иван
     * @bodyParam middle_name string Отчество. Example: Иванович
     * @bodyParam gender int required Пол (0/1). Example: 0
     * @bodyParam birthday string Дата рождения (Y-m-d). Example: 2004-01-15
     * @bodyParam region_id int required ID региона. Example: 1
     * @bodyParam planet_id int required ID направления/планеты. Example: 2
     * @bodyParam institution_id int required Тип учреждения. Example: 1
     * @bodyParam institution string Учреждение (обязательно только при institution_id = 4). Example: "ИУБиП"
     * @bodyParam course int Курс/класс. Example: 2
     * @bodyParam student_group string Группа. Example: "A-12"
     * @bodyParam file file Фото профиля (jpeg/jpg/png).
     * @bodyParam role string required Роль пользователя. Example: student
     * @bodyParam email string required Email. Example: ivan@example.com
     * @bodyParam password string required Пароль. Example: Secret123!
     * @bodyParam password_confirmation string required Подтверждение пароля. Example: Secret123!
     * @bodyParam is_terms_accepted boolean Принятие условий (0/1). Example: 1
     *
     * @response 200 scenario="OK" {"message":"Запись успешно создана!"}
     */
    public function store(MemberRequest $request, AcademyService $academyService): JsonResponse
    {
        $validated = $request->validated();
        $name = explode('@', $validated['email']);

        $user = User::create([
            'name' => $name[0],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_terms_accepted' => 1,
            'is_activated' => 1,
        ]);

        $fields = collect($validated)
            ->except(['role', 'email', 'password', 'is_terms_accepted', 'file'])
            ->toArray();

        $fields['institution'] = (int) $validated['institution_id'] === 4
            ? ($validated['institution'] ?? null)
            : null;

        if ($request->hasFile('file')) {
            $pathToFile = $request->file('file')->store(PublicUploadPath::memberAvatar(), 'public');

            $fields['profile_photo_path'] = $pathToFile;
        }

        $fields['user_id'] = $user->id;

        if (
            $validated['role'] === 'student'
            && ! empty($validated['student_group'])
        ) {
            $institutionId = (int) $validated['institution_id'];
            if ($institutionId === 1) {
                $academy = $academyService->determineAcademyByGroup($validated['student_group']);
            } elseif (in_array($institutionId, [2, 3], true)) {
                $academy = $academyService->resolveAcademyForRegionalInstitutionId($institutionId);
            } else {
                $academy = null;
            }

            $educationLevelName = $academyService->determineEducationLevel($validated['student_group']);
            $educationLevel = EducationLevel::where('name', $educationLevelName)->first();

            if ($academy) {
                $fields['academy_id'] = $academy->id;
            }
            $fields['education_level_id'] = $educationLevel?->id;
        }

        Member::create($fields);

        return response()->json(['message' => 'Запись успешно создана!']);
    }

    /**
     * Получить информацию пользователя
     *
     * @group Users
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     */
    public function show(int $user_id): JsonResponse
    {
        $member = Member::join('users', 'members.user_id', '=', 'users.id')
            ->join('regions', 'members.region_id', '=', 'regions.id')
            ->join('planets', 'members.planet_id', '=', 'planets.id')
            ->leftJoin('institutions', 'members.institution_id', '=', 'institutions.id')
            ->leftJoin('academies', 'members.academy_id', '=', 'academies.id')
            ->leftJoin('education_levels', 'members.education_level_id', '=', 'education_levels.id')
            ->select(
                'members.id',
                'members.last_name',
                'members.first_name',
                'members.middle_name',
                DB::raw("CASE
                    WHEN members.gender = 0 THEN 'Муж.'
                    ELSE 'Жен.' END as gender"),
                'members.birthday',
                'regions.name as region_name',
                'planets.name as planet_name',
                DB::raw('
                    CASE
                        WHEN members.institution_id = 4 THEN members.institution
                        ELSE institutions.name
                    END as institution_full
                '),
                'members.institution_id',
                DB::raw("CASE
                    WHEN members.course IS NULL THEN 'Не указан'
                    ELSE members.course END as course"),
                DB::raw("CASE
                    WHEN members.student_group IS NULL THEN 'Не указана'
                    ELSE members.student_group END as student_group"),
                'members.academy_id',
                'academies.name as academy_name',
                'academies.short_name as academy_short_name',
                'education_levels.name as education_level',
                'users.email',
                'users.role'
            )
            ->where('members.user_id', '=', $user_id)
            ->first();

        return response()->json($member);
    }

    /**
     * Изменение пароля пользователя администратором
     *
     * Позволяет администратору установить новый пароль для любого пользователя
     * без необходимости знать старый пароль. Используется для сброса паролей.
     *
     * @group Users (Admin)
     *
     * @urlParam user_id integer required ID пользователя. Example: 8
     *
     * @bodyParam password string required Новый пароль (минимум 8 символов). Example: newPassword
     * @bodyParam password_confirmation string required Подтверждение нового пароля. Example: newPassword
     *
     * @response 200 {
     *   "message": "Пароль пользователя успешно изменен!"
     * }
     * @response 404 {
     *   "message": "Пользователь не найден"
     * }
     * @response 422 {
     *   "message": "The password field must be at least 8 characters.",
     *   "errors": {
     *     "password": [
     *       "The password field must be at least 8 characters."
     *     ]
     *   }
     * }
     * @response 500 {
     *   "message": "Произошла ошибка в процессе изменения пароля. Пожалуйста, попробуйте позже."
     * }
     */
    public function adminUpdatePassword(Request $request, int $user_id): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $user = User::findOrFail($user_id);
            $user->password = Hash::make($request->password);
            $user->update();

            return response()->json(['message' => 'Пароль пользователя успешно изменен!']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }
    }

    /**
     * Получить информацию пользователя для редактирования
     *
     * @group Users (Student)
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     */
    public function edit(int $user_id): JsonResponse
    {
        $member = Member::join('users', 'members.user_id', '=', 'users.id')
            ->select(
                'members.id',
                'members.last_name',
                'members.first_name',
                'members.middle_name',
                'members.gender',
                'members.birthday',
                'members.region_id',
                'members.planet_id',
                'members.institution_id',
                'members.institution',
                'members.course',
                'members.student_group',
                'members.profile_photo_path',
                'members.user_id',
                'users.email',
                'users.role'
            )
            ->where('members.user_id', '=', $user_id)
            ->first();

        return response()->json($member);
    }

    /**
     * Обновить персональные данные
     *
     * Обновляет ФИО, пол, дату рождения и регион пользователя.
     *
     * @group Users (Student)
     *
     * @urlParam user_id int required ID пользователя. Example: 2
     *
     * @bodyParam last_name string required Фамилия. Example: Иванов
     * @bodyParam first_name string required Имя. Example: Иван
     * @bodyParam middle_name string Отчество. Example: Иванович
     * @bodyParam gender int required Пол (0/1). Example: 0
     * @bodyParam birthday string Дата рождения (Y-m-d). Example: 2004-01-15
     * @bodyParam region_id int required ID региона. Example: 1
     *
     * @response 200 scenario="OK" {"message":"Персональные данные успешно изменены!"}
     */
    public function updatePersonalData(MemberRequest $request, int $user_id)
    {
        $validated = $request->validated();

        $user = User::findOrFail($user_id);

        $member = Member::findOrFail($user->member->id);
        $member->last_name = $validated['last_name'];
        $member->first_name = $validated['first_name'];
        $member->middle_name = $validated['middle_name'] ?? null;
        $member->gender = $validated['gender'];
        $member->birthday = empty($validated['birthday'])
            ? null
            : Carbon::parse($validated['birthday'])->format('Y-m-d');
        $member->region_id = $validated['region_id'];
        $member->update();

        return response()->json(['message' => 'Персональные данные успешно изменены!']);
    }

    /**
     * Обновить данные об учебном заведении
     *
     * Обновляет направление (planet), тип учреждения, название, курс и группу.
     *
     * @group Users (Student)
     *
     * @urlParam user_id int required ID пользователя. Example: 2
     *
     * @bodyParam planet_id int required ID направления/планеты. Example: 2
     * @bodyParam institution_id int required ID учреждения. Example: 1
     * @bodyParam institution string Учреждение (обязательно для institution_id = 4). Example: "ИУБиП"
     * @bodyParam course int Курс/класс. Example: 2
     * @bodyParam student_group string Группа. Example: "A-12"
     *
     * @response 200 scenario="OK" {"message":"Данные об учебном заведении успешно изменены!"}
     */
    public function updateInstitutionData(MemberRequest $request, int $user_id)
    {
        $validated = $request->validated();

        $user = User::findOrFail($user_id);
        $member = Member::findOrFail($user->member->id);

        $member->update([
            'planet_id' => $validated['planet_id'],
            'institution_id' => $validated['institution_id'],
            'institution' => (int) $validated['institution_id'] === 4 ? $validated['institution'] : null,
            'course' => $validated['course'] ?? null,
            'student_group' => $validated['student_group'] ?? null,
        ]);

        return response()->json(['message' => 'Данные об учебном заведении успешно изменены!']);
    }

    /**
     * Обновить данные аккаунта
     *
     * Обновляет email и (опционально) фото профиля.
     *
     * @group Users (Student)
     *
     * @urlParam user_id int required ID пользователя. Example: 2
     *
     * @bodyParam email string required Новый email. Example: newmail@example.com
     * @bodyParam file file Фото профиля (jpeg/jpg/png).
     *
     * @response 200 scenario="OK" {"message":"Данные аккаунта успешно изменены!"}
     */
    public function updateAccountData(MemberRequest $request, int $user_id)
    {
        $validated = $request->validated();

        $user = User::findOrFail($user_id);
        $user->email = $validated['email'];

        if (! is_null($request->file)) {
            $pathToFile = $request->file('file')->store(PublicUploadPath::memberAvatar(), 'public');

            $member = Member::findOrFail($user->member->id);
            $member->profile_photo_path = $pathToFile;
            $member->update();
        }

        $user->update();

        return response()->json(['message' => 'Данные аккаунта успешно изменены!']);
    }

    /**
     * Изменить пароль пользователя
     *
     * Требует старый пароль и задаёт новый пароль.
     *
     * @group Users (Student)
     *
     * @urlParam user_id int required ID пользователя. Example: 2
     *
     * @bodyParam old_password string required Текущий пароль. Example: OldPass123!
     * @bodyParam password string required Новый пароль. Example: NewPass123!
     * @bodyParam password_confirmation string required Подтверждение нового пароля. Example: NewPass123!
     *
     * @response 200 scenario="OK" {"message":"Пароль успешно изменен!"}
     * @response 422 scenario="Validation error" {"errors":{"old_password":["Введенный пароль не совпадает со старым"]}}
     */
    public function updatePassword(MemberRequest $request, int $user_id): JsonResponse
    {
        $validated = $request->validated();

        $user = User::findOrFail($user_id);

        if (Hash::check($validated['old_password'], $user->password)) {
            $user->password = Hash::make($validated['password']);
            $user->update();

            return response()->json(['message' => 'Пароль успешно изменен!']);
        } else {
            throw ValidationException::withMessages([
                'old_password' => ['Введенный пароль не совпадает со старым'],
            ]);
        }
    }

    /**
     * Удаление пользователя
     *
     * @group Users (Admin)
     *
     * @urlParam user_id integer required ID пользователя Example: 2
     */
    public function destroy(int $user_id): JsonResponse
    {
        $member = Member::findOrFail($user_id);
        $userId = $member->user_id;

        $member->delete();

        $user = User::findOrFail($userId);
        $user->delete();

        return response()->json(['message' => 'Запись успешно удалена!']);
    }

    /**
     * Массовое удаление
     *
     * This endpoint allows admin to permanently delete multiple users and their associated member records.
     *
     * @group Users (Admin)
     *
     * @bodyParam user_ids array required Array of user IDs to delete. Example: [1, 2, 3]
     * @bodyParam user_ids.* integer required User ID. Example: 1
     *
     * @response 200 {
     *   "message": "Пользователи успешно удалены!",
     *   "count": 3
     * }
     * @response 400 {
     *   "message": "Не выбраны пользователи"
     * }
     * @response 500 {
     *   "message": "Произошла ошибка при массовом удалении"
     * }
     */
    public function bulkDestroy(Request $request)
    {
        $userIds = $request->input('user_ids', []);

        if (empty($userIds)) {
            return response()->json(['message' => 'Не выбраны пользователи'], 400);
        }

        DB::transaction(function () use ($userIds) {
            Member::whereIn('user_id', $userIds)
                ->chunkById(100, function ($members): void {
                    $members->each->delete();
                });

            User::whereIn('id', $userIds)
                ->chunkById(100, function ($users): void {
                    $users->each->delete();
                });
        });

        return response()->json([
            'message' => 'Пользователи успешно удалены!',
            'count' => count($userIds),
        ]);
    }
}
