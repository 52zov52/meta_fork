<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::query()->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $templates = [
            ['title' => 'Новое мероприятие', 'description' => 'На вашей планете добавлено мероприятие. Можно посмотреть в каталоге.'],
            ['title' => 'Напоминание о регистрации', 'description' => 'Регистрация на выбранное мероприятие скоро закроется.'],
            ['title' => 'Портфолио проверено', 'description' => 'Материалы портфолио были рассмотрены.'],
            ['title' => 'Изменение расписания', 'description' => 'Дата начала мероприятия обновлена — проверьте карточку.'],
            ['title' => 'Сообщение от куратора', 'description' => 'У вас новое сообщение в системе.'],
            ['title' => 'Начислены баллы', 'description' => 'За участие начислены баллы активности.'],
        ];

        $now = now();
        $rows = [];
        $i = 0;

        foreach ($userIds as $userId) {
            $count = rand(2, 4);
            for ($n = 0; $n < $count; $n++) {
                $t = $templates[$i % count($templates)];
                $i++;
                $rows[] = [
                    'user_id' => $userId,
                    'title' => $t['title'],
                    'description' => $t['description'].' (#'.$i.')',
                    'checked' => (bool) rand(0, 1),
                    'created_at' => $now->copy()->subMinutes(rand(0, 60 * 24 * 14)),
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }
    }
}
