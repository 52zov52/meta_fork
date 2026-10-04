<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Support\StudentGroupRules;
use App\Models\User;
use App\Models\Member;
use App\Models\EducationLevel;
use App\Http\Requests\MentorStoreStudentRequest;
use App\Support\PublicUploadPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @group Users (Mentor)
 */
class StudentsController extends Controller
{
    /**
     * Получить студентов наставника
     *
     * Возвращает список студентов, у которых академия совпадает с академией наставника.
     *
     * @queryParam status string Фильтр по статусу (active, blocked). Example: active
     * @queryParam education_level_id int ID уровня образования. Example: 1
     * @queryParam student_group string Группа студента. Example: К1И
     * @queryParam sortBy string Поле сортировки (fio, date_reg). Example: fio
     * @queryParam sortMethod string Метод сортировки (asc, desc). Example: asc
     * @queryParam search string Поиск по ФИО, группе или email. Example: Сидорова
     *
     * @response 403 {
     *   "message": "У наставника не указана академия"
     * }
     */
    public function students(Request $request): JsonResponse
    {
        $mentor = Auth::user();
        $mentorMember = $mentor->member;

        if (! $mentorMember || ! $mentorMember->academy_id) {
            return response()->json(['message' => 'У наставника не указана академия'], 403);
        }

        $query = User::query()
            ->select('users.*')
            ->where('users.role', 'student')
            ->join('members', 'users.id', '=', 'members.user_id')
            ->where('members.academy_id', $mentorMember->academy_id);

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('users.is_activated', 1);
            } elseif ($request->status === 'blocked') {
                $query->where('users.is_activated', 0);
            }
        }

        $educationLevelId = $request->input('education_level_id') ?? $request->input('education_level');
        if ($educationLevelId !== null && $educationLevelId !== '') {
            $query->where('members.education_level_id', $educationLevelId);
        }

        $group = $request->input('student_group') ?? $request->input('group');
        if ($group !== null && $group !== '') {
            $query->where('members.student_group', $group);
        }

        $query->searchByKeyword($request->input('search'));

        $sortBy = $request->input('sortBy');
        $sortMethod = strtolower($request->input('sortMethod', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'fio') {
            $query->orderBy('members.last_name', $sortMethod)
                  ->orderBy('members.first_name', $sortMethod)
                  ->orderBy('members.middle_name', $sortMethod);
        } elseif ($sortBy === 'date_reg') {
            $query->orderBy('users.created_at', $sortMethod);
        } else {
            $query->orderBy('users.created_at', 'desc');
        }

        $paginated = $query
            ->with(['member.academy', 'member.educationLevel'])
            ->paginate(20);

        $paginated->through(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->member->fullName,
                'email' => $student->email,
                'is_activated' => (bool) $student->is_activated,
                'academy' => [
                    'id' => $student->member->academy->id,
                    'name' => $student->member->academy->name,
                    'short_name' => $student->member->academy->short_name,
                ],
                'education_level' => $student->member->educationLevel?->name,
                'education_level_id' => $student->member->education_level_id,
                'student_group' => $student->member->student_group,
            ];
        });

        return response()->json($paginated);
    }

    /**
     * Создать нового студента ментором 
     */
    public function store(MentorStoreStudentRequest $request): JsonResponse
    {
        $mentor = Auth::user();
        $mentorMember = $mentor->member;

        if (! $mentorMember || ! $mentorMember->academy_id) {
            return response()->json(['message' => 'У наставника не указана академия'], 403);
        }

        $validated = $request->validated();
        $name = explode('@', $validated['email']);

        try {
            DB::transaction(function () use ($validated, $name, $mentorMember, $request) {
                $user = User::create([
                    'name' => $name[0],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'student',
                    'is_terms_accepted' => 1,
                    'is_activated' => 1,
                ]);

                $fields = collect($validated)
                    ->except(['email', 'password', 'is_terms_accepted', 'file'])
                    ->toArray();

                $fields['region_id'] = $mentorMember->region_id;
                $fields['planet_id'] = $mentorMember->planet_id;
                $fields['institution_id'] = $mentorMember->institution_id;
                $fields['institution'] = $mentorMember->institution;
                $fields['academy_id'] = $mentorMember->academy_id;
                $fields['user_id'] = $user->id;

                if ($request->hasFile('file')) {
                    $pathToFile = $request->file('file')->store(PublicUploadPath::memberAvatar(), 'public');
                    $fields['profile_photo_path'] = $pathToFile;
                }

                if (! empty($validated['student_group'])) {
                    $academyService = new \App\Services\AcademyService();
                    $educationLevelName = $academyService->determineEducationLevel($validated['student_group']);
                    $educationLevel = EducationLevel::where('name', $educationLevelName)->first();
                    $fields['education_level_id'] = $educationLevel?->id;
                }

                Member::create($fields);
            });

            return response()->json(['message' => 'Запись успешно создана!']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Ошибка при создании студента.'], 500);
        }
    }

    /**
     * Получить список уникальных групп студентов наставника
     */
    public function groups(): JsonResponse
    {
        $mentor = Auth::user();
        $mentorMember = $mentor->member;

        if (! $mentorMember || ! $mentorMember->academy_id) {
            return response()->json(['message' => 'У наставника не указана академия'], 403);
        }

        $groups = \App\Models\Member::query()
            ->join('users', 'members.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->where('members.academy_id', $mentorMember->academy_id)
            ->whereNotNull('members.student_group')
            ->where('members.student_group', '<>', '')
            ->distinct()
            ->orderBy('members.student_group')
            ->pluck('members.student_group');

        return response()->json(['data' => $groups]);
    }

    /**
     * Вспомогательный метод: Изоляция
     */
    private function baseStudentQuery()
    {
        $academyId = Auth::user()->member->academy_id ?? null;

        return \App\Models\Member::join('users', 'members.user_id', '=', 'users.id')
            ->where('users.role', 'student')
            ->where('members.academy_id', $academyId);
    }

    /**
     * Просмотр полной информации о студенте
     *
     * @urlParam user_id integer required ID студента. Example: 2
     */
    public function show(int $user_id): JsonResponse
    {
        $student = $this->baseStudentQuery()
            ->leftJoin('regions', 'members.region_id', '=', 'regions.id')
            ->leftJoin('planets', 'members.planet_id', '=', 'planets.id')
            ->leftJoin('institutions', 'members.institution_id', '=', 'institutions.id')
            ->leftJoin('academies', 'members.academy_id', '=', 'academies.id')
            ->select(
                'members.*',
                'users.email',
                'users.is_activated',
                'regions.name as region_name',
                'planets.name as planet_name',
                \Illuminate\Support\Facades\DB::raw('
                    CASE
                        WHEN members.institution_id = 4 THEN members.institution
                        ELSE institutions.name
                    END as institution_full
                '),
                'academies.name as academy_name',
                'academies.short_name as academy_short_name'
            )
            ->where('members.user_id', $user_id)
            ->first();

        if (! $student) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 404);
        }

        return response()->json($student);
    }

    /**
     * Блокировка / Разблокировка студента
     */
    public function toggleBlock(int $user_id): JsonResponse
    {
        if (! $this->baseStudentQuery()->where('members.user_id', $user_id)->exists()) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 403);
        }

        $user = User::find($user_id);
        $user->is_activated = ! $user->is_activated;
        $user->save();

        $status = $user->is_activated ? 'разблокирован' : 'заблокирован';

        return response()->json(['message' => "Пользователь успешно {$status}!"]);
    }

    /**
     * Сброс пароля студента
     */
    public function resetPassword(Request $request, int $user_id): JsonResponse
    {
        if (! $this->baseStudentQuery()->where('members.user_id', $user_id)->exists()) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 403);
        }

        $request->validate([
            'password' => ['required', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user = User::find($user_id);
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Пароль студента успешно изменен!']);
    }

    /**
     * Редактировать данные студента
     */
    public function update(Request $request, int $user_id): JsonResponse
    {
        if (! $this->baseStudentQuery()->where('members.user_id', $user_id)->exists()) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 403);
        }

        $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', \Illuminate\Validation\Rule::unique('users')->ignore($user_id)],
            'gender' => ['required', 'numeric'],
            'birthday' => ['required', 'date_format:Y-m-d'],
            'course' => ['nullable', 'numeric'],
            'student_group' => StudentGroupRules::rules(),
        ], [
            'student_group.regex' => StudentGroupRules::invalidMessage(),
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user_id) {
                $user = User::find($user_id);
                $user->email = $request->email;
                $user->save();

                $member = \App\Models\Member::where('user_id', $user_id)->first();
                $member->update($request->only([
                    'last_name', 'first_name', 'middle_name',
                    'gender', 'birthday', 'course', 'student_group',
                ]));
            });

            return response()->json(['message' => 'Данные студента успешно обновлены!']);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Ошибка при обновлении данных.'], 500);
        }
    }
}
