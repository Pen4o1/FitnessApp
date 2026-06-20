import type { MacroTotals, MealType, FoodServingOption } from '@/types/nutrition';

export type RecipeDirection = {
  number: number;
  text: string;
};

export type PlannedFoodItem = {
  kind: 'food';
  external_food_id: string;
  external_source: string;
  food_name: string;
  brand_name: string | null;
  quantity: number;
  serving_unit: string;
  serving_description: string;
  base_quantity: number;
  calories: number;
  protein_g: number;
  carbs_g: number;
  fat_g: number;
  servings: FoodServingOption[];
};

export type PlannedRecipeItem = {
  kind: 'recipe';
  recipe_id: string;
  recipe_name: string;
  description: string | null;
  image_url: string | null;
  portions: number;
  grams_per_portion: number | null;
  prep_time_min: number | null;
  cooking_time_min: number | null;
  ingredients: string[];
  recipe_types: string[];
  directions: RecipeDirection[];
  external_food_id: string;
  external_source: string;
  food_name: string;
  brand_name: string | null;
  quantity: number;
  serving_unit: string;
  serving_description: string;
  base_quantity: number;
  calories_per_base: number;
  protein_g_per_base: number;
  carbs_g_per_base: number;
  fat_g_per_base: number;
  calories: number;
  protein_g: number;
  carbs_g: number;
  fat_g: number;
};

export type PlannedDish = PlannedFoodItem | PlannedRecipeItem;

export type PlannedMeal = {
  meal_number: number;
  meal_type: MealType;
  title: string;
  target: MacroTotals;
  totals: MacroTotals;
  dishes: PlannedDish[];
};

export type MealPlanVariance = {
  calories_pct: number;
  protein_g_pct: number;
  carbs_g_pct: number;
  fat_g_pct: number;
};

export type MealPlan = {
  date: string;
  meals_count: number;
  targets: MacroTotals;
  totals: MacroTotals;
  within_target: boolean;
  variance: MealPlanVariance;
  dietary_preferences: string[];
  allergies: string[];
  meals: PlannedMeal[];
};

export type SavedMealPlanSummary = {
  id: number;
  plan_date: string;
  meals_count: number;
  totals: MacroTotals;
  within_target: boolean;
  logged_to_diary_at: string | null;
  created_at: string;
};

export type SavedMealPlan = MealPlan & {
  id: number;
  plan_date: string;
  logged_to_diary_at: string | null;
  created_at: string;
};

export function isPlannedRecipeItem(dish: PlannedDish): dish is PlannedRecipeItem {
  return dish.kind === 'recipe';
}

export function isPlannedFoodItem(dish: PlannedDish): dish is PlannedFoodItem {
  return dish.kind === 'food';
}
