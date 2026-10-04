<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Planet;
use App\Models\Region;
use App\Services\AcademyService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = \App\Models\Member::class;

    /**
     * Русские имена и фамилии
     */
    private array $lastNames = [
        'Иванов', 'Петров', 'Сидоров', 'Смирнов', 'Кузнецов',
        'Попов', 'Васильев', 'Соколов', 'Михайлов', 'Новиков',
    ];

    private array $firstNames = [
        'Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей',
        'Алексей', 'Артём', 'Илья', 'Кирилл', 'Михаил',
    ];

    private array $middleNames = [
        'Александрович', 'Дмитриевич', 'Максимович', 'Сергеевич', 'Андреевич',
        'Алексеевич', 'Артёмович', 'Ильич', 'Кириллович', 'Михайлович',
    ];

    private array $lastNamesFemale = [
        'Иванова', 'Петрова', 'Сидорова', 'Смирнова', 'Кузнецова',
        'Попова', 'Васильева', 'Соколова', 'Михайлова', 'Новикова',
    ];

    private array $firstNamesFemale = [
        'Анна', 'Мария', 'Елена', 'Ольга', 'Наталья',
        'Татьяна', 'Светлана', 'Ирина', 'Юлия', 'Екатерина',
    ];

    private array $middleNamesFemale = [
        'Александровна', 'Дмитриевна', 'Максимовна', 'Сергеевна', 'Андреевна',
        'Алексеевна', 'Артёмовна', 'Ильинична', 'Кирилловна', 'Михайловна',
    ];

    /**
     * Группы с привязкой к академиям (учреждение 1)
     */
    private array $academyGroups = [
        // Академия управления и цифрового развития
        'ИД', 'ИС', 'БДК', 'МД', 'МДК', 'МС', 'МЗ',
        'К1И', 'К2И', 'К3И', 'К4И',
        'К1РУ', 'К2РУ', 'К3РУ', 'К4РУ',
        'К1Л', 'К2Л', 'К3Л', 'К4Л',
        // Академия гуманитарных технологий
        'ПД', 'ПЗ', 'ПМ', 'УП',
        'К1М', 'К2М', 'К3М', 'К4М',
        'К1Ф', 'К2Ф', 'К3Ф', 'К4Ф',
        'К1А', 'К2А', 'К3А', 'К4А',
        'К1С', 'К2С', 'К3С', 'К4С',
        // Академия права и государственной службы
        'ЮД', 'ЮМ', 'ЮЗ', 'ЮС', 'К1Ю', 'К2Ю', 'К3Ю', 'К4Ю',
        // Академия экономики, туризма и индустрии гостеприимства
        'ТД', 'ТС', 'К1Т', 'К2Т', 'К3Т', 'К4Т',
        'ЭД', 'ЭМ', 'ЭС', 'ЭЗ', 'К1Э', 'К2Э', 'К3Э', 'К4Э',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $institution = Institution::inRandomOrder()->first();
        $institutionId = $institution?->id;
        $gender = $this->faker->numberBetween(0, 1);
        $isMale = $gender === 0;

        $studentGroup = $this->faker->randomElement($this->academyGroups);
        $academyService = app(AcademyService::class);
        if ($institutionId === 1) {
            $academy = $academyService->determineAcademyByGroup($studentGroup);
        } elseif (in_array($institutionId, [2, 3], true)) {
            $academy = $academyService->resolveAcademyForRegionalInstitutionId($institutionId);
        } else {
            $academy = null;
        }
        $educationLevelName = $academyService->determineEducationLevel($studentGroup);
        $educationLevel = \App\Models\EducationLevel::where('name', $educationLevelName)->first();

        return [
            'last_name' => $isMale
                ? $this->faker->randomElement($this->lastNames)
                : $this->faker->randomElement($this->lastNamesFemale),
            'first_name' => $isMale
                ? $this->faker->randomElement($this->firstNames)
                : $this->faker->randomElement($this->firstNamesFemale),
            'middle_name' => $this->faker->optional(0.9)->randomElement(
                $isMale ? $this->middleNames : $this->middleNamesFemale
            ),
            'gender' => $gender,
            'birthday' => $this->faker->date('Y-m-d', 'now - 18 years'),
            'region_id' => Region::inRandomOrder()->first()->id,
            'planet_id' => Planet::inRandomOrder()->first()->id,
            'institution_id' => $institutionId,
            'institution' => $institution?->id == 4 ? 'Другое' : null,
            'course' => $this->faker->optional()->numberBetween(1, 4),
            'student_group' => $studentGroup,
            'academy_id' => $academy?->id,
            'education_level_id' => $educationLevel?->id,
            'profile_photo_path' => null,
            'user_id' => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Member $member) {
            $academyService = app(AcademyService::class);

            // Only dynamically resolve if not explicitly set
            if ($member->academy_id === null) {
                $academy = $academyService->resolveAcademyForMember($member);
                if ($academy) {
                    $member->academy_id = $academy->id;
                }
            }

            if ($member->education_level_id === null && $member->student_group) {
                $educationLevelName = $academyService->determineEducationLevel($member->student_group);
                $educationLevel = \App\Models\EducationLevel::where('name', $educationLevelName)->first();
                if ($educationLevel) {
                    $member->education_level_id = $educationLevel->id;
                }
            }
        });
    }

    /**
     * State for Students
     */
    public function forStudent(?string $group = null): static
    {
        return $this->state(fn (array $attributes) => [
            'institution_id' => 1,
            'student_group' => $group ?? $this->faker->randomElement($this->academyGroups),
            'course' => $this->faker->numberBetween(1, 4),
        ]);
    }

    /**
     * State for Mentors
     */
    public function forMentor(?\App\Models\Academy $academy = null): static
    {
        return $this->state(function (array $attributes) use ($academy) {
            $resolvedAcademy = $academy ?? \App\Models\Academy::inRandomOrder()->first();
            return [
                'institution_id' => $resolvedAcademy?->institution_id ?? 1,
                'academy_id' => $resolvedAcademy?->id,
                'student_group' => null,
                'course' => null,
                'education_level_id' => null,
            ];
        });
    }

    /**
     * State for Admin/Moderator Staff
     */
    public function forStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'institution_id' => 1,
            'academy_id' => null,
            'student_group' => null,
            'course' => null,
            'education_level_id' => null,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 0,
            'last_name' => $this->faker->randomElement($this->lastNames),
            'first_name' => $this->faker->randomElement($this->firstNames),
            'middle_name' => $this->faker->optional(0.9)->randomElement($this->middleNames),
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 1,
            'last_name' => $this->faker->randomElement($this->lastNamesFemale),
            'first_name' => $this->faker->randomElement($this->firstNamesFemale),
            'middle_name' => $this->faker->optional(0.9)->randomElement($this->middleNamesFemale),
        ]);
    }
}
