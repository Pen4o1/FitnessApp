<?php

namespace Database\Factories;

use App\Enums\DietType;
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
                'diet_type' => fake()->randomElement(DietType::cases())->value,
                'allergies' => fake()->randomElements(['peanuts', 'gluten', 'shellfish', 'soy'], fake()->numberBetween(0, 2)),
                'intolerances' => fake()->randomElements(['lactose', 'fructose'], fake()->numberBetween(0, 1)),
                'excluded_ingredients' => [],
                'custom_notes' => fake()->optional()->sentence(),
            ],
        ];
    }
}
