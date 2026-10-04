<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'student',
            'remember_token' => Str::random(60),
            'is_terms_accepted' => 1,
            'is_activated' => 1,
        ];
    }

    /**
     * Создать пользователя с конкретной ролью и email
     */
    public function withRole(string $role, ?int $index = null): static
    {
        $email = match ($role) {
            'admin' => $index ? "admin{$index}@i.ru" : 'admin@i.ru',
            'moderator' => $index ? "moderator{$index}@i.ru" : 'moderator@i.ru',
            'mentor' => $index ? "mentor{$index}@i.ru" : 'mentor@i.ru',
            'student' => $index ? "student{$index}@i.ru" : 'student@i.ru',
            default => "user{$index}@i.ru",
        };

        return $this->state(fn (array $attributes) => [
            'role' => $role,
            'email' => $email,
            'name' => Str::before($email, '@'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
