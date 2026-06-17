<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserNutritionTargetResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'activity_level' => $this->activity_level->value,
            'goal_type' => $this->goal_type->value,
            'target_weight_kg' => (float) $this->target_weight_kg,
            'calorie_target' => $this->calorie_target,
            'protein_target_g' => $this->protein_target_g,
            'carbs_target_g' => $this->carbs_target_g,
            'fat_target_g' => $this->fat_target_g,
            'bmr' => $this->bmr,
            'tdee' => $this->tdee,
            'estimated_days_to_goal' => $this->estimated_days_to_goal,
            'effective_from' => $this->effective_from?->toIso8601String(),
        ];
    }
}
