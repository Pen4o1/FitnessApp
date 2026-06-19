<?php

namespace Tests\Feature\Api;

use App\Models\BodyWeightLog;
use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsWeeklySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_weekly_summary(): void
    {
        $user = User::factory()->create();

        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();

        DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => $today->toDateString(),
            'total_calories' => 2100,
            'total_protein_g' => 150,
            'total_carbs_g' => 200,
            'total_fat_g' => 70,
        ]);

        DailyLog::factory()->create([
            'user_id' => $user->id,
            'log_date' => $yesterday->toDateString(),
            'total_calories' => 1800,
            'total_protein_g' => 120,
            'total_carbs_g' => 180,
            'total_fat_g' => 60,
        ]);

        BodyWeightLog::factory()->create([
            'user_id' => $user->id,
            'weight_kg' => 75.5,
            'recorded_at' => $today->copy()->setTime(10, 0),
        ]);

        BodyWeightLog::factory()->create([
            'user_id' => $user->id,
            'weight_kg' => 76.0,
            'recorded_at' => $yesterday->copy()->setTime(9, 0),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/analytics/weekly');

        $response->assertOk()
            ->assertJsonCount(7, 'days')
            ->assertJsonPath('days.6.date', $today->toDateString())
            ->assertJsonPath('days.6.calories', 2100)
            ->assertJsonPath('days.6.protein', 150)
            ->assertJsonPath('days.6.carbs', 200)
            ->assertJsonPath('days.6.fat', 70)
            ->assertJsonPath('days.6.weight', 75.5)
            ->assertJsonPath('days.5.date', $yesterday->toDateString())
            ->assertJsonPath('days.5.calories', 1800)
            ->assertJsonPath('days.5.weight', 76)
            ->assertJsonPath('days.0.calories', 0)
            ->assertJsonPath('days.0.weight', null);
    }

    public function test_unauthenticated_user_cannot_fetch_weekly_summary(): void
    {
        $this->getJson('/api/analytics/weekly')
            ->assertUnauthorized();
    }

    public function test_weekly_summary_only_includes_authenticated_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        DailyLog::factory()->create([
            'user_id' => $otherUser->id,
            'log_date' => now()->toDateString(),
            'total_calories' => 9999,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/analytics/weekly')
            ->assertOk()
            ->assertJsonPath('days.6.calories', 0);
    }
}
