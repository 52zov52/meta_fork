<?php

use App\Models\Academy;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityUser;
use App\Models\Institution;
use App\Models\Planet;
use App\Models\User;
use App\Models\WishlistActivity;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

it('returns student my activities newest first on the first page', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $institution = Institution::create(['name' => 'I-'.uniqid()]);
    $academy = Academy::create([
        'name' => 'Academy-'.uniqid(),
        'short_name' => 'A',
        'institution_id' => $institution->id,
    ]);
    $planet = Planet::create(['name' => 'Planet-'.uniqid()]);
    $category = ActivityCategory::create([
        'name' => 'Category-'.uniqid(),
        'description' => 'desc',
    ]);

    $olderActivity = Activity::create([
        'title' => 'Older activity',
        'description' => 'D',
        'date_start' => now()->subDays(5)->toDateString(),
        'date_end' => now()->subDays(4)->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => $category->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    $newerActivity = Activity::create([
        'title' => 'Newer activity',
        'description' => 'D',
        'date_start' => now()->addDays(5)->toDateString(),
        'date_end' => now()->addDays(6)->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => $category->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    ActivityUser::create([
        'user_id' => $user->id,
        'activity_id' => $olderActivity->id,
    ]);
    ActivityUser::create([
        'user_id' => $user->id,
        'activity_id' => $newerActivity->id,
    ]);

    $response = getJson("/api/v1/student/activity/users/{$user->id}/activities?page=1");
    $response->assertOk()->assertJsonCount(2, 'data');

    expect($response->json('data.0.id'))->toBe($newerActivity->id)
        ->and($response->json('data.1.id'))->toBe($olderActivity->id);
});

it('returns student wishlist activities newest first on the first page', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $institution = Institution::create(['name' => 'I-'.uniqid()]);
    $academy = Academy::create([
        'name' => 'Academy-'.uniqid(),
        'short_name' => 'A',
        'institution_id' => $institution->id,
    ]);
    $planet = Planet::create(['name' => 'Planet-'.uniqid()]);

    $olderActivity = Activity::create([
        'title' => 'Older wishlist activity',
        'description' => 'D',
        'date_start' => now()->subDays(10)->toDateString(),
        'date_end' => now()->subDays(9)->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => ActivityCategory::create([
            'name' => 'Category-'.uniqid(),
            'description' => 'desc',
        ])->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    $newerActivity = Activity::create([
        'title' => 'Newer wishlist activity',
        'description' => 'D',
        'date_start' => now()->addDays(10)->toDateString(),
        'date_end' => now()->addDays(11)->toDateString(),
        'score' => 10,
        'status' => 'started',
        'planet_id' => $planet->id,
        'academy_id' => $academy->id,
        'category_id' => ActivityCategory::create([
            'name' => 'Category-'.uniqid(),
            'description' => 'desc',
        ])->id,
        'creator_id' => $user->id,
        'photo_path' => null,
    ]);

    WishlistActivity::create([
        'user_id' => $user->id,
        'activity_id' => $olderActivity->id,
    ]);
    WishlistActivity::create([
        'user_id' => $user->id,
        'activity_id' => $newerActivity->id,
    ]);

    $response = getJson("/api/v1/student/activity/wishlist/{$user->id}?page=1");
    $response->assertOk()->assertJsonCount(2, 'data');

    expect($response->json('data.0.id'))->toBe($newerActivity->id)
        ->and($response->json('data.1.id'))->toBe($olderActivity->id);
});
