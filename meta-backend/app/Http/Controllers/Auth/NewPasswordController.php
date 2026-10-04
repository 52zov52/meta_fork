<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class NewPasswordController extends Controller
{
    /**
     * Сброс пароля
     *
     * Устанавливает новый пароль пользователя с использованием токена из письма.
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     *
     * @bodyParam token string required Токен сброса пароля. Example: 1a2b3c4d5e6f7g8h9i0j
     * @bodyParam email string required Email пользователя. Example: st@i.ru
     * @bodyParam password string required Новый пароль. Example: passwordd
     * @bodyParam password_confirmation string required Подтверждение нового пароля. Example: passwordd
     *
     * @response 200 scenario="success" {
     *   "message": "Пароль успешно изменён! Вернитесь на страницу входа для авторизации"
     * }
     * @response 500 scenario="invalid_token" {
     *   "message": "Произошла ошибка в процессе сброса пароля. Пожалуйста, попробуйте позже."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Произошла ошибка в процессе сброса пароля. Пожалуйста, попробуйте позже.'], 500);
        }

        return response()->json(['message' => 'Пароль успешно изменён! Вернитесь на страницу входа для авторизации']);
    }
}
