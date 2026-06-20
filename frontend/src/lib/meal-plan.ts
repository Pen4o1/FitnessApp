import type { LogFoodPayload } from '@/lib/api';
import type { FoodServingOption, MealType } from '@/types/nutrition';
import type { MealPlan, PlannedFoodItem } from '@/types/meal-plan';

function pickServingForItem(item: PlannedFoodItem): FoodServingOption {
  const byDescription = item.servings.find(
    (serving) => serving.description === item.serving_description,
  );

  if (byDescription) {
    return byDescription;
  }

  const defaultServing = item.servings.find((serving) => serving.is_default);

  if (defaultServing) {
    return defaultServing;
  }

  const servingUnit = item.servings.find((serving) => serving.unit === 'serving');

  if (servingUnit) {
    return servingUnit;
  }

  return item.servings[0];
}

export function plannedItemToLogPayload(
  item: PlannedFoodItem,
  mealType: MealType,
  date: string,
): LogFoodPayload {
  const serving = pickServingForItem(item);

  return {
    date,
    meal_type: mealType,
    quantity: item.quantity,
    serving_unit: item.serving_unit,
    serving_description: item.serving_description,
    base_quantity: item.base_quantity,
    external_food_id: item.external_food_id,
    external_source: item.external_source,
    food_name: item.food_name,
    brand_name: item.brand_name,
    calories_per_base: serving.calories,
    protein_g_per_base: serving.protein_g,
    carbs_g_per_base: serving.carbs_g,
    fat_g_per_base: serving.fat_g,
  };
}

export async function logMealPlanToDiary(
  plan: MealPlan,
  targetDate: string,
  logFood: (payload: LogFoodPayload) => Promise<unknown>,
): Promise<void> {
  for (const meal of plan.meals) {
    for (const dish of meal.dishes) {
      await logFood(plannedItemToLogPayload(dish, meal.meal_type, targetDate));
    }
  }
}
