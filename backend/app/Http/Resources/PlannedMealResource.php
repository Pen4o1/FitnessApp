<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlannedMealResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'meal_type' => $this->resource['meal_type'],
            'title' => $this->resource['title'],
            'totals' => $this->resource['totals'],
            'items' => PlannedFoodItemResource::collection($this->resource['items']),
        ];
    }
}
