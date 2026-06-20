<?php

namespace Tests\Unit\Services;

use App\Services\RecipeService;
use Tests\TestCase;

class RecipeServiceTest extends TestCase
{
  private RecipeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecipeService::class);
    }

    public function test_normalize_search_result_from_array_of_recipes(): void
    {
        $payload = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/recipes-search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $recipes = $payload['recipes']['recipe'];

        $normalized = $this->service->normalizeSearchResult($recipes[0]);

        $this->assertNotNull($normalized);
        $this->assertSame('91', $normalized['recipe_id']);
        $this->assertSame('Baked Lemon Snapper', $normalized['recipe_name']);
        $this->assertSame('Healthy fish with a tasty sauce.', $normalized['description']);
        $this->assertSame(177, $normalized['calories']);
        $this->assertSame(['Lemon', 'Snapper'], $normalized['ingredients']);
        $this->assertSame(['Main Dish'], $normalized['recipe_types']);
    }

    public function test_normalize_search_result_handles_single_recipe_object(): void
    {
        $recipe = [
            'recipe_id' => '50',
            'recipe_name' => 'Single Recipe',
            'recipe_description' => 'A test recipe.',
            'recipe_image' => 'https://example.com/image.jpg',
            'recipe_nutrition' => [
                'calories' => '200',
                'carbohydrate' => '10',
                'protein' => '20',
                'fat' => '5',
            ],
            'recipe_ingredients' => [
                'ingredient' => 'Egg',
            ],
            'recipe_types' => [
                'recipe_type' => 'Breakfast',
            ],
        ];

        $normalized = $this->service->normalizeSearchResult($recipe);

        $this->assertNotNull($normalized);
        $this->assertSame('50', $normalized['recipe_id']);
        $this->assertSame(['Egg'], $normalized['ingredients']);
        $this->assertSame(['Breakfast'], $normalized['recipe_types']);
    }

    public function test_normalize_detail_includes_directions_and_timing(): void
    {
        $payload = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/recipe-get-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $normalized = $this->service->normalizeDetail($payload['recipe']);

        $this->assertNotNull($normalized);
        $this->assertSame('91', $normalized['recipe_id']);
        $this->assertSame(194.23, $normalized['grams_per_portion']);
        $this->assertSame(5, $normalized['prep_time_min']);
        $this->assertSame(15, $normalized['cooking_time_min']);
        $this->assertCount(2, $normalized['directions']);
        $this->assertSame(1, $normalized['directions'][0]['number']);
        $this->assertStringContainsString('Preheat oven', $normalized['directions'][0]['text']);
        $this->assertSame(['1 1/2 lbs snapper fillets'], $normalized['ingredients']);
    }
}
