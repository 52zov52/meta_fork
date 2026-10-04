<?php

namespace Database\Seeders;

use App\Models\BaseScore;
use Illuminate\Database\Seeder;

class BaseScoreSeeder extends Seeder
{
    public function run(): void
    {
        // === Категория: Проектная (id=1) ===
        BaseScore::create([
            'category_id' => 1,
            'activity_type' => 'internal',
            'has_implementation' => true,
            'participation' => 50,
        ]);

        BaseScore::create([
            'category_id' => 1,
            'activity_type' => 'internal',
            'has_implementation' => false,
            'participation' => 25,
        ]);

        BaseScore::create([
            'category_id' => 1,
            'activity_type' => 'external',
            'has_places' => true,
            'first_place' => 25,
            'second_place' => 20,
            'third_place' => 15,
            'participation' => 10,
        ]);

        BaseScore::create([
            'category_id' => 1,
            'activity_type' => 'external',
            'has_places' => false,
            'participation' => 10,
        ]);

        // === Категория: Научная (id=2) ===
        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'rinz_collection',
            'participation' => 15,
        ]);

        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'conference_presentation',
            'participation' => 20,
        ]);

        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'rinz_journal',
            'participation' => 25,
        ]);

        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'vak_collection',
            'participation' => 50,
        ]);

        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'nir_contract',
            'participation' => 50,
        ]);

        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'nir_competition',
            'has_places' => true,
            'first_place' => 25,
            'second_place' => 20,
            'third_place' => 15,
            'participation' => 10,
        ]);

        BaseScore::create([
            'category_id' => 2,
            'activity_type' => 'nir_competition',
            'has_places' => false,
            'participation' => 10,
        ]);

        // === Категория: Общественная (id=3) ===
        BaseScore::create([
            'category_id' => 3,
            'activity_type' => null,
            'is_leader' => false,
            'participation' => 10,
        ]);

        BaseScore::create([
            'category_id' => 3,
            'activity_type' => null,
            'is_leader' => true,
            'participation' => 15,
        ]);

        // === Категория: Культурно-творческая (id=4) ===
        BaseScore::create([
            'category_id' => 4,
            'activity_type' => 'cultural_internal',
            'participation' => 10,
        ]);

        BaseScore::create([
            'category_id' => 4,
            'activity_type' => 'cultural_external',
            'has_places' => true,
            'first_place' => 25,
            'second_place' => 20,
            'third_place' => 15,
            'participation' => 10,
        ]);

        BaseScore::create([
            'category_id' => 4,
            'activity_type' => 'cultural_external',
            'has_places' => false,
            'participation' => 10,
        ]);

        // Просветительские: диктанты, экскурсии в мемориалы, культурные события.
        // Только participation, без призовых мест.
        BaseScore::create([
            'category_id' => 4,
            'activity_type' => 'cultural_outreach',
            'participation' => 5,
        ]);
    }
}
