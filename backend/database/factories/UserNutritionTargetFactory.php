<?php

namespace Database\Factories;

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Models\User;
use App\Models\UserNutritionTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNutritionTarget>
 */
class UserNutritionTargetFactory extends Factory
{
    protected $model = UserNutritionTarget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_level' => fake()->randomElement(ActivityLevel::cases()),
            'goal_type' => fake()->randomElement(GoalType::cases()),
            'target_weight_kg' => fake()->randomFloat(2, 60, 95),
            'calorie_target' => fake()->numberBetween(1600, 2800),
            'protein_target_g' => fake()->numberBetween(100, 200),
            'carbs_target_g' => fake()->numberBetween(150, 300),
            'fat_target_g' => fake()->numberBetween(50, 90),
            'bmr' => fake()->numberBetween(1400, 2200),
            'tdee' => fake()->numberBetween(1800, 3000),
            'estimated_days_to_goal' => fake()->numberBetween(30, 180),
            'is_active' => true,
            'effective_from' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
