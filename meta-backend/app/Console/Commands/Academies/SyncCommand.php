<?php

namespace App\Console\Commands\Academies;

use App\Models\Academy;
use App\Models\EducationLevel;
use App\Models\Member;
use App\Services\AcademyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academies:sync
                            {--dry-run : Показать, что будет сделано, без изменений}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизировать академии и уровни обучения; детальный вывод — php artisan academies:sync -v';

    /**
     * Execute the console command.
     */
    public function handle(AcademyService $academyService): int
    {
        $dryRun = $this->option('dry-run');
        $verbose = $this->output->isVerbose();

        $academiesByInstitutionAndName = Academy::query()
            ->get(['id', 'name', 'institution_id'])
            ->keyBy(fn (Academy $academy): string => AcademyService::academyMapKey($academy->institution_id, $academy->name));

        if ($dryRun && $verbose) {
            $this->info('🔍 Запуск в режиме dry-run (без изменений)');
        }

        $membersQuery = Member::query()
            ->with(['academy:id,name', 'educationLevel:id,name', 'institution:id,name'])
            ->whereHas('user', function ($query) {
                $query->where('role', 'student');
            });

        if ($verbose) {
            $this->info('Найдено студентов: '.$membersQuery->count());
        }

        $updated = 0;
        $unchanged = 0;
        $missingGroup = 0;
        $academyNotResolved = 0;
        $unmatchedGroups = collect();

        $chunkSize = 500;
        $educationLevelsByName = EducationLevel::query()
            ->pluck('id', 'name');

        try {
            $membersQuery
                ->orderBy('id')
                ->chunkById($chunkSize, function ($members) use (
                    $academyService,
                    $academiesByInstitutionAndName,
                    $dryRun,
                    $educationLevelsByName,
                    $verbose,
                    &$updated,
                    &$unchanged,
                    &$missingGroup,
                    &$academyNotResolved,
                    &$unmatchedGroups,
                ): void {
                    $processChunk = function () use (
                        $academyService,
                        $academiesByInstitutionAndName,
                        $dryRun,
                        $educationLevelsByName,
                        $members,
                        $verbose,
                        &$unchanged,
                        &$missingGroup,
                        &$academyNotResolved,
                        &$unmatchedGroups,
                        &$updated
                    ): void {
                        foreach ($members as $member) {
                            $group = $member->student_group;

                            if (! $group) {
                                if ($verbose) {
                                    $this->warn("⚠️ Студент #{$member->user_id} — нет группы");
                                }
                                $missingGroup++;

                                continue;
                            }

                            $institutionId = (int) $member->institution_id;
                            $academy = $academyService->resolveAcademyForMember($member, $academiesByInstitutionAndName);
                            $educationLevelName = $academyService->determineEducationLevel($group);

                            if (! $academy && in_array($institutionId, [1, 2, 3], true)) {
                                $unmatchedGroups->push($group);
                                if ($verbose) {
                                    $this->warn("⚠️ Студент #{$member->user_id} — не найдена академия для группы \"{$group}\"");
                                }
                                $academyNotResolved++;

                                continue;
                            }

                            $educationLevelId = $educationLevelsByName->get($educationLevelName);
                            $targetAcademyId = $academy?->id;
                            $needsUpdate = $member->academy_id !== $targetAcademyId
                                || $member->education_level_id !== $educationLevelId;

                            if (! $needsUpdate) {
                                $unchanged++;

                                continue;
                            }

                            if ($dryRun) {
                                if ($verbose) {
                                    $this->line("📝 Студент #{$member->user_id}:");
                                    $this->line("   Группа: {$group}");
                                    $this->line('   Академия: '.($member->academy?->name ?? 'null').' → '.($academy?->name ?? 'null'));
                                    $this->line('   Уровень: '.($member->educationLevel?->name ?? 'null').' → '.($educationLevelName ?? 'null'));
                                }
                                $updated++;

                                continue;
                            }

                            $member->update([
                                'academy_id' => $targetAcademyId,
                                'education_level_id' => $educationLevelId,
                            ]);
                            $updated++;
                        }
                    };

                    if ($dryRun) {
                        $processChunk();

                        return;
                    }

                    DB::transaction($processChunk);
                }, 'id');
        } catch (Throwable $exception) {
            $this->error('❌ Синхронизация прервана: '.$exception->getMessage());

            return Command::FAILURE;
        }

        if ($verbose) {
            $this->newLine();
        }
        $this->info('📊 Итоги:');
        $this->line("   Обновлено: {$updated}");
        $this->line("   Без изменений (академия и уровень уже совпадали с расчётом): {$unchanged}");
        $this->line("   Не обновлено — не указана группа (student_group пустой): {$missingGroup}");
        $this->line("   Не обновлено — по группе не найдена академия в справочнике: {$academyNotResolved}");

        $this->newLine();
        $this->info('Группы без академии:');
        if ($unmatchedGroups->isEmpty()) {
            $this->line('   (нет)');
        } else {
            $countsByGroup = $unmatchedGroups->countBy()->sortByDesc(
                fn (int $count): int => $count
            );
            $totalUnmatchedStudents = $unmatchedGroups->count();
            $this->line("   Всего студентов (не сопоставлено с академией): {$totalUnmatchedStudents}");
            $this->newLine();
            $this->table(
                ['Группа', 'Студентов'],
                $countsByGroup->map(fn (int $count, string $group): array => [$group, $count])->values()->all()
            );
        }

        return Command::SUCCESS;
    }
}
