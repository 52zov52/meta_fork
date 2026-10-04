<?php

use App\Models\Academy;
use App\Models\Institution;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\EducationLevelSeeder;
use Database\Seeders\InstitutionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

const MIGRATION = 'database/migrations/2026_05_07_000000_reorganize_academies_ausitigr.php';

/**
 * Состояние базы до реорганизации: 5 академий ИУБиП со старыми именами.
 */
function seedPreReorgAcademies(): void
{
    $academies = [
        ['name' => 'Академия туризма и индустрии гостеприимства', 'short_name' => 'АТиИГ'],
        ['name' => 'Академия цифрового развития', 'short_name' => 'АЦР'],
        ['name' => 'Академия гуманитарных технологий', 'short_name' => 'АГТ'],
        ['name' => 'Академия права и государственной службы', 'short_name' => 'АПиГС'],
        ['name' => 'Академия экономики и управления', 'short_name' => 'АЭиУ'],
    ];

    foreach ($academies as $academy) {
        Academy::create([
            'name' => $academy['name'],
            'short_name' => $academy['short_name'],
            'institution_id' => 1,
        ]);
    }
}

function makeStudent(string $group, ?int $academyId = null): Member
{
    $user = User::factory()->create(['role' => 'student']);

    return Member::create([
        'user_id' => $user->id,
        'last_name' => 'Тест',
        'first_name' => 'Тест',
        'gender' => 0,
        'birthday' => '2005-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 1,
        'student_group' => $group,
        'academy_id' => $academyId,
    ]);
}

function shortNameOf(?int $academyId): ?string
{
    return $academyId ? Academy::find($academyId)?->short_name : null;
}

function runMigration(string $direction): void
{
    $migration = require base_path(MIGRATION);
    $migration->{$direction}();
}

beforeEach(function () {
    DB::table('regions')->insert(['id' => 1, 'code' => 77, 'name' => 'Moscow', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('planets')->insert(['id' => 1, 'name' => 'Test Planet', 'created_at' => now(), 'updated_at' => now()]);
    Artisan::call('db:seed', ['--class' => InstitutionSeeder::class]);
    Artisan::call('db:seed', ['--class' => EducationLevelSeeder::class]);
    seedPreReorgAcademies();
});

it('переименовывает академии на месте, сохраняя id', function () {
    $acrId = Academy::where('short_name', 'АЦР')->value('id');
    $aeiuId = Academy::where('short_name', 'АЭиУ')->value('id');

    runMigration('up');

    expect(Academy::find($acrId)->short_name)->toBe('АУиЦР')
        ->and(Academy::find($acrId)->name)->toBe('Академия управления и цифрового развития')
        ->and(Academy::find($aeiuId)->short_name)->toBe('АЭТиИГ')
        ->and(Academy::find($aeiuId)->name)->toBe('Академия экономики, туризма и индустрии гостеприимства');
});

it('не трогает АГТ и АПиГС', function () {
    $before = Academy::whereIn('short_name', ['АГТ', 'АПиГС'])->pluck('id', 'short_name');

    runMigration('up');

    foreach ($before as $short => $id) {
        expect(Academy::find($id)?->short_name)->toBe($short)
            ->and(Academy::find($id)?->name)->toBe(Academy::find($id)?->name);
    }
    expect(Academy::where('institution_id', 1)->count())->toBe(4);
});

it('ликвидирует АТиИГ', function () {
    runMigration('up');

    expect(Academy::where('short_name', 'АТиИГ')->exists())->toBeFalse()
        ->and(Academy::where('name', 'Академия туризма и индустрии гостеприимства')->exists())->toBeFalse();
});

it('переносит студентов АТиИГ в АЭТиИГ', function () {
    $atiig = Academy::where('short_name', 'АТиИГ')->value('id');
    $tGroup = makeStudent('К1Т1(9)', $atiig);
    $tsGroup = makeStudent('ТС324', $atiig);

    runMigration('up');

    expect(shortNameOf($tGroup->fresh()->academy_id))->toBe('АЭТиИГ')
        ->and(shortNameOf($tsGroup->fresh()->academy_id))->toBe('АЭТиИГ');
});

it('переносит мероприятия АТиИГ в АЭТиИГ', function () {
    $atiig = Academy::where('short_name', 'АТиИГ')->value('id');
    $categoryId = DB::table('activity_categories')->insertGetId([
        'name' => 'Проектная', 'description' => 'd', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $activityId = DB::table('activities')->insertGetId([
        'title' => 'Турнир', 'description' => 'd', 'category_id' => $categoryId,
        'academy_id' => $atiig, 'planet_id' => 1, 'status' => 'completed', 'score' => 10,
        'creator_id' => User::factory()->create(['role' => 'mentor'])->id,
        'date_start' => '2026-04-01', 'date_end' => '2026-04-02',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    runMigration('up');

    expect(shortNameOf(DB::table('activities')->where('id', $activityId)->value('academy_id')))->toBe('АЭТиИГ');
});

it('переводит М* и К*Л* из бывшей АЭиУ в АУиЦР, Э* и Т* — в АЭТиИГ', function () {
    $expected = [
        'МД301' => 'АУиЦР',
        'МДК103' => 'АУиЦР',
        'МС324' => 'АУиЦР',
        'К1Л1(9)' => 'АУиЦР',
        'К3Л2(9)' => 'АУиЦР',
        'БДК103' => 'АУиЦР',
        'ИДК103' => 'АУиЦР',
        'К1РУ2(9)' => 'АУиЦР',
        'ЭД401' => 'АЭТиИГ',
        'ЭМ-201' => 'АЭТиИГ',
        'К2Э1(9)' => 'АЭТиИГ',
        'ТС324' => 'АЭТиИГ',
        'К2Т3(9)' => 'АЭТиИГ',
    ];

    $members = [];
    foreach (array_keys($expected) as $group) {
        $members[$group] = makeStudent($group, Academy::where('short_name', 'АЭиУ')->value('id'));
    }

    runMigration('up');

    foreach ($expected as $group => $expectedShort) {
        expect(shortNameOf($members[$group]->fresh()->academy_id))->toBe($expectedShort, "группа $group");
    }
});

it('дооформляет участников с пустым academy_id', function () {
    $unfilled = [
        'БДК103' => 'АУиЦР',
        'К1Т1(9)' => 'АЭТиИГ',
        'ЮД101' => 'АПиГС',
        'ПД201' => 'АГТ',
    ];

    $members = [];
    foreach (array_keys($unfilled) as $group) {
        $members[$group] = makeStudent($group, null);
    }

    runMigration('up');

    foreach ($unfilled as $group => $expectedShort) {
        expect(shortNameOf($members[$group]->fresh()->academy_id))->toBe($expectedShort, "группа $group");
    }
});

it('обнуляет academy_id у групп вне масок, не оставляя прежнюю академию', function () {
    $member = makeStudent('К1ПД', Academy::where('short_name', 'АЦР')->value('id'));

    runMigration('up');

    expect($member->fresh()->academy_id)->toBeNull();
});

it('не трогает членов филиалов СИЮУ и ДИЮУ', function () {
    $siju = Academy::create(['name' => 'ЧПОУ СИЮУ', 'short_name' => 'СИЮУ', 'institution_id' => 2]);
    $diju = Academy::create(['name' => 'ЧПОУ ДИЮУ', 'short_name' => 'ДИЮУ', 'institution_id' => 3]);

    $sijuMember = makeStudent('К1ПД', $siju->id);
    DB::table('members')->where('id', $sijuMember->id)->update(['institution_id' => 2]);
    $dijuMember = makeStudent('К1И9', $diju->id);
    DB::table('members')->where('id', $dijuMember->id)->update(['institution_id' => 3]);

    runMigration('up');

    expect($sijuMember->fresh()->academy_id)->toBe($siju->id)
        ->and($dijuMember->fresh()->academy_id)->toBe($diju->id);
});

it('пересчитывает объём данных больше размера чанка', function () {
    // 1200 записей при chunkById(500) заставляет миграцию сделать три прохода.
    // Без квалифицированной колонки members.id второй проход падает с
    // "Column 'id' in where clause is ambiguous" из-за join с users.
    $groups = ['К1Т1(9)', 'МД301', 'БДК103', 'ЭД401', 'ЮД101', 'ПД201', 'К1Л1(9)'];
    $expected = ['АЭТиИГ', 'АУиЦР', 'АУиЦР', 'АЭТиИГ', 'АПиГС', 'АГТ', 'АУиЦР'];

    $userId = User::factory()->create(['role' => 'student'])->id;

    $now = now();
    $rows = [];
    for ($i = 0; $i < 1200; $i++) {
        $rows[] = [
            'user_id' => $userId,
            'last_name' => 'Тест',
            'first_name' => 'Тест',
            'gender' => 0,
            'birthday' => '2005-01-01',
            'region_id' => 1,
            'planet_id' => 1,
            'institution_id' => 1,
            'student_group' => $groups[$i % count($groups)],
            'academy_id' => Academy::where('short_name', 'АЭиУ')->value('id'),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    foreach (array_chunk($rows, 500) as $chunk) {
        DB::table('members')->insert($chunk);
    }

    runMigration('up');

    $actual = DB::table('members')
        ->join('academies', 'academies.id', '=', 'members.academy_id')
        ->select('members.student_group', 'academies.short_name')
        ->pluck('short_name', 'student_group')
        ->all();

    expect($actual)->toEqualCanonicalizing(array_combine($groups, $expected));
});

it('откат возвращает мероприятия АТиИГ обратно в восстановленную АТиИГ', function () {
    $atiig = Academy::where('short_name', 'АТиИГ')->value('id');
    $categoryId = DB::table('activity_categories')->insertGetId([
        'name' => 'Проектная', 'description' => 'd', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $activityId = DB::table('activities')->insertGetId([
        'title' => 'Турнир', 'description' => 'd', 'category_id' => $categoryId,
        'academy_id' => $atiig, 'planet_id' => 1, 'status' => 'completed', 'score' => 10,
        'creator_id' => User::factory()->create(['role' => 'mentor'])->id,
        'date_start' => '2026-04-01', 'date_end' => '2026-04-02',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    runMigration('up');
    expect(shortNameOf(DB::table('activities')->where('id', $activityId)->value('academy_id')))->toBe('АЭТиИГ');

    runMigration('down');
    expect(shortNameOf(DB::table('activities')->where('id', $activityId)->value('academy_id')))->toBe('АТиИГ');
});

it('откат возвращает прежние названия, АТиИГ и распределение групп', function () {
    $siju = Academy::create(['name' => 'ЧПОУ СИЮУ', 'short_name' => 'СИЮУ', 'institution_id' => 2]);
    $diju = Academy::create(['name' => 'ЧПОУ ДИЮУ', 'short_name' => 'ДИЮУ', 'institution_id' => 3]);

    $before = [];
    foreach ([
        'К1Т1(9)', 'ТС324',          // АТиИГ до реорганизации
        'МД301', 'К1Л1(9)', 'БДК103', // АЦР
        'К1И1', 'К1РУ1(9)',         // АЦР
        'ЭД401', 'К2Э1(9)',         // АЭиУ
        'ЮД101', 'ПД201',           // АПиГС / АГТ
    ] as $group) {
        $member = makeStudent($group, Academy::where('institution_id', 1)->first()->id);
        DB::table('members')->where('id', $member->id)->update(['institution_id' => 1]);
        $before[$group] = $member->id;
    }

    runMigration('up');
    runMigration('down');

    expect(Academy::where('short_name', 'АТиИГ')->exists())->toBeTrue()
        ->and(Academy::where('short_name', 'АЦР')->value('name'))->toBe('Академия цифрового развития')
        ->and(Academy::where('short_name', 'АЭиУ')->value('name'))->toBe('Академия экономики и управления');

    $byGroup = DB::table('members')->pluck('academy_id', 'id');

    $resolve = fn (string $group) => shortNameOf($byGroup[$before[$group]]);

    expect($resolve('К1Т1(9)'))->toBe('АТиИГ')
        ->and($resolve('ТС324'))->toBe('АТиИГ')
        ->and($resolve('МД301'))->toBe('АЭиУ')
        ->and($resolve('К1Л1(9)'))->toBe('АЭиУ')
        ->and($resolve('К1И1'))->toBe('АЦР')
        ->and($resolve('К1РУ1(9)'))->toBe('АЦР')
        ->and($resolve('ЭД401'))->toBe('АЭиУ')
        ->and($resolve('К2Э1(9)'))->toBe('АЭиУ')
        ->and($resolve('ЮД101'))->toBe('АПиГС')
        ->and($resolve('ПД201'))->toBe('АГТ');

    expect($byGroup->count())->toBe(count($before));
    expect(Institution::find(1))->not->toBeNull();
});
