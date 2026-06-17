import {
  buildMealsFromSummary,
  computeRemaining,
  type DailySummary,
  type MealEntry,
} from '@/types/nutrition';

const breakfastItems = [
  {
    id: 1,
    food_name: 'Greek Yogurt',
    brand_name: 'Fage',
    calories: 130,
    protein_g: 18,
    carbs_g: 9,
    fat_g: 4,
    quantity: 1,
    serving_unit: 'cup',
  },
  {
    id: 2,
    food_name: 'Banana',
    brand_name: null,
    calories: 105,
    protein_g: 1.3,
    carbs_g: 27,
    fat_g: 0.4,
    quantity: 1,
    serving_unit: 'medium',
  },
];

const lunchItems = [
  {
    id: 3,
    food_name: 'Chicken Breast',
    brand_name: null,
    calories: 248,
    protein_g: 47.5,
    carbs_g: 0,
    fat_g: 5.4,
    quantity: 1,
    serving_unit: 'serving',
  },
];

const seededMeals: MealEntry[] = [
  {
    id: 1,
    meal_type: 'breakfast',
    items: breakfastItems,
    totals: { calories: 235, protein_g: 19.3, carbs_g: 36, fat_g: 4.4 },
  },
  {
    id: 2,
    meal_type: 'lunch',
    items: lunchItems,
    totals: { calories: 248, protein_g: 47.5, carbs_g: 0, fat_g: 5.4 },
  },
];

const targets = {
  calories: 2100,
  protein_g: 160,
  carbs_g: 210,
  fat_g: 65,
};

const consumed = {
  calories: 483,
  protein_g: 66.8,
  carbs_g: 36,
  fat_g: 9.8,
};

export const mockDailySummary: DailySummary = {
  date: new Date().toISOString().slice(0, 10),
  targets,
  consumed,
  remaining: computeRemaining(targets, consumed),
  meals: buildMealsFromSummary(seededMeals),
};

export function getMockDailySummary(date?: string): DailySummary {
  return {
    ...mockDailySummary,
    date: date ?? mockDailySummary.date,
  };
}
