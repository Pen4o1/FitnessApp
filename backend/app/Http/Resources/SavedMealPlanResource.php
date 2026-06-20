<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedMealPlanResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $plan = $this->plan_data;

        return [
            'id' => $this->id,
            'plan_date' => $this->plan_date->toDateString(),
            'logged_to_diary_at' => $this->logged_to_diary_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'date' => $plan['date'] ?? $this->plan_date->toDateString(),
            'meals_count' => $plan['meals_count'] ?? $this->meals_count,
            'targets' => $plan['targets'] ?? null,
            'totals' => $plan['totals'] ?? [
                'calories' => $this->total_calories,
                'protein_g' => (float) $this->total_protein_g,
                'carbs_g' => (float) $this->total_carbs_g,
                'fat_g' => (float) $this->total_fat_g,
            ],
            'within_target' => $plan['within_target'] ?? $this->within_target,
            'variance' => $plan['variance'] ?? null,
            'dietary_preferences' => $plan['dietary_preferences'] ?? [],
            'allergies' => $plan['allergies'] ?? [],
            'meals' => isset($plan['meals']) && is_array($plan['meals'])
                ? collect($plan['meals'])->map(function (array $meal) use ($request): array {
                    $resource = new PlannedMealResource($meal);

                    return $resource->resolve($request);
                })->all()
                : [],
        ];
    }
}
