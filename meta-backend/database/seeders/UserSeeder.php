<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->createPredictableUsers();
            $this->createAdditionalUsers();
        });

        $this->outputSummary();
    }

    /**
     * Create predictable core users with fixed IDs
     */
    private function createPredictableUsers(): void
    {
        $predictableUsers = [
            ['id' => 1, 'role' => 'admin', 'email' => 'admin@i.ru'],
            ['id' => 2, 'role' => 'student', 'email' => 'student@i.ru', 'institution_id' => 1],
            ['id' => 3, 'role' => 'mentor', 'email' => 'mentor@i.ru'],
            ['id' => 4, 'role' => 'moderator', 'email' => 'moderator@i.ru', 'institution_id' => 1],
        ];

        $existingEmails = User::whereIn('email', array_column($predictableUsers, 'email'))
            ->pluck('email')
            ->toArray();

        foreach ($predictableUsers as $userData) {
            if (in_array($userData['email'], $existingEmails)) {
                continue;
            }

            $user = User::factory()
                ->withRole($userData['role'])
                ->create([
                    'id' => $userData['id'],
                ]);

            $memberFactory = Member::factory();

            if ($userData['role'] === 'student') {
                $memberFactory = $memberFactory->forStudent();
            } elseif ($userData['role'] === 'mentor') {
                // Associate the predictable mentor with the digital academy (АУиЦР)
                $academy = \App\Models\Academy::where('institution_id', 1)
                    ->where('short_name', 'АУиЦР')
                    ->first() ?? \App\Models\Academy::where('institution_id', 1)->first();
                $memberFactory = $memberFactory->forMentor($academy);
            } else {
                $memberFactory = $memberFactory->forStaff();
            }

            $memberAttributes = ['user_id' => $user->id];

            if ($userData['id'] === 2) {
                $memberAttributes = array_merge($memberAttributes, [
                    'last_name' => 'Иванов',
                    'first_name' => 'Иван',
                    'middle_name' => 'Иванович',
                    'gender' => 0,
                ]);
            }

            if (isset($userData['institution_id'])) {
                $memberAttributes['institution_id'] = $userData['institution_id'];
            }

            $memberFactory->create($memberAttributes);
        }
    }

    /**
     * Create additional users for testing
     */
    private function createAdditionalUsers(): void
    {
        $roles = ['admin', 'moderator', 'mentor', 'student'];
        $counts = ['admin' => 2, 'moderator' => 2, 'mentor' => 2, 'student' => 150];

        foreach ($roles as $role) {
            $this->createUsersWithRole($role, $counts[$role]);
        }
    }

    /**
     * Create users with specific role
     */
    private function createUsersWithRole(string $role, int $count, ?int $startId = null): void
    {
        $expectedEmails = collect();
        for ($i = 1; $i <= $count; $i++) {
            $index = $i === 1 ? null : $i;
            $email = $index ? "{$role}{$index}@i.ru" : "{$role}@i.ru";
            $expectedEmails->push($email);
        }

        $existingEmails = User::whereIn('email', $expectedEmails)
            ->pluck('email')
            ->toArray();

        foreach ($expectedEmails as $email) {
            if (in_array($email, $existingEmails)) {
                continue;
            }

            // Extract index from email (e.g., "admin2@i.ru" → 2)
            preg_match('/(\d+)/', $email, $matches);
            $index = $matches[1] ?? null;

            $user = User::factory()
                ->withRole($role, $index)
                ->create(['email' => $email]);

            $memberFactory = Member::factory();

            $memberFactory = match ($role) {
                'student' => $memberFactory->forStudent(),
                'mentor'  => $memberFactory->forMentor(\App\Models\Academy::inRandomOrder()->first()),
                'admin', 'moderator' => $memberFactory->forStaff(),
                default => $memberFactory,
            };

            $memberFactory->create(['user_id' => $user->id]);
        }
    }

    /**
     * Output summary of created users
     */
    private function outputSummary(): void
    {
        echo "\n>> Predictable users (password: 'password'):\n";
        echo "   - Admin: id=1, email=admin@i.ru\n";
        echo "   - Student: id=2, email=student@i.ru, ФИО: Иванов Иван Иванович\n";
        echo "   - Mentor: id=3, email=mentor@i.ru\n";
        echo "   - Moderator: id=4, email=moderator@i.ru\n";

        $counts = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        echo "\n>> Total users per role:\n";
        foreach ($counts as $role => $total) {
            echo "   - {$role}: {$total}\n";
        }
    }
}
