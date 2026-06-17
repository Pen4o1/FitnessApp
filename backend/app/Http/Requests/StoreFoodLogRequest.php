<?php

namespace App\Http\Requests;

use App\Enums\FoodExternalSource;
use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'meal_type' => ['required', Rule::enum(MealType::class)],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:10000'],
            'external_food_id' => ['required', 'string', 'max:100'],
            'external_source' => ['required', Rule::enum(FoodExternalSource::class)],
            'food_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'calories_per_100g' => ['required', 'integer', 'min:0'],
            'protein_g_per_100g' => ['required', 'numeric', 'min:0'],
            'carbs_g_per_100g' => ['required', 'numeric', 'min:0'],
            'fat_g_per_100g' => ['required', 'numeric', 'min:0'],
        ];
    }
}
