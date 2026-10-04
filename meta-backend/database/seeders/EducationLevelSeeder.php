<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use Illuminate\Database\Seeder;

class EducationLevelSeeder extends Seeder
{
    public function run(): void
    {
        # НУО (Нормативный Уровень Ожиданий) — базовый уровень сложности для студента этого курса.
        EducationLevel::create(['name' => 'Колледж', 'nuo_value' => 1]);
        EducationLevel::create(['name' => 'Бакалавриат', 'nuo_value' => 2]);
        EducationLevel::create(['name' => 'Магистратура', 'nuo_value' => 3]);
    }
}
