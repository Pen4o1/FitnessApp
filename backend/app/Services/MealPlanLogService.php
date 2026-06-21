<?php

namespace App\Services;

use App\Models\SavedMealPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

class MealPlanLogService
{
    public function __construct(
        private readonly DailyLogService $dailyLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $plan
     */
    public function logDishesToDiary(User $user, string $date, array $plan): void
    {
        $payloads = [];

        foreach ($plan['meals'] as $meal) {
            if (! is_array($meal)) {
                continue;
            }

            $mealType = (string) $meal['meal_type'];
            $dishes = $meal['dishes'] ?? [];

            if (! is_array($dishes)) {
                continue;
            }

            foreach ($dishes as $dish) {
                if (! is_array($dish)) {
                    continue;
                }

                $payloads[] = $this->dishToLogPayload($dish, $mealType, $date);
            }
        }

        $this->dailyLogService->logFoodBatch($user, $date, $payloads);
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    public function savePlan(User $user, string $date, array $plan, bool $logged = false): SavedMealPlan
    {
        $totals = $plan['totals'];

        return SavedMealPlan::query()->create([
            'user_id' => $user->id,
            'plan_date' => $date,
            'meals_count' => (int) $plan['meals_count'],
            'total_calories' => (int) $totals['calories'],
            'total_protein_g' => (float) $totals['protein_g'],
            'total_carbs_g' => (float) $totals['carbs_g'],
            'total_fat_g' => (float) $totals['fat_g'],
            'within_target' => (bool) $plan['within_target'],
            'plan_data' => $plan,
            'logged_to_diary_at' => $logged ? Carbon::now() : null,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SavedMealPlan>
     */
    public function listForUser(User $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return SavedMealPlan::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function findForUser(User $user, int $id): ?SavedMealPlan
    {
        return SavedMealPlan::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $dish
     * @return array{
     *     date: string,
     *     meal_type: string,
     *     quantity: float,
     *     serving_unit: string,
     *     serving_description: string,
     *     base_quantity: float,
     *     external_food_id: string,
     *     external_source: string,
     *     food_name: string,
     *     brand_name: string|null,
     *     calories_per_base: int,
     *     protein_g_per_base: float,
     *     carbs_g_per_base: float,
     *     fat_g_per_base: float
     * }
     */
    private function dishToLogPayload(array $dish, string $mealType, string $date): array
    {
        $perBase = $this->perBaseMacrosForDish($dish);

        return [
            'date' => $date,
            'meal_type' => $mealType,
            'quantity' => (float) $dish['quantity'],
            'serving_unit' => (string) $dish['serving_unit'],
            'serving_description' => (string) $dish['serving_description'],
            'base_quantity' => (float) $dish['base_quantity'],
            'external_food_id' => (string) $dish['external_food_id'],
            'external_source' => (string) $dish['external_source'],
            'food_name' => (string) $dish['food_name'],
            'brand_name' => isset($dish['brand_name']) && is_string($dish['brand_name']) ? $dish['brand_name'] : null,
            'calories_per_base' => $perBase['calories'],
            'protein_g_per_base' => $perBase['protein_g'],
            'carbs_g_per_base' => $perBase['carbs_g'],
            'fat_g_per_base' => $perBase['fat_g'],
        ];
    }

    /**
     * @param  array<string, mixed>  $dish
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}
     */
    private function perBaseMacrosForDish(array $dish): array
    {
        if (isset($dish['calories_per_base'])) {
            return [
                'calories' => (int) $dish['calories_per_base'],
                'protein_g' => (float) $dish['protein_g_per_base'],
                'carbs_g' => (float) $dish['carbs_g_per_base'],
                'fat_g' => (float) $dish['fat_g_per_base'],
            ];
        }

        $servings = $dish['servings'] ?? [];

        if (is_array($servings) && $servings !== []) {
            $serving = $this->pickServingForDish($dish, $servings);

            if ($serving !== null) {
                return [
                    'calories' => (int) $serving['calories'],
                    'protein_g' => (float) $serving['protein_g'],
                    'carbs_g' => (float) $serving['carbs_g'],
                    'fat_g' => (float) $serving['fat_g'],
                ];
            }
        }

        $baseQuantity = max((float) $dish['base_quantity'], 0.001);
        $quantity = max((float) $dish['quantity'], 0.001);
        $factor = $baseQuantity / $quantity;

        return [
            'calories' => (int) round((int) $dish['calories'] * $factor),
            'protein_g' => round((float) $dish['protein_g'] * $factor, 2),
            'carbs_g' => round((float) $dish['carbs_g'] * $factor, 2),
            'fat_g' => round((float) $dish['fat_g'] * $factor, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $dish
     * @param  list<array<string, mixed>>  $servings
     * @return array<string, mixed>|null
     */
    private function pickServingForDish(array $dish, array $servings): ?array
    {
        $description = (string) $dish['serving_description'];

        foreach ($servings as $serving) {
            if (! is_array($serving)) {
                continue;
            }

            if (($serving['description'] ?? '') === $description) {
                return $serving;
            }
        }

        foreach ($servings as $serving) {
            if (! is_array($serving)) {
                continue;
            }

            if (($serving['is_default'] ?? false) === true) {
                return $serving;
            }
        }

        return is_array($servings[0]) ? $servings[0] : null;
    }
}
