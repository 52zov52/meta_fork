<?php

use App\Models\ScoringBlackoutSetting;
use App\Models\User;
use App\Services\SemesterService;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    SemesterService::clearCache();
});

afterEach(function () {
    Carbon::setTestNow();
    SemesterService::clearCache();
});
it('returns blackout settings on public GET scoring-blackout', function () {
    ScoringBlackoutSetting::query()->create([
        'winter_start' => '12-26',
        'winter_end' => '01-31',
        'summer_start' => '06-26',
        'summer_end' => '08-31',
    ]);

    $response = $this->getJson('/api/v1/scoring-blackout');

    $response->assertOk()
        ->assertJsonPath('data.winter_start', '12-26')
        ->assertJsonPath('data.winter_end', '01-31')
        ->assertJsonPath('data.summer_start', '06-26')
        ->assertJsonPath('data.summer_end', '08-31');
});

it('updates blackout settings via admin api', function () {
    $admin = User::factory()->withRole('admin')->create();
    Sanctum::actingAs($admin);

    $payload = [
        'winter_start' => '12-20',
        'winter_end' => '01-10',
        'summer_start' => '07-01',
        'summer_end' => '08-15',
    ];

    $response = $this->putJson('/api/v1/admin/scoring-blackout', $payload);

    $response->assertOk()
        ->assertJsonPath('data.winter_start', '12-20')
        ->assertJsonPath('data.winter_end', '01-10')
        ->assertJsonPath('data.summer_start', '07-01')
        ->assertJsonPath('data.summer_end', '08-15');

    $setting = ScoringBlackoutSetting::query()->first();
    expect($setting)->not->toBeNull();
    expect($setting->winter_start)->toBe('12-20');
    expect($setting->winter_end)->toBe('01-10');
    expect($setting->summer_start)->toBe('07-01');
    expect($setting->summer_end)->toBe('08-15');
});

it('applies updated blackout settings in semester service', function () {
    ScoringBlackoutSetting::query()->create([
        'winter_start' => '12-20',
        'winter_end' => '01-10',
        'summer_start' => '07-01',
        'summer_end' => '08-15',
    ]);

    expect(SemesterService::isScoringBlackout(Carbon::parse('2025-12-21')))->toBeTrue();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-01-11')))->toBeFalse();
});

it('uses stored default blackout boundaries in semester service', function () {
    ScoringBlackoutSetting::query()->create([
        'winter_start' => '12-26',
        'winter_end' => '01-31',
        'summer_start' => '06-26',
        'summer_end' => '08-31',
    ]);

    expect(SemesterService::isScoringBlackout(Carbon::parse('2025-12-25')))->toBeFalse();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2025-12-26')))->toBeTrue();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-01-31')))->toBeTrue();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-02-01')))->toBeFalse();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-06-25')))->toBeFalse();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-06-26')))->toBeTrue();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-08-31')))->toBeTrue();
    expect(SemesterService::isScoringBlackout(Carbon::parse('2026-09-01')))->toBeFalse();
});

it('validates admin blackout payload format', function () {
    $admin = User::factory()->withRole('admin')->create();
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/v1/admin/scoring-blackout', [
        'winter_start' => '12-40',
        'winter_end' => '01-31',
        'summer_start' => '06-26',
        'summer_end' => '08-31',
    ]);

    $response->assertStatus(422);
});
