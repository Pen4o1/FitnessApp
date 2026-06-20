<?php

namespace Database\Factories;

use App\Models\SavedMealPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedMealPlan>
 */
class SavedMealPlanFactory extends Factory
{
    protected $model = SavedMealPlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_date' => now()->toDateString(),
            'meals_count' => 4,
            'total_calories' => 2000,
            'total_protein_g' => 150,
            'total_carbs_g' => 200,
            'total_fat_g' => 65,
            'within_target' => true,
            'plan_data' => [
                'date' => now()->toDateString(),
                'meals_count' => 4,
                'targets' => [
                    'calories' => 2000,
                    'protein_g' => 150,
                    'carbs_g' => 200,
                    'fat_g' => 65,
                ],
                'totals' => [
                    'calories' => 2000,
                    'protein_g' => 150,
                    'carbs_g' => 200,
                    'fat_g' => 65,
                ],
                'within_target' => true,
                'variance' => [
                    'calories_pct' => 0,
                    'protein_g_pct' => 0,
                    'carbs_g_pct' => 0,
                    'fat_g_pct' => 0,
                ],
                'dietary_preferences' => [],
                'allergies' => [],
                'meals' => [],
            ],
            'logged_to_diary_at' => now(),
        ];
    }
}
