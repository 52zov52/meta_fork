<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Mapping таблиц к их сидерам
     */
    private array $seedersMap = [
        'education_levels' => EducationLevelSeeder::class,
        'planets' => PlanetsTableSeeder::class,
        'regions' => RegionsTableSeeder::class,
        'institutions' => InstitutionSeeder::class,
        'academies' => AcademySeeder::class,
        'users' => UserSeeder::class,
        'marketplace' => MarketplaceSeeder::class,
        'notifications' => NotificationSeeder::class,
        'activity_categories' => ActivityCategorySeeder::class,
        'activities' => ActivitySeeder::class,
        'activity_files' => ActivityFileSeeder::class,
        'activity_user' => ActivityRegistrationSeeder::class,
        'wishlist_activity' => WishlistActivitySeeder::class,
        'portfolio' => PortfolioSeeder::class,
        'scoring_blackout_settings' => ScoringBlackoutSettingSeeder::class,
        'ksu_matrix' => KsuMatrixSeeder::class,
        'base_scores' => BaseScoreSeeder::class,
        'activity_parameters' => KsuActivitySeeder::class,
    ];

    public function run(): void
    {
        $seedersToRun = [];

        foreach ($this->seedersMap as $table => $seederClass) {
            if (DB::table($table)->count() === 0) {
                $seedersToRun[] = $seederClass;
                echo ">> Table '{$table}' is empty, will seed...\n";
            } else {
                echo ">> Table '{$table}' already has data, skipping...\n";
            }
        }

        if (! empty($seedersToRun)) {
            $this->call($seedersToRun);
            echo ">> Seeding completed!\n";
        } else {
            echo ">> All tables already seeded, nothing to do.\n";
        }
    }
}
