<?php

namespace Tests\Feature\Api;

use App\Enums\MealType;
use App\Models\DailyLog;
use App\Models\FoodLogItem;
use App\Models\MealEntry;
use App\Models\User;
use App\Models\UserNutritionTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_daily_summary(): void
    {
        $user = User::factory()->create();

        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'calorie_target' => 2100,
            'protein_target_g' => 160,
            'carbs_target_g' => 210,
            'fat_target_g' => 65,
            'is_active' => true,
        ]);

        $dailyLog = DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => now()->toDateString(),
            'total_calories' => 195,
            'total_protein_g' => 29.55,
            'total_carbs_g' => 0,
            'total_fat_g' => 7.57,
        ]);

        $mealEntry = MealEntry::factory()->create([
            'daily_log_id' => $dailyLog->id,
            'meal_type' => MealType::Breakfast,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $mealEntry->id,
            'food_name' => 'Chicken Breast',
            'calories' => 195,
            'protein_g' => 29.55,
            'carbs_g' => 0,
            'fat_g' => 7.57,
            'quantity' => 100,
            'serving_unit' => 'g',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/daily-summary?date='.now()->toDateString());

        $response->assertOk()
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonPath('targets.calories', 2100)
            ->assertJsonPath('consumed.calories', 195)
            ->assertJsonPath('remaining.calories', 1905)
            ->assertJsonPath('meals.0.meal_type', 'breakfast')
            ->assertJsonPath('meals.0.items.0.food_name', 'Chicken Breast');
    }
}
