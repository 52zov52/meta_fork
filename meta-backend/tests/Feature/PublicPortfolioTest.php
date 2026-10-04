<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Planet;
use App\Models\Portfolio;
use App\Models\User;

use function Pest\Laravel\getJson;

it('rejects activity date sorting for public portfolio', function () {
    $user = User::factory()->create();

    getJson("/api/v1/public/student/portfolio-public/{$user->id}?sort_by=activity_date")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sort_by');
});

it('requires semester year when semester number is provided for public portfolio filtering', function () {
    $user = User::factory()->create();

    getJson("/api/v1/public/student/portfolio-public/{$user->id}?semester_number=1")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('semester_year');
});

it('filters public portfolio by activity year without semester number', function () {
    $user = User::factory()->create();
    $creator = User::factory()->create();
    $planet = Planet::create(['name' => 'Planet-'.uniqid()]);
    $category = ActivityCategory::create([
        'name' => 'Category-'.uniqid(),
        'description' => 'desc',
    ]);

    $autumnPortfolio = Portfolio::forceCreate([
        'user_id' => $user->id,
        'title' => 'Autumn 2026',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => 'Autumn description',
        'created_at' => '2026-10-10 10:00:00',
    ]);
    $springPortfolio = Portfolio::forceCreate([
        'user_id' => $user->id,
        'title' => 'Spring 2026',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => 'Spring description',
        'created_at' => '2026-05-06 10:00:00',
    ]);
    $otherYearPortfolio = Portfolio::forceCreate([
        'user_id' => $user->id,
        'title' => 'Other year',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => 'Other description',
        'created_at' => '2025-05-06 10:00:00',
    ]);

    $autumnActivity = Activity::create([
        'title' => 'Autumn activity',
        'description' => 'D',
        'date_start' => '2026-10-10',
        'date_end' => '2026-10-11',
        'score' => 10,
        'status' => 'completed',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $creator->id,
        'photo_path' => null,
    ]);
    $springActivity = Activity::create([
        'title' => 'Spring activity',
        'description' => 'D',
        'date_start' => '2026-05-06',
        'date_end' => '2026-05-07',
        'score' => 10,
        'status' => 'completed',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $creator->id,
        'photo_path' => null,
    ]);
    $otherYearActivity = Activity::create([
        'title' => 'Other year activity',
        'description' => 'D',
        'date_start' => '2025-05-06',
        'date_end' => '2025-05-07',
        'score' => 10,
        'status' => 'completed',
        'planet_id' => $planet->id,
        'category_id' => $category->id,
        'creator_id' => $creator->id,
        'photo_path' => null,
    ]);

    $autumnPortfolio->activities()->attach($autumnActivity->id);
    $springPortfolio->activities()->attach($springActivity->id);
    $otherYearPortfolio->activities()->attach($otherYearActivity->id);

    getJson("/api/v1/public/student/portfolio-public/{$user->id}?semester_year=2026&sort_by=title&sort_order=asc")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Autumn 2026')
        ->assertJsonPath('data.1.title', 'Spring 2026');
});

it('returns not found for missing public portfolio user', function () {
    getJson('/api/v1/public/student/portfolio-public/999999')
        ->assertNotFound();
});

it('filters public portfolio by related activity and sorts by portfolio fields', function () {
    $user = User::factory()->create();
    $creator = User::factory()->create();
    $planet = Planet::create(['name' => 'Planet-'.uniqid()]);
    $targetCategory = ActivityCategory::create([
        'name' => 'Target-'.uniqid(),
        'description' => 'desc',
    ]);
    $otherCategory = ActivityCategory::create([
        'name' => 'Other-'.uniqid(),
        'description' => 'desc',
    ]);

    $beta = Portfolio::forceCreate([
        'user_id' => $user->id,
        'title' => 'Beta',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => 'Beta description',
        'created_at' => '2024-09-10 10:00:00',
    ]);
    $alpha = Portfolio::forceCreate([
        'user_id' => $user->id,
        'title' => 'Alpha',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => 'Alpha description',
        'created_at' => '2024-09-10 10:00:00',
    ]);
    $other = Portfolio::forceCreate([
        'user_id' => $user->id,
        'title' => 'Other',
        'path_to_file' => null,
        'original_name' => null,
        'extension' => null,
        'description' => 'Other description',
        'created_at' => '2024-09-10 10:00:00',
    ]);

    $targetActivity = Activity::create([
        'title' => 'Target activity',
        'description' => 'D',
        'date_start' => '2024-09-10',
        'date_end' => '2024-09-11',
        'score' => 10,
        'status' => 'completed',
        'planet_id' => $planet->id,
        'category_id' => $targetCategory->id,
        'creator_id' => $creator->id,
        'photo_path' => null,
    ]);
    $otherActivity = Activity::create([
        'title' => 'Other activity',
        'description' => 'D',
        'date_start' => '2024-09-12',
        'date_end' => '2024-09-13',
        'score' => 10,
        'status' => 'completed',
        'planet_id' => $planet->id,
        'category_id' => $otherCategory->id,
        'creator_id' => $creator->id,
        'photo_path' => null,
    ]);

    $beta->activities()->attach($targetActivity->id);
    $alpha->activities()->attach($targetActivity->id);
    $other->activities()->attach($otherActivity->id);

    $response = getJson(
        "/api/v1/public/student/portfolio-public/{$user->id}?category_id={$targetCategory->id}&month=9&semester_number=1&semester_year=2024&sort_by=title&sort_order=asc"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Alpha')
        ->assertJsonPath('data.1.title', 'Beta')
        ->assertJsonStructure([
            'data' => [[
                'id',
                'user_id',
                'title',
                'description',
                'original_name',
                'extension',
                'file_url',
                'created_at',
                'has_activities',
                'activities_count',
                'activities' => [[
                    'id',
                    'title',
                    'date_start',
                    'date_end',
                    'category_id',
                ]],
            ]],
        ]);
});
