<?php

namespace Database\Factories;

use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyLog>
 */
class DailyLogFactory extends Factory
{
    protected $model = DailyLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'log_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'total_calories' => 0,
            'total_protein_g' => 0,
            'total_carbs_g' => 0,
            'total_fat_g' => 0,
            'notes' => null,
        ];
    }
}
