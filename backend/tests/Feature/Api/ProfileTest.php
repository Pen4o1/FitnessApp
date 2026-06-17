<?php

namespace Tests\Feature\Api;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\GoalType;
use App\Enums\WeightLogSource;
use App\Models\User;
use App\Models\UserNutritionTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validProfilePayload(array $overrides = []): array
    {
        return array_merge([
            'gender' => Gender::Male->value,
            'birthdate' => '1996-01-15',
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
            'activity_level' => ActivityLevel::ModeratelyActive->value,
            'goal_type' => GoalType::Lose->value,
            'target_weight_kg' => 75.0,
        ], $overrides);
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->patchJson('/api/profile', $this->validProfilePayload());

        $response->assertUnauthorized();
    }

    public function test_update_profile_fails_with_invalid_data(): void
    {
        $user = User::factory()->incompleteProfile()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'gender' => 'invalid',
                'birthdate' => 'not-a-date',
                'current_weight_kg' => 10,
                'height_cm' => 50,
                'activity_level' => 'invalid',
                'goal_type' => 'invalid',
                'target_weight_kg' => 10,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'gender',
                'birthdate',
                'current_weight_kg',
                'height_cm',
                'activity_level',
                'goal_type',
                'target_weight_kg',
            ]);
    }

    public function test_user_can_update_profile_and_receives_calculated_targets(): void
    {
        $user = User::factory()->incompleteProfile()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', $this->validProfilePayload());

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'gender',
                'birthdate',
                'current_weight_kg',
                'height_cm',
                'profile_completed_at',
                'nutrition_target' => [
                    'activity_level',
                    'goal_type',
                    'target_weight_kg',
                    'calorie_target',
                    'protein_target_g',
                    'carbs_target_g',
                    'fat_target_g',
                    'bmr',
                    'tdee',
                    'estimated_days_to_goal',
                    'effective_from',
                ],
            ])
            ->assertJsonPath('gender', Gender::Male->value)
            ->assertJsonPath('current_weight_kg', 80)
            ->assertJsonPath('height_cm', 180)
            ->assertJsonPath('nutrition_target.calorie_target', 2207)
            ->assertJsonPath('nutrition_target.bmr', 1780)
            ->assertJsonPath('nutrition_target.tdee', 2759);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'gender' => Gender::Male->value,
            'current_weight_kg' => 80.0,
            'height_cm' => 180,
        ]);

        $this->assertNotNull($user->fresh()->profile_completed_at);

        $this->assertDatabaseHas('user_nutrition_targets', [
            'user_id' => $user->id,
            'activity_level' => ActivityLevel::ModeratelyActive->value,
            'goal_type' => GoalType::Lose->value,
            'calorie_target' => 2207,
            'is_active' => true,
        ]);
    }

    public function test_weight_change_creates_body_weight_log(): void
    {
        $user = User::factory()->create([
            'current_weight_kg' => 82.50,
            'height_cm' => 178,
            'birthdate' => '1996-01-15',
            'gender' => Gender::Male,
            'profile_completed_at' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', $this->validProfilePayload([
                'current_weight_kg' => 80.0,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('body_weight_logs', [
            'user_id' => $user->id,
            'weight_kg' => 80.0,
            'source' => WeightLogSource::GoalUpdate->value,
        ]);
    }

    public function test_subsequent_update_deactivates_previous_nutrition_target(): void
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

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', $this->validProfilePayload([
                'goal_type' => GoalType::Maintain->value,
                'target_weight_kg' => 80.0,
            ]))
            ->assertOk()
            ->assertJsonPath('nutrition_target.goal_type', GoalType::Maintain->value);

        $this->assertFalse($existingTarget->fresh()->is_active);
        $this->assertSame(1, UserNutritionTarget::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->count());
        $this->assertSame(2, UserNutritionTarget::query()
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_get_user_includes_nutrition_target_when_present(): void
    {
        $user = User::factory()->create();
        UserNutritionTarget::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'effective_from' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonStructure([
                'nutrition_target' => [
                    'calorie_target',
                    'protein_target_g',
                    'carbs_target_g',
                    'fat_target_g',
                ],
            ]);
    }
}
