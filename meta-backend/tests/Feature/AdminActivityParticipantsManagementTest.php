<?php

use App\Models\Academy;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityUser;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Member;
use App\Models\Planet;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function adminActivityParticipantsFixture(): array
{
    $institution = Institution::create(['name' => 'Институт']);
    $academy = Academy::create([
        'name' => 'Академия управления и цифрового развития',
        'short_name' => 'АУиЦР',
        'institution_id' => $institution->id,
    ]);
    $planet = Planet::create(['name' => 'IT']);
    $category = ActivityCategory::create([
        'name' => 'Проектная '.uniqid(),
        'description' => 'Описание',
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_activated' => 1,
    ]);

    return [
        'region' => Region::create(['code' => 77, 'name' => 'Москва']),
        'planet' => $planet,
        'institution' => $institution,
        'academy' => $academy,
        'educationLevel' => EducationLevel::create([
            'name' => 'Бакалавриат',
            'nuo_value' => 2,
        ]),
        'admin' => $admin,
        'activity' => Activity::create([
            'title' => 'Олимпиада',
            'description' => 'Описание',
            'date_start' => '2026-01-10',
            'date_end' => '2026-01-11',
            'score' => 10,
            'status' => 'scheduled',
            'planet_id' => $planet->id,
            'category_id' => $category->id,
            'creator_id' => $admin->id,
            'photo_path' => null,
        ]),
    ];
}

function createParticipantStudent(array $fixture, array $userOverrides = [], array $memberOverrides = []): User
{
    $user = User::factory()->create(array_merge([
        'role' => 'student',
        'is_activated' => 1,
    ], $userOverrides));

    Member::create(array_merge([
        'last_name' => 'Иванов',
        'first_name' => 'Иван',
        'middle_name' => 'Иванович',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => $fixture['region']->id,
        'planet_id' => $fixture['planet']->id,
        'institution_id' => $fixture['institution']->id,
        'institution' => null,
        'course' => 2,
        'student_group' => 'К1И',
        'academy_id' => $fixture['academy']->id,
        'education_level_id' => $fixture['educationLevel']->id,
        'user_id' => $user->id,
    ], $memberOverrides));

    return $user;
}

function participantsImportWorkbook(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['ФИО', 'Группа'], null, 'A1');

    foreach ($rows as $index => $row) {
        $sheet->fromArray($row, null, 'A'.($index + 2));
    }

    $path = tempnam(sys_get_temp_dir(), 'participants-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile(
        $path,
        'participants.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

it('searches all student candidates and adds a selected participant idempotently', function () {
    $fixture = adminActivityParticipantsFixture();
    $newStudent = createParticipantStudent($fixture, ['email' => 'ivanov@example.test']);
    $existingStudent = createParticipantStudent(
        $fixture,
        ['email' => 'petrov@example.test'],
        ['last_name' => 'Петров', 'first_name' => 'Пётр', 'middle_name' => 'Петрович', 'student_group' => 'К2И']
    );
    ActivityUser::create([
        'user_id' => $existingStudent->id,
        'activity_id' => $fixture['activity']->id,
    ]);

    Sanctum::actingAs($fixture['admin']);

    // Поиск по одиночному слову
    $search = $this->getJson("/api/v1/admin/activities/{$fixture['activity']->id}/participant-candidates?search=Иванов");
    $search->assertOk();
    $rows = collect($search->json('data'));
    expect($rows->firstWhere('user_id', $newStudent->id)['is_participant'])->toBeFalse()
        ->and($rows->firstWhere('user_id', $existingStudent->id))->toBeNull();

    // Поиск по части ФИО (фамилия + начало имени) — ранее возвращал пустой результат
    $search2 = $this->getJson("/api/v1/admin/activities/{$fixture['activity']->id}/participant-candidates?search=Иванов%20Иван");
    $search2->assertOk();
    $rows2 = collect($search2->json('data'));
    expect($rows2->firstWhere('user_id', $newStudent->id))->not->toBeNull();

    // Поиск в обратном порядке слов должен давать тот же результат
    $search3 = $this->getJson("/api/v1/admin/activities/{$fixture['activity']->id}/participant-candidates?search=Иван%20Иванов");
    $search3->assertOk();
    $rows3 = collect($search3->json('data'));
    expect($rows3->firstWhere('user_id', $newStudent->id))->not->toBeNull();

    // Проверка флага is_participant
    $searchAll = $this->getJson("/api/v1/admin/activities/{$fixture['activity']->id}/participant-candidates?search=П");
    $searchAll->assertOk();
    $rowsAll = collect($searchAll->json('data'));
    expect($rowsAll->firstWhere('user_id', $existingStudent->id)['is_participant'])->toBeTrue();

    $firstAdd = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants", [
        'user_id' => $newStudent->id,
    ]);

    $firstAdd->assertOk()
        ->assertJsonPath('added', true);

    $secondAdd = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants", [
        'user_id' => $newStudent->id,
    ]);

    $secondAdd->assertOk()
        ->assertJsonPath('added', false);

    expect(ActivityUser::where('activity_id', $fixture['activity']->id)
        ->where('user_id', $newStudent->id)
        ->count())->toBe(1);
});

it('provides excel template previews import rows and bulk adds only valid users', function () {
    $fixture = adminActivityParticipantsFixture();
    $newStudent = createParticipantStudent($fixture, ['email' => 'ivanov@example.test']);
    $existingStudent = createParticipantStudent(
        $fixture,
        ['email' => 'petrov@example.test'],
        ['last_name' => 'Петров', 'first_name' => 'Пётр', 'middle_name' => 'Петрович', 'student_group' => 'К2И']
    );
    // Два однофамильца с разными группами → статус ambiguous
    $ambiguousA = createParticipantStudent(
        $fixture,
        ['email' => 'sidorov-a@example.test'],
        ['last_name' => 'Сидоров', 'first_name' => 'Сергей', 'middle_name' => 'Сергеевич', 'student_group' => 'К3И']
    );
    $ambiguousB = createParticipantStudent(
        $fixture,
        ['email' => 'sidorov-b@example.test'],
        ['last_name' => 'Сидоров', 'first_name' => 'Сергей', 'middle_name' => 'Сергеевич', 'student_group' => 'К4И']
    );
    // Два однофамильца с одинаковой группой → статус error (невозможно различить)
    createParticipantStudent(
        $fixture,
        ['email' => 'smirnov-a@example.test'],
        ['last_name' => 'Смирнов', 'first_name' => 'Алексей', 'middle_name' => 'Алексеевич', 'student_group' => 'К5И']
    );
    createParticipantStudent(
        $fixture,
        ['email' => 'smirnov-b@example.test'],
        ['last_name' => 'Смирнов', 'first_name' => 'Алексей', 'middle_name' => 'Алексеевич', 'student_group' => 'К5И']
    );
    ActivityUser::create([
        'user_id' => $existingStudent->id,
        'activity_id' => $fixture['activity']->id,
    ]);

    Sanctum::actingAs($fixture['admin']);

    $template = $this->getJson('/api/v1/admin/activities/participants-import-template');
    $template->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $preview = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants-import/preview", [
        'file' => participantsImportWorkbook([
            ['Иванов Иван Иванович', 'К1И'],           // ready
            ['Петров Пётр Петрович', 'К2И'],           // already_exists
            ['Сидоров Сергей Сергеевич', ''],          // ambiguous: К3И или К4И
            ['Смирнов Алексей Алексеевич', 'К5И'],     // error: одинаковая группа
            ['Несуществующий Студент', ''],             // error: не найден
        ]),
    ]);

    $preview->assertOk()
        ->assertJsonPath('summary.ready_count', 1)
        ->assertJsonPath('summary.already_exists_count', 1)
        ->assertJsonPath('summary.ambiguous_count', 1)
        ->assertJsonPath('summary.error_count', 2)
        ->assertJsonPath('rows.0.status', 'ready')
        ->assertJsonPath('rows.0.user.user_id', $newStudent->id)
        ->assertJsonPath('rows.1.status', 'already_exists')
        ->assertJsonPath('rows.2.status', 'ambiguous')
        ->assertJsonPath('rows.3.status', 'error')
        ->assertJsonPath('rows.4.status', 'error');

    // Кандидаты для ambiguous строки содержат обоих студентов
    $candidates = collect($preview->json('rows.2.candidates'));
    expect($candidates->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$ambiguousA->id, $ambiguousB->id])->sort()->values()->all());

    // Импорт: добавляем ready + выбранного из ambiguous
    $import = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants-import", [
        'user_ids' => [$newStudent->id, $ambiguousA->id],
    ]);

    $import->assertOk()
        ->assertJsonPath('added_count', 2)
        ->assertJsonPath('skipped_count', 0);

    expect(ActivityUser::where('activity_id', $fixture['activity']->id)
        ->whereIn('user_id', [$newStudent->id, $ambiguousA->id])
        ->count())->toBe(2);
});

it('does not allow searching, manually adding, or excel importing unactivated students', function () {
    $fixture = adminActivityParticipantsFixture();
    $unactivatedStudent = createParticipantStudent(
        $fixture,
        ['email' => 'unactivated@example.test', 'is_activated' => 0],
        ['last_name' => 'Неактивный', 'first_name' => 'Студент', 'middle_name' => 'Петрович', 'student_group' => 'К1И']
    );

    Sanctum::actingAs($fixture['admin']);

    // 1. Проверяем, что неактивный студент не находится через поиск кандидатов
    $search = $this->getJson("/api/v1/admin/activities/{$fixture['activity']->id}/participant-candidates?search=Неактивный");
    $search->assertOk();
    $rows = collect($search->json('data'));
    expect($rows->firstWhere('user_id', $unactivatedStudent->id))->toBeNull();

    // 2. Проверяем, что ручное добавление неактивного студента возвращает ошибку 422
    $add = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants", [
        'user_id' => $unactivatedStudent->id,
    ]);
    $add->assertStatus(422)
        ->assertJsonPath('message', 'Студент не активирован.');

    // 3. Проверяем, что при предпросмотре импорта из Excel он помечается как не найденный (так как отфильтрован в запросе)
    $preview = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants-import/preview", [
        'file' => participantsImportWorkbook([
            ['Неактивный Студент Петрович', 'К1И'],
        ]),
    ]);
    $preview->assertOk()
        ->assertJsonPath('summary.ready_count', 0)
        ->assertJsonPath('summary.error_count', 1)
        ->assertJsonPath('rows.0.status', 'error')
        ->assertJsonPath('rows.0.message', 'Студент не найден: Неактивный Студент Петрович, группа К1И.');

    // 4. Проверяем, что при подтверждении импорта неактивного пользователя возвращается ошибка 422
    $import = $this->postJson("/api/v1/admin/activities/{$fixture['activity']->id}/participants-import", [
        'user_ids' => [$unactivatedStudent->id],
    ]);
    $import->assertStatus(422)
        ->assertJsonPath('message', 'В участники можно добавить только активированных студентов.');
});
