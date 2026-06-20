<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedMealPlanSummaryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_date' => $this->plan_date->toDateString(),
            'meals_count' => $this->meals_count,
            'totals' => [
                'calories' => $this->total_calories,
                'protein_g' => (float) $this->total_protein_g,
                'carbs_g' => (float) $this->total_carbs_g,
                'fat_g' => (float) $this->total_fat_g,
            ],
            'within_target' => $this->within_target,
            'logged_to_diary_at' => $this->logged_to_diary_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
