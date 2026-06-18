<?php

namespace Database\Seeders;

use App\Enums\ActivityLevel;
use App\Enums\AllergyRestriction;
use App\Enums\DietaryPreference;
use App\Enums\FoodExternalSource;
use App\Enums\GoalType;
use App\Enums\MealType;
use App\Enums\WeightLogSource;
use App\Models\BodyWeightLog;
use App\Models\DailyLog;
use App\Models\FoodLogItem;
use App\Models\MealEntry;
use App\Models\User;
use App\Models\UserDietaryPreference;
use App\Models\UserNutritionTarget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'current_weight_kg' => 82.50,
            'height_cm' => 178,
        ]);

        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'activity_level' => ActivityLevel::ModeratelyActive,
            'goal_type' => GoalType::Lose,
            'target_weight_kg' => 75.00,
            'calorie_target' => 2100,
            'protein_target_g' => 160,
            'carbs_target_g' => 210,
            'fat_target_g' => 65,
            'bmr' => 1750,
            'tdee' => 2710,
            'estimated_days_to_goal' => 98,
            'is_active' => true,
            'effective_from' => now(),
        ]);

        UserDietaryPreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'dietary_preferences' => [DietaryPreference::Vegetarian->value],
                'allergies' => [AllergyRestriction::NutFree->value],
            ],
        ]);

        BodyWeightLog::factory()->create([
            'user_id' => $user->id,
            'weight_kg' => 82.50,
            'recorded_at' => now(),
            'source' => WeightLogSource::Onboarding,
        ]);

        $dailyLog = DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => now()->toDateString(),
        ]);

        $breakfast = MealEntry::factory()->create([
            'daily_log_id' => $dailyLog->id,
            'meal_type' => MealType::Breakfast,
            'name' => null,
            'logged_at' => now()->setTime(8, 30),
        ]);

        $lunch = MealEntry::factory()->create([
            'daily_log_id' => $dailyLog->id,
            'meal_type' => MealType::Lunch,
            'name' => null,
            'logged_at' => now()->setTime(13, 0),
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $breakfast->id,
            'food_name' => 'Greek Yogurt',
            'brand_name' => 'Fage',
            'external_food_id' => '12345',
            'external_source' => FoodExternalSource::Fatsecret,
            'quantity' => 1,
            'serving_unit' => 'cup',
            'serving_description' => '1 cup',
            'calories' => 130,
            'protein_g' => 18.00,
            'carbs_g' => 9.00,
            'fat_g' => 4.00,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $breakfast->id,
            'food_name' => 'Banana',
            'brand_name' => null,
            'external_food_id' => '67890',
            'external_source' => FoodExternalSource::Fatsecret,
            'quantity' => 1,
            'serving_unit' => 'medium',
            'serving_description' => '1 medium banana',
            'calories' => 105,
            'protein_g' => 1.30,
            'carbs_g' => 27.00,
            'fat_g' => 0.40,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $lunch->id,
            'food_name' => 'Grilled Chicken Breast',
            'brand_name' => null,
            'external_food_id' => null,
            'external_source' => FoodExternalSource::Manual,
            'quantity' => 1.5,
            'serving_unit' => 'serving',
            'serving_description' => '150g',
            'calories' => 248,
            'protein_g' => 46.50,
            'carbs_g' => 0.00,
            'fat_g' => 5.40,
        ]);

        $dailyLog->recalculateTotals();
    }
}
