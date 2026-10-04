<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityRegistrationSeeder extends Seeder
{
    const LEVEL_PARTICIPANT = 0.4;

    public function run(): void
    {
        $devStudent = User::find(2);

        $otherStudents = User::where('role', 'student')->where('id', '!=', 2)->get();

        $activities = Activity::all();

        if ($activities->isEmpty()) {
            return;
        }

        $registrations = [];
        $seq = 0;

        foreach ($activities as $activity) {
            if ($activity->status === 'draft') {
                continue;
            }

            $canConfirmScore = $this->canConfirmScore($activity);

            if ($devStudent) {
                if ($activity->status === 'started') {
                    $isConfirmed = $canConfirmScore;
                    $timestamp = $this->buildRegistrationTimestamp($activity, $devStudent->id, $seq++, $isConfirmed);
                    $registrations[] = [
                        'user_id' => $devStudent->id,
                        'activity_id' => $activity->id,
                        'is_confirmed' => $isConfirmed,
                        'level' => self::LEVEL_PARTICIPANT,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }

            if ($otherStudents->isNotEmpty()) {
                $count = rand(2, 5);
                $randoms = $otherStudents->random(min($count, $otherStudents->count()));

                foreach ($randoms as $student) {
                    $isConfirmed = $canConfirmScore && ($activity->status === 'started' || $activity->status === 'completed');
                    $timestamp = $this->buildRegistrationTimestamp($activity, $student->id, $seq++, $isConfirmed);

                    $registrations[] = [
                        'user_id' => $student->id,
                        'activity_id' => $activity->id,
                        'is_confirmed' => $isConfirmed,
                        'level' => self::LEVEL_PARTICIPANT,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        foreach (array_chunk($registrations, 500) as $chunk) {
            DB::table('activity_user')->insert($chunk);
        }

    }

    private function canConfirmScore(Activity $activity): bool
    {
        return Carbon::parse($activity->date_start)->lessThanOrEqualTo(now());
    }

    private function buildRegistrationTimestamp(Activity $activity, int $userId, int $seq, bool $isConfirmed): Carbon
    {
        $now = now();
        $start = Carbon::parse($activity->date_start)->startOfDay();
        $end = Carbon::parse($activity->date_end)->endOfDay();

        if ($isConfirmed) {
            $maxMoment = $end->lessThan($now) ? $end : $now->copy();
            $daysSpan = max(0, $start->diffInDays($maxMoment, false));
            $base = $start->copy()->addDays(max(0, min($daysSpan, $seq % 7)));
        } else {
            $base = $start->copy()->subDays(rand(3, 30));
            if ($base->greaterThan($now)) {
                $base = $now->copy()->subDays(rand(0, 14));
            }
        }

        $timestamp = $base
            ->addHours(($activity->id + $userId + $seq) % 24)
            ->addMinutes((($activity->id * 3) + $userId + $seq) % 60);

        return $timestamp->greaterThan($now) ? $now->copy()->subMinute() : $timestamp;
    }
}
