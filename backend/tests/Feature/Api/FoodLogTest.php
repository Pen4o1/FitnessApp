<?php

namespace Tests\Feature\Api;

use App\Enums\FoodExternalSource;
use App\Enums\MealType;
use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FoodLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_log_food(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/foods/log', [
            'date' => now()->toDateString(),
            'meal_type' => MealType::Lunch->value,
            'quantity' => 150,
            'serving_unit' => 'g',
            'serving_description' => '100 g',
            'base_quantity' => 100,
            'external_food_id' => '1641',
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => 'Chicken Breast',
            'brand_name' => null,
            'calories_per_base' => 195,
            'protein_g_per_base' => 29.55,
            'carbs_g_per_base' => 0,
            'fat_g_per_base' => 7.57,
        ]);

        $response->assertCreated()
            ->assertJsonPath('item.food_name', 'Chicken Breast')
            ->assertJsonPath('item.quantity', 150)
            ->assertJsonPath('item.calories', 293)
            ->assertJsonPath('item.protein_g', 44.33)
            ->assertJsonPath('consumed.calories', 293);

        $this->assertDatabaseHas('food_log_items', [
            'food_name' => 'Chicken Breast',
            'calories' => 293,
        ]);

        $dailyLog = DailyLog::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($dailyLog);
        $this->assertSame(293, $dailyLog->total_calories);
    }

    public function test_authenticated_user_can_log_count_based_food(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/foods/log', [
            'date' => now()->toDateString(),
            'meal_type' => MealType::Breakfast->value,
            'quantity' => 2,
            'serving_unit' => 'large',
            'serving_description' => '1 large',
            'base_quantity' => 1,
            'external_food_id' => '3442',
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => 'Egg',
            'brand_name' => null,
            'calories_per_base' => 72,
            'protein_g_per_base' => 6.29,
            'carbs_g_per_base' => 0.36,
            'fat_g_per_base' => 4.75,
        ]);

        $response->assertCreated()
            ->assertJsonPath('item.food_name', 'Egg')
            ->assertJsonPath('item.quantity', 2)
            ->assertJsonPath('item.serving_unit', 'large')
            ->assertJsonPath('item.serving_description', '1 large')
            ->assertJsonPath('item.calories', 144)
            ->assertJsonPath('item.protein_g', 12.58);
    }

    public function test_log_food_requires_authentication(): void
    {
        $response = $this->postJson('/api/foods/log', [
            'date' => now()->toDateString(),
            'meal_type' => MealType::Lunch->value,
            'quantity' => 100,
            'serving_unit' => 'g',
            'serving_description' => '100 g',
            'base_quantity' => 100,
            'external_food_id' => '1641',
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => 'Chicken Breast',
            'calories_per_base' => 195,
            'protein_g_per_base' => 29.55,
            'carbs_g_per_base' => 0,
            'fat_g_per_base' => 7.57,
        ]);

        $response->assertUnauthorized();
    }
}
