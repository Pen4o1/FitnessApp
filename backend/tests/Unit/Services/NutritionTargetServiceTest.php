<?php

namespace Tests\Unit\Services;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\GoalPace;
use App\Enums\GoalType;
use App\Models\User;
use App\Models\UserNutritionTarget;
use App\Services\NutritionTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NutritionTargetServiceTest extends TestCase
{
    use RefreshDatabase;

    private NutritionTargetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NutritionTargetService;
    }

    public function test_calculate_returns_expected_values_for_lose_goal(): void
    {
        $birthdate = Carbon::parse('1996-01-15');

        $result = $this->service->calculate([
            'activity_level' => ActivityLevel::ModeratelyActive,
            'goal_type' => GoalType::Lose,
            'goal_pace' => GoalPace::Moderate,
            'target_weight_kg' => 75.0,
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'birthdate' => $birthdate,
            'gender' => Gender::Male,
        ]);

        $this->assertSame(1780, $result['bmr']);
        $this->assertSame(2759, $result['tdee']);
        $this->assertSame(2207, $result['calorie_target']);
        $this->assertSame(144, $result['protein_target_g']);
        $this->assertSame(61, $result['fat_target_g']);
        $this->assertSame(271, $result['carbs_target_g']);
        $this->assertSame(70, $result['estimated_days_to_goal']);
    }

    public function test_maintain_goal_at_target_weight_has_null_estimated_days(): void
    {
        $result = $this->service->calculate([
            'activity_level' => ActivityLevel::ModeratelyActive,
            'goal_type' => GoalType::Maintain,
            'goal_pace' => GoalPace::Moderate,
            'target_weight_kg' => 80.0,
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'birthdate' => Carbon::parse('1996-01-15'),
            'gender' => Gender::Male,
        ]);

        $this->assertNull($result['estimated_days_to_goal']);
    }

    public function test_lose_goal_with_weight_delta_returns_positive_estimated_days(): void
    {
        $result = $this->service->calculate([
            'activity_level' => ActivityLevel::ModeratelyActive,
            'goal_type' => GoalType::Lose,
            'goal_pace' => GoalPace::Moderate,
            'target_weight_kg' => 75.0,
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'birthdate' => Carbon::parse('1996-01-15'),
            'gender' => Gender::Male,
        ]);

        $this->assertGreaterThan(0, $result['estimated_days_to_goal']);
    }

    public function test_aggressive_lose_pace_lowers_calorie_target(): void
    {
        $inputs = [
            'activity_level' => ActivityLevel::ModeratelyActive,
            'goal_type' => GoalType::Lose,
            'target_weight_kg' => 75.0,
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'birthdate' => Carbon::parse('1996-01-15'),
            'gender' => Gender::Male,
        ];

        $moderate = $this->service->calculate([...$inputs, 'goal_pace' => GoalPace::Moderate]);
        $aggressive = $this->service->calculate([...$inputs, 'goal_pace' => GoalPace::Aggressive]);

        $this->assertSame(2207, $moderate['calorie_target']);
        $this->assertSame(1931, $aggressive['calorie_target']);
        $this->assertLessThan($moderate['estimated_days_to_goal'], $aggressive['estimated_days_to_goal']);
    }

    public function test_recalculate_and_save_deactivates_previous_target(): void
    {
        $user = User::factory()->create([
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'birthdate' => '1996-01-15',
            'gender' => Gender::Male,
        ]);

        $existingTarget = UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'effective_from' => now()->subDay(),
        ]);

        $newTarget = $this->service->recalculateAndSave($user, [
            'activity_level' => ActivityLevel::ModeratelyActive,
            'goal_type' => GoalType::Lose,
            'goal_pace' => GoalPace::Moderate,
            'target_weight_kg' => 75.0,
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'birthdate' => Carbon::parse('1996-01-15'),
            'gender' => Gender::Male,
        ]);

        $this->assertTrue($newTarget->is_active);
        $this->assertFalse($existingTarget->fresh()->is_active);
        $this->assertSame(1, $user->nutritionTargets()->where('is_active', true)->count());
    }
}
