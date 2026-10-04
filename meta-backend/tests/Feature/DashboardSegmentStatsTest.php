<?php

use App\Models\Academy;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityUser;
use App\Models\Member;
use App\Models\Planet;
use App\Models\Portfolio;
use App\Models\Region;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

afterEach(function () {
    Carbon::setTestNow();
});

it('возвращает структурированную статистику за периоды: all_time, semester, academic_year', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'СИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'name' => 'ДИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 4, 'name' => 'Другое', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academy = Academy::create([
        'name' => 'Тестовая',
        'short_name' => 'SegTestAcad',
        'institution_id' => 1,
    ]);

    $academyInst2 = Academy::create([
        'name' => 'ЧПОУ тест',
        'short_name' => 'SegTestChpou',
        'institution_id' => 2,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'Pseg']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-seg-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Act',
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

    $mkMember = function (User $user, int $institutionId, ?int $academyId, int $course) use ($region, $planet) {
        return Member::create([
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => null,
            'gender' => 0,
            'birthday' => '2000-01-01',
            'region_id' => $region->id,
            'planet_id' => $planet->id,
            'institution_id' => $institutionId,
            'institution' => null,
            'course' => $course,
            'student_group' => 'ТД',
            'profile_photo_path' => null,
            'user_id' => $user->id,
            'academy_id' => $academyId,
            'education_level_id' => null,
        ]);
    };

    $userA = User::factory()->create(['is_activated' => 1]);
    $mkMember($userA, 1, $academy->id, 1);

    $auA = ActivityUser::create([
        'user_id' => $userA->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);
    $auA->forceFill(['updated_at' => Carbon::parse('2026-03-10 10:00:00')])->save();

    $userB = User::factory()->create(['is_activated' => 1]);
    $mkMember($userB, 2, $academyInst2->id, 1);

    $auB = ActivityUser::create([
        'user_id' => $userB->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);
    $auB->forceFill(['updated_at' => Carbon::parse('2026-03-11 10:00:00')])->save();

    $userC = User::factory()->create(['is_activated' => 1]);
    $mkMember($userC, 1, $academy->id, 1);

    $portfolio = Portfolio::create([
        'user_id' => $userC->id,
        'title' => 'Сертификат',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => null,
    ]);

    DB::table('activity_portfolio')->insert([
        'portfolio_id' => $portfolio->id,
        'activity_id' => $activity->id,
        'created_at' => Carbon::parse('2026-03-12 10:00:00'),
        'updated_at' => Carbon::parse('2026-03-12 10:00:00'),
    ]);

    $userD = User::factory()->create(['is_activated' => 1]);
    $mkMember($userD, 1, $academy->id, 2);

    $auD = ActivityUser::create([
        'user_id' => $userD->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);
    $auD->forceFill(['updated_at' => Carbon::parse('2026-03-13 10:00:00')])->save();

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/statistics');

    $response->assertOk();

    expect($response->json())->toHaveKeys(['periods', 'demographics', 'activities_by_year']);

    $periods = $response->json('periods');
    expect($periods)->toHaveKeys(['all_time', 'semester', 'academic_year']);

    expect($periods['all_time']['users']['total'])->toBe($periods['all_time']['users']['active']);

    expect($periods['semester']['activities']['total'])->toBe(1);
    expect($periods['semester']['activities']['active'])->toBe(1);
    expect($periods['semester'])->toHaveKeys(['type', 'label', 'start', 'end', 'total_score_sum']);
    expect($periods['semester']['type'])->toBe('semester');
    expect($periods['semester']['label'])->toBe('Весна 2026');

    expect($periods['academic_year'])->toHaveKeys(['type', 'label', 'start', 'end', 'total_score_sum']);
    expect($periods['academic_year']['type'])->toBe('academic_year');

    $demographics = $response->json('demographics');
    expect($demographics)->toHaveKeys(['by_planet']);
    expect($demographics['by_planet'])->toBeArray();
    expect(collect($demographics['by_planet'])->pluck('id')->all())->toBeArray();

    $demoItem = $demographics['by_planet'][0];
    expect($demoItem)->toHaveKeys(['id', 'label', 'count']);

    $activitiesByYear = $response->json('activities_by_year');
    expect($activitiesByYear)->toBeArray();
    expect($activitiesByYear[0] ?? null)->toHaveKeys(['year', 'count']);
});

it('считает суммы баллов глобально для года и семестра только по активным подтверждённым студентам', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'СИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'name' => 'ДИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 4, 'name' => 'Другое', 'created_at' => $now, 'updated_at' => $now],
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

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'Pyear']);
    $admin = User::factory()->withRole('admin')->create();
    $mentor = User::factory()->withRole('mentor')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-year-'.uniqid(),
        'description' => 'd',
    ]);

    $activityInSemester = Activity::create([
        'title' => 'Semester Act',
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

    $activityInAcademicYearOnly = Activity::create([
        'title' => 'Academic year only Act',
        'description' => 'D',
        'date_start' => '2025-10-01',
        'date_end' => '2025-10-02',
        'score' => 8,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $mkMember = function (User $user, ?int $academyId) use ($region, $planet) {
        return Member::create([
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
    };

    $mkConfirmedScore = function (User $user, int $level, Activity $activity) {
        return ActivityUser::create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'level' => $level,
            'is_confirmed' => true,
        ]);
    };

    $mkMember($mentor, $academyA->id);

    $academyAStudent = User::factory()->create(['is_activated' => 1]);
    $mkMember($academyAStudent, $academyA->id);
    $mkConfirmedScore($academyAStudent, 2, $activityInSemester); // 20
    $mkConfirmedScore($academyAStudent, 1, $activityInAcademicYearOnly); // +8 (только в уч.год)

    $academyBStudent = User::factory()->withRole('student', 2)->create(['is_activated' => 1]);
    $mkMember($academyBStudent, $academyB->id);
    $mkConfirmedScore($academyBStudent, 3, $activityInSemester); // 30

    $inactiveStudent = User::factory()->withRole('student', 3)->create(['is_activated' => 0]);
    $mkMember($inactiveStudent, $academyA->id);
    $mkConfirmedScore($inactiveStudent, 4, $activityInAcademicYearOnly); // 32 — неактивный студент, исключается.

    $mentorWithScore = User::factory()->withRole('mentor', 2)->create(['is_activated' => 1]);
    $mkMember($mentorWithScore, $academyA->id);
    $mkConfirmedScore($mentorWithScore, 5, $activityInAcademicYearOnly); // 40 — не student, исключается.

    Sanctum::actingAs($admin);
    $adminResponse = $this->getJson('/api/v1/admin/statistics');
    $adminResponse->assertOk();
    expect($adminResponse->json('periods.academic_year.total_score_sum'))->toBe(58);
    expect($adminResponse->json('periods.semester.total_score_sum'))->toBe(50);

    Sanctum::actingAs($mentor);
    $mentorResponse = $this->getJson('/api/v1/mentor/statistics');
    $mentorResponse->assertOk();
    expect($mentorResponse->json('periods.academic_year.total_score_sum'))->toBe(58);
    expect($mentorResponse->json('periods.semester.total_score_sum'))->toBe(50);
});

it('считает баллы дашборда по earned_score и только по активированным студентам', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'СИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'name' => 'ДИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 4, 'name' => 'Другое', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academy = Academy::create([
        'name' => 'Академия earned',
        'short_name' => 'Earned',
        'institution_id' => 1,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-earned-dashboard']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-dashboard-earned-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Dashboard earned act',
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

    $mkMember = function (User $user) use ($region, $planet, $academy) {
        return Member::create([
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
            'academy_id' => $academy->id,
            'education_level_id' => null,
        ]);
    };

    $activeStudent = User::factory()->withRole('student', 1)->create(['is_activated' => 1]);
    $mkMember($activeStudent);
    ActivityUser::create([
        'user_id' => $activeStudent->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 28,
    ]);

    $inactiveStudent = User::factory()->withRole('student', 2)->create(['is_activated' => 0]);
    $mkMember($inactiveStudent);
    ActivityUser::create([
        'user_id' => $inactiveStudent->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 40,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/statistics');
    $response->assertOk();

    $scoreRow = collect($response->json('general.score_by_categories.all.items'))
        ->firstWhere('category_id', $category->id);

    expect($scoreRow)->not->toBeNull()
        ->and((float) $scoreRow['value'])->toBe(28.0)
        ->and((float) $response->json('general.score_by_categories.all.total'))->toBe(28.0);

    $analyticsCategory = collect($response->json('analytics.score.byCategory'))
        ->firstWhere('id', $category->id);

    expect($analyticsCategory)->not->toBeNull()
        ->and((int) $analyticsCategory['value'])->toBe(28);
});

it('возвращает dashboard analytics в контракте аналитики рейтинга', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $admin = User::factory()->withRole('admin')->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/statistics');

    $response->assertOk();
    $response->assertJsonStructure([
        'analytics' => [
            'period' => ['type', 'label', 'start', 'end'],
            'score' => ['byInstitution', 'byPlanet', 'byCategory', 'byAcademy', 'byAcademyAndCategory'],
            'engagement' => ['byAcademy'],
            'events' => [
                'byCategory',
                'byAcademy' => ['total', 'common', 'common_percent', 'distributed_total', 'items'],
            ],
        ],
    ]);

    $analytics = $response->json('analytics');
    expect($analytics['period']['type'])->toBe('semester')
        ->and($analytics['period']['label'])->toBe('Весна 2026')
        ->and(array_key_exists('ratingByInstitutionType', $analytics))->toBeFalse()
        ->and(array_key_exists('ratingByPlanets', $analytics))->toBeFalse()
        ->and(array_key_exists('ratingByCategories', $analytics))->toBeFalse();
});

it('считает dashboard statistics только по активированным студентам', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-dashboard-active']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-dashboard-active-'.uniqid(),
        'description' => 'd',
    ]);

    $makeMember = function (User $user, int $isActivated) use ($region, $planet) {
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

        $user->forceFill(['is_activated' => $isActivated])->save();
    };

    $activeStudent = User::factory()->withRole('student', 1)->create(['is_activated' => 1]);
    $makeMember($activeStudent, 1);

    $inactiveStudent = User::factory()->withRole('student', 2)->create(['is_activated' => 0]);
    $makeMember($inactiveStudent, 0);

    $mentor = User::factory()->withRole('mentor')->create(['is_activated' => 1]);
    $makeMember($mentor, 1);

    $activity = Activity::create([
        'title' => 'Active stats act',
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
        'title' => 'Inactive stats act',
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
        'title' => 'Mentor stats act',
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
        'activity_id' => $activity->id,
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

    Sanctum::actingAs($admin);
    $response = $this->getJson('/api/v1/admin/statistics');
    $response->assertOk();

    expect($response->json('demographics.by_planet.0.count'))->toBe(1)
        ->and($response->json('general.active_students_in_semester_count'))->toBe(1);
});

it('возвращает одинаковую dashboard analytics для admin mentor и moderator', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'СИЮУ', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academyA = Academy::create([
        'name' => 'Академия A',
        'short_name' => 'A',
        'institution_id' => 1,
    ]);
    $academyB = Academy::create([
        'name' => 'Академия B',
        'short_name' => 'B',
        'institution_id' => 2,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-dashboard-scope']);
    $admin = User::factory()->withRole('admin')->create();
    $mentor = User::factory()->withRole('mentor')->create();
    $moderator = User::factory()->withRole('moderator')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-dashboard-scope-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Dashboard scope act',
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

    $mkMember = function (User $user, int $institutionId, int $academyId, int $course = 1) use ($region, $planet) {
        return Member::create([
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => null,
            'gender' => 0,
            'birthday' => '2000-01-01',
            'region_id' => $region->id,
            'planet_id' => $planet->id,
            'institution_id' => $institutionId,
            'institution' => null,
            'course' => $course,
            'student_group' => 'ТД',
            'profile_photo_path' => null,
            'user_id' => $user->id,
            'academy_id' => $academyId,
            'education_level_id' => null,
        ]);
    };

    $mkMember($mentor, 1, $academyA->id);
    $mkMember($moderator, 1, $academyA->id);

    $studentA = User::factory()->withRole('student', 1)->create(['is_activated' => 1]);
    $mkMember($studentA, 1, $academyA->id);
    ActivityUser::create([
        'user_id' => $studentA->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 30,
    ]);

    $studentB = User::factory()->withRole('student', 2)->create(['is_activated' => 1]);
    $mkMember($studentB, 2, $academyB->id);
    ActivityUser::create([
        'user_id' => $studentB->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
        'earned_score' => 20,
    ]);

    Sanctum::actingAs($admin);
    $adminResponse = $this->getJson('/api/v1/admin/statistics');
    $adminResponse->assertOk();

    Sanctum::actingAs($mentor);
    $mentorResponse = $this->getJson('/api/v1/mentor/statistics');
    $mentorResponse->assertOk();

    $mentorWithFilterResponse = $this->getJson('/api/v1/mentor/statistics?academy_id='.$academyB->id);
    $mentorWithFilterResponse->assertOk();

    Sanctum::actingAs($admin);
    $adminWithFilterResponse = $this->getJson('/api/v1/admin/statistics?academy_id='.$academyB->id);
    $adminWithFilterResponse->assertOk();

    Sanctum::actingAs($moderator);
    $moderatorResponse = $this->getJson('/api/v1/moderator/statistics');
    $moderatorResponse->assertOk();

    expect($mentorResponse->json())->toBe($adminResponse->json())
        ->and($mentorWithFilterResponse->json())->toBe($adminWithFilterResponse->json())
        ->and($moderatorResponse->json())->toBe($adminResponse->json());
});

it('считает мероприятия по категориям из activities, а курсы — через участников курса', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insert([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'СИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 3, 'name' => 'ДИЮУ', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 4, 'name' => 'Другое', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academy = Academy::create([
        'name' => 'Академия мероприятий',
        'short_name' => 'Events',
        'institution_id' => 1,
    ]);
    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-events-dashboard']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-events-'.uniqid(),
        'description' => 'd',
    ]);

    $makeActivity = fn (string $title, string $dateEnd) => Activity::create([
        'title' => $title,
        'description' => 'D',
        'date_start' => $dateEnd,
        'date_end' => $dateEnd,
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $noParticipantActivity = $makeActivity('No participants', '2026-03-01');
    $courseOneActivity = $makeActivity('Course 1', '2026-03-02');
    $courseTwoActivity = $makeActivity('Course 2', '2026-03-03');
    $inactiveOnlyActivity = $makeActivity('Inactive only', '2026-03-04');
    $outsideSemesterActivity = $makeActivity('Outside semester', '2026-01-15');

    $mkStudent = function (int $index, int $course, bool $isActivated) use ($region, $planet, $academy) {
        $user = User::factory()->withRole('student', $index)->create(['is_activated' => $isActivated ? 1 : 0]);
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
            'student_group' => 'ТД',
            'profile_photo_path' => null,
            'user_id' => $user->id,
            'academy_id' => $academy->id,
            'education_level_id' => null,
        ]);

        return $user;
    };

    $courseOneStudent = $mkStudent(1, 1, true);
    $courseTwoStudent = $mkStudent(2, 2, true);
    $inactiveCourseOneStudent = $mkStudent(3, 1, false);

    ActivityUser::create([
        'user_id' => $courseOneStudent->id,
        'activity_id' => $courseOneActivity->id,
        'level' => 1,
        'is_confirmed' => false,
    ]);
    ActivityUser::create([
        'user_id' => $courseTwoStudent->id,
        'activity_id' => $courseTwoActivity->id,
        'level' => 1,
        'is_confirmed' => false,
    ]);
    ActivityUser::create([
        'user_id' => $inactiveCourseOneStudent->id,
        'activity_id' => $inactiveOnlyActivity->id,
        'level' => 1,
        'is_confirmed' => false,
    ]);
    ActivityUser::create([
        'user_id' => $courseOneStudent->id,
        'activity_id' => $outsideSemesterActivity->id,
        'level' => 1,
        'is_confirmed' => false,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/statistics');
    $response->assertOk();

    $allRow = collect($response->json('general.activities_by_categories.all.items'))
        ->firstWhere('category_id', $category->id);
    $courseOneRow = collect($response->json('general.activities_by_categories.course_1.items'))
        ->firstWhere('category_id', $category->id);
    $courseTwoRow = collect($response->json('general.activities_by_categories.course_2.items'))
        ->firstWhere('category_id', $category->id);

    expect($noParticipantActivity)->not->toBeNull()
        ->and((float) $response->json('general.activities_by_categories.all.total'))->toBe(4.0)
        ->and((float) $allRow['value'])->toBe(4.0)
        ->and((float) $response->json('general.activities_by_categories.course_1.total'))->toBe(1.0)
        ->and((float) $courseOneRow['value'])->toBe(1.0)
        ->and((float) $response->json('general.activities_by_categories.course_2.total'))->toBe(1.0)
        ->and((float) $courseTwoRow['value'])->toBe(1.0)
        ->and((float) $response->json('general.activities_by_categories.course_3.total'))->toBe(0.0)
        ->and((float) $response->json('general.activities_by_categories.course_4.total'))->toBe(0.0);
});

it('не считает не-студентов (например наставников) в качестве активных участников на дашборде', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $now = now();
    DB::table('institutions')->insertOrIgnore([
        ['id' => 1, 'name' => 'ИУБиП', 'created_at' => $now, 'updated_at' => $now],
    ]);

    $academy = Academy::create([
        'name' => 'Академия тестирования ролей',
        'short_name' => 'TestRole',
        'institution_id' => 1,
    ]);

    $region = Region::create(['code' => random_int(900000, 999999), 'name' => 'Test-'.uniqid()]);
    $planet = Planet::create(['name' => 'P-role-dashboard']);
    $admin = User::factory()->withRole('admin')->create();
    $category = ActivityCategory::create([
        'name' => 'cat-role-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'Role test act',
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

    // Создаем наставника с подтвержденной активностью
    $mentorUser = User::factory()->withRole('mentor')->create(['is_activated' => 1]);
    Member::create([
        'last_name' => 'Наставников',
        'first_name' => 'Петр',
        'middle_name' => null,
        'gender' => 0,
        'birthday' => '1990-01-01',
        'region_id' => $region->id,
        'planet_id' => $planet->id,
        'institution_id' => 1,
        'course' => null,
        'student_group' => 'М',
        'profile_photo_path' => null,
        'user_id' => $mentorUser->id,
        'academy_id' => $academy->id,
        'education_level_id' => null,
    ]);

    ActivityUser::create([
        'user_id' => $mentorUser->id,
        'activity_id' => $activity->id,
        'level' => 1,
        'is_confirmed' => true,
    ]);

    // Создаем реального студента с подтвержденной активностью
    $studentUser = User::factory()->withRole('student')->create(['is_activated' => 1]);
    Member::create([
        'last_name' => 'Студентов',
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
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/statistics');
    $response->assertOk();

    expect($response->json())->not->toHaveKey('engagement');
});

it('группирует мероприятия по учебным годам с учетом периодов blackout и выносит "Без даты" в конец', function () {
    Carbon::setTestNow('2026-03-15 12:00:00');

    $admin = User::factory()->withRole('admin')->create();
    $planet = Planet::create(['name' => 'P-years-test']);
    $category = ActivityCategory::create([
        'name' => 'cat-years-' . uniqid(),
        'description' => 'd',
    ]);

    // Создаем мероприятия в разных учебных годах
    // 1. Учебный год 2025-2026 (Октябрь 2025)
    Activity::create([
        'title' => 'Act 2025-2026 A',
        'description' => 'D',
        'date_start' => '2025-10-15',
        'date_end' => '2025-10-16',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
    ]);

    // 2. Учебный год 2025-2026 (Март 2026)
    Activity::create([
        'title' => 'Act 2025-2026 B',
        'description' => 'D',
        'date_start' => '2026-03-20',
        'date_end' => '2026-03-21',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
    ]);

    // 3. Учебный год 2026-2027 (Сентябрь 2026)
    Activity::create([
        'title' => 'Act 2026-2027',
        'description' => 'D',
        'date_start' => '2026-09-10',
        'date_end' => '2026-09-11',
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/statistics');
    $response->assertOk();

    $activitiesByYear = $response->json('activities_by_year');

    // Проверяем структуру и наполнение
    expect($activitiesByYear)->toBeArray()->toHaveCount(2);

    expect($activitiesByYear[0]['year'])->toBe('2025-2026');
    expect($activitiesByYear[0]['count'])->toBe(2);

    expect($activitiesByYear[1]['year'])->toBe('2026-2027');
    expect($activitiesByYear[1]['count'])->toBe(1);
});
