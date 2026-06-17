<?php

namespace App\Services;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\GoalType;
use App\Models\User;
use App\Models\UserNutritionTarget;
use Illuminate\Support\Carbon;

class NutritionTargetService
{
    /**
     * @param  array{
     *     activity_level: ActivityLevel,
     *     goal_type: GoalType,
     *     target_weight_kg: float,
     *     current_weight_kg: float,
     *     height_cm: int,
     *     birthdate: Carbon,
     *     gender: Gender,
     * }  $inputs
     */
    public function recalculateAndSave(User $user, array $inputs): UserNutritionTarget
    {
        $computed = $this->calculate($inputs);

        $user->nutritionTargets()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return $user->nutritionTargets()->create([
            'activity_level' => $inputs['activity_level'],
            'goal_type' => $inputs['goal_type'],
            'target_weight_kg' => $inputs['target_weight_kg'],
            'calorie_target' => $computed['calorie_target'],
            'protein_target_g' => $computed['protein_target_g'],
            'carbs_target_g' => $computed['carbs_target_g'],
            'fat_target_g' => $computed['fat_target_g'],
            'bmr' => $computed['bmr'],
            'tdee' => $computed['tdee'],
            'estimated_days_to_goal' => $computed['estimated_days_to_goal'],
            'is_active' => true,
            'effective_from' => now(),
        ]);
    }

    /**
     * @param  array{
     *     activity_level: ActivityLevel,
     *     goal_type: GoalType,
     *     target_weight_kg: float,
     *     current_weight_kg: float,
     *     height_cm: int,
     *     birthdate: Carbon,
     *     gender: Gender,
     * }  $inputs
     * @return array{
     *     bmr: int,
     *     tdee: int,
     *     calorie_target: int,
     *     protein_target_g: int,
     *     carbs_target_g: int,
     *     fat_target_g: int,
     *     estimated_days_to_goal: int|null,
     * }
     */
    public function calculate(array $inputs): array
    {
        $bmr = $this->calculateBmr(
            $inputs['current_weight_kg'],
            $inputs['height_cm'],
            $this->ageFromBirthdate($inputs['birthdate']),
            $inputs['gender'],
        );

        $tdee = (int) round($bmr * $inputs['activity_level']->multiplier());
        $calorieTarget = (int) round($tdee * $inputs['goal_type']->calorieAdjustmentFactor());
        $macros = $this->calculateMacros(
            $calorieTarget,
            $inputs['current_weight_kg'],
            $inputs['goal_type'],
        );

        return [
            'bmr' => $bmr,
            'tdee' => $tdee,
            'calorie_target' => $calorieTarget,
            'protein_target_g' => $macros['protein_target_g'],
            'carbs_target_g' => $macros['carbs_target_g'],
            'fat_target_g' => $macros['fat_target_g'],
            'estimated_days_to_goal' => $this->estimateDaysToGoal(
                $inputs['goal_type'],
                $inputs['current_weight_kg'],
                $inputs['target_weight_kg'],
                $tdee,
                $calorieTarget,
            ),
        ];
    }

    public function calculateBmr(float $weightKg, int $heightCm, int $age, Gender $gender): int
    {
        $genderOffset = match ($gender) {
            Gender::Male => 5,
            Gender::Female => -161,
            Gender::Other, Gender::PreferNotToSay => -78,
        };

        return (int) round(10 * $weightKg + 6.25 * $heightCm - 5 * $age + $genderOffset);
    }

    public function ageFromBirthdate(Carbon $birthdate): int
    {
        return (int) $birthdate->diffInYears(now());
    }

    /**
     * @return array{protein_target_g: int, carbs_target_g: int, fat_target_g: int}
     */
    private function calculateMacros(int $calorieTarget, float $weightKg, GoalType $goalType): array
    {
        $proteinPerKg = match ($goalType) {
            GoalType::Lose => 1.8,
            GoalType::Maintain => 1.6,
            GoalType::Gain => 2.0,
        };

        $proteinTargetG = (int) round($proteinPerKg * $weightKg);
        $fatTargetG = (int) round(($calorieTarget * 0.25) / 9);
        $proteinCalories = $proteinTargetG * 4;
        $fatCalories = $fatTargetG * 9;
        $carbsTargetG = (int) round(max(0, $calorieTarget - $proteinCalories - $fatCalories) / 4);

        return [
            'protein_target_g' => $proteinTargetG,
            'carbs_target_g' => $carbsTargetG,
            'fat_target_g' => $fatTargetG,
        ];
    }

    private function estimateDaysToGoal(
        GoalType $goalType,
        float $currentWeightKg,
        float $targetWeightKg,
        int $tdee,
        int $calorieTarget,
    ): ?int {
        if ($goalType === GoalType::Maintain) {
            return null;
        }

        if (abs($targetWeightKg - $currentWeightKg) < 0.01) {
            return null;
        }

        $dailyDelta = abs($tdee - $calorieTarget);

        if ($dailyDelta === 0) {
            return null;
        }

        return (int) round(abs($targetWeightKg - $currentWeightKg) * 7700 / $dailyDelta);
    }
}
