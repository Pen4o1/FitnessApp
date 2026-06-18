<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserDietaryPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPreferencesPayload(array $overrides = []): array
    {
        return array_merge([
            'dietary_preferences' => ['vegan', 'keto'],
            'allergies' => ['gluten_free', 'nut_free'],
        ], $overrides);
    }

    public function test_unauthenticated_user_cannot_access_preferences(): void
    {
        $this->getJson('/api/user/preferences')->assertUnauthorized();
        $this->putJson('/api/user/preferences', $this->validPreferencesPayload())->assertUnauthorized();
    }

    public function test_get_returns_empty_defaults_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/preferences');

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => [],
                'allergies' => [],
            ]);
    }

    public function test_update_strips_invalid_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/preferences', [
                'dietary_preferences' => ['invalid'],
                'allergies' => ['also_invalid'],
            ]);

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => [],
                'allergies' => [],
            ]);
    }

    public function test_user_can_update_preferences(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPreferencesPayload();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/preferences', $payload);

        $response->assertOk()
            ->assertJson($payload);

        $this->assertDatabaseHas('user_dietary_preferences', [
            'user_id' => $user->id,
        ]);

        $record = UserDietaryPreference::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($record);
        $this->assertSame(['vegan', 'keto'], $record->preferences['dietary_preferences']);
        $this->assertSame(['gluten_free', 'nut_free'], $record->preferences['allergies']);
    }

    public function test_user_can_save_allergies_without_dietary_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/preferences', [
                'dietary_preferences' => [],
                'allergies' => ['gluten_free'],
            ]);

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => [],
                'allergies' => ['gluten_free'],
            ]);
    }

    public function test_user_can_save_dietary_preferences_without_allergies(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/preferences', [
                'dietary_preferences' => ['vegan'],
                'allergies' => [],
            ]);

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => ['vegan'],
                'allergies' => [],
            ]);
    }

    public function test_legacy_allergy_values_are_ignored_on_update(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/preferences', [
                'dietary_preferences' => [],
                'allergies' => ['peanuts', 'gluten_free'],
            ]);

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => [],
                'allergies' => ['gluten_free'],
            ]);
    }

    public function test_get_strips_legacy_allergy_values(): void
    {
        $user = User::factory()->create();

        UserDietaryPreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'dietary_preferences' => [],
                'allergies' => ['peanuts', 'lactose'],
            ],
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/preferences');

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => [],
                'allergies' => [],
            ]);
    }

    public function test_get_returns_saved_preferences(): void
    {
        $user = User::factory()->create();

        UserDietaryPreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'dietary_preferences' => ['paleo'],
                'allergies' => ['dairy_free', 'soy_free'],
            ],
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/preferences');

        $response->assertOk()
            ->assertJson([
                'dietary_preferences' => ['paleo'],
                'allergies' => ['dairy_free', 'soy_free'],
            ]);
    }
}
