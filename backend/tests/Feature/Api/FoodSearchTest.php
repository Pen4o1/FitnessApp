<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FoodSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fatsecret.client_id' => 'test-client-id',
            'services.fatsecret.client_secret' => 'test-client-secret',
            'services.fatsecret.scope' => 'premier',
            'services.fatsecret.region' => 'US',
        ]);

        Cache::flush();
    }

    public function test_authenticated_user_can_search_foods(): void
    {
        $this->fakeFatSecretResponses();

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/foods/search?q=chicken');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_food_id', '1641')
            ->assertJsonPath('data.0.external_source', 'fatsecret')
            ->assertJsonPath('data.0.food_name', 'Chicken Breast')
            ->assertJsonPath('data.0.calories', 195)
            ->assertJsonPath('data.0.protein_g', 29.55)
            ->assertJsonPath('data.0.carbs_g', 0)
            ->assertJsonPath('data.0.fat_g', 7.57)
            ->assertJsonPath('data.0.serving_unit', 'g')
            ->assertJsonPath('data.0.serving_description', '100 g');
    }

    public function test_unauthenticated_search_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/foods/search?q=chicken');

        $response->assertUnauthorized();
    }

    public function test_search_requires_minimum_query_length(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/foods/search?q=a');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_returns_bad_gateway_when_fatsecret_fails(): void
    {
        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response([], 500),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/foods/search?q=chicken');

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Food search is temporarily unavailable.');
    }

    private function fakeFatSecretResponses(): void
    {
        $searchResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/search-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/foods/search/v5*' => Http::response($searchResponse, 200),
        ]);
    }
}
