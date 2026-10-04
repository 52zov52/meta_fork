<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Academy;
use App\Models\Planet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    private array $statuses = [
        'draft',
        'scheduled',
        'started',
        'completed',
        'canceled',
    ];

    private array $activityTitles = [
        'Спортивный турнир по волейболу',
        'Научная конференция студентов',
        'Мастер-класс по программированию',
        'Экологическая акция "Чистый город"',
        'Культурный вечер "Традиции народов"',
        'Хакатон по разработке мобильных приложений',
        'Благотворительный концерт',
        'Квест "Интеллектуальный марафон"',
        'Тренинг по личностному росту',
        'Фотовыставка "Моя планета"',
        'Дебаты "Актуальные вопросы современности"',
        'Кулинарный мастер-класс',
        'Турнир по киберспорту',
        'Литературный вечер',
        'Выставка научных проектов',
    ];

    private array $descriptions = [
        'Присоединяйтесь к увлекательному мероприятию, где вы сможете проявить свои таланты и познакомиться с единомышленниками.',
        'Уникальная возможность получить новые знания и навыки от экспертов в своей области.',
        'Командное мероприятие для развития лидерских качеств и творческого мышления.',
        'Интерактивная площадка для обмена опытом и генерации новых идей.',
        'Захватывающее событие с призами и подарками для всех участников.',
    ];

    public function run(): void
    {
        $creators = User::whereIn('role', ['admin', 'moderator', 'mentor'])->pluck('id')->toArray();

        if (empty($creators)) {
            echo ">> No creators found. Please seed users first.\n";

            return;
        }

        $planets = Planet::pluck('id')->toArray();
        $categories = ActivityCategory::pluck('id')->toArray();
        $academies = Academy::pluck('id')->toArray();

        if (empty($planets) || empty($categories)) {
            echo ">> Planets or categories not found.\n";

            return;
        }

        $count = 50;

        for ($i = 0; $i < $count; $i++) {
            $rand = rand(1, 100);

            if ($rand <= 60) {
                $status = 'started';
            } elseif ($rand <= 80) {
                $status = 'scheduled';
            } elseif ($rand <= 90) {
                $status = 'completed';
            } else {
                $status = $this->statuses[array_rand($this->statuses)];
            }

            $dates = $this->generateDates($status, $i);
            $status = $this->resolveStatusByDates($status, $dates['start'], $dates['end']);

            Activity::create([
                'title' => $this->activityTitles[array_rand($this->activityTitles)].' #'.($i + 1),
                'description' => $this->descriptions[array_rand($this->descriptions)],
                'date_start' => $dates['start'],
                'date_end' => $dates['end'],
                'score' => rand(3, 25) * 4,
                'status' => $status,
                'planet_id' => $planets[array_rand($planets)],
                'category_id' => $categories[array_rand($categories)],
                'academy_id' => $this->resolveAcademyId($academies),
                'creator_id' => $creators[array_rand($creators)],
                'photo_path' => null,
            ]);
        }

        echo ">> Created {$count} activities (mostly 'started').\n";
    }

    private function resolveAcademyId(array $academies): ?int
    {
        if ($academies === []) {
            return null;
        }

        // By default, some activities are available for all academies.
        return rand(1, 100) <= 40 ? null : $academies[array_rand($academies)];
    }

    private function generateDates(string $status, int $index): array
    {
        $now = Carbon::now();
        $baseDate = Carbon::create(
            $now->year - 2 + intdiv($index, 12),
            ($index % 12) + 1,
            rand(5, 24),
            rand(8, 18),
            [0, 15, 30, 45][array_rand([0, 15, 30, 45])],
            0
        );

        return match ($status) {
            'draft' => [
                'start' => $baseDate->copy()->addDays(rand(10, 40)),
                'end' => $baseDate->copy()->addDays(rand(41, 80)),
            ],
            'scheduled' => [
                'start' => $baseDate->copy()->addDays(rand(1, 20)),
                'end' => $baseDate->copy()->addDays(rand(21, 45)),
            ],
            'started' => [
                'start' => $now->copy()->subDays(rand(1, 45)),
                'end' => $now->copy()->addDays(rand(1, 30)),
            ],
            'completed' => [
                'start' => $baseDate->copy()->subDays(rand(40, 120)),
                'end' => $baseDate->copy()->subDays(rand(5, 30)),
            ],
            'canceled' => [
                'start' => $baseDate->copy()->subDays(rand(20, 60)),
                'end' => $baseDate->copy()->subDays(rand(1, 19)),
            ],
            default => [
                'start' => $baseDate->copy()->addDays(rand(1, 30)),
                'end' => $baseDate->copy()->addDays(rand(31, 60)),
            ],
        };
    }

    private function resolveStatusByDates(string $status, Carbon $start, Carbon $end): string
    {
        $now = Carbon::now();

        if ($start->isFuture()) {
            return in_array($status, ['draft', 'scheduled'], true) ? $status : 'scheduled';
        }

        if ($end->isPast()) {
            return in_array($status, ['completed', 'canceled'], true) ? $status : 'completed';
        }

        return in_array($status, ['started', 'scheduled'], true) ? $status : 'started';
    }
}
