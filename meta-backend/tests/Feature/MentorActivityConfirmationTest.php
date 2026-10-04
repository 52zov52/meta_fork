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

it('allows any mentor to confirm activity points even if not creator', function () {
    Carbon::setTestNow('2026-02-01 12:00:00');

    $creatorMentor = User::factory()->withRole('mentor', 1)->create(['is_activated' => 1]);
    $otherMentor = User::factory()->withRole('mentor', 2)->create(['is_activated' => 1]);
    $student = User::factory()->create();
    $planet = Planet::create(['name' => 'TestPlanet'.uniqid()]);
    $category = ActivityCategory::create([
        'name' => 'Cat-'.uniqid(),
        'description' => 'desc',
    ]);

    $activity = Activity::create([
        'title' => 'Общее мероприятие (ИТ-диктант)',
        'description' => 'Описание',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 20,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $creatorMentor->id,
        'photo_path' => null,
    ]);

    $pivot = ActivityUser::create([
        'user_id' => $student->id,
        'activity_id' => $activity->id,
        'level' => 0.4,
        'is_confirmed' => false,
    ]);

    Sanctum::actingAs($otherMentor);

    $response = $this->postJson('/api/v1/mentor/user-confirmation-mentor', [
        'id' => $pivot->id,
        'level' => 1.0,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Участие участника успешно подтверждено!']);

    $pivot->refresh();
    expect($pivot->is_confirmed)->toBeTrue();
    expect((float) $pivot->level)->toBe(1.0);
});
