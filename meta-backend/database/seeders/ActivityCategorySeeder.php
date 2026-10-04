<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('activity_categories')->insert([
            ['name' => 'Проектная', 'description' => 'Работа над проектами в составе команды, участие в кейс-чемпионатах и хакатонах', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Научная', 'description' => 'Публикационная активность, выступления на научных мероприятиях, участие в НИР и олимпиадах', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Общественная', 'description' => 'Волонтёрство, реализация социальных инициатив, участие в студенческих объединениях, профориентационная работа', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Культурно-творческая', 'description' => 'Участие в культурных, спортивных, творческих мероприятиях, конкурсах, фестивалях', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
