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
            'external_food_id' => '1641',
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => 'Chicken Breast',
            'brand_name' => null,
            'calories_per_100g' => 195,
            'protein_g_per_100g' => 29.55,
            'carbs_g_per_100g' => 0,
            'fat_g_per_100g' => 7.57,
        ]);

        $response->assertCreated()
            ->assertJsonPath('food_name', 'Chicken Breast')
            ->assertJsonPath('quantity', 150)
            ->assertJsonPath('calories', 293)
            ->assertJsonPath('protein_g', 44.33);

        $this->assertDatabaseHas('food_log_items', [
            'food_name' => 'Chicken Breast',
            'calories' => 293,
        ]);

        $dailyLog = DailyLog::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($dailyLog);
        $this->assertSame(293, $dailyLog->total_calories);
    }

    public function test_log_food_requires_authentication(): void
    {
        $response = $this->postJson('/api/foods/log', [
            'date' => now()->toDateString(),
            'meal_type' => MealType::Lunch->value,
            'quantity' => 100,
            'external_food_id' => '1641',
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => 'Chicken Breast',
            'calories_per_100g' => 195,
            'protein_g_per_100g' => 29.55,
            'carbs_g_per_100g' => 0,
            'fat_g_per_100g' => 7.57,
        ]);

        $response->assertUnauthorized();
    }
}
