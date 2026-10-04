<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Member;
use Illuminate\Support\Collection;

class AcademyService
{
    /**
     * Маски групп для определения академии (Учреждение 1)
     */
    private array $academyMasks = [
        'Академия управления и цифрового развития' => ['И%', 'К[1-4]И%', 'БИС%', 'БДК%', 'К[1-4]РУ%', 'М%', 'К[1-4]Л%'],
        'Академия гуманитарных технологий' => ['П%', 'УП%', 'К[1-4]М%', 'К[1-4]Ф%', 'К[1-4]А%', 'К[1-4]С%'],
        'Академия права и государственной службы' => ['Ю%', 'К[1-4]Ю%'],
        'Академия экономики, туризма и индустрии гостеприимства' => ['Т%', 'К[1-4]Т%', 'Э%', 'К[1-4]Э%'],
    ];

    /**
     * Определить академию по номеру группы
     */
    public function determineAcademyByGroup(string $group): ?Academy
    {
        // Учреждение 1 - определяем по маске
        foreach ($this->academyMasks as $academyName => $masks) {
            foreach ($masks as $mask) {
                if ($this->matchesMask($group, $mask)) {
                    return Academy::where('name', $academyName)
                        ->where('institution_id', 1)
                        ->first();
                }
            }
        }

        return null;
    }

    /**
     * Академия ЧПОУ СИЮУ / ЧПОУ ДИЮУ по institution_id (2 или 3) и названию учреждения.
     * Для institution_id 1, 4 и остальных — null.
     */
    public function resolveAcademyForRegionalInstitutionId(int $institutionId): ?Academy
    {
        if (! in_array($institutionId, [2, 3], true)) {
            return null;
        }

        $institution = Institution::find($institutionId);
        if (! $institution) {
            return null;
        }

        return $this->findAcademyByInstitutionAndName($institutionId, $institution->name);
    }

    /**
     * То же для участника с подгруженной связью institution (синк в чанках).
     *
     * @param  Collection<string, Academy>|null  $academiesByKey  ключ academyMapKey(institution_id, name)
     */
    public function resolveAcademyForRegionalMember(Member $member, ?Collection $academiesByKey = null): ?Academy
    {
        $institutionId = (int) $member->institution_id;
        if (! in_array($institutionId, [2, 3], true)) {
            return null;
        }

        $institutionName = $member->relationLoaded('institution')
            ? $member->getRelation('institution')?->name
            : Institution::whereKey($institutionId)->value('name');
        if (! $institutionName) {
            return null;
        }

        if ($academiesByKey !== null) {
            return $academiesByKey->get($this->academyMapKey($institutionId, $institutionName));
        }

        return $this->findAcademyByInstitutionAndName($institutionId, $institutionName);
    }

    /**
     * Полная логика: ИУБиП — маски; 2/3 — ЧПОУ; иначе — без академии.
     *
     * @param  Collection<string, Academy>|null  $academiesByKey
     */
    public function resolveAcademyForMember(Member $member, ?Collection $academiesByKey = null): ?Academy
    {
        $institutionId = (int) $member->institution_id;

        if ($institutionId === 1) {
            $group = $member->student_group;
            if (! $group) {
                return null;
            }

            return $this->determineAcademyByGroup($group);
        }

        if (in_array($institutionId, [2, 3], true)) {
            return $this->resolveAcademyForRegionalMember($member, $academiesByKey);
        }

        return null;
    }

    public static function academyMapKey(int $institutionId, string $academyOrInstitutionName): string
    {
        return $institutionId.'|'.mb_strtolower(trim($academyOrInstitutionName));
    }

    /**
     * Определить уровень обучения по группе
     */
    public function determineEducationLevel(string $group): ?string
    {
        // Колледж - группа начинается с К (первый символ)
        if (str_starts_with($group, 'К')) {
            return 'Колледж';
        }

        // Магистратура - буква М есть, но не на первом месте
        $mPosition = mb_strpos($group, 'М');
        if ($mPosition !== false && $mPosition > 0) {
            return 'Магистратура';
        }

        // Бакалавриат - по умолчанию
        return 'Бакалавриат';
    }

    /**
     * Обновить академию и уровень обучения участника
     */
    public function syncMemberAcademy(Member $member): void
    {
        if (! $member->student_group) {
            return;
        }

        $academy = $this->resolveAcademyForMember($member);
        $educationLevelName = $this->determineEducationLevel($member->student_group);
        $educationLevel = EducationLevel::where('name', $educationLevelName)->first();

        $member->update([
            'academy_id' => $academy?->id,
            'education_level_id' => $educationLevel?->id,
        ]);
    }

    private function findAcademyByInstitutionAndName(int $institutionId, string $name): ?Academy
    {
        $normalized = mb_strtolower(trim($name));

        return Academy::query()
            ->where('institution_id', $institutionId)
            ->get()
            ->first(fn (Academy $a): bool => mb_strtolower(trim($a->name)) === $normalized);
    }

    /**
     * Проверка соответствия группы маске
     */
    private function matchesMask(string $group, string $mask): bool
    {
        // Преобразуем SQL-маску в regex
        $pattern = str_replace(
            ['%', '[', ']'],
            ['.*', '[', ']'],
            $mask
        );
        $pattern = '/^'.$pattern.'$/iu';

        return preg_match($pattern, $group) === 1;
    }
}
