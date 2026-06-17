<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodLogItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'food_name' => $this->food_name,
            'brand_name' => $this->brand_name,
            'external_food_id' => $this->external_food_id,
            'external_source' => $this->external_source->value,
            'quantity' => (float) $this->quantity,
            'serving_unit' => $this->serving_unit,
            'serving_description' => $this->serving_description,
            'calories' => $this->calories,
            'protein_g' => (float) $this->protein_g,
            'carbs_g' => (float) $this->carbs_g,
            'fat_g' => (float) $this->fat_g,
        ];
    }
}
