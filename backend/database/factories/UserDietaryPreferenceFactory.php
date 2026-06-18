<?php

namespace Database\Factories;

use App\Enums\AllergyRestriction;
use App\Enums\DietaryPreference;
use App\Models\User;
use App\Models\UserDietaryPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserDietaryPreference>
 */
class UserDietaryPreferenceFactory extends Factory
{
    protected $model = UserDietaryPreference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'preferences' => [
                'dietary_preferences' => fake()->randomElements(
                    array_map(fn (DietaryPreference $preference) => $preference->value, DietaryPreference::cases()),
                    fake()->numberBetween(0, 2),
                ),
                'allergies' => fake()->randomElements(
                    array_map(fn (AllergyRestriction $restriction) => $restriction->value, AllergyRestriction::cases()),
                    fake()->numberBetween(0, 2),
                ),
            ],
        ];
    }
}
