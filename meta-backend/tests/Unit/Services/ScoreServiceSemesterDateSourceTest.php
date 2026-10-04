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

it('counts confirmed score by activity date_end within current semester', function () {
    Carbon::setTestNow('2026-03-15');

    try {
        $user = User::factory()->create();
        $planet = Planet::create(['name' => 'P-sem']);
        $category = ActivityCategory::create([
            'name' => 'cat-'.uniqid(),
            'description' => 'd',
        ]);

        $activity = Activity::create([
            'title' => 'A-sem',
            'description' => 'D',
            'date_start' => '2026-03-10',
            'date_end' => '2026-03-11', // current semester
            'score' => 20,
            'status' => 'scheduled',
            'planet_id' => $planet->id,
            'category_id' => $category->id,
            'creator_id' => $user->id,
            'photo_path' => null,
        ]);

        $link = ActivityUser::create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'level' => 1,
            'is_confirmed' => true,
        ]);

        // outside current semester; should not affect counting basis
        $link->timestamps = false;
        $link->updated_at = '2026-01-27 12:00:00';
        $link->created_at = '2026-01-27 12:00:00';
        $link->save();

        $total = ScoreService::getCappedTotalForUser((int) $user->id);
        expect($total)->toBe(20);
    } finally {
        Carbon::setTestNow(null);
    }
});
