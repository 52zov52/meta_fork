<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @group Users (Moderator)
 */
class ModeratorStudentController extends Controller
{
    private function getModeratorInstitutionId(): ?int
    {
        return Auth::user()->member->institution_id ?? null;
    }

    /**
     * Базовый запрос: только студенты своего учреждения
     */
    private function baseStudentQuery()
    {
        $institutionId = $this->getModeratorInstitutionId();
        return User::whereHas('member', function ($query) use ($institutionId) {
            $query->where('institution_id', $institutionId);
        });
    }

    /**
     * Список студентов
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseStudentQuery();

        $query->searchByKeyword($request->search);

        if ($request->filled('group_id')) {
            $query->whereHas('member', function ($q) use ($request) {
                $q->where('group_id', $request->group_id);
            });
        }

        if ($request->filled('course')) {
            $query->whereHas('member', function ($q) use ($request) {
                $q->where('course', $request->course);
            });
        }

        if ($request->filled('education_level')) {
            $query->whereHas('member', function ($q) use ($request) {
                $q->where('education_level', $request->education_level);
            });
        }

        $students = $query->with('member')->paginate(20);

        return response()->json($students);
    }

    /**
     * Просмотр профиля студента
     *
     * @urlParam id integer required ID студента. Example: 2
     */
    public function show(int $id): JsonResponse
    {
        $student = $this->baseStudentQuery()->with(['member', 'activities'])->find($id);

        if (!$student) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 404);
        }

        return response()->json($student);
    }

    /**
     * Блокировка / Разблокировка студента
     *
     * @urlParam id integer required ID студента. Example: 2
     *
     * @response 200 {
     *   "message": "Студент успешно заблокирован!"
     * }
     * @response 404 {
     *   "error": "Студент не найден или доступ запрещен"
     * }
     */
    public function toggleBlock(int $id): JsonResponse
    {
        $student = $this->baseStudentQuery()->find($id);

        if (!$student) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 404);
        }

        $student->is_activated = !$student->is_activated;
        $student->save();

        $statusText = $student->is_activated ? 'разблокирован' : 'заблокирован';

        return response()->json(['message' => "Студент успешно {$statusText}!"]);
    }

    /**
     * Сброс пароля студента
     *
     * @urlParam id integer required ID студента. Example: 2
     *
     * @response 200 {
     *   "message": "Пароль успешно сброшен!",
     *   "new_password": "Ab3xYz9Q"
     * }
     * @response 404 {
     *   "error": "Студент не найден или доступ запрещен"
     * }
     */
    public function resetPassword(int $id): JsonResponse
    {
        $student = $this->baseStudentQuery()->find($id);

        if (!$student) {
            return response()->json(['error' => 'Студент не найден или доступ запрещен'], 404);
        }

        $newPassword = Str::random(8);

        $student->password = Hash::make($newPassword);
        $student->save();

        return response()->json([
            'message' => 'Пароль успешно сброшен!',
            'new_password' => $newPassword
        ]);
    }
}
