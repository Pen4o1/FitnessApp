<?php

namespace Tests\Feature\Api;

use App\Models\DailyLog;
use App\Models\FoodLogItem;
use App\Models\SavedMealPlan;
use App\Models\User;
use App\Models\UserNutritionTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedMealPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_log_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/meal-planner/log', [
            'date' => '2026-06-20',
            'plan' => $this->samplePlanPayload(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_save_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/meal-planner/save', [
            'date' => '2026-06-20',
            'plan' => $this->samplePlanPayload(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_save_meal_plan_to_profile_without_diary_entries(): void
    {
        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/meal-planner/save', [
            'date' => '2026-06-20',
            'plan' => $this->samplePlanPayload(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('plan_date', '2026-06-20')
            ->assertJsonPath('meals_count', 1)
            ->assertJsonPath('totals.calories', 500)
            ->assertJsonPath('logged_to_diary_at', null);

        $this->assertDatabaseHas('saved_meal_plans', [
            'user_id' => $user->id,
            'plan_date' => '2026-06-20',
            'total_calories' => 500,
        ]);

        $this->assertNull(
            DailyLog::query()->where('user_id', $user->id)->whereDate('log_date', '2026-06-20')->first(),
        );
        $this->assertSame(0, FoodLogItem::query()->count());
    }

    public function test_user_can_log_meal_plan_to_diary_without_saving_to_profile(): void
    {
        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/meal-planner/log', [
            'date' => '2026-06-20',
            'plan' => $this->samplePlanPayload(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Meal plan logged to your diary.');

        $this->assertDatabaseCount('saved_meal_plans', 0);

        $dailyLog = DailyLog::query()->where('user_id', $user->id)->whereDate('log_date', '2026-06-20')->first();
        $this->assertNotNull($dailyLog);
        $this->assertSame(500, $dailyLog->total_calories);

        $this->assertSame(1, FoodLogItem::query()->count());
    }

    public function test_user_can_list_saved_meal_plans(): void
    {
        $user = User::factory()->create();
        SavedMealPlan::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-plans');

        $response->assertOk();

        $items = $response->json('data') ?? $response->json();
        $this->assertCount(2, $items);
    }

    public function test_user_can_view_saved_meal_plan_detail(): void
    {
        $user = User::factory()->create();
        $savedPlan = SavedMealPlan::factory()->create([
            'user_id' => $user->id,
            'plan_data' => $this->samplePlanPayload(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/meal-plans/{$savedPlan->id}");

        $response->assertOk()
            ->assertJsonPath('id', $savedPlan->id)
            ->assertJsonPath('meals.0.title', 'Breakfast Bowl');
    }

    public function test_user_cannot_view_another_users_saved_meal_plan(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $savedPlan = SavedMealPlan::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($other);

        $response = $this->getJson("/api/meal-plans/{$savedPlan->id}");

        $response->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePlanPayload(): array
    {
        return [
            'date' => '2026-06-20',
            'meals_count' => 1,
            'targets' => [
                'calories' => 2000,
                'protein_g' => 150,
                'carbs_g' => 200,
                'fat_g' => 65,
            ],
            'totals' => [
                'calories' => 500,
                'protein_g' => 40,
                'carbs_g' => 50,
                'fat_g' => 15,
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
            'meals' => [
                [
                    'meal_number' => 1,
                    'meal_type' => 'breakfast',
                    'title' => 'Breakfast Bowl',
                    'target' => [
                        'calories' => 500,
                        'protein_g' => 40,
                        'carbs_g' => 50,
                        'fat_g' => 15,
                    ],
                    'totals' => [
                        'calories' => 500,
                        'protein_g' => 40,
                        'carbs_g' => 50,
                        'fat_g' => 15,
                    ],
                    'dishes' => [
                        [
                            'kind' => 'recipe',
                            'recipe_id' => '91',
                            'recipe_name' => 'Oatmeal Breakfast Bowl',
                            'description' => 'Warm oatmeal.',
                            'image_url' => 'https://example.com/image.jpg',
                            'portions' => 2,
                            'grams_per_portion' => null,
                            'prep_time_min' => 5,
                            'cooking_time_min' => 10,
                            'ingredients' => ['Oatmeal', 'Banana'],
                            'recipe_types' => ['Breakfast'],
                            'directions' => [
                                ['number' => 1, 'text' => 'Cook oatmeal.'],
                            ],
                            'external_food_id' => '91',
                            'external_source' => 'fatsecret',
                            'food_name' => 'Oatmeal Breakfast Bowl',
                            'brand_name' => null,
                            'quantity' => 2,
                            'serving_unit' => 'serving',
                            'serving_description' => '1 serving',
                            'base_quantity' => 1,
                            'calories_per_base' => 250,
                            'protein_g_per_base' => 20,
                            'carbs_g_per_base' => 25,
                            'fat_g_per_base' => 7.5,
                            'calories' => 500,
                            'protein_g' => 40,
                            'carbs_g' => 50,
                            'fat_g' => 15,
                        ],
                    ],
                ],
            ],
        ];
    }
}
