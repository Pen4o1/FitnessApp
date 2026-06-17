<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodSearchResultResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'external_food_id' => $this->resource['external_food_id'],
            'external_source' => $this->resource['external_source'],
            'food_name' => $this->resource['food_name'],
            'brand_name' => $this->resource['brand_name'],
            'calories' => $this->resource['calories'],
            'protein_g' => $this->resource['protein_g'],
            'carbs_g' => $this->resource['carbs_g'],
            'fat_g' => $this->resource['fat_g'],
            'serving_unit' => $this->resource['serving_unit'],
            'serving_description' => $this->resource['serving_description'],
        ];
    }
}
