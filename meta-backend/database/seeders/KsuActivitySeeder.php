<?php

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\ActivityParameter;
use App\Models\ActivityUser;
use App\Models\EducationLevel;
use App\Models\Member;
use App\Models\Planet;
use App\Models\User;
use App\Services\ScoreCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Создаёт ~20 КСУ-мероприятий по всем категориям с разнообразными
 * параметрами и целевыми уровнями.
 */
class KsuActivitySeeder extends Seeder
{
    /**
     * @var list<array{
     *   title: string,
     *   description: string,
     *   category_id: int,
     *   parameters: array<string, mixed>,
     *   target_education_level_ids: list<int>,
     * }>
     */
    private array $scenarios = [
        // === Проектная ===
        [
            'title' => 'Внутренний проект ИТ-песочницы',
            'description' => 'Командная разработка прототипа в составе студенческой лаборатории.',
            'category_id' => 1,
            'parameters' => ['activity_type' => 'internal', 'has_implementation' => false],
            'target_education_level_ids' => [1], // целевой — Колледж
        ],
        [
            'title' => 'Внутренний проект с внедрением',
            'description' => 'Реализация проекта с измеримым эффектом и внедрением в практику.',
            'category_id' => 1,
            'parameters' => ['activity_type' => 'internal', 'has_implementation' => true],
            'target_education_level_ids' => [3], // Магистратура
        ],
        [
            'title' => 'Хакатон по разработке мобильных приложений',
            'description' => 'Внешний конкурс с призовыми местами.',
            'category_id' => 1,
            'parameters' => ['activity_type' => 'external', 'has_places' => true],
            'target_education_level_ids' => [2], // Бакалавриат
        ],
        [
            'title' => 'Олимпиада по программированию',
            'description' => 'Внешний конкурс с призовыми местами на уровне города.',
            'category_id' => 1,
            'parameters' => ['activity_type' => 'external', 'has_places' => true],
            'target_education_level_ids' => [1, 2], // Колледж + Бакалавриат
        ],
        [
            'title' => 'Образовательный вебинар по архитектуре ПО',
            'description' => 'Участие в обучающем семинаре с получением сертификата.',
            'category_id' => 1,
            'parameters' => ['activity_type' => 'external', 'has_places' => false],
            'target_education_level_ids' => [],  // равные баллы для всех
        ],

        // === Научная ===
        [
            'title' => 'Публикация статьи в сборнике РИНЦ',
            'description' => 'Статья принята к публикации в сборнике, индексируемом в РИНЦ.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'rinz_collection'],
            'target_education_level_ids' => [2],
        ],
        [
            'title' => 'Выступление на конференции РИНЦ',
            'description' => 'Доклад на научной конференции с публикацией материалов в РИНЦ.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'conference_presentation'],
            'target_education_level_ids' => [2, 3], // Бакалавриат + Магистратура
        ],
        [
            'title' => 'Публикация в журнале РИНЦ',
            'description' => 'Полноценная научная статья, опубликованная в журнале РИНЦ.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'rinz_journal'],
            'target_education_level_ids' => [3],
        ],
        [
            'title' => 'Публикация в сборнике ВАК',
            'description' => 'Научная статья в издании из перечня ВАК.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'vak_collection'],
            'target_education_level_ids' => [3],
        ],
        [
            'title' => 'Хоздоговорная НИР',
            'description' => 'Выполнение хоздоговорной научно-исследовательской работы.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'nir_contract'],
            'target_education_level_ids' => [3],
        ],
        [
            'title' => 'НИР в рамках конкурса с местами',
            'description' => 'Научно-исследовательская работа в рамках конкурса с распределением призовых мест.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'nir_competition', 'has_places' => true],
            'target_education_level_ids' => [2],
        ],
        [
            'title' => 'НИР в рамках конкурса без мест',
            'description' => 'Подготовка работы в рамках конкурса без распределения призовых мест.',
            'category_id' => 2,
            'parameters' => ['activity_type' => 'nir_competition', 'has_places' => false],
            'target_education_level_ids' => [1, 2],
        ],

        // === Общественная ===
        [
            'title' => 'Волонтёрство в социальной инициативе',
            'description' => 'Участие в волонтёрской деятельности студенческого объединения.',
            'category_id' => 3,
            'parameters' => ['is_leader' => false],
            'target_education_level_ids' => [1],
        ],
        [
            'title' => 'Координация университетского мероприятия',
            'description' => 'Особая благодарность за организацию направления на уровне университета.',
            'category_id' => 3,
            'parameters' => ['is_leader' => true],
            'target_education_level_ids' => [2],
        ],
        [
            'title' => 'Лидер общественного движения',
            'description' => 'Особая благодарность за руководство общественным проектом.',
            'category_id' => 3,
            'parameters' => ['is_leader' => true],
            'target_education_level_ids' => [2, 3],
        ],

        // === Культурно-творческая ===
        [
            'title' => 'Внутривузовский творческий вечер',
            'description' => 'Участие в творческом мероприятии университета.',
            'category_id' => 4,
            'parameters' => ['activity_type' => 'cultural_internal'],
            'target_education_level_ids' => [1],
        ],
        [
            'title' => 'Региональный фестиваль искусств',
            'description' => 'Выступление с призовыми местами на уровне города/региона.',
            'category_id' => 4,
            'parameters' => ['activity_type' => 'cultural_external', 'has_places' => true],
            'target_education_level_ids' => [2],
        ],
        [
            'title' => 'Внешнее культурное мероприятие без мест',
            'description' => 'Участие во внешнем культурном мероприятии без распределения мест.',
            'category_id' => 4,
            'parameters' => ['activity_type' => 'cultural_external', 'has_places' => false],
            'target_education_level_ids' => [],
        ],
        [
            'title' => 'Просветительский диктант',
            'description' => 'Участие в просветительском мероприятии (диктант, посещение мемориала).',
            'category_id' => 4,
            'parameters' => ['activity_type' => 'cultural_outreach'],
            'target_education_level_ids' => [],
        ],
        [
            'title' => 'Экскурсия в мемориал «Самбекские высоты»',
            'description' => 'Посещение мемориального комплекса в рамках просветительской программы.',
            'category_id' => 4,
            'parameters' => ['activity_type' => 'cultural_outreach'],
            'target_education_level_ids' => [1, 2],
        ],
    ];

    public function run(): void
    {
        $creators = User::whereIn('role', ['admin', 'moderator', 'mentor'])->pluck('id')->toArray();
        $planets = Planet::pluck('id')->toArray();

        if ($creators === [] || $planets === []) {
            echo ">> [KsuActivitySeeder] Skipped: no creators or planets.\n";

            return;
        }

        $students = $this->findStudentsByEducationLevel();
        $now = Carbon::now();
        $createdCount = 0;
        $registrationsCount = 0;

        foreach ($this->scenarios as $index => $scenario) {
            $status = $this->pickStatus($index);
            $dates = $this->dateRangeForStatus($status, $now, $index);

            $activity = Activity::create([
                'title' => $scenario['title'].' #'.($index + 1),
                'description' => $scenario['description'],
                'date_start' => $dates['start'],
                'date_end' => $dates['end'],
                'score' => 0,
                'is_dynamic_score' => true,
                'status' => $status,
                'planet_id' => $planets[array_rand($planets)],
                'academy_id' => null,
                'category_id' => $scenario['category_id'],
                'creator_id' => $creators[array_rand($creators)],
                'photo_path' => null,
            ]);

            ActivityParameter::create([
                'activity_id' => $activity->id,
                'activity_type' => $scenario['parameters']['activity_type'] ?? null,
                'has_places' => $scenario['parameters']['has_places'] ?? false,
                'has_implementation' => $scenario['parameters']['has_implementation'] ?? null,
                'is_leader' => $scenario['parameters']['is_leader'] ?? false,
                'target_education_level_ids' => $scenario['target_education_level_ids'],
            ]);

            ScoreCalculationService::calculateAndStore(
                $activity,
                $scenario['parameters'],
                $scenario['target_education_level_ids'] === [] ? null : $scenario['target_education_level_ids'],
            );

            $registrationsCount += $this->registerSampleStudents($activity, $students, $status);

            $createdCount++;
        }

        echo ">> [KsuActivitySeeder] Created {$createdCount} КСУ-activities, {$registrationsCount} registrations.\n";
    }

    /**
     * @return array<int, list<int>>  education_level_id => [user_id, ...]
     */
    private function findStudentsByEducationLevel(): array
    {
        return Member::whereNotNull('education_level_id')
            ->get(['user_id', 'education_level_id'])
            ->groupBy('education_level_id')
            ->map(fn ($members) => $members->pluck('user_id')->all())
            ->toArray();
    }

    /**
     * Регистрирует 2–5 случайных студентов; для completed-мероприятий часть из них
     * подтверждается с place=participation и earned_score из activity_scores.
     */
    private function registerSampleStudents(Activity $activity, array $studentsByLevel, string $status): int
    {
        if ($studentsByLevel === []) {
            return 0;
        }

        $allStudents = array_merge(...array_values($studentsByLevel));
        if ($allStudents === []) {
            return 0;
        }

        $sampleSize = min(rand(2, 5), count($allStudents));
        $picked = (array) array_rand(array_flip($allStudents), $sampleSize);
        $count = 0;

        foreach ($picked as $userId) {
            $au = ActivityUser::create([
                'user_id' => $userId,
                'activity_id' => $activity->id,
            ]);

            if ($status === 'completed' && rand(0, 100) < 70) {
                $this->confirmParticipationKsu($au, $activity);
            }

            $count++;
        }

        return $count;
    }

    private function confirmParticipationKsu(ActivityUser $au, Activity $activity): void
    {
        $member = Member::where('user_id', $au->user_id)->first();
        if ($member === null || $member->education_level_id === null) {
            return;
        }

        $score = $activity->activityScores()
            ->where('education_level_id', $member->education_level_id)
            ->where('place', 'participation')
            ->first();

        if ($score === null) {
            return;
        }

        $au->place = 'participation';
        $au->earned_score = $score->final_score;
        $au->level = 0.4;
        $au->is_confirmed = true;
        $au->save();
    }

    private function pickStatus(int $index): string
    {
        $bucket = $index % 4;

        return match ($bucket) {
            0 => 'started',
            1 => 'scheduled',
            2 => 'completed',
            default => 'started',
        };
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function dateRangeForStatus(string $status, Carbon $now, int $offset): array
    {
        return match ($status) {
            'scheduled' => [
                'start' => $now->copy()->addDays(7 + $offset),
                'end' => $now->copy()->addDays(20 + $offset),
            ],
            'completed' => [
                'start' => $now->copy()->subDays(60 + $offset),
                'end' => $now->copy()->subDays(20 + $offset),
            ],
            default => [ // started
                'start' => $now->copy()->subDays(rand(1, 14)),
                'end' => $now->copy()->addDays(rand(7, 30)),
            ],
        };
    }
}
