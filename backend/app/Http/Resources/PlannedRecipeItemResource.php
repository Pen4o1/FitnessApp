<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlannedRecipeItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kind' => 'recipe',
            'recipe_id' => $this->resource['recipe_id'],
            'recipe_name' => $this->resource['recipe_name'],
            'description' => $this->resource['description'],
            'image_url' => $this->resource['image_url'],
            'portions' => $this->resource['portions'],
            'grams_per_portion' => $this->resource['grams_per_portion'],
            'prep_time_min' => $this->resource['prep_time_min'],
            'cooking_time_min' => $this->resource['cooking_time_min'],
            'ingredients' => $this->resource['ingredients'],
            'recipe_types' => $this->resource['recipe_types'],
            'directions' => $this->resource['directions'],
            'external_food_id' => $this->resource['external_food_id'],
            'external_source' => $this->resource['external_source'],
            'food_name' => $this->resource['food_name'],
            'brand_name' => $this->resource['brand_name'],
            'quantity' => $this->resource['quantity'],
            'serving_unit' => $this->resource['serving_unit'],
            'serving_description' => $this->resource['serving_description'],
            'base_quantity' => $this->resource['base_quantity'],
            'calories_per_base' => $this->resource['calories_per_base'],
            'protein_g_per_base' => $this->resource['protein_g_per_base'],
            'carbs_g_per_base' => $this->resource['carbs_g_per_base'],
            'fat_g_per_base' => $this->resource['fat_g_per_base'],
            'calories' => $this->resource['calories'],
            'protein_g' => $this->resource['protein_g'],
            'carbs_g' => $this->resource['carbs_g'],
            'fat_g' => $this->resource['fat_g'],
        ];
    }
}
