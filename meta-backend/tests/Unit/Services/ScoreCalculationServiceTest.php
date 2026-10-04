<?php

use App\Models\KsuMatrix;
use App\Services\ScoreCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    KsuMatrix::insert([
        ['delta' => -2, 'coefficient' => 0.80, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['delta' => -1, 'coefficient' => 0.90, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['delta' =>  0, 'coefficient' => 1.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['delta' => +1, 'coefficient' => 1.10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['delta' => +2, 'coefficient' => 1.20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('calculates correct KSU score for college level (uta=2, nuo=1)', function () {
    // delta = 2 - 1 = +1, ksu = 1.10, round(25 * 1.10) = 28
    $result = ScoreCalculationService::calculateKsuScore(25, 1, 2);
    expect($result)->toBe(28);
});

it('calculates correct KSU score for bachelor level (uta=2, nuo=2)', function () {
    // delta = 2 - 2 = 0, ksu = 1.00, round(25 * 1.00) = 25
    $result = ScoreCalculationService::calculateKsuScore(25, 2, 2);
    expect($result)->toBe(25);
});

it('calculates correct KSU score for master level (uta=2, nuo=3)', function () {
    // delta = 2 - 3 = -1, ksu = 0.90, round(25 * 0.90) = 23
    $result = ScoreCalculationService::calculateKsuScore(25, 3, 2);
    expect($result)->toBe(23);
});

it('calculates correct KSU score for significant advantage (uta=3, nuo=1)', function () {
    // delta = 3 - 1 = +2, ksu = 1.20, round(50 * 1.20) = 60
    $result = ScoreCalculationService::calculateKsuScore(50, 1, 3);
    expect($result)->toBe(60);
});

it('returns earned score expression string', function () {
    $expr = ScoreCalculationService::earnedScoreExpression();
    expect($expr)->toBe('COALESCE(au.earned_score, a.score * au.level)');
});

it('returns earned score expression with custom aliases', function () {
    $expr = ScoreCalculationService::earnedScoreExpression('activity_user', 'activities');
    expect($expr)->toBe('COALESCE(activity_user.earned_score, activities.score * activity_user.level)');
});
