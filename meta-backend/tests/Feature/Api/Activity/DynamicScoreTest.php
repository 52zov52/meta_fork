<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityScore;
use App\Models\ActivityUser;
use App\Models\EducationLevel;
use App\Models\Member;
use App\Models\Planet;
use App\Models\ScoringBlackoutSetting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Создаем справочники через DB::table чтобы bypass mass-assignment
    DB::table('activity_categories')->insert([
        ['id' => 1, 'name' => 'Проектная', 'description' => '', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Научная', 'description' => '', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Общественная', 'description' => '', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'name' => 'Культурно-творческая', 'description' => '', 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('education_levels')->insert([
        ['id' => 1, 'name' => 'Колледж', 'nuo_value' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Бакалавриат', 'nuo_value' => 2, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Магистратура', 'nuo_value' => 3, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('planets')->insert(['id' => 1, 'name' => 'Test Planet', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('regions')->insert(['id' => 1, 'code' => 77, 'name' => 'Moscow', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('institutions')->insert(['id' => 1, 'name' => 'Test Institution', 'created_at' => now(), 'updated_at' => now()]);

    ScoringBlackoutSetting::create([
        'winter_start' => '01-15',
        'winter_end' => '02-01',
        'summer_start' => '06-15',
        'summer_end' => '07-01',
    ]);

    $this->seed(\Database\Seeders\KsuMatrixSeeder::class);
    $this->seed(\Database\Seeders\BaseScoreSeeder::class);
});

afterEach(function () {
    \App\Services\SemesterService::clearCache();
});

it('mentor can create activity with dynamic score calculation', function () {
    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $response = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Test Dynamic Activity',
        'description' => 'Test description',
        'category_id' => 1,
        'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
            'target_education_level_ids' => [2],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);

    $response->assertOk();

    $activity = Activity::latest()->first();
    expect((bool) $activity->is_dynamic_score)->toBeTrue();
    expect($activity->score)->toBe(0);

    // 4 места × 3 уровня обучения = 12 строк (баллы сохраняются для всех уровней)
    $scoresCount = ActivityScore::where('activity_id', $activity->id)->count();
    expect($scoresCount)->toBe(12);
});

it('cultural outreach saves 3 scores (only participation × 3 levels)', function () {
    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $response = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Просветительское мероприятие',
        'description' => 'Диктант',
        'category_id' => 4,
        'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'cultural_outreach',
            'target_education_level_ids' => [1],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);

    $response->assertOk();

    $activity = Activity::latest()->first();
    // Только participation × 3 уровня (без призовых мест)
    expect(ActivityScore::where('activity_id', $activity->id)->count())->toBe(3);

    $college = ActivityScore::where('activity_id', $activity->id)
        ->where('education_level_id', 1)
        ->where('place', 'participation')
        ->first();
    expect($college->final_score)->toBe(5); // base=5, target=Колледж, для Колледжа KSU=1.00

    $bachelor = ActivityScore::where('activity_id', $activity->id)
        ->where('education_level_id', 2)
        ->where('place', 'participation')
        ->first();
    expect($bachelor->final_score)->toBe(5); // base=5, KSU=0.90 → round(5*0.9)=5
});

it('mentor can create social dynamic activity without activity type', function () {
    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $response = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Social Dynamic Activity',
        'description' => 'Test description',
        'category_id' => 3,
        'is_dynamic_score' => true,
        'parameters' => [
            'is_leader' => false,
            'target_education_level_ids' => [1],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);

    $response->assertOk();

    $activity = Activity::latest()->first();
    expect($activity->parameters->activity_type)->toBeNull();
    expect(ActivityScore::where('activity_id', $activity->id)->count())->toBe(3);
});

it('legacy activity still uses score multiplied by level', function () {
    $user = User::factory()->create(['role' => 'student']);
    $member = Member::create([
        'user_id' => $user->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'education_level_id' => 2,
    ]);

    $activity = Activity::create([
        'title' => 'Legacy Activity',
        'description' => 'Legacy',
        'category_id' => 1,
        'is_dynamic_score' => false,
        'score' => 30,
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'completed',
        'creator_id' => $user->id,
    ]);

    ActivityUser::create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'level' => 1.0,
        'is_confirmed' => true,
    ]);

    // ScoreService должен вернуть 30 (legacy формула)
    $total = \App\Services\ScoreService::getCappedTotalForUser($user->id);
    expect($total)->toBe(30);
});

it('calculates preview for single target with KSU coefficient', function () {
    $response = $this->postJson('/api/v1/activities/calculate-preview', [
        'category_id' => 1,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
        ],
        'target_education_level_ids' => [2],
    ]);

    $response->assertOk();
    $preview = $response->json('preview');

    expect($preview)->toHaveCount(3);

    // Колледж (nuo=1, target=2, delta=+1, ksu=1.10)
    $college = collect($preview)->firstWhere('education_level_id', 1);
    expect($college['scores']['1st_place'])->toBe(28);
    expect($college['scores']['participation'])->toBe(11);
    expect($college['is_target'])->toBeFalse();

    // Бакалавриат — целевой
    $bachelor = collect($preview)->firstWhere('education_level_id', 2);
    expect($bachelor['scores']['1st_place'])->toBe(25);
    expect($bachelor['is_target'])->toBeTrue();

    // Магистратура (nuo=3, target=2, delta=-1, ksu=0.90)
    $master = collect($preview)->firstWhere('education_level_id', 3);
    expect($master['scores']['1st_place'])->toBe(23);
});

it('calculates preview with adjacent pair Колледж + Бакалавриат: магистратура −10%', function () {
    $response = $this->postJson('/api/v1/activities/calculate-preview', [
        'category_id' => 1,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
        ],
        'target_education_level_ids' => [1, 2],
    ]);

    $response->assertOk();
    $preview = $response->json('preview');

    $college = collect($preview)->firstWhere('education_level_id', 1);
    expect($college['is_target'])->toBeTrue();
    expect((float) $college['ksu'])->toBe(1.00);
    expect($college['scores']['participation'])->toBe(10);

    $bachelor = collect($preview)->firstWhere('education_level_id', 2);
    expect($bachelor['is_target'])->toBeTrue();
    expect((float) $bachelor['ksu'])->toBe(1.00);
    expect($bachelor['scores']['participation'])->toBe(10);

    // Магистратура: ближайший target = Бакалавриат (NUO=2), delta = -1, ksu = 0.90
    $master = collect($preview)->firstWhere('education_level_id', 3);
    expect($master['is_target'])->toBeFalse();
    expect((float) $master['ksu'])->toBe(0.90);
    expect($master['scores']['participation'])->toBe(9); // round(10*0.9)
});

it('calculates preview with adjacent pair Бакалавриат + Магистратура: колледж +10%', function () {
    $response = $this->postJson('/api/v1/activities/calculate-preview', [
        'category_id' => 1,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
        ],
        'target_education_level_ids' => [2, 3],
    ]);

    $response->assertOk();
    $preview = $response->json('preview');

    // Колледж: ближайший target = Бакалавриат (NUO=2), delta = +1, ksu = 1.10
    $college = collect($preview)->firstWhere('education_level_id', 1);
    expect($college['is_target'])->toBeFalse();
    expect((float) $college['ksu'])->toBe(1.10);
    expect($college['scores']['participation'])->toBe(11);

    $bachelor = collect($preview)->firstWhere('education_level_id', 2);
    expect($bachelor['is_target'])->toBeTrue();
    expect((float) $bachelor['ksu'])->toBe(1.00);

    $master = collect($preview)->firstWhere('education_level_id', 3);
    expect($master['is_target'])->toBeTrue();
    expect((float) $master['ksu'])->toBe(1.00);
});

it('rejects non-adjacent target pair Колледж + Магистратура in preview', function () {
    $response = $this->postJson('/api/v1/activities/calculate-preview', [
        'category_id' => 1,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
        ],
        'target_education_level_ids' => [1, 3],
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('соседние');
});

it('rejects non-adjacent target pair when storing activity', function () {
    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $response = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Bad pair',
        'description' => 'Test',
        'category_id' => 1,
        'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
            'target_education_level_ids' => [1, 3],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);

    $response->assertStatus(422);
});

it('preview without target returns equal base scores for all levels', function () {
    $response = $this->postJson('/api/v1/activities/calculate-preview', [
        'category_id' => 1,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
        ],
    ]);

    $response->assertOk();
    $preview = $response->json('preview');

    expect($preview)->toHaveCount(3);

    foreach ($preview as $row) {
        expect($row['scores']['1st_place'])->toBe(25);
        expect($row['scores']['participation'])->toBe(10);
        expect($row['is_target'])->toBeFalse();
    }
});

it('student can view activity scores for dynamic activity', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'education_level_id' => 2,
    ]);

    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Dynamic View Test',
        'description' => 'Test',
        'category_id' => 1,
        'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
            'target_education_level_ids' => [2],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);

    $activity = Activity::latest()->first();

    Sanctum::actingAs($student);
    $response = $this->getJson("/api/v1/activities/{$activity->id}/scores");
    $response->assertOk();

    $data = $response->json();
    expect($data['is_dynamic_score'])->toBeTrue();
    expect($data['user_level_id'])->toBe(2);
    expect($data['target_education_level_ids'])->toBe([2]);
    expect($data['user_scores']['1st_place'])->toBe(25);
    expect($data['user_personal_score'])->toBe(10);
});

it('available_places: 4 пункта для мероприятия с местами', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'X', 'first_name' => 'Y', 'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1, 'planet_id' => 1, 'institution_id' => 1,
        'education_level_id' => 2,
    ]);

    $mentor = User::factory()->create(['role' => 'mentor']);
    Sanctum::actingAs($mentor);
    $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Hackathon', 'description' => '-',
        'category_id' => 1, 'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
            'target_education_level_ids' => [2],
        ],
        'date_start' => '2026-04-01', 'date_end' => '2026-04-02',
        'planet_id' => 1, 'status' => 'scheduled', 'user_id' => $mentor->id,
    ])->assertOk();

    $activity = Activity::latest()->first();
    Sanctum::actingAs($student);
    $data = $this->getJson("/api/v1/activities/{$activity->id}/scores")->json();

    expect($data['available_places'])->toHaveCount(4);
    expect(collect($data['available_places'])->pluck('value')->all())
        ->toBe(['1st', '2nd', '3rd', 'participation']);
    expect($data['available_places'][3]['label'])->toBe('Подтвердить участие');
});

it('available_places: только participation для просветительского мероприятия', function () {
    $mentor = User::factory()->create(['role' => 'mentor']);
    Sanctum::actingAs($mentor);
    $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Диктант', 'description' => '-',
        'category_id' => 4, 'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'cultural_outreach',
            'target_education_level_ids' => [],
        ],
        'date_start' => '2026-04-01', 'date_end' => '2026-04-02',
        'planet_id' => 1, 'status' => 'scheduled', 'user_id' => $mentor->id,
    ])->assertOk();

    $activity = Activity::latest()->first();
    $data = $this->getJson("/api/v1/activities/{$activity->id}/scores")->json();

    expect($data['available_places'])->toHaveCount(1);
    expect($data['available_places'][0]['value'])->toBe('participation');
    expect($data['available_places'][0]['label'])->toBe('Подтвердить участие');
});

it('available_places: лейбл «лидер движения» для общественной с is_leader=true', function () {
    $mentor = User::factory()->create(['role' => 'mentor']);
    Sanctum::actingAs($mentor);
    $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Лидер движения', 'description' => '-',
        'category_id' => 3, 'is_dynamic_score' => true,
        'parameters' => [
            'is_leader' => true,
            'target_education_level_ids' => [2],
        ],
        'date_start' => '2026-04-01', 'date_end' => '2026-04-02',
        'planet_id' => 1, 'status' => 'scheduled', 'user_id' => $mentor->id,
    ])->assertOk();

    $activity = Activity::latest()->first();
    $data = $this->getJson("/api/v1/activities/{$activity->id}/scores")->json();

    expect($data['available_places'])->toHaveCount(1);
    expect($data['available_places'][0]['value'])->toBe('participation');
    expect($data['available_places'][0]['label'])->toBe('Подтвердить (лидер движения)');
    expect($data['is_leader'])->toBeTrue();
});

it('admin can confirm participation with ksu for dynamic activity', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'education_level_id' => 1,
    ]);

    $mentor = User::factory()->create(['role' => 'mentor']);

    // target=Бакалавриат, KSU для Колледжа = 1.10
    Sanctum::actingAs($mentor);
    $response = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'Ksu Confirmation Test',
        'description' => 'Test',
        'category_id' => 1,
        'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => true,
            'target_education_level_ids' => [2],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);
    $response->assertOk();

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull();

    $au = ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($admin);
    $response = $this->postJson('/api/v1/admin/user-confirmation-ksu', [
        'id' => $au->id,
        'place' => '1st',
    ]);

    $response->assertOk();

    $au->refresh();
    expect($au->is_confirmed)->toBeTrue();
    expect($au->earned_score)->toBe(28); // round(25 * 1.10)
    expect($au->place)->toBe('1st');
});

it('returns error when confirming legacy activity via ksu endpoint', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'education_level_id' => 2,
    ]);

    $activity = Activity::create([
        'title' => 'Legacy Activity',
        'description' => 'Legacy',
        'category_id' => 1,
        'is_dynamic_score' => false,
        'score' => 30,
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'completed',
        'creator_id' => $student->id,
    ]);

    $au = ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($admin);
    $response = $this->postJson('/api/v1/admin/user-confirmation-ksu', [
        'id' => $au->id,
        'place' => '1st',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('КСУ');
});

it('returns error on bulk confirmation for ksu activity when scores are missing', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'education_level_id' => 1,
    ]);

    $activity = Activity::create([
        'title' => 'KSU Activity Without Scores',
        'description' => 'No scores generated',
        'category_id' => 1,
        'is_dynamic_score' => true,
        'score' => 0,
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'creator_id' => $student->id,
    ]);

    ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/activities/bulk-confirmation', [
        'activity_id' => $activity->id,
        'level' => 1.0,
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('Баллы для данного места не найдены');
});

it('bulk confirmation uses participation place for ksu activity without prize places', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'education_level_id' => 1,
    ]);

    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $createResponse = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'KSU No Places',
        'description' => 'Test',
        'category_id' => 1,
        'is_dynamic_score' => true,
        'parameters' => [
            'activity_type' => 'external',
            'has_places' => false,
            'target_education_level_ids' => [1],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);
    $createResponse->assertOk();

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull();

    ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/activities/bulk-confirmation', [
        'activity_id' => $activity->id,
        'level' => 1.0,
    ]);

    $response->assertOk();
    expect($response->json('message'))->toContain('1 студентов');

    $au = ActivityUser::where('user_id', $student->id)
        ->where('activity_id', $activity->id)
        ->first();

    expect($au)->not->toBeNull();
    expect($au->is_confirmed)->toBeTrue();
    expect($au->place)->toBe('participation');
    expect((float) $au->level)->toBe(0.4);
    expect($au->earned_score)->not->toBeNull();
});

it('bulk confirmation rejects ksu participants without education level', function () {
    $student = User::factory()->create(['role' => 'student']);
    Member::create([
        'user_id' => $student->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'student_group' => 'К1П',
        'education_level_id' => null,
    ]);

    $mentor = User::factory()->create(['role' => 'mentor']);

    Sanctum::actingAs($mentor);
    $createResponse = $this->postJson('/api/v1/mentor/activity/store-mentor', [
        'title' => 'KSU Equal Scores Missing Level',
        'description' => 'Test',
        'category_id' => 3,
        'is_dynamic_score' => true,
        'parameters' => [
            'has_places' => false,
            'target_education_level_ids' => [],
        ],
        'date_start' => '2026-04-01',
        'date_end' => '2026-04-02',
        'planet_id' => 1,
        'status' => 'scheduled',
        'user_id' => $mentor->id,
    ]);
    $createResponse->assertOk();

    $activity = Activity::latest()->first();
    expect($activity)->not->toBeNull();

    ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/activities/bulk-confirmation', [
        'activity_id' => $activity->id,
        'level' => 0.4,
        'only_with_portfolio' => false,
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('отсутствуют уровни обучения');

    $au = ActivityUser::where('user_id', $student->id)
        ->where('activity_id', $activity->id)
        ->first();

    expect($au)->not->toBeNull();
    expect($au->is_confirmed)->toBeFalse();
    expect($au->place)->toBeNull();
    expect($au->earned_score)->toBeNull();
});
