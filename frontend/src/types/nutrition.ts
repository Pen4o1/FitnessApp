export type MacroTotals = {
  calories: number;
  protein_g: number;
  carbs_g: number;
  fat_g: number;
};

export type FoodLogItem = {
  id: number;
  food_name: string;
  brand_name: string | null;
  calories: number;
  protein_g: number;
  carbs_g: number;
  fat_g: number;
  quantity: number;
  serving_unit: string;
};

export type FoodSearchResult = {
  external_food_id: string;
  external_source: string;
  food_name: string;
  brand_name: string | null;
  calories: number;
  protein_g: number;
  carbs_g: number;
  fat_g: number;
  serving_unit: string;
  serving_description: string;
};

export type MealType = 'breakfast' | 'lunch' | 'dinner' | 'snack';

export type MealEntry = {
  id: number;
  meal_type: MealType;
  items: FoodLogItem[];
  totals: MacroTotals;
};

export type DailySummary = {
  date: string;
  targets: MacroTotals;
  consumed: MacroTotals;
  remaining: MacroTotals;
  meals: MealEntry[];
};

export const MEAL_TYPE_LABELS: Record<MealType, string> = {
  breakfast: 'Breakfast',
  lunch: 'Lunch',
  dinner: 'Dinner',
  snack: 'Snack',
};

export const MEAL_TYPES: MealType[] = ['breakfast', 'lunch', 'dinner', 'snack'];

function computeTotals(items: FoodLogItem[]): MacroTotals {
  return items.reduce(
    (acc, item) => ({
      calories: acc.calories + item.calories,
      protein_g: acc.protein_g + item.protein_g,
      carbs_g: acc.carbs_g + item.carbs_g,
      fat_g: acc.fat_g + item.fat_g,
    }),
    { calories: 0, protein_g: 0, carbs_g: 0, fat_g: 0 },
  );
}

function emptyMeal(id: number, mealType: MealType): MealEntry {
  return {
    id,
    meal_type: mealType,
    items: [],
    totals: { calories: 0, protein_g: 0, carbs_g: 0, fat_g: 0 },
  };
}

function mealWithItems(id: number, mealType: MealType, items: FoodLogItem[]): MealEntry {
  return {
    id,
    meal_type: mealType,
    items,
    totals: computeTotals(items),
  };
}

export function buildMealsFromSummary(meals: MealEntry[]): MealEntry[] {
  return MEAL_TYPES.map((mealType) => {
    const existing = meals.find((meal) => meal.meal_type === mealType);
    return existing ?? emptyMeal(0, mealType);
  });
}

export function computeRemaining(targets: MacroTotals, consumed: MacroTotals): MacroTotals {
  return {
    calories: Math.max(0, targets.calories - consumed.calories),
    protein_g: Math.max(0, targets.protein_g - consumed.protein_g),
    carbs_g: Math.max(0, targets.carbs_g - consumed.carbs_g),
    fat_g: Math.max(0, targets.fat_g - consumed.fat_g),
  };
}

export function scaleMacrosFrom100g(
  calories: number,
  protein_g: number,
  carbs_g: number,
  fat_g: number,
  quantityGrams: number,
): MacroTotals {
  const factor = quantityGrams / 100;

  return {
    calories: Math.round(calories * factor),
    protein_g: Math.round(protein_g * factor * 100) / 100,
    carbs_g: Math.round(carbs_g * factor * 100) / 100,
    fat_g: Math.round(fat_g * factor * 100) / 100,
  };
}

export { computeTotals, emptyMeal, mealWithItems };
