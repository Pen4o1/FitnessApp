<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\DailyLog;
use App\Models\MealEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealEntry>
 */
class MealEntryFactory extends Factory
{
    protected $model = MealEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'daily_log_id' => DailyLog::factory(),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'name' => fake()->optional()->words(2, true),
            'logged_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
