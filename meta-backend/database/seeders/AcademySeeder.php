<?php

namespace Database\Seeders;

use App\Models\Academy;
use Illuminate\Database\Seeder;

class AcademySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academies = [
            // Учреждение 1 (ЮУ (ИУБиП)) - 4 академии
            ['name' => 'Академия управления и цифрового развития', 'short_name' => 'АУиЦР', 'institution_id' => 1],
            ['name' => 'Академия гуманитарных технологий', 'short_name' => 'АГТ', 'institution_id' => 1],
            ['name' => 'Академия права и государственной службы', 'short_name' => 'АПиГС', 'institution_id' => 1],
            ['name' => 'Академия экономики, туризма и индустрии гостеприимства', 'short_name' => 'АЭТиИГ', 'institution_id' => 1],

            // Учреждение 2 (СИЮУ) - академия = учреждение
            ['name' => 'ЧПОУ СИЮУ', 'short_name' => 'СИЮУ', 'institution_id' => 2],

            // Учреждение 3 (ДИЮУ) - академия = учреждение
            ['name' => 'ЧПОУ ДИЮУ', 'short_name' => 'ДИЮУ', 'institution_id' => 3],
        ];

        foreach ($academies as $academy) {
            // Ключ — short_name + institution_id: он переживает переименование,
            // поэтому повторный seed обновляет строку на месте, а не плодит дубли.
            Academy::updateOrCreate(
                [
                    'short_name' => $academy['short_name'],
                    'institution_id' => $academy['institution_id'],
                ],
                ['name' => $academy['name']]
            );
        }
    }
}
