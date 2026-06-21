<?php

namespace App\Services;

use App\Enums\FoodExternalSource;
use App\Enums\MealType;
use App\Models\DailyLog;
use App\Models\FoodLogItem;
use App\Models\MealEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

class DailyLogService
{
    /**
     * @param  array{
     *     date: string,
     *     meal_type: string,
     *     quantity: float|int|string,
     *     serving_unit: string,
     *     serving_description: string,
     *     base_quantity: float|int|string,
     *     external_food_id: string,
     *     external_source: string,
     *     food_name: string,
     *     brand_name?: string|null,
     *     calories_per_base: int,
     *     protein_g_per_base: float|int|string,
     *     carbs_g_per_base: float|int|string,
     *     fat_g_per_base: float|int|string
     * }  $data
     */
    public function logFood(User $user, array $data): FoodLogItem
    {
        $dailyLog = $this->getOrCreateDailyLog($user, $data['date']);
        $mealEntry = $this->getOrCreateMealEntry($dailyLog, MealType::from($data['meal_type']));

        $quantity = (float) $data['quantity'];
        $baseQuantity = (float) $data['base_quantity'];
        $factor = $quantity / $baseQuantity;

        $foodLogItem = $mealEntry->foodLogItems()->create([
            'food_name' => $data['food_name'],
            'brand_name' => $data['brand_name'] ?? null,
            'external_food_id' => $data['external_food_id'],
            'external_source' => FoodExternalSource::from($data['external_source']),
            'quantity' => $quantity,
            'serving_unit' => $data['serving_unit'],
            'serving_description' => $data['serving_description'],
            'calories' => (int) round($data['calories_per_base'] * $factor),
            'protein_g' => round((float) $data['protein_g_per_base'] * $factor, 2),
            'carbs_g' => round((float) $data['carbs_g_per_base'] * $factor, 2),
            'fat_g' => round((float) $data['fat_g_per_base'] * $factor, 2),
            'source_metadata' => [
                'base_quantity' => $baseQuantity,
                'calories_per_base' => $data['calories_per_base'],
                'protein_g_per_base' => (float) $data['protein_g_per_base'],
                'carbs_g_per_base' => (float) $data['carbs_g_per_base'],
                'fat_g_per_base' => (float) $data['fat_g_per_base'],
            ],
        ]);

        $dailyLog->recalculateTotals();

        return $foodLogItem;
    }

    /**
     * @param  list<array{
     *     date: string,
     *     meal_type: string,
     *     quantity: float|int|string,
     *     serving_unit: string,
     *     serving_description: string,
     *     base_quantity: float|int|string,
     *     external_food_id: string,
     *     external_source: string,
     *     food_name: string,
     *     brand_name?: string|null,
     *     calories_per_base: int,
     *     protein_g_per_base: float|int|string,
     *     carbs_g_per_base: float|int|string,
     *     fat_g_per_base: float|int|string
     * }>  $items
     */
    public function logFoodBatch(User $user, string $date, array $items): void
    {
        if ($items === []) {
            return;
        }

        $dailyLog = $this->getOrCreateDailyLog($user, $date);

        foreach ($items as $data) {
            $mealEntry = $this->getOrCreateMealEntry($dailyLog, MealType::from($data['meal_type']));

            $quantity = (float) $data['quantity'];
            $baseQuantity = (float) $data['base_quantity'];
            $factor = $quantity / $baseQuantity;

            $mealEntry->foodLogItems()->create([
                'food_name' => $data['food_name'],
                'brand_name' => $data['brand_name'] ?? null,
                'external_food_id' => $data['external_food_id'],
                'external_source' => FoodExternalSource::from($data['external_source']),
                'quantity' => $quantity,
                'serving_unit' => $data['serving_unit'],
                'serving_description' => $data['serving_description'],
                'calories' => (int) round($data['calories_per_base'] * $factor),
                'protein_g' => round((float) $data['protein_g_per_base'] * $factor, 2),
                'carbs_g' => round((float) $data['carbs_g_per_base'] * $factor, 2),
                'fat_g' => round((float) $data['fat_g_per_base'] * $factor, 2),
                'source_metadata' => [
                    'base_quantity' => $baseQuantity,
                    'calories_per_base' => $data['calories_per_base'],
                    'protein_g_per_base' => (float) $data['protein_g_per_base'],
                    'carbs_g_per_base' => (float) $data['carbs_g_per_base'],
                    'fat_g_per_base' => (float) $data['fat_g_per_base'],
                ],
            ]);
        }

        $dailyLog->recalculateTotals();
    }

    /**
     * @return array{
     *     date: string,
     *     targets: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     consumed: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     remaining: array{calories: int, protein_g: float, carbs_g: float, fat_g: float},
     *     meals: list<array{
     *         id: int,
     *         meal_type: string,
     *         items: list<array<string, mixed>>,
     *         totals: array{calories: int, protein_g: float, carbs_g: float, fat_g: float}
     *     }>
     * }
     */
    public function getSummary(User $user, string $date): array
    {
        $user->loadMissing('activeNutritionTarget');

        $target = $user->activeNutritionTarget;
        $targets = [
            'calories' => $target?->calorie_target ?? 2000,
            'protein_g' => (float) ($target?->protein_target_g ?? 150),
            'carbs_g' => (float) ($target?->carbs_target_g ?? 200),
            'fat_g' => (float) ($target?->fat_target_g ?? 65),
        ];

        $dailyLog = DailyLog::query()
            ->where('user_id', $user->id)
            ->whereDate('log_date', $date)
            ->with(['mealEntries.foodLogItems'])
            ->first();

        $consumed = [
            'calories' => $dailyLog?->total_calories ?? 0,
            'protein_g' => (float) ($dailyLog?->total_protein_g ?? 0),
            'carbs_g' => (float) ($dailyLog?->total_carbs_g ?? 0),
            'fat_g' => (float) ($dailyLog?->total_fat_g ?? 0),
        ];

        $mealsByType = [];

        foreach ($dailyLog?->mealEntries ?? [] as $mealEntry) {
            $items = $mealEntry->foodLogItems->map(fn (FoodLogItem $item): array => [
                'id' => $item->id,
                'food_name' => $item->food_name,
                'brand_name' => $item->brand_name,
                'calories' => $item->calories,
                'protein_g' => (float) $item->protein_g,
                'carbs_g' => (float) $item->carbs_g,
                'fat_g' => (float) $item->fat_g,
                'quantity' => (float) $item->quantity,
                'serving_unit' => $item->serving_unit,
            ])->all();

            $totals = $this->computeItemTotals($items);

            $mealsByType[$mealEntry->meal_type->value] = [
                'id' => $mealEntry->id,
                'meal_type' => $mealEntry->meal_type->value,
                'items' => $items,
                'totals' => $totals,
            ];
        }

        $mealTypes = [
            MealType::Breakfast,
            MealType::Lunch,
            MealType::Dinner,
            MealType::Snack,
        ];

        $meals = array_map(
            fn (MealType $mealType): array => $mealsByType[$mealType->value] ?? [
                'id' => 0,
                'meal_type' => $mealType->value,
                'items' => [],
                'totals' => [
                    'calories' => 0,
                    'protein_g' => 0.0,
                    'carbs_g' => 0.0,
                    'fat_g' => 0.0,
                ],
            ],
            $mealTypes,
        );

        return [
            'date' => $date,
            'targets' => $targets,
            'consumed' => $consumed,
            'remaining' => [
                'calories' => max(0, $targets['calories'] - $consumed['calories']),
                'protein_g' => max(0, $targets['protein_g'] - $consumed['protein_g']),
                'carbs_g' => max(0, $targets['carbs_g'] - $consumed['carbs_g']),
                'fat_g' => max(0, $targets['fat_g'] - $consumed['fat_g']),
            ],
            'meals' => $meals,
        ];
    }

    private function getOrCreateDailyLog(User $user, string $date): DailyLog
    {
        return DailyLog::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'log_date' => $date,
            ],
            [
                'total_calories' => 0,
                'total_protein_g' => 0,
                'total_carbs_g' => 0,
                'total_fat_g' => 0,
            ],
        );
    }

    private function getOrCreateMealEntry(DailyLog $dailyLog, MealType $mealType): MealEntry
    {
        return MealEntry::query()->firstOrCreate(
            [
                'daily_log_id' => $dailyLog->id,
                'meal_type' => $mealType,
            ],
            [
                'name' => null,
                'logged_at' => Carbon::parse($dailyLog->log_date)->setTimeFromTimeString(
                    match ($mealType) {
                        MealType::Breakfast => '08:00:00',
                        MealType::Lunch => '13:00:00',
                        MealType::Dinner => '19:00:00',
                        MealType::Snack, MealType::Other => '16:00:00',
                    },
                ),
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{calories: int, protein_g: float, carbs_g: float, fat_g: float}
     */
    private function computeItemTotals(array $items): array
    {
        return array_reduce(
            $items,
            fn (array $totals, array $item): array => [
                'calories' => $totals['calories'] + (int) $item['calories'],
                'protein_g' => round($totals['protein_g'] + (float) $item['protein_g'], 2),
                'carbs_g' => round($totals['carbs_g'] + (float) $item['carbs_g'], 2),
                'fat_g' => round($totals['fat_g'] + (float) $item['fat_g'], 2),
            ],
            ['calories' => 0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0],
        );
    }
}
