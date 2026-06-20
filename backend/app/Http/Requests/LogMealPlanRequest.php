<?php

namespace App\Http\Requests;

use App\Enums\FoodExternalSource;
use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogMealPlanRequest extends FormRequest
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
            'plan' => ['required', 'array'],
            'plan.date' => ['required', 'date_format:Y-m-d'],
            'plan.meals_count' => ['required', 'integer', 'min:1', 'max:6'],
            'plan.targets' => ['required', 'array'],
            'plan.targets.calories' => ['required', 'integer', 'min:0'],
            'plan.targets.protein_g' => ['required', 'numeric', 'min:0'],
            'plan.targets.carbs_g' => ['required', 'numeric', 'min:0'],
            'plan.targets.fat_g' => ['required', 'numeric', 'min:0'],
            'plan.totals' => ['required', 'array'],
            'plan.totals.calories' => ['required', 'integer', 'min:0'],
            'plan.totals.protein_g' => ['required', 'numeric', 'min:0'],
            'plan.totals.carbs_g' => ['required', 'numeric', 'min:0'],
            'plan.totals.fat_g' => ['required', 'numeric', 'min:0'],
            'plan.within_target' => ['required', 'boolean'],
            'plan.variance' => ['required', 'array'],
            'plan.dietary_preferences' => ['present', 'array'],
            'plan.dietary_preferences.*' => ['string'],
            'plan.allergies' => ['present', 'array'],
            'plan.allergies.*' => ['string'],
            'plan.meals' => ['required', 'array', 'min:1'],
            'plan.meals.*.meal_number' => ['required', 'integer', 'min:1'],
            'plan.meals.*.meal_type' => ['required', Rule::enum(MealType::class)],
            'plan.meals.*.title' => ['required', 'string', 'max:255'],
            'plan.meals.*.target' => ['required', 'array'],
            'plan.meals.*.totals' => ['required', 'array'],
            'plan.meals.*.dishes' => ['present', 'array'],
            'plan.meals.*.dishes.*.kind' => ['required', Rule::in(['recipe', 'food'])],
            'plan.meals.*.dishes.*.external_food_id' => ['required', 'string', 'max:100'],
            'plan.meals.*.dishes.*.external_source' => ['required', Rule::enum(FoodExternalSource::class)],
            'plan.meals.*.dishes.*.food_name' => ['required', 'string', 'max:255'],
            'plan.meals.*.dishes.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:10000'],
            'plan.meals.*.dishes.*.serving_unit' => ['required', 'string', 'max:50'],
            'plan.meals.*.dishes.*.serving_description' => ['required', 'string', 'max:255'],
            'plan.meals.*.dishes.*.base_quantity' => ['required', 'numeric', 'min:0.001', 'max:10000'],
            'plan.meals.*.dishes.*.calories' => ['required', 'integer', 'min:0'],
            'plan.meals.*.dishes.*.protein_g' => ['required', 'numeric', 'min:0'],
            'plan.meals.*.dishes.*.carbs_g' => ['required', 'numeric', 'min:0'],
            'plan.meals.*.dishes.*.fat_g' => ['required', 'numeric', 'min:0'],
            'plan.meals.*.dishes.*.calories_per_base' => ['required_without:plan.meals.*.dishes.*.servings', 'integer', 'min:0'],
            'plan.meals.*.dishes.*.protein_g_per_base' => ['required_without:plan.meals.*.dishes.*.servings', 'numeric', 'min:0'],
            'plan.meals.*.dishes.*.carbs_g_per_base' => ['required_without:plan.meals.*.dishes.*.servings', 'numeric', 'min:0'],
            'plan.meals.*.dishes.*.fat_g_per_base' => ['required_without:plan.meals.*.dishes.*.servings', 'numeric', 'min:0'],
            'plan.meals.*.dishes.*.servings' => ['sometimes', 'array'],
        ];
    }
}
