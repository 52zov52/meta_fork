<?php

use App\Models\Academy;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Member;
use App\Models\Planet;
use App\Models\Region;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createMentorStudentsReferenceData(): array
{
    $institution = Institution::create(['name' => 'Институт']);

    return [
        'region' => Region::create(['code' => 77, 'name' => 'Москва']),
        'planet' => Planet::create(['name' => 'IT']),
        'institution' => $institution,
        'academy' => Academy::create([
            'name' => 'Академия тестирования',
            'short_name' => 'АТ',
            'institution_id' => $institution->id,
        ]),
        'educationLevel' => EducationLevel::create([
            'name' => 'Бакалавриат',
            'nuo_value' => 2,
        ]),
    ];
}

function createMemberForMentorStudents(User $user, array $data, array $overrides = []): Member
{
    return Member::create(array_merge([
        'last_name' => 'Иванов',
        'first_name' => 'Иван',
        'middle_name' => 'Иванович',
        'gender' => 0,
        'birthday' => '2000-01-01',
        'region_id' => $data['region']->id,
        'planet_id' => $data['planet']->id,
        'institution_id' => $data['institution']->id,
        'institution' => null,
        'course' => 1,
        'student_group' => 'К1И',
        'academy_id' => $data['academy']->id,
        'education_level_id' => $data['educationLevel']->id,
        'user_id' => $user->id,
    ], $overrides));
}

it('returns mentor students with user id and activation status', function () {
    $data = createMentorStudentsReferenceData();

    $mentor = User::factory()->create([
        'role' => 'mentor',
        'is_activated' => 1,
    ]);
    createMemberForMentorStudents($mentor, $data, [
        'last_name' => 'Наставник',
        'first_name' => 'Николай',
        'middle_name' => null,
        'student_group' => null,
    ]);

    $student = User::factory()->create([
        'role' => 'student',
        'email' => 'student@example.test',
        'is_activated' => 0,
    ]);
    createMemberForMentorStudents($student, $data);

    Sanctum::actingAs($mentor);

    $response = $this->getJson('/api/v1/mentor/students');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $student->id)
        ->assertJsonPath('data.0.email', 'student@example.test')
        ->assertJsonPath('data.0.is_activated', false);
});

it('filters mentor students by search query', function () {
    $data = createMentorStudentsReferenceData();

    $mentor = User::factory()->create([
        'role' => 'mentor',
        'is_activated' => 1,
    ]);
    createMemberForMentorStudents($mentor, $data, [
        'last_name' => 'Наставник',
        'first_name' => 'Николай',
        'middle_name' => null,
        'student_group' => null,
    ]);

    $student1 = User::factory()->create([
        'role' => 'student',
        'email' => 'sidorova@example.test',
        'is_activated' => 1,
    ]);
    createMemberForMentorStudents($student1, $data, [
        'last_name' => 'Сидорова',
        'first_name' => 'Наталья',
        'middle_name' => 'Максимовна',
    ]);

    $student2 = User::factory()->create([
        'role' => 'student',
        'email' => 'ivanov@example.test',
        'is_activated' => 1,
    ]);
    createMemberForMentorStudents($student2, $data, [
        'last_name' => 'Иванов',
        'first_name' => 'Иван',
        'middle_name' => 'Иванович',
    ]);

    Sanctum::actingAs($mentor);

    // Search for "Сидорова" should return student1, but not student2
    $response = $this->getJson('/api/v1/mentor/students?search=Сидорова');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $student1->id)
        ->assertJsonPath('data.0.email', 'sidorova@example.test');

    // Search for "Иванов" should return student2, but not student1
    $response = $this->getJson('/api/v1/mentor/students?search=Иванов');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $student2->id)
        ->assertJsonPath('data.0.email', 'ivanov@example.test');
});
