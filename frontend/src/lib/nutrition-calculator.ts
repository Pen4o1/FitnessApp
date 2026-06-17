import type {
  ActivityLevel,
  CalculatedNutritionTarget,
  Gender,
  GoalPace,
  GoalType,
  UpdateProfilePayload,
} from '@/types/profile';

const ACTIVITY_MULTIPLIERS: Record<ActivityLevel, number> = {
  sedentary: 1.2,
  lightly_active: 1.375,
  moderately_active: 1.55,
  very_active: 1.725,
  extra_active: 1.9,
};

const GOAL_PACE_FACTORS: Record<GoalPace, number> = {
  slow: 0.9,
  moderate: 0.8,
  aggressive: 0.7,
};

const GOAL_PACE_GAIN_FACTORS: Record<GoalPace, number> = {
  slow: 1.1,
  moderate: 1.2,
  aggressive: 1.3,
};

function calorieAdjustmentFactor(goalType: GoalType, goalPace: GoalPace): number {
  if (goalType === 'maintain') {
    return 1.0;
  }

  if (goalType === 'lose') {
    return GOAL_PACE_FACTORS[goalPace];
  }

  return GOAL_PACE_GAIN_FACTORS[goalPace];
}

const PROTEIN_PER_KG: Record<GoalType, number> = {
  lose: 1.8,
  maintain: 1.6,
  gain: 2.0,
};

function genderOffset(gender: Gender): number {
  switch (gender) {
    case 'male':
      return 5;
    case 'female':
      return -161;
    default:
      return -78;
  }
}

export function ageFromBirthdate(birthdate: string): number | null {
  const parsed = new Date(`${birthdate}T12:00:00`);
  if (Number.isNaN(parsed.getTime())) {
    return null;
  }

  const today = new Date();
  let age = today.getFullYear() - parsed.getFullYear();
  const monthDiff = today.getMonth() - parsed.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < parsed.getDate())) {
    age -= 1;
  }

  return age;
}

export function calculateBmr(
  weightKg: number,
  heightCm: number,
  age: number,
  gender: Gender,
): number {
  return Math.round(10 * weightKg + 6.25 * heightCm - 5 * age + genderOffset(gender));
}

function calculateMacros(
  calorieTarget: number,
  weightKg: number,
  goalType: GoalType,
): Pick<CalculatedNutritionTarget, 'protein_target_g' | 'carbs_target_g' | 'fat_target_g'> {
  const proteinTargetG = Math.round(PROTEIN_PER_KG[goalType] * weightKg);
  const fatTargetG = Math.round((calorieTarget * 0.25) / 9);
  const proteinCalories = proteinTargetG * 4;
  const fatCalories = fatTargetG * 9;
  const carbsTargetG = Math.round(Math.max(0, calorieTarget - proteinCalories - fatCalories) / 4);

  return {
    protein_target_g: proteinTargetG,
    carbs_target_g: carbsTargetG,
    fat_target_g: fatTargetG,
  };
}

function estimateDaysToGoal(
  goalType: GoalType,
  currentWeightKg: number,
  targetWeightKg: number,
  tdee: number,
  calorieTarget: number,
): number | null {
  if (goalType === 'maintain') {
    return null;
  }

  if (Math.abs(targetWeightKg - currentWeightKg) < 0.01) {
    return null;
  }

  const dailyDelta = Math.abs(tdee - calorieTarget);
  if (dailyDelta === 0) {
    return null;
  }

  return Math.round((Math.abs(targetWeightKg - currentWeightKg) * 7700) / dailyDelta);
}

export function calculateNutritionTarget(
  payload: Pick<
    UpdateProfilePayload,
    | 'gender'
    | 'birthdate'
    | 'current_weight_kg'
    | 'height_cm'
    | 'activity_level'
    | 'goal_type'
    | 'goal_pace'
    | 'target_weight_kg'
  >,
): CalculatedNutritionTarget | null {
  const age = ageFromBirthdate(payload.birthdate);
  const weightKg = payload.current_weight_kg;
  const heightCm = payload.height_cm;

  if (
    age === null ||
    age < 13 ||
    !Number.isFinite(weightKg) ||
    weightKg < 20 ||
    !Number.isFinite(heightCm) ||
    heightCm < 100
  ) {
    return null;
  }

  const bmr = calculateBmr(weightKg, heightCm, age, payload.gender);
  const tdee = Math.round(bmr * ACTIVITY_MULTIPLIERS[payload.activity_level]);
  const calorieTarget = Math.round(
    tdee * calorieAdjustmentFactor(payload.goal_type, payload.goal_pace),
  );
  const macros = calculateMacros(calorieTarget, weightKg, payload.goal_type);

  return {
    bmr,
    tdee,
    calorie_target: calorieTarget,
    ...macros,
    estimated_days_to_goal: estimateDaysToGoal(
      payload.goal_type,
      weightKg,
      payload.target_weight_kg,
      tdee,
      calorieTarget,
    ),
  };
}
