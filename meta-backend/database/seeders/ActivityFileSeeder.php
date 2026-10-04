<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityFile;
use App\Support\PublicUploadPath;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ActivityFileSeeder extends Seeder
{
    /**
     * Прикрепляет тестовый файл из resources/templates (как в PortfolioSeeder).
     * Для activity_id=1 — всегда; для остальных — случайно (~половина без файлов),
     */
    public function run(): void
    {
        $templatePath = resource_path('templates/Portfolio-Test.jpg');

        if (! file_exists($templatePath)) {
            echo ">> Template not found at {$templatePath}. Skipping activity files.\n";

            return;
        }

        $size = filesize($templatePath);
        $originalName = 'Portfolio-Test.jpg';

        $created = 0;
        $skipped = 0;

        foreach (Activity::query()->orderBy('id')->cursor() as $activity) {
            $mustAttach = $activity->id === 1;
            if (! $mustAttach && rand(0, 1) === 0) {
                $skipped++;

                continue;
            }

            $relativePath = Storage::disk('public')->putFile(
                PublicUploadPath::activityAttachment($activity->id),
                new File($templatePath)
            );

            ActivityFile::create([
                'activity_id' => $activity->id,
                'file_path' => $relativePath,
                'original_name' => $originalName,
                'mime_type' => 'image/jpeg',
                'file_size' => $size,
            ]);

            $created++;
        }

        echo ">> Created {$created} activity file(s) from template";
        if ($skipped > 0) {
            echo " (skipped {$skipped} activities without files)";
        }
        echo ".\n";
    }
}
