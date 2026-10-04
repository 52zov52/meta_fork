<?php

namespace App\Console\Commands\Academies;

use App\Models\Member;
use Illuminate\Console\Command;

class ClearInstitutionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutions:clear {--dry-run : Показать, что будет сделано, без изменений}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить поле institution для записей, где institution_id != 4';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Запуск в режиме dry-run (без изменений)');
        }

        // Находим записи, где institution_id != 4 и institution не null
        $members = Member::where('institution_id', '!=', 4)
            ->whereNotNull('institution')
            ->get();

        $this->info("Найдено записей для очистки: {$members->count()}");

        if ($members->isEmpty()) {
            $this->info('✅ Нечего очищать');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info('📋 Записи, которые будут очищены:');
            foreach ($members as $member) {
                $this->line("  #{$member->id} ({$member->user_id}): institution = \"{$member->institution}\"");
            }

            return Command::SUCCESS;
        }

        // Очищаем institution
        $updated = Member::where('institution_id', '!=', 4)
            ->whereNotNull('institution')
            ->update(['institution' => null]);

        $this->info("✅ Очищено записей: {$updated}");

        return Command::SUCCESS;
    }
}
