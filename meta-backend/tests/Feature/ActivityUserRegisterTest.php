<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityUser;
use App\Models\Planet;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

afterEach(function () {
    Carbon::setTestNow();
});

it('registers for an activity then returns conflict on duplicate', function () {
    $user = User::factory()->create();
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
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    Sanctum::actingAs($user);

    $payload = ['user_id' => $user->id, 'activity_id' => $activity->id];

    $first = $this->postJson('/api/v1/student/activity/register', $payload);
    $first->assertOk()
        ->assertJson(['message' => 'Вы успешно зарегистрировались на мероприятие!']);

    expect(ActivityUser::where('user_id', $user->id)->where('activity_id', $activity->id)->count())->toBe(1);

    $second = $this->postJson('/api/v1/student/activity/register', $payload);
    $second->assertStatus(409)
        ->assertJson(['message' => 'Вы уже зарегистрированы на это мероприятие.']);

    expect(ActivityUser::where('user_id', $user->id)->where('activity_id', $activity->id)->count())->toBe(1);
});

it('allows registering during blackout period', function () {
    Carbon::setTestNow('2026-01-15 12:00:00');

    $user = User::factory()->create();
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
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/student/activity/register', [
        'user_id' => $user->id,
        'activity_id' => $activity->id,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Вы успешно зарегистрировались на мероприятие!']);
});
