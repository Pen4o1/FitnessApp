import type { MacroTotals, MealType, FoodServingOption } from '@/types/nutrition';

export type PlannedFoodItem = {
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

export type PlannedMeal = {
  meal_number: number;
  meal_type: MealType;
  title: string;
  target: MacroTotals;
  totals: MacroTotals;
  dishes: PlannedFoodItem[];
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
