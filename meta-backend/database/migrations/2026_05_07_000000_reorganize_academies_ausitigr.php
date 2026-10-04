<?php

use App\Models\Academy;
use App\Services\AcademyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Реорганизация академий ИУБиП.
 *
 * АТиИГ «Академия туризма и индустрии гостеприимства» — ликвидирована,
 *      её группы Т* переходят в АЭТиИГ (вместе с её мероприятиями).
 * АЦР   → АУиЦР «Академия управления и цифрового развития».
 * АЭиУ  → АЭТиИГ «Академия экономики, туризма и индустрии гостеприимства».
 *
 * Дополнительно АУиЦР забирает по новым маскам:
 *   М%    и К[1-4]Л% — группы, ранее относившиеся к АЭиУ;
 *   БДК%  — новая маска, которой раньше не было.
 *
 * Переименование идёт «на месте», id сохраняются, поэтому members.academy_id
 * и activities.academy_id остаются валидными. Порядок важен: сначала
 * переносим ссылки на ликвидируемую АТиИГ, и только потом удаляем строку,
 * иначе сработает ON DELETE SET NULL.
 */
return new class extends Migration
{
    private const RENAMES = [
        'АЦР' => [
            'name' => 'Академия управления и цифрового развития',
            'short_name' => 'АУиЦР',
        ],
        'АЭиУ' => [
            'name' => 'Академия экономики, туризма и индустрии гостеприимства',
            'short_name' => 'АЭТиИГ',
        ],
    ];

    private const MERGED_AWAY_SHORT = 'АТиИГ';

    private const MERGED_AWAY_NAME = 'Академия туризма и индустрии гостеприимства';

    /**
     * Штатная short_name академии-правопреемника (после up() — АЭТиИГ,
     * после down() — снова АЭиУ). По ней переносим наследие АТиИГ.
     */
    private const SUCCESSOR_SHORT = 'АЭТиИГ';

    /**
     * Маски до реорганизации — нужны только для точного отката.
     *
     * @var array<string, list<string>>
     */
    private const PRE_REORG_MASKS = [
        'Академия туризма и индустрии гостеприимства' => ['Т%', 'К[1-4]Т%'],
        'Академия цифрового развития' => ['И%', 'К[1-4]И%', 'БИС%', 'К[1-4]РУ%'],
        'Академия гуманитарных технологий' => ['П%', 'УП%', 'К[1-4]М%', 'К[1-4]Ф%', 'К[1-4]А%', 'К[1-4]С%'],
        'Академия права и государственной службы' => ['Ю%', 'К[1-4]Ю%'],
        'Академия экономики и управления' => ['Э%', 'М%', 'К[1-4]Э%', 'К[1-4]Л%'],
    ];

    public function up(): void
    {
        $this->applyRenames(self::RENAMES);

        $mergedAwayId = $this->academyIdByShort(self::MERGED_AWAY_SHORT);
        $successorId = $this->academyIdByShort(self::SUCCESSOR_SHORT);

        if ($mergedAwayId && $successorId) {
            DB::table('members')->where('academy_id', $mergedAwayId)->update(['academy_id' => $successorId]);
            DB::table('activities')->where('academy_id', $mergedAwayId)->update(['academy_id' => $successorId]);
            DB::table('academies')->where('id', $mergedAwayId)->delete();
        }

        // Пересчёт по новым маскам ловит переезд М*/К*Л* в АУиЦР, новую маску
        // БДК% и незаполненные academy_id (импорт DOCX, саморегистрация,
        // создание студента наставником их не пишут вовсе).
        $this->reassignMembersUsing(fn (string $group): ?int => app(AcademyService::class)
            ->determineAcademyByGroup($group)
            ?->id);
    }

    public function down(): void
    {
        // АТиИГ восстанавливаем первой, чтобы пересчёт по старым маскам
        // мог найти её по имени. Новый id не совпадёт с исходным: точность
        // отката обеспечивается пересчётом всех ссылок по маскам ниже.
        $mergedAwayId = Academy::create([
            'name' => self::MERGED_AWAY_NAME,
            'short_name' => self::MERGED_AWAY_SHORT,
            'institution_id' => 1,
        ])->id;

        $this->applyRenames([
            'АУиЦР' => ['name' => 'Академия цифрового развития', 'short_name' => 'АЦР'],
            'АЭТиИГ' => ['name' => 'Академия экономики и управления', 'short_name' => 'АЭиУ'],
        ]);

        $this->reassignMembersUsing(fn (string $group): ?int => $this->academyIdByName($this->resolvePreReorgAcademyName($group)));

        // У мероприятий нет группы, поэтому восстановить принадлежность
        // к АТиИГ / АЦР невозможно: все мероприятия правопреемника уходят в АТиИГ.
        $successorId = $this->academyIdByShort('АЭиУ');
        if ($successorId) {
            DB::table('activities')->where('academy_id', $successorId)->update(['academy_id' => $mergedAwayId]);
        }
    }

    /**
     * @param  array<string, array{name: string, short_name: string}>  $renames
     */
    private function applyRenames(array $renames): void
    {
        foreach ($renames as $fromShort => $to) {
            Academy::where('institution_id', 1)
                ->where('short_name', $fromShort)
                ->update([
                    'name' => $to['name'],
                    'short_name' => $to['short_name'],
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Проставить каждому студенту ИУБиП академию, вычисленную из его группы.
     *
     * @param  callable(string): ?int  $resolver
     */
    private function reassignMembersUsing(callable $resolver): void
    {
        DB::table('members')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->where('users.role', 'student')
            ->where('members.institution_id', 1)
            ->whereNotNull('members.student_group')
            ->orderBy('members.id')
            ->select('members.id', 'members.student_group')
            // Колонка чанка обязана быть квалифицирована: из-за join с users
            // без префикса MySQL ругается "Column 'id' is ambiguous" уже на
            // втором проходе, то есть ровно там, где записей больше размера чанка.
            ->chunkById(500, function ($rows) use ($resolver) {
                foreach ($rows as $row) {
                    DB::table('members')
                        ->where('id', $row->id)
                        ->update(['academy_id' => $resolver($row->student_group), 'updated_at' => now()]);
                }
            }, 'members.id', 'id');
    }

    private function resolvePreReorgAcademyName(string $group): ?string
    {
        foreach (self::PRE_REORG_MASKS as $academyName => $masks) {
            if ($this->matchesAnyMask($group, $masks)) {
                return $academyName;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $masks
     */
    private function matchesAnyMask(string $group, array $masks): bool
    {
        foreach ($masks as $mask) {
            $pattern = '/^'.str_replace(['%', '[', ']'], ['.*', '[', ']'], $mask).'$/iu';

            if (preg_match($pattern, $group) === 1) {
                return true;
            }
        }

        return false;
    }

    private function academyIdByShort(string $shortName): ?int
    {
        return Academy::where('institution_id', 1)->where('short_name', $shortName)->value('id');
    }

    private function academyIdByName(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        return Academy::where('institution_id', 1)->where('name', $name)->value('id');
    }
};
