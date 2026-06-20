<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodServingOptionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'description' => $this->resource['description'],
            'unit' => $this->resource['unit'],
            'unit_label' => $this->resource['unit_label'],
            'base_quantity' => $this->resource['base_quantity'],
            'default_quantity' => $this->resource['default_quantity'],
            'calories' => $this->resource['calories'],
            'protein_g' => $this->resource['protein_g'],
            'carbs_g' => $this->resource['carbs_g'],
            'fat_g' => $this->resource['fat_g'],
            'is_default' => $this->resource['is_default'],
        ];
    }
}
