<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Common\UserScoreController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    /**
     * Регистрация нового пользователя
     *
     * Создает нового пользователя и его профиль (Member), возвращает токен для дальнейших запросов.
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     *
     * @bodyParam last_name string required Фамилия пользователя. Example: Иванов
     * @bodyParam first_name string required Имя пользователя. Example: Иван
     * @bodyParam middle_name string nullable Отчество пользователя. Example: Иванович
     * @bodyParam gender integer required Пол пользователя (0-муж, 1-жен). Example: 0
     * @bodyParam birthday string nullable Дата рождения (Y-m-d). Example: 2000-01-01
     * @bodyParam region_id integer required ID региона. Example: 1
     * @bodyParam planet_id integer required ID планеты. Example: 1
     * @bodyParam institution_id integer required ID учебного заведения. Example: 1
     * @bodyParam institution string nullable Название учреждения (обязательно при institution_id=4). Example: МГУ
     * @bodyParam course integer nullable Курс обучения. Example: 2
     * @bodyParam student_group string nullable Группа студента. Example: К1И1
     * @bodyParam email string required Email пользователя. Example: st@i.ru
     * @bodyParam password string required Пароль пользователя. Example: password
     * @bodyParam password_confirmation string required Подтверждение пароля. Example: password
     * @bodyParam role string required Роль пользователя (student, mentor, employer). Example: student
     * @bodyParam is_terms_accepted boolean required Согласие с условиями. Example: true
     *
     * @response 200 scenario="success" {
     *   "user_id": 2,
     *   "token": "2|nA27RBn90hFX7kV4DwyIuJ7ZZcKhKrEIY6feMPLV43be8213",
     *   "role": "student",
     *   "shortName": "Иванов И.",
     *   "fullName": "Иванов Иван Иванович",
     *   "region": "Москва",
     *   "institution": "МГУ",
     *   "score": 0,
     *   "profile_photo_path": null,
     *   "is_activated": 0
     * }
     * @response 422 scenario="institution_required" {
     *   "message": "Поле \"Учреждение\" обязательно для заполнения"
     * }
     * @response 422 scenario="validation_error" {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function store(RegisterRequest $request)
    {
        // institution обязательно только если institution_id = 4 (Другое)
        if ($request->institution_id == 4 && empty($request->institution)) {
            return response()->json(['message' => 'Поле "Учреждение" обязательно для заполнения'], 422);
        }

        $user = null;

        DB::transaction(function () use ($request, &$user) {
            $name = explode('@', $request->email);

            $user = User::create([
                'name' => $name[0],
                'email' => $request->email,
                'password' => Hash::make($request->string('password')),
                'role' => $request->role,
                'is_terms_accepted' => $request->is_terms_accepted,
            ]);

            $fields = $request->except(['role', 'email', 'password', 'is_terms_accepted']);
            $fields['user_id'] = $user->id;

            // institution = null для ID 1,2,3
            if ($request->institution_id != 4) {
                $fields['institution'] = null;
            }

            Member::create($fields);

            event(new Registered($user));
            Auth::login($user);
        });

        $token = $user->createToken('auth')->plainTextToken;
        $user->load(['member.institution', 'member.region']);
        $score = UserScoreController::getUserScoreByAcademicYear($user->id) ?? 0;

        return response()->json([
            'user_id' => $user->id,
            'token' => $token,
            'role' => $user->role,
            'shortName' => $user->member->shortName,
            'fullName' => $user->member->fullName,
            'region' => $user->member->region->name,
            'institution' => $user->member->institutionDisplay,
            'score' => $score,
            'profile_photo_path' => $user->member->profile_photo_path,
            'is_activated' => 0,
        ]);
    }
}
