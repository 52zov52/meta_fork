<?php

use App\Models\Academy;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Institution;
use App\Models\Planet;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

it('returns all started activities by default and filters by academy when provided', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $institution = Institution::create(['name' => 'I-'.uniqid()]);
    $academyA = Academy::create([
        'name' => 'Academy A',
        'short_name' => 'A',
        'institution_id' => $institution->id,
    ]);
    $academyB = Academy::create([
        'name' => 'Academy B',
        'short_name' => 'B',
        'institution_id' => $institution->id,
    ]);

    $planet = Planet::create(['name' => 'Planet-'.uniqid()]);
    $category = ActivityCategory::create([
        'name' => 'Category-'.uniqid(),
        'description' => 'desc',
    ]);

    $first = Activity::create([
        'title' => 'Activity A1',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academyA->id,
        'category_id' => $category->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);
    $second = Activity::create([
        'title' => 'Activity B1',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academyB->id,
        'category_id' => $category->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);
    $third = Activity::create([
        'title' => 'Activity A2',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academyA->id,
        'category_id' => $category->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    $allResponse = getJson('/api/v1/student/activity?status=started&sort_method=desc');
    $allResponse->assertOk()->assertJsonCount(3, 'data');

    $academyResponse = getJson("/api/v1/student/activity?status=started&sort_method=desc&academy={$academyA->id}");
    $academyResponse->assertOk()->assertJsonCount(2, 'data');

    $ids = $academyResponse->json('data.*.id');
    expect($ids)->toContain($first->id, $third->id)
        ->not->toContain($second->id);
});
