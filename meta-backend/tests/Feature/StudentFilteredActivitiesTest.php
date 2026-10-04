<?php

use App\Models\Academy;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Institution;
use App\Models\Planet;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

it('filters student activities by status multiple categories search and sort in one request', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $institution = Institution::create(['name' => 'I-'.uniqid()]);
    $academy = Academy::create([
        'name' => 'Academy A',
        'short_name' => 'A',
        'institution_id' => $institution->id,
    ]);

    $planet = Planet::create(['name' => 'Planet-'.uniqid()]);
    $targetCategory = ActivityCategory::create([
        'name' => 'Target-'.uniqid(),
        'description' => 'desc',
    ]);
    $otherCategory = ActivityCategory::create([
        'name' => 'Other-'.uniqid(),
        'description' => 'desc',
    ]);

    Activity::create([
        'title' => 'Alpha target',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => $targetCategory->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);
    Activity::create([
        'title' => 'Beta target',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => $targetCategory->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);
    Activity::create([
        'title' => 'Alpha other category',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => $otherCategory->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);
    Activity::create([
        'title' => 'Alpha target scheduled',
        'description' => 'D',
        'date_start' => now()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
        'score' => 10,
        'status' => 'scheduled',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => $targetCategory->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    $response = getJson(
        "/api/v1/student/activity?status=started&category_ids[]={$targetCategory->id}&category_ids[]={$otherCategory->id}&search=Alpha&sort_method=desc"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $titles = $response->json('data.*.title');
    expect($titles)->toContain('Alpha target', 'Alpha other category')
        ->not->toContain('Alpha target scheduled');
});

