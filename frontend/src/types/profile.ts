export type Gender = 'male' | 'female' | 'other' | 'prefer_not_to_say';

export type ActivityLevel =
  | 'sedentary'
  | 'lightly_active'
  | 'moderately_active'
  | 'very_active'
  | 'extra_active';

export type GoalType = 'lose' | 'maintain' | 'gain';

export type GoalPace = 'slow' | 'moderate' | 'aggressive';

export type UserNutritionTarget = {
  activity_level: ActivityLevel;
  goal_type: GoalType;
  goal_pace: GoalPace;
  target_weight_kg: number;
  calorie_target: number;
  protein_target_g: number;
  carbs_target_g: number;
  fat_target_g: number;
  bmr: number | null;
  tdee: number | null;
  estimated_days_to_goal: number | null;
  effective_from: string | null;
};

export type UpdateProfilePayload = {
  gender: Gender;
  birthdate: string;
  current_weight_kg: number;
  height_cm: number;
  activity_level: ActivityLevel;
  goal_type: GoalType;
  goal_pace: GoalPace;
  target_weight_kg: number;
};

export type CalculatedNutritionTarget = {
  bmr: number;
  tdee: number;
  calorie_target: number;
  protein_target_g: number;
  carbs_target_g: number;
  fat_target_g: number;
  estimated_days_to_goal: number | null;
};

export const GENDER_OPTIONS: { value: Gender; label: string }[] = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' },
  { value: 'prefer_not_to_say', label: 'Prefer not to say' },
];

export const ACTIVITY_LEVEL_OPTIONS: { value: ActivityLevel; label: string; description: string }[] = [
  { value: 'sedentary', label: 'Sedentary', description: 'Desk job, little exercise' },
  { value: 'lightly_active', label: 'Light', description: '1–3 workouts per week' },
  { value: 'moderately_active', label: 'Moderate', description: '3–5 workouts per week' },
  { value: 'very_active', label: 'Very active', description: '6–7 workouts per week' },
  { value: 'extra_active', label: 'Extra active', description: 'Athlete or physical job' },
];

export const GOAL_TYPE_OPTIONS: { value: GoalType; label: string }[] = [
  { value: 'lose', label: 'Lose weight' },
  { value: 'maintain', label: 'Maintain' },
  { value: 'gain', label: 'Build muscle' },
];

export const GOAL_PACE_OPTIONS: { value: GoalPace; label: string; description: string }[] = [
  { value: 'slow', label: 'Slow', description: 'Gentle · ~0.25 kg/week' },
  { value: 'moderate', label: 'Moderate', description: 'Steady · ~0.5 kg/week' },
  { value: 'aggressive', label: 'Aggressive', description: 'Fast · ~1 kg/week' },
];
