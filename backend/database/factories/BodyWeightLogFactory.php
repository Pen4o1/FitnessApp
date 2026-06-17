<?php

namespace Database\Factories;

use App\Enums\WeightLogSource;
use App\Models\BodyWeightLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BodyWeightLog>
 */
class BodyWeightLogFactory extends Factory
{
    protected $model = BodyWeightLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'weight_kg' => fake()->randomFloat(2, 55, 110),
            'recorded_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'source' => fake()->randomElement(WeightLogSource::cases()),
            'created_at' => now(),
        ];
    }
}
