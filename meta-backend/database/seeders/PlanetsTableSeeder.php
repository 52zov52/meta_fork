<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanetsTableSeeder extends Seeder
{
    public function run(): void
    {
        $planets = [
            ['name' => 'Сфера туризма и гостеприимства'],
            ['name' => 'Знания в области информатике'],
            ['name' => 'Финасовая компетентность'],
            ['name' => 'Гуманитарные технологии'],
            ['name' => 'Правовая компетентность'],
            ['name' => 'Менеджмент и искусственный интеллект'],
            ['name' => 'ИУБиП 360'],
        ];

        foreach ($planets as $planet) {
            if (! DB::table('planets')->where('name', $planet['name'])->exists()) {
                DB::table('planets')->insert($planet);
            }
        }
    }
}
