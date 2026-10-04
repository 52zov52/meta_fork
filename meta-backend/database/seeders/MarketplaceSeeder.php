<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        if (! $creatorId) {
            return;
        }

        $now = now();

        DB::table('marketplace')->insert([
            [
                'name' => 'Фирменный блокнот',
                'score' => 15,
                'is_archive' => false,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Термокружка',
                'score' => 25,
                'is_archive' => false,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Шоппер учебного заведения',
                'score' => 35,
                'is_archive' => false,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Архивный тестовый приз',
                'score' => 10,
                'is_archive' => true,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
