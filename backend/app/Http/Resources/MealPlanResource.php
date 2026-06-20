<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'targets' => $this->resource['targets'],
            'totals' => $this->resource['totals'],
            'within_target' => $this->resource['within_target'],
            'variance' => $this->resource['variance'],
            'dietary_preferences' => $this->resource['dietary_preferences'],
            'allergies' => $this->resource['allergies'],
            'meals' => PlannedMealResource::collection($this->resource['meals']),
        ];
    }
}
