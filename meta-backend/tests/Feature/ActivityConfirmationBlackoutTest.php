<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ScoringBlackoutSetting;
use App\Models\ActivityUser;
use App\Models\Planet;
use App\Models\User;
use App\Services\SemesterService;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

afterEach(function () {
    Carbon::setTestNow();
    SemesterService::clearCache();
});

beforeEach(function () {
    SemesterService::clearCache();
    ScoringBlackoutSetting::query()->create([
        'winter_start' => '12-26',
        'winter_end' => '01-31',
        'summer_start' => '06-26',
        'summer_end' => '08-31',
    ]);
});

it('blocks activity confirmation during blackout dates', function () {
    Carbon::setTestNow('2026-01-15 12:00:00');

    $admin = User::factory()->withRole('admin')->create();
    $student = User::factory()->create();
    $planet = Planet::create(['name' => 'T']);
    $category = ActivityCategory::create([
        'name' => 'c-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'A',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $pivot = ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
        'level' => 0.4,
        'is_confirmed' => false,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/user-confirmation', [
        'id' => $pivot->id,
        'level' => 1.0,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Подтверждение баллов временно недоступно в период подсчета семестра.',
        ]);

    $pivot->refresh();
    expect($pivot->is_confirmed)->toBeFalse();
});

it('allows activity confirmation outside blackout dates', function () {
    Carbon::setTestNow('2026-02-01 12:00:00');

    $admin = User::factory()->withRole('admin')->create();
    $student = User::factory()->create();
    $planet = Planet::create(['name' => 'T']);
    $category = ActivityCategory::create([
        'name' => 'c-'.uniqid(),
        'description' => 'd',
    ]);

    $activity = Activity::create([
        'title' => 'A',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $admin->id,
        'photo_path' => null,
    ]);

    $pivot = ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
        'level' => 0.4,
        'is_confirmed' => false,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/user-confirmation', [
        'id' => $pivot->id,
        'level' => 1.0,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Участие участника успешно подтверждено!']);

    $pivot->refresh();
    expect($pivot->is_confirmed)->toBeTrue();
    expect((float) $pivot->level)->toBe(1.0);
});
