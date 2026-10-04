<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels; // Импортируем модель User

class ResetPassword extends Mailable implements ShouldQueue // Добавим ShouldQueue, чтобы письма отправлялись через очередь
{
    use Queueable, SerializesModels;

    public User $user; // Добавляем публичное свойство для пользователя

    public string $newPassword; // Добавляем публичное свойство для нового пароля

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $newPassword)
    {
        $this->user = $user;
        $this->newPassword = $newPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Динамическая тема письма в зависимости от имени пользователя
        $userName = $this->user->member->fullName ?? $this->user->name;

        return new Envelope(
            subject: "Ваш новый пароль для доступа к метавселенной, {$userName}!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password', // Путь к нашему Blade-шаблону
            with: [
                'user' => $this->user,
                'newPassword' => $this->newPassword,
                'userName' => $this->user->member->fullName ?? $this->user->name, // Получаем полное имя
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
