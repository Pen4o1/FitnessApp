<?php

namespace App\Services;

use App\Enums\AllergyRestriction;
use App\Enums\FoodExternalSource;
use App\Models\User;
use App\Services\FatSecret\FatSecretClient;
use App\Support\DietaryFoodFilter;

class FoodService
{
    public function __construct(
        private readonly FatSecretClient $fatSecretClient,
        private readonly UserPreferencesService $userPreferencesService,
        private readonly DietaryFoodFilter $dietaryFoodFilter,
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
     *     serving_description: string,
     *     servings: list<array{
     *         id: string,
     *         description: string,
     *         unit: string,
     *         unit_label: string,
     *         base_quantity: float,
     *         default_quantity: float,
     *         calories: int,
     *         protein_g: float,
     *         carbs_g: float,
     *         fat_g: float,
     *         is_default: bool
     *     }>
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
     * @return array{
     *     barcode: string,
     *     external_food_id: string,
     *     external_source: string,
     *     food_name: string,
     *     brand_name: string|null,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     serving_unit: string,
     *     serving_description: string,
     *     servings: list<array{
     *         id: string,
     *         description: string,
     *         unit: string,
     *         unit_label: string,
     *         base_quantity: float,
     *         default_quantity: float,
     *         calories: int,
     *         protein_g: float,
     *         carbs_g: float,
     *         fat_g: float,
     *         is_default: bool
     *     }>,
     *     has_allergen: bool,
     *     has_dietary_conflict: bool
     * }|null
     */
    public function searchByBarcode(string $barcode, User $user): ?array
    {
        $gtin13 = $this->normalizeBarcodeToGtin13($barcode);
        $response = $this->fatSecretClient->findFoodByBarcode($gtin13);
        $food = data_get($response, 'food');

        if (! is_array($food)) {
            return null;
        }

        $normalized = $this->normalizeFood($food);

        if ($normalized === null) {
            return null;
        }

        $normalized['external_source'] = FoodExternalSource::Barcode->value;

        $preferences = $this->userPreferencesService->getPreferences($user);
        $foodLabel = $this->buildFoodLabel($normalized['food_name'], $normalized['brand_name']);

        $hasAllergen = $this->detectAllergenConflict(
            $food,
            $foodLabel,
            $preferences['allergies'],
        );

        $hasDietaryConflict = $this->dietaryFoodFilter->conflictsWithDietaryPreferences(
            $foodLabel,
            $preferences['dietary_preferences'],
        );

        return array_merge($normalized, [
            'barcode' => $gtin13,
            'has_allergen' => $hasAllergen,
            'has_dietary_conflict' => $hasDietaryConflict,
        ]);
    }

    private function normalizeBarcodeToGtin13(string $barcode): string
    {
        $digits = preg_replace('/\D/', '', $barcode) ?? '';

        return str_pad($digits, 13, '0', STR_PAD_LEFT);
    }

    private function buildFoodLabel(string $foodName, ?string $brandName): string
    {
        if ($brandName !== null && $brandName !== '') {
            return trim($brandName.' '.$foodName);
        }

        return $foodName;
    }

    /**
     * @param  array<string, mixed>  $food
     * @param  list<string>  $allergies
     */
    private function detectAllergenConflict(array $food, string $foodLabel, array $allergies): bool
    {
        if ($allergies === []) {
            return false;
        }

        if ($this->fatSecretAllergenConflict($food, $allergies)) {
            return true;
        }

        return $this->dietaryFoodFilter->conflictsWithAllergies($foodLabel, $allergies);
    }

    /**
     * @param  array<string, mixed>  $food
     * @param  list<string>  $allergies
     */
    private function fatSecretAllergenConflict(array $food, array $allergies): bool
    {
        $allergens = $this->normalizeAllergens(data_get($food, 'food_attributes.allergens.allergen'));

        if ($allergens === []) {
            return false;
        }

        $contained = [];

        foreach ($allergens as $allergen) {
            $name = data_get($allergen, 'name');
            $value = data_get($allergen, 'value');

            if (! is_string($name) || ($value !== '1' && $value !== 1)) {
                continue;
            }

            $contained[] = strtolower($name);
        }

        if ($contained === []) {
            return false;
        }

        foreach ($allergies as $allergy) {
            foreach ($this->fatSecretAllergenNamesFor($allergy) as $allergenName) {
                if (in_array(strtolower($allergenName), $contained, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function fatSecretAllergenNamesFor(string $allergy): array
    {
        return match ($allergy) {
            AllergyRestriction::GlutenFree->value => ['Gluten'],
            AllergyRestriction::NutFree->value => ['Nuts', 'Peanuts'],
            AllergyRestriction::DairyFree->value => ['Milk', 'Lactose'],
            AllergyRestriction::SoyFree->value => ['Soy'],
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeAllergens(mixed $allergens): array
    {
        if (! is_array($allergens)) {
            return [];
        }

        if (array_is_list($allergens)) {
            return array_values(array_filter($allergens, 'is_array'));
        }

        return [$allergens];
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
     *     serving_description: string,
     *     servings: list<array{
     *         id: string,
     *         description: string,
     *         unit: string,
     *         unit_label: string,
     *         base_quantity: float,
     *         default_quantity: float,
     *         calories: int,
     *         protein_g: float,
     *         carbs_g: float,
     *         fat_g: float,
     *         is_default: bool
     *     }>
     * }|null
     */
    public function normalizeFood(array $food): ?array
    {
        $foodId = data_get($food, 'food_id');
        $foodName = data_get($food, 'food_name');

        if (! is_scalar($foodId) || ! is_string($foodName) || $foodName === '') {
            return null;
        }

        $servings = $this->extractServingOptions($food);

        if ($servings === []) {
            return null;
        }

        $defaultServing = $this->pickDefaultServing($servings);
        $brandName = data_get($food, 'brand_name');

        return [
            'external_food_id' => (string) $foodId,
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => $foodName,
            'brand_name' => is_string($brandName) && $brandName !== '' ? $brandName : null,
            'calories' => $defaultServing['calories'],
            'protein_g' => $defaultServing['protein_g'],
            'carbs_g' => $defaultServing['carbs_g'],
            'fat_g' => $defaultServing['fat_g'],
            'serving_unit' => $defaultServing['unit_label'],
            'serving_description' => $defaultServing['description'],
            'servings' => $servings,
        ];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return list<array{
     *     id: string,
     *     description: string,
     *     unit: string,
     *     unit_label: string,
     *     base_quantity: float,
     *     default_quantity: float,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     is_default: bool
     * }>
     */
    private function extractServingOptions(array $food): array
    {
        $servings = $this->normalizeServings(data_get($food, 'servings.serving'));
        $options = [];
        $seenDescriptions = [];

        foreach ($servings as $serving) {
            $option = $this->normalizeServingOption($serving);

            if ($option === null) {
                continue;
            }

            $key = strtolower($option['description']);

            if (isset($seenDescriptions[$key])) {
                continue;
            }

            $seenDescriptions[$key] = true;
            $options[] = $option;
        }

        $has100g = false;

        foreach ($options as $option) {
            if ($option['unit'] === 'g' && $option['base_quantity'] === 100.0) {
                $has100g = true;
                break;
            }
        }

        if (! $has100g) {
            $per100g = $this->extractPer100gMacros($food);

            if ($per100g !== null) {
                $options[] = [
                    'id' => '100g',
                    'description' => '100 g',
                    'unit' => 'g',
                    'unit_label' => 'g',
                    'base_quantity' => 100.0,
                    'default_quantity' => 100.0,
                    'calories' => $per100g['calories'],
                    'protein_g' => $per100g['protein_g'],
                    'carbs_g' => $per100g['carbs_g'],
                    'fat_g' => $per100g['fat_g'],
                    'is_default' => false,
                ];
            }
        }

        usort($options, function (array $a, array $b): int {
            if ($a['is_default'] !== $b['is_default']) {
                return $b['is_default'] <=> $a['is_default'];
            }

            if ($a['unit'] !== $b['unit']) {
                return $a['unit'] === 'g' ? 1 : -1;
            }

            return 0;
        });

        return $options;
    }

    /**
     * @param  array<string, mixed>  $serving
     * @return array{
     *     id: string,
     *     description: string,
     *     unit: string,
     *     unit_label: string,
     *     base_quantity: float,
     *     default_quantity: float,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     is_default: bool
     * }|null
     */
    private function normalizeServingOption(array $serving): ?array
    {
        $description = data_get($serving, 'serving_description');

        if (! is_string($description) || $description === '') {
            return null;
        }

        $servingId = data_get($serving, 'serving_id');
        $isDefault = $this->isDefaultServing($serving);
        $unit = $this->detectServingUnit($serving, $description);

        if ($unit === 'g') {
            $factor = $this->gramScaleFactor($serving);

            if ($factor === null && ! $this->isExact100GramServing($serving)) {
                return null;
            }

            $macros = $this->scaleMacros($serving, $factor ?? 1.0);

            if ($macros === null) {
                return null;
            }

            return [
                'id' => is_scalar($servingId) ? (string) $servingId : md5($description),
                'description' => $description,
                'unit' => 'g',
                'unit_label' => 'g',
                'base_quantity' => 100.0,
                'default_quantity' => 100.0,
                'calories' => $macros['calories'],
                'protein_g' => $macros['protein_g'],
                'carbs_g' => $macros['carbs_g'],
                'fat_g' => $macros['fat_g'],
                'is_default' => $isDefault,
            ];
        }

        $macros = $this->scaleMacros($serving, 1.0);

        if ($macros === null) {
            return null;
        }

        return [
            'id' => is_scalar($servingId) ? (string) $servingId : md5($description),
            'description' => $description,
            'unit' => 'serving',
            'unit_label' => $this->extractUnitLabel($description),
            'base_quantity' => 1.0,
            'default_quantity' => 1.0,
            'calories' => $macros['calories'],
            'protein_g' => $macros['protein_g'],
            'carbs_g' => $macros['carbs_g'],
            'fat_g' => $macros['fat_g'],
            'is_default' => $isDefault,
        ];
    }

    /**
     * @param  list<array{
     *     id: string,
     *     description: string,
     *     unit: string,
     *     unit_label: string,
     *     base_quantity: float,
     *     default_quantity: float,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     is_default: bool
     * }>  $options
     * @return array{
     *     id: string,
     *     description: string,
     *     unit: string,
     *     unit_label: string,
     *     base_quantity: float,
     *     default_quantity: float,
     *     calories: int,
     *     protein_g: float,
     *     carbs_g: float,
     *     fat_g: float,
     *     is_default: bool
     * }
     */
    private function pickDefaultServing(array $options): array
    {
        foreach ($options as $option) {
            if ($option['is_default']) {
                return $option;
            }
        }

        foreach ($options as $option) {
            if ($option['unit'] === 'serving') {
                return $option;
            }
        }

        return $options[0];
    }

    /**
     * @param  array<string, mixed>  $serving
     */
    private function detectServingUnit(array $serving, string $description): string
    {
        $lower = strtolower($description);

        if ($this->isExact100GramServing($serving) || str_contains($lower, '100 g')) {
            return 'g';
        }

        if (preg_match('/\d+(?:\.\d+)?\s*g\b/', $lower) === 1) {
            return 'g';
        }

        return 'serving';
    }

    private function extractUnitLabel(string $description): string
    {
        if (preg_match('/^\d+(?:\.\d+)?(?:\/\d+)?\s+(.+)/i', $description, $matches) === 1) {
            $label = trim($matches[1]);

            return $label !== '' ? $label : 'serving';
        }

        return 'serving';
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
