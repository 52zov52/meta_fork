<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * @group Users
 */
class MailController extends Controller
{
    /**
     * Сбрасывает пароли для пользователей определенной роли и отправляет им новые.
     *
     * @group Users (Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetGroupPasswords(Request $request)
    {
        // 1. Валидация входных данных
        $request->validate([
            'role' => ['required', 'string', 'in:student,mentor,admin,moderator'],
        ]);

        $role = $request->input('role');
        $updatedCount = 0;
        $failedEmails = [];

        // 2. Получаем всех пользователей с указанной ролью
        // Используем with('member') для жадной загрузки связанных данных Member
        $users = User::where('role', $role)->with('member')->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => "Пользователи с ролью '{$role}' не найдены.",
                'status' => 'info',
            ], 404);
        }

        // 3. Перебираем пользователей, генерируем новый пароль и отправляем письма
        foreach ($users as $user) {
            // Генерируем новый пароль (например, 12 символов)
            // Str::random() генерирует случайную буквенно-цифровую строку
            // Str::password() (доступно с Laravel 9+) генерирует более комплексные пароли
            $newPassword = Str::password(12, true, true, false, false); // 12 символов, буквы, цифры, без спец. символов

            try {
                // Обновляем пароль пользователя в базе данных
                // Благодаря 'password' => 'hashed' в User model casts, Laravel автоматически хеширует пароль
                $user->password = $newPassword;
                $user->save(); // Сохраняем пользователя с новым хешированным паролем

                $emailFrom = config('mail.from.address');
                $emailTo = $user->email;

                Mail::send('emails.reset-password', [
                    'user' => $user,
                    'newPassword' => $newPassword,
                    'userName' => $user->member->fullName ?? $user->name,
                ], function ($message) use ($emailFrom, $emailTo) {
                    $message->from($emailFrom, 'ЧОУ ВО ЮУ (ИУБиП)');
                    $message->to($emailTo)->subject('Ваш новый пароль для доступа к метавселенной');
                });

                $updatedCount++;
            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем процесс для других пользователей
                // В реальном приложении здесь можно использовать Monolog или другой логгер
                error_log("Failed to reset password for user ID {$user->id} ({$user->email}): ".$e->getMessage());
                $failedEmails[] = $user->email;
            }
        }

        // 4. Возвращаем результат
        if ($updatedCount > 0) {
            $response = [
                'message' => "Пароли для {$updatedCount} пользователей с ролью '{$role}' успешно обновлены и отправлены.",
                'status' => 'success',
                'total_users_in_role' => $users->count(),
                'successfully_updated' => $updatedCount,
            ];
            if (! empty($failedEmails)) {
                $response['message'] .= ' Не удалось отправить письма для: '.implode(', ', $failedEmails);
                $response['status'] = 'partial_success';
                $response['failed_emails'] = $failedEmails;
            }

            return response()->json($response);
        } else {
            return response()->json([
                'message' => "Не удалось обновить пароли ни для одного пользователя с ролью '{$role}'. Проверьте логи.",
                'status' => 'error',
                'failed_emails' => $failedEmails,
            ], 500);
        }
    }
}
