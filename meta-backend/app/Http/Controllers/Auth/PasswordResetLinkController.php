<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Запрос на сброс пароля
     *
     * Отправляет письмо со ссылкой для сброса пароля на указанный email.
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email пользователя. Example: st@i.ru
     *
     * @response 200 scenario="success" {
     *   "message": "Ссылка для сброса пароля отправлена на ваш email!"
     * }
     * @response 500 scenario="user_not_found" {
     *   "message": "Пользователь с таким email не найден"
     * }
     * @response 500 scenario="error" {
     *   "message": "Произошла ошибка в процессе формирования ссылки на сброс пароля. Пожалуйста, попробуйте позже."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return response()->json(['message' => 'Пользователь с таким email не найден'], 500);
            }

            $token = Password::createToken($user);
            $resetUrl = config('app.frontend_url').'/auth/reset-password?token='.$token.'&email='.urlencode($user->email);

            $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            $emailFrom = config('mail.from.address');
            $emailTo = $user->email;

            Mail::send('emails.reset-password-link', [
                'email' => $emailTo,
                'resetUrl' => $resetUrl,
                'expire' => $expire,
            ], function ($message) use ($emailFrom, $emailTo) {
                $message->from($emailFrom, 'ЧОУ ВО ЮУ (ИУБиП)');
                $message->to($emailTo)->subject('Восстановление пароля к метавселенной IUBIP');
            });

            return response()->json(['message' => 'Ссылка для сброса пароля отправлена на ваш email!']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Произошла ошибка в процессе формирования ссылки на сброс пароля. Пожалуйста, попробуйте позже.'], 500);
        }
    }
}
