<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WishlistActivitySeeder extends Seeder
{
    private const STUDENT_WISHLIST_MIN = 2;

    private const STUDENT_WISHLIST_MAX = 5;

    public function run(): void
    {
        $students = User::query()->where('role', 'student')->orderBy('id')->get();

        if ($students->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($students as $student) {
            $count = rand(self::STUDENT_WISHLIST_MIN, self::STUDENT_WISHLIST_MAX);

            $registeredIds = DB::table('activity_user')
                ->where('user_id', $student->id)
                ->pluck('activity_id');

            $candidateIds = Activity::query()
                ->where('status', '!=', 'draft')
                ->whereNotIn('id', $registeredIds)
                ->pluck('id');

            if ($candidateIds->isEmpty()) {
                continue;
            }

            $take = min($count, $candidateIds->count());
            $picked = $candidateIds->shuffle()->take($take);

            foreach ($picked as $activityId) {
                $rows[] = [
                    'user_id' => $student->id,
                    'activity_id' => $activityId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('wishlist_activity')->insert($chunk);
        }
    }
}
