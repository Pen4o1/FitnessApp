<?php

namespace App\Services;

use App\Enums\FoodExternalSource;
use App\Services\FatSecret\FatSecretClient;

class RecipeService
{
    public function __construct(
        private readonly FatSecretClient $fatSecretClient,
    ) {}

    /**
     * @param  list<string>  $recipeTypes
     * @return list<array{
     *     recipe_id: string,
     *     recipe_name: string,
     *     description: string|null,
     *     image_url: string|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     ingredients: list<string>,
     *     recipe_types: list<string>
     * }>
     */
    public function search(
        string $query,
        int $page = 0,
        int $maxResults = 20,
        ?int $caloriesFrom = null,
        ?int $caloriesTo = null,
        array $recipeTypes = [],
    ): array {
        $filters = [];

        if ($caloriesFrom !== null) {
            $filters['calories.from'] = $caloriesFrom;
        }

        if ($caloriesTo !== null) {
            $filters['calories.to'] = $caloriesTo;
        }

        if ($recipeTypes !== []) {
            $filters['recipe_types'] = implode(',', $recipeTypes);
        }

        $response = $this->fatSecretClient->searchRecipes(trim($query), $page, $maxResults, $filters);
        $recipes = data_get($response, 'recipes.recipe', []);

        if (! is_array($recipes)) {
            return [];
        }

        if (array_is_list($recipes)) {
            return array_values(array_filter(array_map(
                fn (mixed $recipe): ?array => is_array($recipe) ? $this->normalizeSearchResult($recipe) : null,
                $recipes,
            )));
        }

        $normalized = $this->normalizeSearchResult($recipes);

        return $normalized !== null ? [$normalized] : [];
    }

    /**
     * @return array{
     *     recipe_id: string,
     *     recipe_name: string,
     *     description: string|null,
     *     image_url: string|null,
     *     number_of_servings: float|null,
     *     grams_per_portion: float|null,
     *     prep_time_min: int|null,
     *     cooking_time_min: int|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     ingredients: list<string>,
     *     recipe_types: list<string>,
     *     directions: list<array{number: int, text: string}>
     * }|null
     */
    public function get(string $recipeId): ?array
    {
        $response = $this->fatSecretClient->getRecipe($recipeId);
        $recipe = data_get($response, 'recipe');

        if (! is_array($recipe)) {
            return null;
        }

        return $this->normalizeDetail($recipe);
    }

    /**
     * @param  list<string>  $recipeIds
     * @return array<string, array{
     *     recipe_id: string,
     *     recipe_name: string,
     *     description: string|null,
     *     image_url: string|null,
     *     number_of_servings: float|null,
     *     grams_per_portion: float|null,
     *     prep_time_min: int|null,
     *     cooking_time_min: int|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     ingredients: list<string>,
     *     recipe_types: list<string>,
     *     directions: list<array{number: int, text: string}>
     * }>
     */
    public function getMany(array $recipeIds): array
    {
        $responses = $this->fatSecretClient->getRecipes($recipeIds);
        $details = [];

        foreach ($responses as $recipeId => $response) {
            $recipe = data_get($response, 'recipe');

            if (! is_array($recipe)) {
                continue;
            }

            $normalized = $this->normalizeDetail($recipe);

            if ($normalized !== null) {
                $details[$recipeId] = $normalized;
            }
        }

        return $details;
    }

    /**
     * @param  array<string, mixed>  $recipe
     * @return array{
     *     recipe_id: string,
     *     recipe_name: string,
     *     description: string|null,
     *     image_url: string|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     ingredients: list<string>,
     *     recipe_types: list<string>
     * }|null
     */
    public function normalizeSearchResult(array $recipe): ?array
    {
        $recipeId = data_get($recipe, 'recipe_id');
        $recipeName = data_get($recipe, 'recipe_name');

        if (! is_scalar($recipeId) || ! is_string($recipeName) || $recipeName === '') {
            return null;
        }

        $nutrition = data_get($recipe, 'recipe_nutrition');

        if (! is_array($nutrition)) {
            return null;
        }

        $macros = $this->extractMacros($nutrition);

        if ($macros === null) {
            return null;
        }

        return [
            'recipe_id' => (string) $recipeId,
            'recipe_name' => $recipeName,
            'description' => $this->nullableString(data_get($recipe, 'recipe_description')),
            'image_url' => $this->nullableString(data_get($recipe, 'recipe_image')),
            'calories' => $macros['calories'],
            'protein_g' => $macros['protein_g'],
            'carbs_g' => $macros['carbs_g'],
            'fat_g' => $macros['fat_g'],
            'ingredients' => $this->extractStringIngredients(data_get($recipe, 'recipe_ingredients.ingredient')),
            'recipe_types' => $this->extractRecipeTypes(data_get($recipe, 'recipe_types.recipe_type')),
        ];
    }

    /**
     * @param  array<string, mixed>  $recipe
     * @return array{
     *     recipe_id: string,
     *     recipe_name: string,
     *     description: string|null,
     *     image_url: string|null,
     *     number_of_servings: float|null,
     *     grams_per_portion: float|null,
     *     prep_time_min: int|null,
     *     cooking_time_min: int|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     ingredients: list<string>,
     *     recipe_types: list<string>,
     *     directions: list<array{number: int, text: string}>
     * }|null
     */
    public function normalizeDetail(array $recipe): ?array
    {
        $base = $this->normalizeSearchResult($recipe);

        if ($base === null) {
            $recipeId = data_get($recipe, 'recipe_id');
            $recipeName = data_get($recipe, 'recipe_name');

            if (! is_scalar($recipeId) || ! is_string($recipeName) || $recipeName === '') {
                return null;
            }

            $serving = $this->extractDefaultServing(data_get($recipe, 'serving_sizes.serving'));

            if ($serving === null) {
                return null;
            }

            $macros = $this->extractMacros($serving);

            if ($macros === null) {
                return null;
            }

            $base = [
                'recipe_id' => (string) $recipeId,
                'recipe_name' => $recipeName,
                'description' => $this->nullableString(data_get($recipe, 'recipe_description')),
                'image_url' => $this->extractImageUrl(data_get($recipe, 'recipe_images.recipe_image')),
                'calories' => $macros['calories'],
                'protein_g' => $macros['protein_g'],
                'carbs_g' => $macros['carbs_g'],
                'fat_g' => $macros['fat_g'],
                'ingredients' => $this->extractIngredientDescriptions(data_get($recipe, 'ingredients.ingredient')),
                'recipe_types' => $this->extractRecipeTypes(data_get($recipe, 'recipe_types.recipe_type')),
            ];
        } else {
            $detailImage = $this->extractImageUrl(data_get($recipe, 'recipe_images.recipe_image'));

            if ($detailImage !== null) {
                $base['image_url'] = $detailImage;
            }

            $detailIngredients = $this->extractIngredientDescriptions(data_get($recipe, 'ingredients.ingredient'));

            if ($detailIngredients !== []) {
                $base['ingredients'] = $detailIngredients;
            }
        }

        $serving = $this->extractDefaultServing(data_get($recipe, 'serving_sizes.serving'));

        if ($serving !== null) {
            $macros = $this->extractMacros($serving);

            if ($macros !== null) {
                $base['calories'] = $macros['calories'];
                $base['protein_g'] = $macros['protein_g'];
                $base['carbs_g'] = $macros['carbs_g'];
                $base['fat_g'] = $macros['fat_g'];
            }
        }

        return [
            ...$base,
            'number_of_servings' => $this->nullableFloat(data_get($recipe, 'number_of_servings')),
            'grams_per_portion' => $this->nullableFloat(data_get($recipe, 'grams_per_portion')),
            'prep_time_min' => $this->nullableInt(data_get($recipe, 'preparation_time_min')),
            'cooking_time_min' => $this->nullableInt(data_get($recipe, 'cooking_time_min')),
            'directions' => $this->extractDirections(data_get($recipe, 'directions.direction')),
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}|null
     */
    private function extractMacros(array $source): ?array
    {
        $calories = data_get($source, 'calories');
        $protein = data_get($source, 'protein');
        $carbs = data_get($source, 'carbohydrate');
        $fat = data_get($source, 'fat');

        if (! is_numeric($calories) || ! is_numeric($protein) || ! is_numeric($carbs) || ! is_numeric($fat)) {
            return null;
        }

        return [
            'calories' => (int) round((float) $calories),
            'protein_g' => round((float) $protein, 2),
            'carbs_g' => round((float) $carbs, 2),
            'fat_g' => round((float) $fat, 2),
        ];
    }

    /**
     * @return list<string>
     */
    private function extractStringIngredients(mixed $ingredients): array
    {
        if (! is_array($ingredients)) {
            return is_string($ingredients) && $ingredients !== '' ? [$ingredients] : [];
        }

        if (! array_is_list($ingredients)) {
            $ingredients = [$ingredients];
        }

        $result = [];

        foreach ($ingredients as $ingredient) {
            if (is_string($ingredient) && $ingredient !== '') {
                $result[] = $ingredient;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function extractIngredientDescriptions(mixed $ingredients): array
    {
        if (! is_array($ingredients)) {
            return [];
        }

        if (! array_is_list($ingredients)) {
            $ingredients = [$ingredients];
        }

        $result = [];

        foreach ($ingredients as $ingredient) {
            if (! is_array($ingredient)) {
                continue;
            }

            $description = data_get($ingredient, 'ingredient_description');

            if (is_string($description) && $description !== '') {
                $result[] = $description;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function extractRecipeTypes(mixed $types): array
    {
        if (! is_array($types)) {
            return is_string($types) && $types !== '' ? [$types] : [];
        }

        if (! array_is_list($types)) {
            $types = [$types];
        }

        $result = [];

        foreach ($types as $type) {
            if (is_string($type) && $type !== '') {
                $result[] = $type;
            }
        }

        return $result;
    }

    /**
     * @return list<array{number: int, text: string}>
     */
    private function extractDirections(mixed $directions): array
    {
        if (! is_array($directions)) {
            return [];
        }

        if (! array_is_list($directions)) {
            $directions = [$directions];
        }

        $result = [];

        foreach ($directions as $direction) {
            if (! is_array($direction)) {
                continue;
            }

            $number = data_get($direction, 'direction_number');
            $text = data_get($direction, 'direction_description');

            if (! is_numeric($number) || ! is_string($text) || $text === '') {
                continue;
            }

            $result[] = [
                'number' => (int) $number,
                'text' => $text,
            ];
        }

        usort($result, fn (array $a, array $b): int => $a['number'] <=> $b['number']);

        return $result;
    }

  /**
     * @return array<string, mixed>|null
     */
    private function extractDefaultServing(mixed $serving): ?array
    {
        if (! is_array($serving)) {
            return null;
        }

        if (array_is_list($serving)) {
            return $serving[0] ?? null;
        }

        return $serving;
    }

    private function extractImageUrl(mixed $images): ?string
    {
        if (is_string($images) && $images !== '') {
            return $images;
        }

        if (! is_array($images)) {
            return null;
        }

        if (array_is_list($images)) {
            foreach ($images as $image) {
                if (is_string($image) && $image !== '') {
                    return $image;
                }
            }

            return null;
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
