<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Support\PublicUploadPath;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $templatePath = resource_path('templates/Portfolio-Test.jpg');

        if (! file_exists($templatePath)) {
            echo ">> Template not found at {$templatePath}. Skipping.\n";

            return;
        }

        // Get all students with at least one confirmed activity
        $students = DB::table('activity_user')
            ->join('activities', 'activity_user.activity_id', '=', 'activities.id')
            ->join('users', 'activity_user.user_id', '=', 'users.id')
            ->where('activity_user.is_confirmed', true)
            ->where('activities.status', 'started')
            ->where('users.role', 'student')
            ->select('activity_user.user_id')
            ->distinct()
            ->get();

        if ($students->isEmpty()) {
            echo ">> No students with confirmed activities found.\n";

            return;
        }

        $totalCreated = 0;

        foreach ($students as $student) {
            $userId = $student->user_id;

            // Get all confirmed activities for this student
            $activities = DB::table('activity_user')
                ->join('activities', 'activity_user.activity_id', '=', 'activities.id')
                ->where('activity_user.user_id', $userId)
                ->where('activity_user.is_confirmed', true)
                ->where('activities.status', 'started')
                ->select('activity_user.user_id', 'activity_user.activity_id', 'activities.title')
                ->get();

            if ($activities->isEmpty()) {
                continue;
            }

            // For user_id 2: create portfolio for ALL activities
            if ($userId == 2) {
                $selectedActivities = $activities;
            } else {
                // Random 50% chance to skip this student entirely
                if (rand(0, 1) === 0) {
                    continue;
                }
                // Randomly select 1 to all activities
                $selectedActivities = $activities->random(rand(1, $activities->count()));
            }

            foreach ($selectedActivities as $activity) {
                // Randomly decide whether to create portfolio with file or with link
                if (rand(0, 1) === 0) {
                    // Create portfolio with file
                    $pathToFile = Storage::disk('public')->putFile(PublicUploadPath::portfolioFile(), new File($templatePath));

                    $portfolio = Portfolio::create([
                        'user_id' => $activity->user_id,
                        'title' => 'Отчет: '.$activity->title,
                        'description' => 'Автоматически сгенерированное портфолио для тестирования.',
                        'path_to_file' => $pathToFile,
                        'original_name' => 'Portfolio-Test.jpg',
                        'extension' => 'jpg',
                    ]);
                } else {
                    // Create portfolio with link only
                    $portfolio = Portfolio::create([
                        'user_id' => $activity->user_id,
                        'title' => 'Ссылка: '.$activity->title,
                        'description' => 'LINK:https://example.com/project/'.$activity->activity_id.PHP_EOL.PHP_EOL.'Автоматически сгенерированная ссылка для тестирования.',
                        'path_to_file' => null,
                        'original_name' => null,
                        'extension' => null,
                    ]);
                }

                DB::table('activity_portfolio')->insert([
                    'portfolio_id' => $portfolio->id,
                    'activity_id' => $activity->activity_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalCreated++;
            }
        }

        echo ">> Created {$totalCreated} portfolio entries.\n";
    }
}
