<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoringBlackoutSettingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('scoring_blackout_settings')->insert([
            'winter_start' => '12-26',
            'winter_end' => '01-31',
            'summer_start' => '06-26',
            'summer_end' => '08-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
