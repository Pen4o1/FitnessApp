<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'gender' => fake()->randomElement(Gender::cases()),
            'birthdate' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'current_weight_kg' => fake()->randomFloat(2, 55, 110),
            'height_cm' => fake()->numberBetween(155, 195),
            'google_id' => null,
            'avatar_path' => null,
            'profile_completed_at' => now(),
            'remember_token' => Str::random(10),
        ];
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

    /**
     * Indicate that the profile is incomplete (e.g. Google OAuth signup).
     */
    public function incompleteProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_weight_kg' => null,
            'height_cm' => null,
            'birthdate' => null,
            'gender' => null,
            'profile_completed_at' => null,
        ]);
    }
}
