<?php

namespace App\Services;

use App\Enums\DietaryPreference;
use App\Enums\MealType;
use App\Models\User;
use App\Support\DietaryFoodFilter;
use Illuminate\Support\Carbon;

class MealPlannerService
{
    private const MARGIN = 0.10;

    private const MAX_CANDIDATE_POOL = 40;

    private const SEARCH_RESULTS_PER_QUERY = 10;

    /**
     * @var array<string, list<string>>
     */
    private const MEAL_SEARCH_QUERIES = [
        MealType::Breakfast->value => ['oatmeal', 'eggs', 'yogurt'],
        MealType::Lunch->value => ['chicken salad', 'rice bowl', 'sandwich'],
        MealType::Dinner->value => ['salmon', 'chicken breast', 'stir fry'],
        MealType::Snack->value => ['almonds', 'apple', 'protein bar'],
    ];

    /**
     * @var array<string, float>
     */
    private const CALORIE_SPLIT_WITH_SNACK = [
        MealType::Breakfast->value => 0.25,
        MealType::Lunch->value => 0.35,
        MealType::Dinner->value => 0.30,
        MealType::Snack->value => 0.10,
    ];

    /**
     * @var array<string, float>
     */
    private const CALORIE_SPLIT_WITHOUT_SNACK = [
        MealType::Breakfast->value => 0.25,
        MealType::Lunch->value => 0.35,
        MealType::Dinner->value => 0.40,
    ];

    public function __construct(
        private readonly FoodService $foodService,
        private readonly UserPreferencesService $userPreferencesService,
        private readonly DietaryFoodFilter $dietaryFoodFilter,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     targets: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     within_target: bool,
     *     variance: array{calories_pct: float, protein_g_pct: float, carbs_g_pct: float, fat_g_pct: float},
     *     dietary_preferences: list<string>,
     *     allergies: list<string>,
     *     meals: list<array{
     *         meal_type: string,
     *         title: string,
     *         totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *         items: list<array<string, mixed>>
     *     }>
     * }|null
     */
    public function generateDailyPlan(User $user, bool $includeSnack = true): ?array
    {
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

        $mealTypes = $this->mealTypesForPlan($includeSnack);
        $calorieSplit = $includeSnack ? self::CALORIE_SPLIT_WITH_SNACK : self::CALORIE_SPLIT_WITHOUT_SNACK;

        $candidatesByMeal = $this->fetchCandidatesByMeal($mealTypes, $dietaryPreferences, $allergies);

        $meals = [];

        foreach ($mealTypes as $mealType) {
            $share = $calorieSplit[$mealType->value];
            $mealTargets = $this->scaleTargets($targets, $share);
            $candidates = $candidatesByMeal[$mealType->value] ?? [];

            $meals[] = $this->buildMeal($mealType, $mealTargets, $candidates);
        }

        $this->adjustLastMealForDailyMargin($meals, $targets, $includeSnack);

        $totals = $this->sumMealTotals($meals);
        $variance = $this->calculateVariance($totals, $targets);
        $withinTarget = $this->isWithinTarget($totals, $targets);

        return [
            'date' => Carbon::today()->toDateString(),
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
     * @return list<MealType>
     */
    private function mealTypesForPlan(bool $includeSnack): array
    {
        $types = [MealType::Breakfast, MealType::Lunch, MealType::Dinner];

        if ($includeSnack) {
            $types[] = MealType::Snack;
        }

        return $types;
    }

    /**
     * @param  list<MealType>  $mealTypes
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     * @return array<string, list<array<string, mixed>>>
     */
    private function fetchCandidatesByMeal(array $mealTypes, array $dietaryPreferences, array $allergies): array
    {
        $candidatesByMeal = [];
        $totalCandidates = 0;

        foreach ($mealTypes as $mealType) {
            $queries = self::MEAL_SEARCH_QUERIES[$mealType->value];
            $mealCandidates = [];
            $seenIds = [];

            foreach ($queries as $baseQuery) {
                if ($totalCandidates >= self::MAX_CANDIDATE_POOL) {
                    break;
                }

                $query = $this->buildSearchQuery($baseQuery, $dietaryPreferences);
                $results = $this->foodService->search($query, 0, self::SEARCH_RESULTS_PER_QUERY);

                foreach ($results as $food) {
                    if ($totalCandidates >= self::MAX_CANDIDATE_POOL) {
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
                    $totalCandidates++;
                }
            }

            $candidatesByMeal[$mealType->value] = $mealCandidates;
        }

        return $candidatesByMeal;
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
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $mealTargets
     * @param  list<array<string, mixed>>  $candidates
     * @return array{
     *     meal_type: string,
     *     title: string,
     *     totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     items: list<array<string, mixed>>
     * }
     */
    private function buildMeal(MealType $mealType, array $mealTargets, array $candidates): array
    {
        $food = $this->pickBestCandidate($candidates, $mealTargets);

        if ($food === null) {
            return $this->buildFallbackMeal($mealType, $mealTargets);
        }

        $item = $this->scaleFoodToTarget($food, $mealTargets['calories']);

        return [
            'meal_type' => $mealType->value,
            'title' => $food['food_name'].' '.$this->mealLabel($mealType),
            'totals' => [
                'calories' => $item['calories'],
                'protein_g' => $item['protein_g'],
                'carbs_g' => $item['carbs_g'],
                'fat_g' => $item['fat_g'],
            ],
            'items' => [$item],
        ];
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
     *     meal_type: string,
     *     title: string,
     *     totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     items: list<array<string, mixed>>
     * }
     */
    private function buildFallbackMeal(MealType $mealType, array $mealTargets): array
    {
        $label = $this->mealLabel($mealType);

        return [
            'meal_type' => $mealType->value,
            'title' => $label,
            'totals' => [
                'calories' => 0,
                'protein_g' => 0.0,
                'carbs_g' => 0.0,
                'fat_g' => 0.0,
            ],
            'items' => [],
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
     * @param  list<array{meal_type: string, title: string, totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, items: list<array<string, mixed>>}>  $meals
     * @param  array{calories: int, protein_g: float, carbs_g: float, fat_g: float}  $targets
     */
    private function adjustLastMealForDailyMargin(array &$meals, array $targets, bool $includeSnack): void
    {
        if ($meals === []) {
            return;
        }

        $lastIndex = count($meals) - 1;
        $lastMeal = &$meals[$lastIndex];

        if ($lastMeal['items'] === []) {
            return;
        }

        $totals = $this->sumMealTotals($meals);

        if ($this->isWithinTarget($totals, $targets)) {
            return;
        }

        $otherCalories = $totals['calories'] - $lastMeal['totals']['calories'];
        $desiredLastMealCalories = $targets['calories'] - $otherCalories;

        if ($desiredLastMealCalories <= 0) {
            return;
        }

        $item = &$lastMeal['items'][0];
        $baseQuantity = (float) $item['base_quantity'];
        $currentCalories = (int) $item['calories'];

        if ($currentCalories <= 0) {
            return;
        }

        $scaleFactor = $desiredLastMealCalories / $currentCalories;
        $item['quantity'] = round((float) $item['quantity'] * $scaleFactor, 1);
        $item['calories'] = (int) round($currentCalories * $scaleFactor);
        $item['protein_g'] = round((float) $item['protein_g'] * $scaleFactor, 2);
        $item['carbs_g'] = round((float) $item['carbs_g'] * $scaleFactor, 2);
        $item['fat_g'] = round((float) $item['fat_g'] * $scaleFactor, 2);

        $lastMeal['totals'] = [
            'calories' => $item['calories'],
            'protein_g' => $item['protein_g'],
            'carbs_g' => $item['carbs_g'],
            'fat_g' => $item['fat_g'],
        ];
    }

    /**
     * @param  list<array{meal_type: string, title: string, totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}, items: list<array<string, mixed>>}>  $meals
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

    private function mealLabel(MealType $mealType): string
    {
        return match ($mealType) {
            MealType::Breakfast => 'Breakfast',
            MealType::Lunch => 'Lunch',
            MealType::Dinner => 'Dinner',
            MealType::Snack => 'Snack',
            MealType::Other => 'Meal',
        };
    }
}
