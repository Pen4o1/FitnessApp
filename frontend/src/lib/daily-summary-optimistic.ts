import type { LogFoodPayload } from '@/lib/api';
import {
  buildMealsFromSummary,
  computeRemaining,
  computeTotals,
  mealWithItems,
  scaleMacrosFromBase,
  type DailySummary,
  type FoodLogItem,
  type MacroTotals,
  type MealType,
} from '@/types/nutrition';

export const OPTIMISTIC_FOOD_LOG_ID = -1;

export function createOptimisticFoodLogItem(
  payload: LogFoodPayload,
  tempId: number = OPTIMISTIC_FOOD_LOG_ID,
): FoodLogItem {
  const macros = scaleMacrosFromBase(
    payload.calories_per_base,
    payload.protein_g_per_base,
    payload.carbs_g_per_base,
    payload.fat_g_per_base,
    payload.base_quantity,
    payload.quantity,
  );

  return {
    id: tempId,
    food_name: payload.food_name,
    brand_name: payload.brand_name,
    calories: macros.calories,
    protein_g: macros.protein_g,
    carbs_g: macros.carbs_g,
    fat_g: macros.fat_g,
    quantity: payload.quantity,
    serving_unit: payload.serving_unit,
  };
}

export function applyOptimisticFoodLog(
  summary: DailySummary,
  item: FoodLogItem,
  mealType: MealType,
): DailySummary {
  const meals = buildMealsFromSummary(summary.meals).map((meal) => {
    if (meal.meal_type !== mealType) {
      return meal;
    }

    const items = [...meal.items, item];

    return mealWithItems(meal.id, meal.meal_type, items);
  });

  const consumed = meals.reduce<MacroTotals>(
    (totals, meal) => ({
      calories: totals.calories + meal.totals.calories,
      protein_g: totals.protein_g + meal.totals.protein_g,
      carbs_g: totals.carbs_g + meal.totals.carbs_g,
      fat_g: totals.fat_g + meal.totals.fat_g,
    }),
    { calories: 0, protein_g: 0, carbs_g: 0, fat_g: 0 },
  );

  return {
    ...summary,
    consumed,
    remaining: computeRemaining(summary.targets, consumed),
    meals,
  };
}

export function replaceOptimisticFoodLogItem(
  summary: DailySummary,
  tempId: number,
  item: FoodLogItem,
  mealType: MealType,
): DailySummary {
  const meals = buildMealsFromSummary(summary.meals).map((meal) => {
    if (meal.meal_type !== mealType) {
      return meal;
    }

    const items = meal.items.map((existing) => (existing.id === tempId ? item : existing));

    return mealWithItems(meal.id, meal.meal_type, items);
  });

  return {
    ...summary,
    meals,
  };
}

export function patchDailySummaryFromLogResponse(
  summary: DailySummary,
  tempId: number,
  item: FoodLogItem,
  mealType: MealType,
  consumed: MacroTotals,
  remaining: MacroTotals,
): DailySummary {
  const meals = buildMealsFromSummary(summary.meals).map((meal) => {
    if (meal.meal_type !== mealType) {
      return meal;
    }

    const items = meal.items.map((existing) => (existing.id === tempId ? item : existing));

    return mealWithItems(meal.id, meal.meal_type, items);
  });

  return {
    ...summary,
    consumed,
    remaining,
    meals,
  };
}

export { computeTotals };
