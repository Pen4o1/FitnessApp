<?php

namespace Database\Factories;

use App\Enums\FoodExternalSource;
use App\Models\FoodLogItem;
use App\Models\MealEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodLogItem>
 */
class FoodLogItemFactory extends Factory
{
    protected $model = FoodLogItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_entry_id' => MealEntry::factory(),
            'food_name' => fake()->words(2, true),
            'brand_name' => fake()->optional()->company(),
            'external_food_id' => fake()->optional()->numerify('######'),
            'external_source' => fake()->randomElement(FoodExternalSource::cases()),
            'quantity' => fake()->randomElement([0.25, 0.5, 1, 1.5, 2]),
            'serving_unit' => 'serving',
            'serving_description' => '1 serving',
            'calories' => fake()->numberBetween(50, 600),
            'protein_g' => fake()->randomFloat(2, 0, 40),
            'carbs_g' => fake()->randomFloat(2, 0, 80),
            'fat_g' => fake()->randomFloat(2, 0, 30),
            'source_metadata' => null,
        ];
    }
}
