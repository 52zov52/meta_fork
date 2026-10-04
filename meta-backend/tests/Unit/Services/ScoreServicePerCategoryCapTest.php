<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityUser;
use App\Models\Planet;
use App\Models\User;
use App\Services\ScoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('ограничивает сумму по категории до cap, а не каждую строку участия', function () {
    Carbon::setTestNow('2026-03-15');

    try {
        $user = User::factory()->create();
        $planet = Planet::create(['name' => 'P-cap']);
        $category = ActivityCategory::create([
            'name' => 'cat-'.uniqid(),
            'description' => 'd',
        ]);

        $makeActivity = fn (string $suffix) => Activity::create([
            'title' => 'A'.$suffix,
            'description' => 'D',
            'date_start' => '2026-03-10',
            'date_end' => '2026-03-11',
            'score' => 30,
            'status' => 'scheduled',
            'planet_id' => $planet->id,
            'category_id' => $category->id,
            'creator_id' => $user->id,
            'photo_path' => null,
        ]);

        $a1 = $makeActivity('1');
        $a2 = $makeActivity('2');

        ActivityUser::create([
            'user_id' => $user->id,
            'activity_id' => $a1->id,
            'level' => 1,
            'is_confirmed' => true,
        ]);
        ActivityUser::create([
            'user_id' => $user->id,
            'activity_id' => $a2->id,
            'level' => 1,
            'is_confirmed' => true,
        ]);

        $total = ScoreService::getCappedTotalForUser((int) $user->id);
        expect($total)->toBe(50);

        $byCategory = ScoreService::getCappedScores((int) $user->id);
        $row = collect($byCategory)->firstWhere('category', $category->name);
        expect($row)->not->toBeNull()
            ->and($row['real_score'])->toBe(60)
            ->and($row['score'])->toBe(50);
    } finally {
        Carbon::setTestNow(null);
    }
});

it('использует earned_score для реальных и ограниченных баллов категории', function () {
    Carbon::setTestNow('2026-03-15');

    try {
        $user = User::factory()->create();
        $planet = Planet::create(['name' => 'P-earned']);
        $category = ActivityCategory::create([
            'name' => 'cat-earned-'.uniqid(),
            'description' => 'd',
        ]);

        $activity = Activity::create([
            'title' => 'A-earned',
            'description' => 'D',
            'date_start' => '2026-03-10',
            'date_end' => '2026-03-11',
            'score' => 10,
            'status' => 'scheduled',
            'planet_id' => $planet->id,
            'category_id' => $category->id,
            'creator_id' => $user->id,
            'photo_path' => null,
        ]);

        ActivityUser::create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'level' => 1,
            'is_confirmed' => true,
            'earned_score' => 60,
        ]);

        $total = ScoreService::getCappedTotalForUser((int) $user->id);
        expect($total)->toBe(50);

        $byCategory = ScoreService::getCappedScores((int) $user->id);
        $row = collect($byCategory)->firstWhere('category', $category->name);
        expect($row)->not->toBeNull()
            ->and($row['real_score'])->toBe(60)
            ->and($row['score'])->toBe(50);
    } finally {
        Carbon::setTestNow(null);
    }
});
