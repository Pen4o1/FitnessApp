import type { LogFoodPayload } from '@/lib/api';
import type { FoodServingOption, MealType } from '@/types/nutrition';
import type { MealPlan, PlannedDish, PlannedFoodItem, PlannedMeal, PlannedRecipeItem } from '@/types/meal-plan';
import { isPlannedFoodItem, isPlannedRecipeItem } from '@/types/meal-plan';

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
  item: PlannedDish,
  mealType: MealType,
  date: string,
): LogFoodPayload {
  if (isPlannedRecipeItem(item)) {
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
      calories_per_base: item.calories_per_base,
      protein_g_per_base: item.protein_g_per_base,
      carbs_g_per_base: item.carbs_g_per_base,
      fat_g_per_base: item.fat_g_per_base,
    };
  }

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

export function normalizeMealPlan(plan: MealPlan): MealPlan {
  return {
    ...plan,
    meals: plan.meals.map((meal) => ({
      ...meal,
      dishes: normalizeDishes(meal.dishes),
    })),
  };
}

function normalizeDishes(
  dishes: PlannedMeal['dishes'] | { data?: PlannedMeal['dishes'] },
): PlannedMeal['dishes'] {
  if (Array.isArray(dishes)) {
    return dishes.map(normalizeDish);
  }

  if (dishes && Array.isArray(dishes.data)) {
    return dishes.data.map(normalizeDish);
  }

  return [];
}

function normalizeDish(dish: PlannedDish | Record<string, unknown>): PlannedDish {
  if ('kind' in dish && dish.kind === 'recipe') {
    const recipe = dish as PlannedRecipeItem;

    return {
      ...recipe,
      ingredients: recipe.ingredients ?? [],
      directions: recipe.directions ?? [],
      recipe_types: recipe.recipe_types ?? [],
    };
  }

  if ('kind' in dish && dish.kind === 'food') {
    const food = dish as PlannedFoodItem;

    return {
      ...food,
      servings: food.servings ?? [],
    };
  }

  if ('recipe_id' in dish && typeof dish.recipe_id === 'string') {
    return {
      kind: 'recipe',
      recipe_id: dish.recipe_id,
      recipe_name: String(dish.recipe_name ?? ''),
      description: typeof dish.description === 'string' ? dish.description : null,
      image_url: typeof dish.image_url === 'string' ? dish.image_url : null,
      portions: Number(dish.portions ?? 1),
      grams_per_portion: typeof dish.grams_per_portion === 'number' ? dish.grams_per_portion : null,
      prep_time_min: typeof dish.prep_time_min === 'number' ? dish.prep_time_min : null,
      cooking_time_min: typeof dish.cooking_time_min === 'number' ? dish.cooking_time_min : null,
      ingredients: Array.isArray(dish.ingredients) ? dish.ingredients as string[] : [],
      recipe_types: Array.isArray(dish.recipe_types) ? dish.recipe_types as string[] : [],
      directions: Array.isArray(dish.directions) ? dish.directions as PlannedRecipeItem['directions'] : [],
      external_food_id: String(dish.external_food_id ?? dish.recipe_id),
      external_source: String(dish.external_source ?? 'fatsecret'),
      food_name: String(dish.food_name ?? dish.recipe_name ?? ''),
      brand_name: typeof dish.brand_name === 'string' ? dish.brand_name : null,
      quantity: Number(dish.quantity ?? dish.portions ?? 1),
      serving_unit: String(dish.serving_unit ?? 'serving'),
      serving_description: String(dish.serving_description ?? '1 serving'),
      base_quantity: Number(dish.base_quantity ?? 1),
      calories_per_base: Number(dish.calories_per_base ?? dish.calories ?? 0),
      protein_g_per_base: Number(dish.protein_g_per_base ?? dish.protein_g ?? 0),
      carbs_g_per_base: Number(dish.carbs_g_per_base ?? dish.carbs_g ?? 0),
      fat_g_per_base: Number(dish.fat_g_per_base ?? dish.fat_g ?? 0),
      calories: Number(dish.calories ?? 0),
      protein_g: Number(dish.protein_g ?? 0),
      carbs_g: Number(dish.carbs_g ?? 0),
      fat_g: Number(dish.fat_g ?? 0),
    };
  }

  const food = dish as PlannedFoodItem;

  return {
    kind: 'food',
    external_food_id: String(food.external_food_id ?? ''),
    external_source: String(food.external_source ?? 'fatsecret'),
    food_name: String(food.food_name ?? ''),
    brand_name: food.brand_name ?? null,
    quantity: Number(food.quantity ?? 0),
    serving_unit: String(food.serving_unit ?? 'g'),
    serving_description: String(food.serving_description ?? ''),
    base_quantity: Number(food.base_quantity ?? 1),
    calories: Number(food.calories ?? 0),
    protein_g: Number(food.protein_g ?? 0),
    carbs_g: Number(food.carbs_g ?? 0),
    fat_g: Number(food.fat_g ?? 0),
    servings: food.servings ?? [],
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
