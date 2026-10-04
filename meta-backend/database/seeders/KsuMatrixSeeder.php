<?php

namespace Database\Seeders;

use App\Models\KsuMatrix;
use Illuminate\Database\Seeder;

class KsuMatrixSeeder extends Seeder
{
    public function run(): void
    {
        KsuMatrix::insert([
            ['delta' => -2, 'coefficient' => 0.80, 'description' => 'УТА значительно ниже НУО', 'created_at' => now(), 'updated_at' => now()],
            ['delta' => -1, 'coefficient' => 0.90, 'description' => 'УТА ниже НУО', 'created_at' => now(), 'updated_at' => now()],
            ['delta' =>  0, 'coefficient' => 1.00, 'description' => 'УТА соответствует НУО', 'created_at' => now(), 'updated_at' => now()],
            ['delta' => +1, 'coefficient' => 1.10, 'description' => 'УТА выше НУО', 'created_at' => now(), 'updated_at' => now()],
            ['delta' => +2, 'coefficient' => 1.20, 'description' => 'УТА значительно выше НУО', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
