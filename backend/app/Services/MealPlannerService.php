<?php

namespace App\Services;

use App\Enums\DietaryPreference;
use App\Enums\FoodExternalSource;
use App\Enums\MealType;
use App\Models\User;
use App\Support\DietaryFoodFilter;
use Illuminate\Support\Carbon;

class MealPlannerService
{
    private const MARGIN = 0.10;

    private const MAX_CANDIDATES_PER_SLOT = 12;

    private const SEARCH_RESULTS_PER_QUERY = 10;

    private const MAX_RECIPES_PER_MEAL = 2;

    private const MAX_FOOD_DISHES_PER_MEAL = 4;

    /**
     * @var list<string>
     */
    private const FALLBACK_SEARCH_QUERIES = [
        'chicken',
        'rice',
        'vegetables',
        'fruit',
    ];

    /**
     * @var list<string>
     */
    private const SEARCH_QUERY_POOL = [
        'oatmeal',
        'eggs',
        'yogurt',
        'chicken salad',
        'rice bowl',
        'sandwich',
        'salmon',
        'chicken breast',
        'stir fry',
        'almonds',
        'apple',
        'protein bar',
        'tofu',
        'quinoa',
        'avocado',
        'greek yogurt',
        'turkey',
        'broccoli',
    ];

    public function __construct(
        private readonly RecipeService $recipeService,
        private readonly FoodService $foodService,
        private readonly UserPreferencesService $userPreferencesService,
        private readonly DietaryFoodFilter $dietaryFoodFilter,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     meals_count: int,
     *     targets: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     within_target: bool,
     *     variance: array{calories_pct: float, protein_g_pct: float, carbs_g_pct: float, fat_g_pct: float},
     *     dietary_preferences: list<string>,
     *     allergies: list<string>,
     *     meals: list<array{
     *         meal_number: int,
     *         meal_type: string,
     *         title: string,
     *         target: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *         totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *         dishes: list<array<string, mixed>>
     *     }>
     * }|null
     */
    public function generateDailyPlan(User $user, int $mealsCount = 4): ?array
    {
        $mealsCount = max(2, min(6, $mealsCount));

        $user->loadMissing(['activeNutritionTarget', 'dietaryPreferences']);

        $target = $user->activeNutritionTarget;

        if ($target === null) {
            return null;
        }

        $targets = [
            'calories' => $target->calorie_target,
            'protein_g' => (float) $target->protein_target_g,
            'carbs_g' => (float) $target->carbs_target_g,
            'fat_g' => (float) $target->fat_target_g,
        ];

        $preferences = $this->userPreferencesService->getPreferences($user);
        $dietaryPreferences = $preferences['dietary_preferences'];
        $allergies = $preferences['allergies'];

        $mealSlots = $this->mealSlotsForCount($mealsCount);
        $share = 1.0 / $mealsCount;

        $meals = [];

        foreach ($mealSlots as $slot) {
            $mealTargets = $this->scaleTargets($targets, $share);
            $candidates = $this->fetchCandidatesForSlot(
                $slot,
                $mealTargets,
                $dietaryPreferences,
                $allergies,
            );

            $meals[] = $this->buildMeal(
                $slot['meal_type'],
                $slot['meal_number'],
                $mealTargets,
                $candidates,
            );
        }

        $this->adjustLastMealForDailyMargin($meals, $targets);
        $this->hydrateSelectedRecipes($meals);

        $totals = $this->sumMealTotals($meals);
        $variance = $this->calculateVariance($totals, $targets);
        $withinTarget = $this->isWithinTarget($totals, $targets);

        return [
            'date' => Carbon::today()->toDateString(),
            'meals_count' => $mealsCount,
            'targets' => $targets,
            'totals' => $totals,
            'within_target' => $withinTarget,
            'variance' => $variance,
            'dietary_preferences' => $dietaryPreferences,
            'allergies' => $allergies,
            'meals' => $meals,
        ];
    }

    /**
     * @return list<array{index: int, meal_number: int, meal_type: MealType}>
     */
    private function mealSlotsForCount(int $mealsCount): array
    {
        $mealTypes = $this->mealTypesForCount($mealsCount);
        $slots = [];

        foreach ($mealTypes as $index => $mealType) {
            $slots[] = [
                'index' => $index,
                'meal_number' => $index + 1,
                'meal_type' => $mealType,
            ];
        }

        return $slots;
    }

    /**
     * @return list<MealType>
     */
    private function mealTypesForCount(int $mealsCount): array
    {
        return match ($mealsCount) {
            2 => [MealType::Breakfast, MealType::Dinner],
            3 => [MealType::Breakfast, MealType::Lunch, MealType::Dinner],
            4 => [MealType::Breakfast, MealType::Lunch, MealType::Dinner, MealType::Snack],
            5 => [MealType::Breakfast, MealType::Lunch, MealType::Dinner, MealType::Snack, MealType::Other],
            default => [
                MealType::Breakfast,
                MealType::Lunch,
                MealType::Dinner,
                MealType::Snack,
                MealType::Other,
                MealType::Other,
            ],
        };
    }

    /**
     * @param  array{index: int, meal_number: int, meal_type: MealType}  $slot
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $mealTargets
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     * @return array{kind: string, items: list<array<string, mixed>>}
     */
    private function fetchCandidatesForSlot(
        array $slot,
        array $mealTargets,
        array $dietaryPreferences,
        array $allergies,
    ): array {
        $recipeCandidates = $this->searchRecipeCandidatesForSlot(
            $slot['index'],
            $slot['meal_type'],
            $mealTargets,
            $dietaryPreferences,
            $allergies,
            count(self::SEARCH_QUERY_POOL),
        );

        if ($recipeCandidates !== []) {
            return ['kind' => 'recipe', 'items' => $recipeCandidates];
        }

        $foodCandidates = $this->searchFoodCandidatesForSlot(
            $slot['index'],
            $dietaryPreferences,
            $allergies,
            count(self::SEARCH_QUERY_POOL),
        );

        if ($foodCandidates === []) {
            $foodCandidates = $this->searchFoodFallbackCandidates($dietaryPreferences, $allergies);
        }

        return ['kind' => 'food', 'items' => $foodCandidates];
    }

    /**
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $mealTargets
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     * @return list<array<string, mixed>>
     */
    private function searchRecipeCandidatesForSlot(
        int $slotIndex,
        MealType $mealType,
        array $mealTargets,
        array $dietaryPreferences,
        array $allergies,
        int $poolSize,
    ): array {
        $mealCandidates = [];
        $seenIds = [];
        $queriesPerSlot = 3;
        $caloriesFrom = max(1, (int) floor($mealTargets['calories'] * (1 - self::MARGIN)));
        $caloriesTo = (int) ceil($mealTargets['calories'] * (1 + self::MARGIN));
        $recipeTypes = $this->recipeTypesForMealType($mealType);

        for ($queryOffset = 0; $queryOffset < $queriesPerSlot; $queryOffset++) {
            if (count($mealCandidates) >= self::MAX_CANDIDATES_PER_SLOT) {
                break;
            }

            $poolIndex = ($slotIndex * $queriesPerSlot + $queryOffset) % $poolSize;
            $baseQuery = self::SEARCH_QUERY_POOL[$poolIndex];
            $query = $this->buildSearchQuery($baseQuery, $dietaryPreferences);
            $results = $this->recipeService->search(
                $query,
                0,
                self::SEARCH_RESULTS_PER_QUERY,
                $caloriesFrom,
                $caloriesTo,
                $recipeTypes,
            );

            foreach ($results as $recipe) {
                if (count($mealCandidates) >= self::MAX_CANDIDATES_PER_SLOT) {
                    break;
                }

                $recipeId = $recipe['recipe_id'];

                if (isset($seenIds[$recipeId])) {
                    continue;
                }

                if (! $this->dietaryFoodFilter->allowsRecipe(
                    $recipe['recipe_name'],
                    $recipe['ingredients'],
                    $dietaryPreferences,
                    $allergies,
                )) {
                    continue;
                }

                $seenIds[$recipeId] = true;
                $mealCandidates[] = $recipe;
            }
        }

        return $mealCandidates;
    }

    /**
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     * @return list<array<string, mixed>>
     */
    private function searchFoodCandidatesForSlot(
        int $slotIndex,
        array $dietaryPreferences,
        array $allergies,
        int $poolSize,
    ): array {
        $mealCandidates = [];
        $seenIds = [];
        $queriesPerSlot = 3;

        for ($queryOffset = 0; $queryOffset < $queriesPerSlot; $queryOffset++) {
            if (count($mealCandidates) >= self::MAX_CANDIDATES_PER_SLOT) {
                break;
            }

            $poolIndex = ($slotIndex * $queriesPerSlot + $queryOffset) % $poolSize;
            $baseQuery = self::SEARCH_QUERY_POOL[$poolIndex];
            $query = $this->buildSearchQuery($baseQuery, $dietaryPreferences);
            $results = $this->foodService->search($query, 0, self::SEARCH_RESULTS_PER_QUERY);

            foreach ($results as $food) {
                if (count($mealCandidates) >= self::MAX_CANDIDATES_PER_SLOT) {
                    break;
                }

                $foodId = $food['external_food_id'];

                if (isset($seenIds[$foodId])) {
                    continue;
                }

                if (! $this->dietaryFoodFilter->allows($food['food_name'], $dietaryPreferences, $allergies)) {
                    continue;
                }

                $seenIds[$foodId] = true;
                $mealCandidates[] = $food;
            }
        }

        return $mealCandidates;
    }

    /**
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     * @return list<array<string, mixed>>
     */
    private function searchFoodFallbackCandidates(array $dietaryPreferences, array $allergies): array
    {
        $mealCandidates = [];
        $seenIds = [];

        foreach (self::FALLBACK_SEARCH_QUERIES as $baseQuery) {
            if (count($mealCandidates) >= self::MAX_CANDIDATES_PER_SLOT) {
                break;
            }

            $query = $this->buildSearchQuery($baseQuery, $dietaryPreferences);
            $results = $this->foodService->search($query, 0, self::SEARCH_RESULTS_PER_QUERY);

            foreach ($results as $food) {
                if (count($mealCandidates) >= self::MAX_CANDIDATES_PER_SLOT) {
                    break;
                }

                $foodId = $food['external_food_id'];

                if (isset($seenIds[$foodId])) {
                    continue;
                }

                if (! $this->dietaryFoodFilter->allows($food['food_name'], $dietaryPreferences, $allergies)) {
                    continue;
                }

                $seenIds[$foodId] = true;
                $mealCandidates[] = $food;
            }
        }

        return $mealCandidates;
    }

    /**
     * @return list<string>
     */
    private function recipeTypesForMealType(MealType $mealType): array
    {
        return match ($mealType) {
            MealType::Breakfast => ['Breakfast'],
            MealType::Lunch => ['Lunch', 'Main Dish'],
            MealType::Dinner => ['Main Dish', 'Dinner'],
            MealType::Snack => ['Snack', 'Appetizer'],
            MealType::Other => [],
        };
    }

    /**
     * @param  list<string>  $dietaryPreferences
     */
    private function buildSearchQuery(string $baseQuery, array $dietaryPreferences): string
    {
        $hint = $this->dietHintForSearch($dietaryPreferences);

        if ($hint === null) {
            return $baseQuery;
        }

        return $hint.' '.$baseQuery;
    }

    /**
     * @param  list<string>  $dietaryPreferences
     */
    private function dietHintForSearch(array $dietaryPreferences): ?string
    {
        if (in_array(DietaryPreference::Vegan->value, $dietaryPreferences, true)) {
            return 'vegan';
        }

        if (in_array(DietaryPreference::Vegetarian->value, $dietaryPreferences, true)) {
            return 'vegetarian';
        }

        if (in_array(DietaryPreference::Keto->value, $dietaryPreferences, true)) {
            return 'keto';
        }

        if (in_array(DietaryPreference::Paleo->value, $dietaryPreferences, true)) {
            return 'paleo';
        }

        return null;
    }

    /**
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $targets
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}
     */
    private function scaleTargets(array $targets, float $share): array
    {
        return [
            'calories' => (int) round($targets['calories'] * $share),
            'protein_g' => round($targets['protein_g'] * $share, 2),
            'carbs_g' => round($targets['carbs_g'] * $share, 2),
            'fat_g' => round($targets['fat_g'] * $share, 2),
        ];
    }

    /**
     * @param  array{kind: string, items: list<array<string, mixed>>}  $candidates
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $mealTargets
     * @return array{
     *     meal_number: int,
     *     meal_type: string,
     *     title: string,
     *     target: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     dishes: list<array<string, mixed>>
     * }
     */
    private function buildMeal(MealType $mealType, int $mealNumber, array $mealTargets, array $candidates): array
    {
        $candidateItems = $candidates['items'] ?? [];
        $isRecipe = ($candidates['kind'] ?? 'food') === 'recipe';

        if ($candidateItems === []) {
            return $this->buildFallbackMeal($mealType, $mealNumber, $mealTargets);
        }

        $dishes = [];
        $usedIds = [];
        $maxDishes = $isRecipe
            ? min(self::MAX_RECIPES_PER_MEAL, count($candidateItems))
            : min(self::MAX_FOOD_DISHES_PER_MEAL, count($candidateItems));

        for ($attempt = 0; $attempt < $maxDishes; $attempt++) {
            $currentTotals = $this->sumDishTotals($dishes);

            if ($dishes !== [] && $this->withinMargin($currentTotals['calories'], $mealTargets['calories'])) {
                break;
            }

            $remainingCalories = $mealTargets['calories'] - $currentTotals['calories'];

            if ($remainingCalories <= 0 && $dishes !== []) {
                $this->scaleDishesToCalorieTarget($dishes, $mealTargets['calories']);
                break;
            }

            $pool = array_values(array_filter(
                $candidateItems,
                function (array $candidate): bool {
                    $id = $candidate['recipe_id'] ?? $candidate['external_food_id'] ?? '';

                    return $id !== '';
                },
            ));

            $pool = array_values(array_filter(
                $pool,
                fn (array $candidate): bool => ! in_array(
                    $candidate['recipe_id'] ?? $candidate['external_food_id'],
                    $usedIds,
                    true,
                ),
            ));

            $dishTargets = $this->scaleTargets(
                $mealTargets,
                max($remainingCalories, 1) / max($mealTargets['calories'], 1),
            );

            $selected = $this->pickBestCandidate($pool, $dishTargets);

            if ($selected === null) {
                break;
            }

            $candidateId = $selected['recipe_id'] ?? $selected['external_food_id'];
            $usedIds[] = $candidateId;

            if ($isRecipe) {
                $dishes[] = $this->scaleRecipeToTarget($selected, max(1, $remainingCalories));
            } else {
                $dishes[] = $this->scaleFoodToTarget($selected, max(1, $remainingCalories));
            }
        }

        if ($dishes === []) {
            return $this->buildFallbackMeal($mealType, $mealNumber, $mealTargets);
        }

        if (! $this->withinMargin($this->sumDishTotals($dishes)['calories'], $mealTargets['calories'])) {
            $this->scaleDishesToCalorieTarget($dishes, $mealTargets['calories']);
        }

        $totals = $this->sumDishTotals($dishes);
        $title = count($dishes) === 1
            ? $this->dishDisplayName($dishes[0])
            : 'Meal '.$mealNumber;

        return [
            'meal_number' => $mealNumber,
            'meal_type' => $mealType->value,
            'title' => $title,
            'target' => $mealTargets,
            'totals' => $totals,
            'dishes' => $dishes,
        ];
    }

    /**
     * @param  array<string, mixed>  $dish
     */
    private function dishDisplayName(array $dish): string
    {
        if (($dish['kind'] ?? '') === 'recipe') {
            return (string) ($dish['recipe_name'] ?? 'Meal');
        }

        return (string) ($dish['food_name'] ?? 'Meal');
    }

    /**
     * @param  list<array{meal_number: int, meal_type: string, title: string, target: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, dishes: list<array<string, mixed>>}>  $meals
     */
    private function hydrateSelectedRecipes(array &$meals): void
    {
        $recipeIds = [];

        foreach ($meals as $meal) {
            foreach ($meal['dishes'] as $dish) {
                if (($dish['kind'] ?? '') === 'recipe') {
                    $recipeIds[$dish['recipe_id']] = true;
                }
            }
        }

        if ($recipeIds === []) {
            return;
        }

        $details = $this->recipeService->getMany(array_keys($recipeIds));

        foreach ($meals as &$meal) {
            foreach ($meal['dishes'] as &$dish) {
                if (($dish['kind'] ?? '') !== 'recipe') {
                    continue;
                }

                $detail = $details[$dish['recipe_id']] ?? null;

                if ($detail === null) {
                    continue;
                }

                $dish['directions'] = $detail['directions'];

                if ($detail['grams_per_portion'] !== null) {
                    $dish['grams_per_portion'] = $detail['grams_per_portion'];
                }

                if ($detail['prep_time_min'] !== null) {
                    $dish['prep_time_min'] = $detail['prep_time_min'];
                }

                if ($detail['cooking_time_min'] !== null) {
                    $dish['cooking_time_min'] = $detail['cooking_time_min'];
                }

                if ($detail['image_url'] !== null) {
                    $dish['image_url'] = $detail['image_url'];
                }

                if ($detail['ingredients'] !== []) {
                    $dish['ingredients'] = $detail['ingredients'];
                }

                if ($detail['description'] !== null) {
                    $dish['description'] = $detail['description'];
                }
            }

            unset($dish);
        }

        unset($meal);
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $mealTargets
     * @return array<string, mixed>|null
     */
    private function pickBestCandidate(array $candidates, array $mealTargets): ?array
    {
        if ($candidates === []) {
            return null;
        }

        $best = null;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($candidates as $candidate) {
            $calories = max((int) $candidate['calories'], 1);
            $calorieDistance = abs($calories - $mealTargets['calories']) / max($mealTargets['calories'], 1);
            $macroDistance = (
                abs((float) $candidate['protein_g'] - $mealTargets['protein_g']) +
                abs((float) $candidate['carbs_g'] - $mealTargets['carbs_g']) +
                abs((float) $candidate['fat_g'] - $mealTargets['fat_g'])
            ) / max($mealTargets['protein_g'] + $mealTargets['carbs_g'] + $mealTargets['fat_g'], 1);

            $score = $calorieDistance + ($macroDistance * 0.25);

            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @return array{
     *     meal_number: int,
     *     meal_type: string,
     *     title: string,
     *     target: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     dishes: list<array<string, mixed>>
     * }
     */
    private function buildFallbackMeal(MealType $mealType, int $mealNumber, array $mealTargets): array
    {
        return [
            'meal_number' => $mealNumber,
            'meal_type' => $mealType->value,
            'title' => 'Meal '.$mealNumber,
            'target' => $mealTargets,
            'totals' => [
                'calories' => 0,
                'protein_g' => 0.0,
                'carbs_g' => 0.0,
                'fat_g' => 0.0,
            ],
            'dishes' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $dishes
     */
    private function scaleDishesToCalorieTarget(array &$dishes, int $targetCalories): void
    {
        $currentTotals = $this->sumDishTotals($dishes);

        if ($currentTotals['calories'] <= 0) {
            return;
        }

        $scaleFactor = $targetCalories / $currentTotals['calories'];

        foreach ($dishes as &$dish) {
            if (($dish['kind'] ?? '') === 'recipe') {
                $dish['portions'] = round((float) $dish['portions'] * $scaleFactor, 1);
            }

            $dish['quantity'] = round((float) $dish['quantity'] * $scaleFactor, 1);
            $dish['calories'] = (int) round((int) $dish['calories'] * $scaleFactor);
            $dish['protein_g'] = round((float) $dish['protein_g'] * $scaleFactor, 2);
            $dish['carbs_g'] = round((float) $dish['carbs_g'] * $scaleFactor, 2);
            $dish['fat_g'] = round((float) $dish['fat_g'] * $scaleFactor, 2);
        }

        unset($dish);
    }

    /**
     * @param  array<string, mixed>  $recipe
     * @return array<string, mixed>
     */
    private function scaleRecipeToTarget(array $recipe, int $targetCalories): array
    {
        $caloriesPerServing = max((int) $recipe['calories'], 1);
        $rawPortions = $targetCalories / $caloriesPerServing;
        $portions = max(0.5, round($rawPortions * 2) / 2);

        return [
            'kind' => 'recipe',
            'recipe_id' => $recipe['recipe_id'],
            'recipe_name' => $recipe['recipe_name'],
            'description' => $recipe['description'],
            'image_url' => $recipe['image_url'],
            'portions' => $portions,
            'grams_per_portion' => null,
            'prep_time_min' => null,
            'cooking_time_min' => null,
            'ingredients' => $recipe['ingredients'],
            'recipe_types' => $recipe['recipe_types'],
            'directions' => [],
            'external_food_id' => $recipe['recipe_id'],
            'external_source' => FoodExternalSource::Fatsecret->value,
            'food_name' => $recipe['recipe_name'],
            'brand_name' => null,
            'quantity' => $portions,
            'serving_unit' => 'serving',
            'serving_description' => '1 serving',
            'base_quantity' => 1.0,
            'calories_per_base' => $recipe['calories'],
            'protein_g_per_base' => $recipe['protein_g'],
            'carbs_g_per_base' => $recipe['carbs_g'],
            'fat_g_per_base' => $recipe['fat_g'],
            'calories' => (int) round($recipe['calories'] * $portions),
            'protein_g' => round((float) $recipe['protein_g'] * $portions, 2),
            'carbs_g' => round((float) $recipe['carbs_g'] * $portions, 2),
            'fat_g' => round((float) $recipe['fat_g'] * $portions, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array<string, mixed>
     */
    private function scaleFoodToTarget(array $food, int $targetCalories): array
    {
        $defaultServing = $this->pickDefaultServing($food);
        $baseQuantity = (float) $defaultServing['base_quantity'];
        $caloriesPerBase = max((int) $defaultServing['calories'], 1);
        $factor = $targetCalories / $caloriesPerBase;
        $quantity = round($baseQuantity * $factor, 1);

        return [
            'kind' => 'food',
            'external_food_id' => $food['external_food_id'],
            'external_source' => $food['external_source'],
            'food_name' => $food['food_name'],
            'brand_name' => $food['brand_name'],
            'quantity' => $quantity,
            'serving_unit' => $defaultServing['unit_label'],
            'serving_description' => $defaultServing['description'],
            'base_quantity' => $baseQuantity,
            'calories' => (int) round($caloriesPerBase * $factor),
            'protein_g' => round((float) $defaultServing['protein_g'] * $factor, 2),
            'carbs_g' => round((float) $defaultServing['carbs_g'] * $factor, 2),
            'fat_g' => round((float) $defaultServing['fat_g'] * $factor, 2),
            'servings' => $food['servings'],
        ];
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array<string, mixed>
     */
    private function pickDefaultServing(array $food): array
    {
        $servings = $food['servings'];

        foreach ($servings as $serving) {
            if ($serving['is_default']) {
                return $serving;
            }
        }

        return $servings[0];
    }

    /**
     * @param  list<array{meal_number: int, meal_type: string, title: string, target: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, dishes: list<array<string, mixed>>}>  $meals
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $targets
     */
    private function adjustLastMealForDailyMargin(array &$meals, array $targets): void
    {
        if ($meals === []) {
            return;
        }

        $lastIndex = count($meals) - 1;
        $lastMeal = &$meals[$lastIndex];

        if ($lastMeal['dishes'] === []) {
            return;
        }

        $totals = $this->sumMealTotals($meals);

        if ($this->isWithinTarget($totals, $targets)) {
            return;
        }

        $otherCalories = $totals['calories'] - $lastMeal['totals']['calories'];
        $desiredLastMealCalories = $targets['calories'] - $otherCalories;

        if ($desiredLastMealCalories <= 0 || $lastMeal['totals']['calories'] <= 0) {
            return;
        }

        $this->scaleDishesToCalorieTarget($lastMeal['dishes'], $desiredLastMealCalories);
        $lastMeal['totals'] = $this->sumDishTotals($lastMeal['dishes']);
    }

    /**
     * @param  list<array<string, mixed>>  $dishes
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}
     */
    private function sumDishTotals(array $dishes): array
    {
        $totals = [
            'calories' => 0,
            'protein_g' => 0.0,
            'carbs_g' => 0.0,
            'fat_g' => 0.0,
        ];

        foreach ($dishes as $dish) {
            $totals['calories'] += (int) $dish['calories'];
            $totals['protein_g'] += (float) $dish['protein_g'];
            $totals['carbs_g'] += (float) $dish['carbs_g'];
            $totals['fat_g'] += (float) $dish['fat_g'];
        }

        $totals['protein_g'] = round($totals['protein_g'], 2);
        $totals['carbs_g'] = round($totals['carbs_g'], 2);
        $totals['fat_g'] = round($totals['fat_g'], 2);

        return $totals;
    }

    /**
     * @param  list<array{meal_number: int, meal_type: string, title: string, target: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, dishes: list<array<string, mixed>>}>  $meals
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}
     */
    private function sumMealTotals(array $meals): array
    {
        $totals = [
            'calories' => 0,
            'protein_g' => 0.0,
            'carbs_g' => 0.0,
            'fat_g' => 0.0,
        ];

        foreach ($meals as $meal) {
            $totals['calories'] += $meal['totals']['calories'];
            $totals['protein_g'] += $meal['totals']['protein_g'];
            $totals['carbs_g'] += $meal['totals']['carbs_g'];
            $totals['fat_g'] += $meal['totals']['fat_g'];
        }

        $totals['protein_g'] = round($totals['protein_g'], 2);
        $totals['carbs_g'] = round($totals['carbs_g'], 2);
        $totals['fat_g'] = round($totals['fat_g'], 2);

        return $totals;
    }

    /**
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $totals
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $targets
     * @return array{calories_pct: float, protein_g_pct: float, carbs_g_pct: float, fat_g_pct: float}
     */
    private function calculateVariance(array $totals, array $targets): array
    {
        return [
            'calories_pct' => $this->percentVariance($totals['calories'], $targets['calories']),
            'protein_g_pct' => $this->percentVariance($totals['protein_g'], $targets['protein_g']),
            'carbs_g_pct' => $this->percentVariance($totals['carbs_g'], $targets['carbs_g']),
            'fat_g_pct' => $this->percentVariance($totals['fat_g'], $targets['fat_g']),
        ];
    }

    private function percentVariance(float|int $actual, float|int $target): float
    {
        if ($target === 0) {
            return 0.0;
        }

        return round((($actual - $target) / $target) * 100, 2);
    }

    /**
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $totals
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $targets
     */
    private function isWithinTarget(array $totals, array $targets): bool
    {
        return $this->withinMargin($totals['calories'], $targets['calories'])
            && $this->withinMargin($totals['protein_g'], $targets['protein_g'])
            && $this->withinMargin($totals['carbs_g'], $targets['carbs_g'])
            && $this->withinMargin($totals['fat_g'], $targets['fat_g']);
    }

    private function withinMargin(float $actual, float $target): bool
    {
        if ($target === 0.0) {
            return $actual === 0.0;
        }

        return abs($actual - $target) <= $target * self::MARGIN;
    }
}
