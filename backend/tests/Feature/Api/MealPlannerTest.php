<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserDietaryPreference;
use App\Models\UserNutritionTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MealPlannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fatsecret.client_id' => 'test-client-id',
            'services.fatsecret.client_secret' => 'test-client-secret',
            'services.fatsecret.scope' => 'premier',
            'services.fatsecret.region' => 'US',
        ]);

        Cache::flush();
    }

    public function test_unauthenticated_generate_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/meal-planner/generate');

        $response->assertUnauthorized();
    }

    public function test_generate_returns_unprocessable_when_user_has_no_nutrition_target(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/meal-planner/generate');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Complete your profile to set nutrition targets.');
    }

    public function test_authenticated_user_can_generate_daily_meal_plan(): void
    {
        $this->fakeFatSecretResponses();

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'calorie_target' => 2000,
            'protein_target_g' => 150,
            'carbs_target_g' => 200,
            'fat_target_g' => 65,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate');

        $response->assertOk()
            ->assertJsonCount(4, 'meals')
            ->assertJsonStructure([
                'date',
                'targets' => ['calories', 'protein_g', 'carbs_g', 'fat_g'],
                'totals' => ['calories', 'protein_g', 'carbs_g', 'fat_g'],
                'within_target',
                'variance' => ['calories_pct', 'protein_g_pct', 'carbs_g_pct', 'fat_g_pct'],
                'dietary_preferences',
                'allergies',
                'meals' => [
                    '*' => [
                        'meal_type',
                        'title',
                        'totals' => ['calories', 'protein_g', 'carbs_g', 'fat_g'],
                        'items' => [
                            '*' => [
                                'external_food_id',
                                'external_source',
                                'food_name',
                                'quantity',
                                'calories',
                                'protein_g',
                                'carbs_g',
                                'fat_g',
                                'servings',
                            ],
                        ],
                    ],
                ],
            ]);

        $targetCalories = $response->json('targets.calories');
        $totalCalories = $response->json('totals.calories');
        $margin = $targetCalories * 0.10;

        $this->assertLessThanOrEqual($margin, abs($totalCalories - $targetCalories));
    }

    public function test_generate_without_snack_returns_three_meals(): void
    {
        $this->fakeFatSecretResponses();

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate?include_snack=false');

        $response->assertOk()
            ->assertJsonCount(3, 'meals')
            ->assertJsonMissingPath('meals.3');
    }

    public function test_generate_returns_bad_gateway_when_fatsecret_fails(): void
    {
        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate');

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Food search is temporarily unavailable.');
    }

    public function test_generate_excludes_foods_matching_nut_free_allergy(): void
    {
        $this->fakeFatSecretResponsesWithPeanutButter();

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        UserDietaryPreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'dietary_preferences' => [],
                'allergies' => ['nut_free'],
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate');

        $response->assertOk();

        $foodNames = collect($response->json('meals'))
            ->flatMap(fn (array $meal): array => collect($meal['items'])->pluck('food_name')->all())
            ->all();

        foreach ($foodNames as $foodName) {
            $this->assertFalse(
                str_contains(strtolower($foodName), 'peanut'),
                "Expected nut-free plan to exclude peanut foods, got: {$foodName}",
            );
        }
    }

    private function fakeFatSecretResponses(): void
    {
        $searchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response($searchResponse, 200),
        ]);
    }

    private function fakeFatSecretResponsesWithPeanutButter(): void
    {
        $searchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $searchResponse['foods_search']['results']['food'][] = [
            'food_id' => '5555',
            'food_name' => 'Peanut Butter',
            'food_type' => 'Generic',
            'food_url' => 'https://foods.fatsecret.com/calories-nutrition/generic/peanut-butter',
            'servings' => [
                'serving' => [
                    'serving_id' => '90001',
                    'serving_description' => '100 g',
                    'metric_serving_amount' => '100.000',
                    'metric_serving_unit' => 'g',
                    'calories' => '588',
                    'carbohydrate' => '20',
                    'protein' => '25',
                    'fat' => '50',
                    'is_default' => 'true',
                ],
            ],
        ];

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response($searchResponse, 200),
        ]);
    }
}
