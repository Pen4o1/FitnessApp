<?php

namespace App\Services;

use App\Enums\FoodExternalSource;
use App\Services\FatSecret\FatSecretClient;

class FoodService
{
    public function __construct(
        private readonly FatSecretClient $fatSecretClient,
    ) {}

    /**
     * @return list<array{
     *     external_food_id: string,
     *     external_source: string,
     *     food_name: string,
     *     brand_name: string|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     serving_unit: string,
     *     serving_description: string
     * }>
     */
    public function search(string $query, int $page = 0, int $maxResults = 20): array
    {
        $response = $this->fatSecretClient->searchFoods(trim($query), $page, $maxResults);
        $foods = data_get($response, 'foods_search.results.food', []);

        if (! is_array($foods)) {
            return [];
        }

        if (array_is_list($foods)) {
            return array_values(array_filter(array_map(
                fn (mixed $food): ?array => is_array($food) ? $this->normalizeFood($food) : null,
                $foods,
            )));
        }

        $normalized = $this->normalizeFood($foods);

        return $normalized !== null ? [$normalized] : [];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array{
     *     external_food_id: string,
     *     external_source: string,
     *     food_name: string,
     *     brand_name: string|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     serving_unit: string,
     *     serving_description: string
     * }|null
     */
    public function normalizeFood(array $food): ?array
    {
        $foodId = data_get($food, 'food_id');
        $foodName = data_get($food, 'food_name');

        if (! is_scalar($foodId) || ! is_string($foodName) || $foodName === '') {
            return null;
        }

        $macros = $this->extractPer100gMacros($food);

        if ($macros === null) {
            return null;
        }

        $brandName = data_get($food, 'brand_name');

        return [
            'external_food_id' => (string) $foodId,
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => $foodName,
            'brand_name' => is_string($brandName) && $brandName !== '' ? $brandName : null,
            'calories' => $macros['calories'],
            'protein_g' => $macros['protein_g'],
            'carbs_g' => $macros['carbs_g'],
            'fat_g' => $macros['fat_g'],
            'serving_unit' => 'g',
            'serving_description' => '100 g',
        ];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}|null
     */
    private function extractPer100gMacros(array $food): ?array
    {
        $servings = $this->normalizeServings(data_get($food, 'servings.serving'));

        if ($servings === []) {
            return null;
        }

        foreach ($servings as $serving) {
            if ($this->isExact100GramServing($serving)) {
                return $this->scaleMacros($serving, 1.0);
            }
        }

        foreach ($servings as $serving) {
            $description = data_get($serving, 'serving_description');

            if (is_string($description) && str_contains(strtolower($description), '100 g')) {
                return $this->scaleMacros($serving, 1.0);
            }
        }

        foreach ($servings as $serving) {
            if ($this->isDefaultServing($serving)) {
                $factor = $this->gramScaleFactor($serving);

                if ($factor !== null) {
                    return $this->scaleMacros($serving, $factor);
                }
            }
        }

        foreach ($servings as $serving) {
            $factor = $this->gramScaleFactor($serving);

            if ($factor !== null) {
                return $this->scaleMacros($serving, $factor);
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeServings(mixed $servings): array
    {
        if (! is_array($servings)) {
            return [];
        }

        if (array_is_list($servings)) {
            return array_values(array_filter($servings, 'is_array'));
        }

        return [$servings];
    }

    /**
     * @param  array<string, mixed>  $serving
     */
    private function isExact100GramServing(array $serving): bool
    {
        $unit = data_get($serving, 'metric_serving_unit');
        $amount = data_get($serving, 'metric_serving_amount');

        return $unit === 'g' && is_numeric($amount) && (float) $amount === 100.0;
    }

    /**
     * @param  array<string, mixed>  $serving
     */
    private function isDefaultServing(array $serving): bool
    {
        $isDefault = data_get($serving, 'is_default');

        return $isDefault === true || $isDefault === 'true' || $isDefault === 1 || $isDefault === '1';
    }

    /**
     * @param  array<string, mixed>  $serving
     */
    private function gramScaleFactor(array $serving): ?float
    {
        $unit = data_get($serving, 'metric_serving_unit');
        $amount = data_get($serving, 'metric_serving_amount');

        if ($unit !== 'g' || ! is_numeric($amount) || (float) $amount <= 0) {
            return null;
        }

        return 100.0 / (float) $amount;
    }

    /**
     * @param  array<string, mixed>  $serving
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}|null
     */
    private function scaleMacros(array $serving, float $factor): ?array
    {
        $calories = data_get($serving, 'calories');
        $protein = data_get($serving, 'protein');
        $carbs = data_get($serving, 'carbohydrate');
        $fat = data_get($serving, 'fat');

        if (! is_numeric($calories) || ! is_numeric($protein) || ! is_numeric($carbs) || ! is_numeric($fat)) {
            return null;
        }

        return [
            'calories' => (int) round((float) $calories * $factor),
            'protein_g' => round((float) $protein * $factor, 2),
            'carbs_g' => round((float) $carbs * $factor, 2),
            'fat_g' => round((float) $fat * $factor, 2),
        ];
    }
}
