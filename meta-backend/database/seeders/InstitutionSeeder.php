<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = [
            ['id' => 1, 'name' => 'ЧОУ ВО ЮУ (ИУБиП)'],
            ['id' => 2, 'name' => 'ЧПОУ СИЮУ'],
            ['id' => 3, 'name' => 'ЧПОУ ДИЮУ'],
            ['id' => 4, 'name' => 'Другое'],
        ];

        foreach ($institutions as $institution) {
            Institution::firstOrCreate(
                ['id' => $institution['id']],
                ['name' => $institution['name']]
            );
        }
    }
}
