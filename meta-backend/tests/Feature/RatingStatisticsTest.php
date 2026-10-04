<?php

use App\Models\{Activity, ActivityCategory, ActivityUser, Institution, Member, Planet, Region, User};
use Carbon\Carbon;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15');
    $this->region = Region::create(['name' => 'R', 'code' => 1]);
    $this->inst = Institution::create(['name' => 'U']);
    $this->planet = Planet::create(['name' => 'P']);
    $this->cat = ActivityCategory::create(['name' => 'C', 'description' => 'C']);
    $this->admin = User::factory()->create(['role' => 'admin']);
});

afterEach(fn () => Carbon::setTestNow());

function student(Institution $inst, Planet $planet, array $userOverride = []): User
{
    $user = User::factory()->create(array_merge(['role' => 'student', 'is_activated' => true], $userOverride));
    Member::create(['last_name' => 'T', 'first_name' => 'S', 'gender' => 0, 'birthday' => '2000-01-01',
        'region_id' => Region::first()->id, 'planet_id' => $planet->id,
        'institution_id' => $inst->id, 'user_id' => $user->id]);
    return $user;
}

function act(Planet $planet, ActivityCategory $cat, int $score, string $dateStart = null, string $dateEnd = null): Activity
{
    return Activity::create([
        'title' => 'A', 'description' => 'A', 'score' => $score, 'status' => 'completed',
        'planet_id' => $planet->id, 'category_id' => $cat->id, 'creator_id' => User::where('role', 'admin')->first()->id,
        'date_start' => $dateStart ?? now()->subDays(10), 'date_end' => $dateEnd ?? now()->subDays(5),
    ]);
}

function confirm(User $user, Activity $activity, bool $confirmed = true, Carbon $updatedAt = null): void
{
    $au = ActivityUser::create(['user_id' => $user->id, 'activity_id' => $activity->id, 'level' => 1.0, 'is_confirmed' => $confirmed]);
    if ($updatedAt) {
        $au->forceFill(['updated_at' => $updatedAt])->save();
    }
}

function catValue(array $json, int $id): int
{
    return collect($json['participantsByCategory'])->first(fn ($c) => $c['id'] === $id)['value'];
}

function planetValue(array $json, int $id): int
{
    return collect($json['participantsByPlanet'])->first(fn ($p) => $p['id'] === $id)['value'];
}

it('returns zeros when no activities', function () {
    $r = get('/api/v1/rating/statistics');
    $r->assertOk();
    expect($r->json())->toHaveKeys(['period', 'participantsByPlanet', 'participantsByCategory']);
});

it('calculates score * level', function () {
    $s = student($this->inst, $this->planet);
    confirm($s, act($this->planet, $this->cat, 10));

    $r = get('/api/v1/rating/statistics');
    $r->assertOk();
    expect(planetValue($r->json(), $this->planet->id))->toBe(10)
        ->and(catValue($r->json(), $this->cat->id))->toBe(10);
});

it('does not cap category at 50 in statistics', function () {
    $s = student($this->inst, $this->planet);
    confirm($s, act($this->planet, $this->cat, 60));

    $r = get('/api/v1/rating/statistics');
    expect(catValue($r->json(), $this->cat->id))->toBe(60);
});

it('sums scores without cap per category in statistics', function () {
    $cat2 = ActivityCategory::create(['name' => 'C2', 'description' => 'C2']);
    $s = student($this->inst, $this->planet);
    confirm($s, act($this->planet, $this->cat, 30));
    confirm($s, act($this->planet, $this->cat, 40));
    confirm($s, act($this->planet, $cat2, 35));

    $r = get('/api/v1/rating/statistics');
    expect(catValue($r->json(), $this->cat->id))->toBe(70)
        ->and(catValue($r->json(), $cat2->id))->toBe(35);
});

it('aggregates multiple users in same planet', function () {
    $inst2 = Institution::create(['name' => 'U2']);
    $planet2 = Planet::create(['name' => 'P2']);
    $s1 = student($this->inst, $this->planet);
    $s2 = student($this->inst, $planet2);
    confirm($s1, act($this->planet, $this->cat, 10));
    confirm($s2, act($planet2, $this->cat, 25));

    $r = get('/api/v1/rating/statistics');
    expect(planetValue($r->json(), $this->planet->id))->toBe(10)
        ->and(planetValue($r->json(), $planet2->id))->toBe(25);
});

it('excludes unconfirmed activities', function () {
    $s = student($this->inst, $this->planet);
    confirm($s, act($this->planet, $this->cat, 50), false);

    $r = get('/api/v1/rating/statistics');
    expect($r->json('participantsByPlanet'))->toBeArray();
});

it('excludes non-activated users', function () {
    $s = student($this->inst, $this->planet, ['is_activated' => false]);
    confirm($s, act($this->planet, $this->cat, 30));

    $r = get('/api/v1/rating/statistics');
    expect($r->json('participantsByPlanet'))->toBeArray();
});

it('excludes non-student roles', function () {
    Member::create(['last_name' => 'A', 'first_name' => 'U', 'gender' => 0, 'birthday' => '2000-01-01',
        'region_id' => $this->region->id, 'planet_id' => $this->planet->id,
        'institution_id' => $this->inst->id, 'user_id' => $this->admin->id]);
    confirm($this->admin, act($this->planet, $this->cat, 40));

    $r = get('/api/v1/rating/statistics');
    expect($r->json('participantsByPlanet'))->toBeArray();
});

it('counts current-semester activity even if confirmation happened outside current semester', function () {
    $s = student($this->inst, $this->planet);
    $a1 = act($this->planet, $this->cat, 20);
    $a2 = act($this->planet, $this->cat, 50);
    confirm($s, $a1);
    confirm($s, $a2, true, Carbon::parse('2025-12-15'));

    $r = get('/api/v1/rating/statistics');
    expect(catValue($r->json(), $this->cat->id))->toBe(70);
});

it('excludes activities outside current semester even if confirmation happened inside current semester', function () {
    $s = student($this->inst, $this->planet);
    confirm($s, act($this->planet, $this->cat, 50, '2025-12-10', '2025-12-15'));

    $r = get('/api/v1/rating/statistics');
    expect(catValue($r->json(), $this->cat->id))->toBe(0);
});

it('returns correct data structure', function () {
    $r = get('/api/v1/rating/statistics');
    $r->assertOk();

    $json = $r->json();
    expect($json)->toHaveKeys(['period', 'participantsByPlanet', 'participantsByCategory']);
    expect($json['period'])->toHaveKeys(['type', 'label', 'start', 'end']);
    expect($json['period']['type'])->toBe('semester');

    foreach ($json['participantsByPlanet'] as $item) {
        expect($item)->toHaveKeys(['id', 'label', 'value']);
    }
});

it('sorts categories descending by value', function () {
    $cat2 = ActivityCategory::create(['name' => 'C2', 'description' => 'C2']);
    $s1 = student($this->inst, $this->planet);
    $s2 = student($this->inst, $this->planet);
    confirm($s1, act($this->planet, $cat2, 40));
    confirm($s2, act($this->planet, $this->cat, 15));

    $r = get('/api/v1/rating/statistics');
    $cats = $r->json('participantsByCategory');
    expect($cats[0]['id'])->toBe($cat2->id)
        ->and($cats[0]['value'])->toBe(40)
        ->and($cats[1]['id'])->toBe($this->cat->id)
        ->and($cats[1]['value'])->toBe(15);
});

it('sums are isolated per category in statistics', function () {
    $cat2 = ActivityCategory::create(['name' => 'C2', 'description' => 'C2']);
    $s = student($this->inst, $this->planet);
    confirm($s, act($this->planet, $this->cat, 60));
    confirm($s, act($this->planet, $cat2, 55));

    $r = get('/api/v1/rating/statistics');
    expect(catValue($r->json(), $this->cat->id))->toBe(60)
        ->and(catValue($r->json(), $cat2->id))->toBe(55);
});

it('aggregates multiple activities in same category without cap in statistics', function () {
    $s = student($this->inst, $this->planet);
    foreach ([20, 25, 15] as $score) {
        confirm($s, act($this->planet, $this->cat, $score));
    }

    $r = get('/api/v1/rating/statistics');
    expect(catValue($r->json(), $this->cat->id))->toBe(60);
});
