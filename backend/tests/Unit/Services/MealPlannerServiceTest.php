<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserNutritionTarget;
use App\Services\FoodService;
use App\Services\MealPlannerService;
use App\Services\RecipeService;
use App\Services\UserPreferencesService;
use App\Support\DietaryFoodFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MealPlannerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_meal_slots_receive_dishes_when_generating_six_meals(): void
    {
        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'calorie_target' => 2400,
            'protein_target_g' => 150,
            'carbs_target_g' => 200,
            'fat_target_g' => 65,
            'is_active' => true,
        ]);

        $callIndex = 0;

        $recipeService = Mockery::mock(RecipeService::class);
        $recipeService->shouldReceive('search')
            ->andReturnUsing(function (): array {
                static $callIndex = 0;
                $callIndex++;
                $id = (string) $callIndex;

                return [
                    $this->makeCandidateRecipe($id, "Recipe {$id}"),
                ];
            });
        $recipeService->shouldReceive('get')
            ->andReturnUsing(function (string $recipeId): array {
                return [
                    'recipe_id' => $recipeId,
                    'recipe_name' => "Recipe {$recipeId}",
                    'description' => 'Test recipe.',
                    'image_url' => 'https://example.com/image.jpg',
                    'number_of_servings' => 4.0,
                    'grams_per_portion' => 200.0,
                    'prep_time_min' => 5,
                    'cooking_time_min' => 15,
                    'calories' => 200,
                    'protein_g' => 20.0,
                    'carbs_g' => 10.0,
                    'fat_g' => 8.0,
                    'ingredients' => ['Ingredient A'],
                    'recipe_types' => ['Main Dish'],
                    'directions' => [
                        ['number' => 1, 'text' => 'Cook it.'],
                    ],
                ];
            });

        $foodService = Mockery::mock(FoodService::class);
        $foodService->shouldNotReceive('search');

        $service = new MealPlannerService(
            $recipeService,
            $foodService,
            app(UserPreferencesService::class),
            app(DietaryFoodFilter::class),
        );

        $plan = $service->generateDailyPlan($user, 6);

        $this->assertNotNull($plan);
        $this->assertCount(6, $plan['meals']);

        foreach ($plan['meals'] as $meal) {
            $this->assertNotEmpty(
                $meal['dishes'],
                "Expected meal {$meal['meal_number']} to include dishes.",
            );
            $this->assertSame('recipe', $meal['dishes'][0]['kind']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCandidateRecipe(string $id, string $name): array
    {
        return [
            'recipe_id' => $id,
            'recipe_name' => $name,
            'description' => 'Test recipe.',
            'image_url' => 'https://example.com/image.jpg',
            'calories' => 200,
            'protein_g' => 20.0,
            'carbs_g' => 10.0,
            'fat_g' => 8.0,
            'ingredients' => ['Ingredient A'],
            'recipe_types' => ['Main Dish'],
        ];
    }
}
