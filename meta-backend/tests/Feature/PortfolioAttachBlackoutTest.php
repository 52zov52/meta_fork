<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Planet;
use App\Models\Portfolio;
use App\Models\ScoringBlackoutSetting;
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

it('blocks portfolio attach to activity during scoring blackout', function () {
    Carbon::setTestNow('2026-08-15 12:00:00');

    $student = User::factory()->withRole('student')->create();
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
        'creator_id' => $student->id,
        'photo_path' => null,
    ]);

    $portfolio = Portfolio::create([
        'user_id' => $student->id,
        'title' => 'P',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => null,
    ]);

    Sanctum::actingAs($student);

    $response = $this->postJson('/api/v1/student/portfolio/attach-to-activity', [
        'portfolio_id' => $portfolio->id,
        'activity_id' => $activity->id,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Привязка портфолио временно недоступна в период подсчета семестра.',
        ]);

    expect($portfolio->activities()->where('activity_id', $activity->id)->exists())->toBeFalse();
});

it('allows portfolio attach outside scoring blackout', function () {
    Carbon::setTestNow('2026-09-05 12:00:00');

    $student = User::factory()->withRole('student')->create();
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
        'creator_id' => $student->id,
        'photo_path' => null,
    ]);

    $portfolio = Portfolio::create([
        'user_id' => $student->id,
        'title' => 'P',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => null,
    ]);

    Sanctum::actingAs($student);

    $response = $this->postJson('/api/v1/student/portfolio/attach-to-activity', [
        'portfolio_id' => $portfolio->id,
        'activity_id' => $activity->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($portfolio->activities()->where('activity_id', $activity->id)->exists())->toBeTrue();
});
