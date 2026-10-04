<?php

use App\Models\Academy;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityUser;
use App\Models\Member;
use App\Models\Planet;
use App\Models\Region;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
afterEach(function () {
    Carbon::setTestNow();
});

it('возвращает рейтинг с полем place и стабильной сортировкой', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'СИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'name' => 'ДИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 4, 'name' => 'Другое', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'R-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-rating']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-r-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Act',
        'description' => 'D',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-31',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $mkStudent = function (int $userId, int $course) use ($region, $planet) {
        Member::create([
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => null,
            'gender' => 0,
            'birthday' => '2000-01-01',
            'region_id' => $region->id,
            'planet_id' => $planet->id,
            'institution_id' => 1,
            'institution' => null,
            'course' => $course,
            'student_group' => 'Э1-01',
            'profile_photo_path' => null,
            'user_id' => $userId,
            'academy_id' => null,
            'education_level_id' => null,
        ]);
    };

    $u1 = User::factory()->create(['is_activated' => 1, 'role' => 'student']);
    $mkStudent($u1->id, 1);
    $u2 = User::factory()->create(['is_activated' => 1, 'role' => 'student']);
    $mkStudent($u2->id, 1);

    $u3 = User::factory()->create(['is_activated' => 1, 'role' => 'student']);
    $mkStudent($u3->id, 1);

    $au1 = ActivityUser::create([
        'user_id' => $u1->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);
    $au1->forceFill(['updated_at' => Carbon::parse('2026-03-10 10:00:00')])->save();

    $au2 = ActivityUser::create([
        'user_id' => $u2->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);
    $au2->forceFill(['updated_at' => Carbon::parse('2026-03-11 10:00:00')])->save();

    // Lower score to validate DENSE_RANK() (not RANK / ROW_NUMBER)
    $activityLow = Activity::create([
        'title' => 'Act-low',
        'description' => 'D',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-31',
        'score' => 5,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);
    $au3 = ActivityUser::create([
        'user_id' => $u3->id,
        'activity_id' => $activityLow->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);
    $au3->forceFill(['updated_at' => Carbon::parse('2026-03-12 10:00:00')])->save();

    $response = $this->getJson('/api/v1/rating/users?per_page=10');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(3);
    $places = collect($data)->pluck('place')->sort()->values()->all();
    expect($places)->toBe([1, 1, 2]);
    $scores = collect($data)->pluck('score')->sortDesc()->values()->all();
    expect($scores[0])->toBe($scores[1])
        ->and($scores[2])->toBeLessThan($scores[0]);

    expect(collect($queries)->contains(fn (string $sql) => str_contains(strtolower($sql), ' over ')))->toBeFalse();
});

it('возвращает полный payload аналитики рейтинга', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $response = $this->getJson('/api/v1/rating/analytics');

    $response->assertOk();
    $response->assertJsonStructure([
        'period' => ['type', 'label', 'start', 'end'],
        'score' => ['byInstitution', 'byPlanet', 'byCategory', 'byAcademy', 'byAcademyAndCategory'],
        'engagement' => ['byAcademy'],
        'events' => [
            'byCategory',
            'byAcademy' => ['total', 'common', 'common_percent', 'distributed_total', 'items'],
        ],
    ]);
    expect($response->json('period.type'))->toBe('semester');
    expect($response->json('period.label'))->toBe('Весна 2026');
});

it('строит аналитику только по активированным студентам', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'R-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-active-only']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-active-only-'.uniqid(),
        'description' => 'd',
    ]);

    $makeMember = function (User $user, bool $activated) use ($region, $planet) {
        Member::create([
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => null,
            'gender' => 0,
            'birthday' => '2000-01-01',
            'region_id' => $region->id,
            'planet_id' => $planet->id,
            'institution_id' => 1,
            'institution' => null,
            'course' => 1,
            'student_group' => 'ТД',
            'profile_photo_path' => null,
            'user_id' => $user->id,
            'academy_id' => null,
            'education_level_id' => null,
        ]);

        $user->forceFill(['is_activated' => $activated ? 1 : 0])->save();
    };

    $activeStudent = User::factory()->withRole('student', 1)->create(['is_activated' => 1]);
    $makeMember($activeStudent, true);

    $inactiveStudent = User::factory()->withRole('student', 2)->create(['is_activated' => 0]);
    $makeMember($inactiveStudent, false);

    $mentor = User::factory()->withRole('mentor')->create(['is_activated' => 1]);
    Member::create([
        'last_name' => 'Наставник',
        'first_name' => 'Иван',
        'middle_name' => null,
        'gender' => 0,
        'birthday' => '1990-01-01',
        'region_id' => $region->id,
        'planet_id' => $planet->id,
        'institution_id' => 1,
        'institution' => null,
        'course' => null,
        'student_group' => 'ТД',
        'profile_photo_path' => null,
        'user_id' => $mentor->id,
        'academy_id' => null,
        'education_level_id' => null,
    ]);

    $activeActivity = Activity::create([
        'title' => 'Active act',
        'description' => 'D',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-02',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $inactiveActivity = Activity::create([
        'title' => 'Inactive act',
        'description' => 'D',
        'date_start' => '2026-03-03',
        'date_end' => '2026-03-04',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $mentorActivity = Activity::create([
        'title' => 'Mentor act',
        'description' => 'D',
        'date_start' => '2026-03-05',
        'date_end' => '2026-03-06',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    ActivityUser::create([
        'user_id' => $activeStudent->id,
        'activity_id' => $activeActivity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 10,
    ]);

    ActivityUser::create([
        'user_id' => $inactiveStudent->id,
        'activity_id' => $inactiveActivity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 10,
    ]);

    ActivityUser::create([
        'user_id' => $mentor->id,
        'activity_id' => $mentorActivity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 10,
    ]);

    $response = $this->getJson('/api/v1/rating/analytics');
    $response->assertOk();

    expect($response->json('score.byAcademy'))->toBe([])
        ->and($response->json('score.byInstitution.0.value'))->toBe(10)
        ->and($response->json('events.byCategory.0.count'))->toBe(1)
        ->and($response->json('events.byAcademy.total'))->toBe(1);
});

it('распределяет мероприятия по академиям для аналитики через явную привязку, наставника и общие', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academyA = Academy::create([
        'name' => 'Академия A',
        'short_name' => 'A',
        'institution_id' => 1,
    ]);
    $academyB = Academy::create([
        'name' => 'Академия B',
        'short_name' => 'B',
        'institution_id' => 1,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'R-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-events-by-academy']);
    $category = ActivityCategory::create([
        'name' => 'cat-events-by-academy-'.uniqid(),
        'description' => 'd',
    ]);

    $admin = User::factory()->withRole('admin')->create();
    $mentorA = User::factory()->withRole('mentor')->create();

    Member::create([
        'last_name' => 'Наставник',
        'first_name' => 'А',
        'middle_name' => null,
        'gender' => 0,
        'birthday' => '1990-01-01',
        'region_id' => $region->id,
        'planet_id' => $planet->id,
        'institution_id' => 1,
        'institution' => null,
        'course' => null,
        'student_group' => 'ТД',
        'profile_photo_path' => null,
        'user_id' => $mentorA->id,
        'academy_id' => $academyA->id,
        'education_level_id' => null,
    ]);

    $makeActivity = function (string $title, User $creator, ?int $academyId) use ($planet, $category) {
        return Activity::create([
            'title' => $title,
            'description' => 'D',
            'date_start' => '2026-03-01',
            'date_end' => '2026-03-02',
            'score' => 10,
            'status' => 'scheduled',
            'planet_id' => $planet->id,
            'academy_id' => $academyId,
            'category_id' => $category->id,
            'creator_id' => $creator->id,
            'photo_path' => null,
        ]);
    };

    $makeActivity('Explicit B by admin', $admin, $academyB->id);
    $makeActivity('Mentor A without explicit academy', $mentorA, null);
    $makeActivity('Common admin without academy', $admin, null);

    $response = $this->getJson('/api/v1/rating/analytics');
    $response->assertOk();

    $byAcademy = $response->json('events.byAcademy');
    $items = collect($byAcademy['items']);

    expect($byAcademy['total'])->toBe(0)
        ->and($byAcademy['common'])->toBe(0)
        ->and((float) $byAcademy['common_percent'])->toBe(0.0)
        ->and($byAcademy['distributed_total'])->toBe(0)
        ->and($items)->toBeEmpty();
});

it('считает процент баллов академии от общей суммы по категории', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academyA = Academy::create([
        'name' => 'Академия A',
        'short_name' => 'A',
        'institution_id' => 1,
    ]);
    $academyB = Academy::create([
        'name' => 'Академия B',
        'short_name' => 'B',
        'institution_id' => 1,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'R-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-percent']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'Проектная-percent-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Percent Act',
        'description' => 'D',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-02',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $mkStudent = function (int $index, int $academyId, int $earnedScore) use ($region, $planet, $activity) {
        $user = User::factory()->withRole('student', $index)->create(['is_activated' => 1]);
        Member::create([
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => null,
            'gender' => 0,
            'birthday' => '2000-01-01',
            'region_id' => $region->id,
            'planet_id' => $planet->id,
            'institution_id' => 1,
            'institution' => null,
            'course' => 1,
            'student_group' => 'ТД',
            'profile_photo_path' => null,
            'user_id' => $user->id,
            'academy_id' => $academyId,
            'education_level_id' => null,
        ]);
        ActivityUser::create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'level' => 1,
            'is_confirmed' => true,
            'earned_score' => $earnedScore,
        ]);
    };

    $mkStudent(1, $academyA->id, 30);
    $mkStudent(2, $academyB->id, 20);

    $response = $this->getJson('/api/v1/rating/analytics');
    $response->assertOk();

    $rows = collect($response->json('score.byAcademyAndCategory'));
    $academyACategory = collect($rows->firstWhere('id', $academyA->id)['categories'])
        ->firstWhere('label', $category->name);
    $academyBCategory = collect($rows->firstWhere('id', $academyB->id)['categories'])
        ->firstWhere('label', $category->name);

    expect($academyACategory['value'])->toBe(30)
        ->and((float) $academyACategory['percent'])->toBe(60.0)
        ->and($academyBCategory['value'])->toBe(20)
        ->and((float) $academyBCategory['percent'])->toBe(40.0);
});

it('возвращает нелимитированные (uncapped) баллы в аналитике рейтинга для студентов с баллами больше 50 в одной категории', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insertOrIgnore([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academy = Academy::create([
        'name' => 'Академия аналитики без лимита',
        'short_name' => 'NoLimit',
        'institution_id' => 1,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-nolimit-dashboard']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-nolimit-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Nolimit act',
        'description' => 'D',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-02',
        'score' => 100,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    // Создаем студента с 100 подтвержденными баллами (больше лимита 50)
    $studentUser = User::factory()->withRole('student')->create(['is_activated' => 1]);
    Member::create([
        'last_name' => 'Сверхлимитный',
        'first_name' => 'Иван',
        'middle_name' => null,
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => $region->id,
        'planet_id' => $planet->id,
        'institution_id' => 1,
        'course' => 1,
        'student_group' => 'С',
        'profile_photo_path' => null,
        'user_id' => $studentUser->id,
        'academy_id' => $academy->id,
        'education_level_id' => null,
    ]);

    ActivityUser::create([
        'user_id' => $studentUser->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 100,
    ]);

    $response = $this->getJson('/api/v1/rating/analytics');
    $response->assertOk();

    // 1. Проверяем, что в аналитике отдается полная сумма 100 (Uncapped)
    $academyData = collect($response->json('score.byAcademy'))
        ->firstWhere('id', $academy->id);
    expect($academyData['value'])->toBe(100);

    // 2. А в таблице лидеров (рейтинге пользователей) по-прежнему лимитируется (Capped до 50)
    $leaderboardResponse = $this->getJson('/api/v1/rating/users');
    $leaderboardResponse->assertOk();
    $leaderboardData = collect($leaderboardResponse->json('data'))
        ->firstWhere('user_id', $studentUser->id);
    expect($leaderboardData['score'])->toBe(50);
});
