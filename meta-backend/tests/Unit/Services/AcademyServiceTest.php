<?php

use App\Models\Academy;
use App\Models\Institution;
use App\Models\Member;
use App\Models\User;
use App\Services\AcademyService;
use Database\Seeders\AcademySeeder;
use Database\Seeders\InstitutionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    DB::table('regions')->insert(['id' => 1, 'code' => 77, 'name' => 'Moscow', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('planets')->insert(['id' => 1, 'name' => 'Test Planet', 'created_at' => now(), 'updated_at' => now()]);
    Artisan::call('db:seed', ['--class' => InstitutionSeeder::class]);
    Artisan::call('db:seed', ['--class' => AcademySeeder::class]);
});

it('определяет академию по группе для учреждения 1', function (string $group, string $academyName) {
    $service = app(AcademyService::class);
    $academy = $service->determineAcademyByGroup($group);

    expect($academy)->not->toBeNull()
        ->and($academy->name)->toBe($academyName)
        ->and($academy->institution_id)->toBe(1);
})->with([
    ['К1М1(9)', 'Академия гуманитарных технологий'],
    ['К2М1.1', 'Академия гуманитарных технологий'],
    ['К1Ф1(9)', 'Академия гуманитарных технологий'],
    ['К1А1(9)', 'Академия гуманитарных технологий'],
    ['К1С1(9)', 'Академия гуманитарных технологий'],
    ['К3Ю2(9)', 'Академия права и государственной службы'],
    ['К3Л2(9)', 'Академия управления и цифрового развития'],
    ['К1Э2(9)', 'Академия экономики, туризма и индустрии гостеприимства'],
    ['к1л1(9)', 'Академия управления и цифрового развития'],
    ['К1Т1(9)', 'Академия экономики, туризма и индустрии гостеприимства'],
]);

it('разводит все группы академии управления и цифрового развития', function (string $group) {
    expect(app(AcademyService::class)->determineAcademyByGroup($group)?->name)
        ->toBe('Академия управления и цифрового развития');
})->with([
    'ИДК103', 'ИДК203', 'БДК103', 'ИС324', 'ИМ-201',
    'МД301', 'МДК103', 'МДК203', 'МС324',
    'К1РУ1(9)', 'К1РУ2(9)', 'К1РУ1(11)', 'К2РУ3(9)', 'К2РУ1(9)', 'К2РУ2(9)',
    'К2И1(11)', 'К3И1(9)', 'К3И2(9)', 'К3И3(9)', 'К3И1(11)', 'К4И1(9)', 'К4И2(9)', 'К4И3(9)',
    'К1Л1(9)', 'К1Л2(9)', 'К1Л1(11)', 'К2Л4(9)', 'К2Л3(9)',
    'К2Л1(9)', 'К2Л2(9)', 'К2Л1(11)', 'К3Л2(9)', 'К3Л1(9)',
]);

it('разводит все группы академии экономики, туризма и индустрии гостеприимства', function (string $group) {
    expect(app(AcademyService::class)->determineAcademyByGroup($group)?->name)
        ->toBe('Академия экономики, туризма и индустрии гостеприимства');
})->with([
    'ТС324', 'ЭДК103', 'ЭД401', 'ЭДК203', 'ЭС324', 'ЭМ-201',
    'К1Т1(9)', 'К1Т3(9)', 'К1Т2(9)', 'К1Т1(11)', 'К2Т2(9)', 'К2Т1(9)', 'К2Т3(9)', 'К2Т1(11)',
    'К3Т2(9)', 'К3Т1(9)', 'К3Т3(9)',
    'К1Э1(9)', 'К1Э2(9)', 'К1Э1(11)', 'К2Э1(9)', 'К2Э2(9)', 'К2Э1(11)', 'К3Э1(9)',
]);

it('разводит все группы академии гуманитарных технологий без изменений', function (string $group) {
    expect(app(AcademyService::class)->determineAcademyByGroup($group)?->name)
        ->toBe('Академия гуманитарных технологий');
})->with([
    'ПД101', 'ПД201', 'ПД301',
    'К1Ф1(9)', 'К2Ф1(9)', 'К2Ф2(9)', 'К3Ф1(9)',
    'К1М1(9)', 'К1М2(9)', 'К2М1(9)', 'К2М2(9)', 'К2М3(9)', 'К2М4(9)',
    'К3М1(9)', 'К3М2(9)', 'К3М3(9)', 'К3М4(9)',
    'К1А1(9)', 'К2А1(9)', 'К2А2(9)',
    'К1С1(9)', 'К2С1(9)', 'К2С2(9)',
]);

it('разводит все группы академии права и государственной службы без изменений', function (string $group) {
    expect(app(AcademyService::class)->determineAcademyByGroup($group)?->name)
        ->toBe('Академия права и государственной службы');
})->with([
    'ЮД101', 'ЮД201', 'ЮД301', 'ЮДК103', 'ЮДК104', 'ЮД401', 'ЮДК203', 'ЮДК204',
    'ЮС225.1', 'ЮС225.2', 'ЮС225.3', 'ЮС225.4',
    'К1Ю3(9)', 'К1Ю1(9)', 'К1Ю2(9)', 'К1Ю1(11)', 'К2Ю1(9)', 'К1Ю2(11)', 'К2Ю3(9)',
    'К2Ю2(9)', 'К2Ю4(9)', 'К2Ю1(11)', 'К3Ю1(9)', 'К3Ю2(9)', 'К3Ю3(9)', 'К3Ю4(9)',
]);

it('больше не содержит академию туризма и индустрии гостеприимства', function () {
    $names = Academy::where('institution_id', 1)->pluck('name');

    expect($names)->not->toContain('Академия туризма и индустрии гостеприимства')
        ->and($names->all())->toBe([
            'Академия управления и цифрового развития',
            'Академия гуманитарных технологий',
            'Академия права и государственной службы',
            'Академия экономики, туризма и индустрии гостеприимства',
        ]);
});

it('возвращает null для групп вне действующих масок', function (string $group) {
    $service = app(AcademyService::class);

    expect($service->determineAcademyByGroup($group))->toBeNull();
})->with([
    'К1П',
    'Унэ11',
    'К1ДО',
    'K1M1',
]);

it('резолвит чпоу сиюу и диюу по institution_id', function () {
    $service = app(AcademyService::class);

    expect($service->resolveAcademyForRegionalInstitutionId(2)?->name)->toBe('ЧПОУ СИЮУ')
        ->and($service->resolveAcademyForRegionalInstitutionId(3)?->name)->toBe('ЧПОУ ДИЮУ');
});

it('не назначает чпоу для institution_id 1 и 4', function () {
    $service = app(AcademyService::class);

    expect($service->resolveAcademyForRegionalInstitutionId(1))->toBeNull()
        ->and($service->resolveAcademyForRegionalInstitutionId(4))->toBeNull();
});

it('для сиюу не использует маски иубип при выборе академии', function () {
    $service = app(AcademyService::class);
    $member = new Member([
        'institution_id' => 2,
        'student_group' => 'К1И1',
    ]);
    $member->setRelation('institution', Institution::find(2));

    $academy = $service->resolveAcademyForMember($member);

    expect($academy)->not->toBeNull()
        ->and($academy->name)->toBe('ЧПОУ СИЮУ')
        ->and($academy->institution_id)->toBe(2);
});

it('резолвит чпоу сиюу для сохраненного участника с пустым текстовым institution', function () {
    $service = app(AcademyService::class);
    $user = User::factory()->create(['role' => 'student']);
    $member = Member::create([
        'user_id' => $user->id,
        'last_name' => 'Test',
        'first_name' => 'Test',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => 1,
        'planet_id' => 1,
        'institution_id' => 2,
        'institution' => null,
        'student_group' => 'К2ДО',
    ]);

    $academy = $service->resolveAcademyForMember($member->load('institution'));

    expect($academy)->not->toBeNull()
        ->and($academy->name)->toBe('ЧПОУ СИЮУ')
        ->and($academy->institution_id)->toBe(2);
});

it('для иубип группа к1и1 даёт академию управления и цифрового развития', function () {
    $service = app(AcademyService::class);
    $member = new Member([
        'institution_id' => 1,
        'student_group' => 'К1И1',
    ]);
    $member->setRelation('institution', Institution::find(1));

    expect($service->resolveAcademyForMember($member)?->name)->toBe('Академия управления и цифрового развития');
});

it('для учреждения другое академия не назначается', function () {
    $service = app(AcademyService::class);
    $member = new Member([
        'institution_id' => 4,
        'student_group' => 'К1И1',
    ]);
    $member->setRelation('institution', Institution::find(4));

    expect($service->resolveAcademyForMember($member))->toBeNull();
});
