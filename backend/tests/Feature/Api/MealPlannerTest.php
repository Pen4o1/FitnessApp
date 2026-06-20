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
                        'meal_number',
                        'meal_type',
                        'title',
                        'target' => ['calories', 'protein_g', 'carbs_g', 'fat_g'],
                        'totals' => ['calories', 'protein_g', 'carbs_g', 'fat_g'],
                        'dishes' => [
                            '*' => [
                                'kind',
                                'external_food_id',
                                'external_source',
                                'food_name',
                                'quantity',
                                'calories',
                                'protein_g',
                                'carbs_g',
                                'fat_g',
                            ],
                        ],
                    ],
                ],
            ]);

        $firstDish = $response->json('meals.0.dishes.0');
        $this->assertSame('recipe', $firstDish['kind']);
        $this->assertArrayHasKey('recipe_id', $firstDish);
        $this->assertArrayHasKey('portions', $firstDish);
        $this->assertArrayHasKey('image_url', $firstDish);
        $this->assertArrayHasKey('directions', $firstDish);

        $targetCalories = $response->json('targets.calories');
        $totalCalories = $response->json('totals.calories');
        $margin = $targetCalories * 0.10;

        $this->assertLessThanOrEqual($margin, abs($totalCalories - $targetCalories));
    }

    public function test_generate_with_meals_count_returns_requested_meals(): void
    {
        $this->fakeFatSecretResponses();

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate?meals_count=5');

        $response->assertOk()
            ->assertJsonPath('meals_count', 5)
            ->assertJsonCount(5, 'meals')
            ->assertJsonPath('meals.0.meal_number', 1)
            ->assertJsonPath('meals.4.meal_number', 5);
    }

    public function test_generate_with_three_meals_count_returns_three_meals(): void
    {
        $this->fakeFatSecretResponses();

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate?meals_count=3');

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
            'platform.fatsecret.com/rest/recipes/search/v3*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate');

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Recipe search is temporarily unavailable.');
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
            ->flatMap(fn (array $meal): array => collect($meal['dishes'])->pluck('food_name')->all())
            ->all();

        foreach ($foodNames as $foodName) {
            $this->assertFalse(
                str_contains(strtolower($foodName), 'peanut'),
                "Expected nut-free plan to exclude peanut foods, got: {$foodName}",
            );
        }
    }

    public function test_generate_rejects_invalid_meals_count(): void
    {
        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate?meals_count=7');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['meals_count']);
    }

    public function test_each_meal_includes_per_meal_target_calories(): void
    {
        $this->fakeFatSecretResponses();

        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'calorie_target' => 2400,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meal-planner/generate?meals_count=4');

        $response->assertOk()
            ->assertJsonPath('meals.0.target.calories', 600);
    }

    private function fakeFatSecretResponses(): void
    {
        $foodSearchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $recipeSearchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/recipes-search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $recipeGetResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/recipe-get-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/recipes/search/v3*' => Http::response($recipeSearchResponse, 200),
            'platform.fatsecret.com/rest/recipe/v2*' => Http::response($recipeGetResponse, 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response($foodSearchResponse, 200),
        ]);
    }

    private function fakeFatSecretResponsesWithPeanutButter(): void
    {
        $foodSearchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $foodSearchResponse['foods_search']['results']['food'][] = [
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

        $recipeSearchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/recipes-search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $recipeSearchResponse['recipes']['recipe'][] = [
            'recipe_id' => '999',
            'recipe_name' => 'Peanut Butter Smoothie',
            'recipe_description' => 'Creamy peanut smoothie.',
            'recipe_image' => 'https://m.ftscrt.com/static/recipe/peanut-smoothie.jpg',
            'recipe_nutrition' => [
                'calories' => '400',
                'carbohydrate' => '30',
                'protein' => '20',
                'fat' => '22',
            ],
            'recipe_ingredients' => [
                'ingredient' => ['Peanut Butter', 'Milk'],
            ],
            'recipe_types' => [
                'recipe_type' => ['Snack'],
            ],
        ];

        $recipeGetResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/recipe-get-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/recipes/search/v3*' => Http::response($recipeSearchResponse, 200),
            'platform.fatsecret.com/rest/recipe/v2*' => Http::response($recipeGetResponse, 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response($foodSearchResponse, 200),
        ]);
    }
}
