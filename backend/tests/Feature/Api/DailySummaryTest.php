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

    public function test_daily_summary_defaults_to_today_when_date_omitted(): void
    {
        $user = User::factory()->create();

        $dailyLog = DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => now()->toDateString(),
            'total_calories' => 300,
            'total_protein_g' => 20,
            'total_carbs_g' => 30,
            'total_fat_g' => 10,
        ]);

        $mealEntry = MealEntry::factory()->create([
            'daily_log_id' => $dailyLog->id,
            'meal_type' => MealType::Lunch,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $mealEntry->id,
            'food_name' => 'Today Lunch',
            'calories' => 300,
            'protein_g' => 20,
            'carbs_g' => 30,
            'fat_g' => 10,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/daily-summary');

        $response->assertOk()
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonPath('consumed.calories', 300)
            ->assertJsonPath('meals.1.meal_type', 'lunch')
            ->assertJsonPath('meals.1.items.0.food_name', 'Today Lunch');
    }

    public function test_daily_summary_returns_historical_day_data(): void
    {
        $user = User::factory()->create();
        $historicalDate = '2026-06-15';

        $dailyLog = DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => $historicalDate,
            'total_calories' => 450,
            'total_protein_g' => 35,
            'total_carbs_g' => 40,
            'total_fat_g' => 15,
        ]);

        $mealEntry = MealEntry::factory()->create([
            'daily_log_id' => $dailyLog->id,
            'meal_type' => MealType::Dinner,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $mealEntry->id,
            'food_name' => 'Historical Salmon',
            'calories' => 450,
            'protein_g' => 35,
            'carbs_g' => 40,
            'fat_g' => 15,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/daily-summary?date='.$historicalDate);

        $response->assertOk()
            ->assertJsonPath('date', $historicalDate)
            ->assertJsonPath('consumed.calories', 450)
            ->assertJsonPath('consumed.protein_g', 35)
            ->assertJsonPath('meals.2.meal_type', 'dinner')
            ->assertJsonPath('meals.2.items.0.food_name', 'Historical Salmon');
    }

    public function test_daily_summary_does_not_include_other_days(): void
    {
        $user = User::factory()->create();
        $requestedDate = '2026-06-15';
        $otherDate = '2026-06-16';

        $requestedLog = DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => $requestedDate,
            'total_calories' => 200,
            'total_protein_g' => 10,
            'total_carbs_g' => 20,
            'total_fat_g' => 5,
        ]);

        $otherLog = DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => $otherDate,
            'total_calories' => 800,
            'total_protein_g' => 50,
            'total_carbs_g' => 60,
            'total_fat_g' => 30,
        ]);

        $requestedMeal = MealEntry::factory()->create([
            'daily_log_id' => $requestedLog->id,
            'meal_type' => MealType::Breakfast,
        ]);

        $otherMeal = MealEntry::factory()->create([
            'daily_log_id' => $otherLog->id,
            'meal_type' => MealType::Breakfast,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $requestedMeal->id,
            'food_name' => 'Requested Day Food',
            'calories' => 200,
            'protein_g' => 10,
            'carbs_g' => 20,
            'fat_g' => 5,
        ]);

        FoodLogItem::factory()->create([
            'meal_entry_id' => $otherMeal->id,
            'food_name' => 'Other Day Food',
            'calories' => 800,
            'protein_g' => 50,
            'carbs_g' => 60,
            'fat_g' => 30,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/daily-summary?date='.$requestedDate);

        $response->assertOk()
            ->assertJsonPath('date', $requestedDate)
            ->assertJsonPath('consumed.calories', 200)
            ->assertJsonPath('meals.0.items.0.food_name', 'Requested Day Food');

        $items = collect($response->json('meals'))->flatMap(fn (array $meal): array => $meal['items']);
        $this->assertFalse($items->contains(fn (array $item): bool => $item['food_name'] === 'Other Day Food'));
    }

    public function test_daily_summary_returns_empty_data_for_day_with_no_log(): void
    {
        $user = User::factory()->create();
        $emptyDate = '2026-06-10';

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/daily-summary?date='.$emptyDate);

        $response->assertOk()
            ->assertJsonPath('date', $emptyDate)
            ->assertJsonPath('consumed.calories', 0)
            ->assertJsonPath('consumed.protein_g', 0)
            ->assertJsonPath('consumed.carbs_g', 0)
            ->assertJsonPath('consumed.fat_g', 0)
            ->assertJsonPath('meals.0.items', [])
            ->assertJsonPath('meals.1.items', [])
            ->assertJsonPath('meals.2.items', [])
            ->assertJsonPath('meals.3.items', []);
    }

    public function test_daily_summary_rejects_invalid_date_format(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/daily-summary?date=06-15-2026');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }
}
