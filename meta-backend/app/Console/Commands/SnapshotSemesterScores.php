<?php

namespace App\Console\Commands;

use App\Services\ScoreService;
use App\Services\SemesterService;
use App\Services\ScoreCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotSemesterScores extends Command
{
    /**
     * Сигнатура команды.
     *
     * @var string
     */
    protected $signature = 'scores:snapshot';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Подсчитать и заморозить баллы за завершившийся семестр в previous_semester_score';

    /**
     * Выполнить команду.
     *
     * Семестры определяются динамически через SemesterService на основе настроек blackout.
     */
    public function handle(): int
    {
        // Завершившийся семестр: при текущем С1 (сентябрь+) — предыдущий С2;
        // при текущем С2 (февраль+) — предыдущий С1.
        $semester = SemesterService::getPreviousSemester();

        $this->info("Подсчёт баллов за период (по дате окончания мероприятия): {$semester['start']->format('Y-m-d')} — {$semester['end']->format('Y-m-d')}");

        if (! $this->confirm('Это перезапишет previous_semester_score для ВСЕХ пользователей. Продолжить?')) {
            return Command::FAILURE;
        }

        $cap = ScoreService::CAP_PER_CATEGORY;

        // Сумма по категориям после LEAST(сумма_в_категории, cap) — как ScoreService::getCappedTotalForUser
        $earnedScoreExpr = ScoreCalculationService::earnedScoreExpression('au', 'a');
        $affected = DB::update("
            UPDATE users
            SET previous_semester_score = (
                SELECT COALESCE(SUM(category_capped), 0)
                FROM (
                    SELECT ROUND(LEAST(SUM({$earnedScoreExpr}), ?)) as category_capped
                    FROM activity_user au
                    JOIN activities a ON au.activity_id = a.id
                    WHERE au.user_id = users.id
                    AND au.is_confirmed = 1
                    AND a.date_end BETWEEN ? AND ?
                    GROUP BY a.category_id
                ) t
            )
        ", [$cap, $semester['start'], $semester['end']]);

        $this->info("✅ Обновлено пользователей: {$affected}");

        return Command::SUCCESS;
    }
}
