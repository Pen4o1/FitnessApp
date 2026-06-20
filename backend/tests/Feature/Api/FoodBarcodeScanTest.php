<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserDietaryPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FoodBarcodeScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fatsecret.client_id' => 'test-client-id',
            'services.fatsecret.client_secret' => 'test-client-secret',
            'services.fatsecret.scope' => 'premier',
            'services.fatsecret.region' => 'BG',
            'services.fatsecret.barcode_regions' => ['BG', 'DE', 'US'],
        ]);

        Cache::flush();
    }

    public function test_authenticated_user_can_scan_barcode(): void
    {
        $this->fakeFatSecretBarcodeResponse();

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertOk()
            ->assertJsonPath('barcode', '0041570054161')
            ->assertJsonPath('external_food_id', '50953')
            ->assertJsonPath('external_source', 'barcode')
            ->assertJsonPath('food_name', 'Whole Grain Cheerios')
            ->assertJsonPath('brand_name', 'General Mills')
            ->assertJsonPath('calories', 100)
            ->assertJsonPath('protein_g', 3)
            ->assertJsonPath('carbs_g', 20)
            ->assertJsonPath('fat_g', 2)
            ->assertJsonPath('serving_description', '1 cup')
            ->assertJsonPath('has_allergen', false)
            ->assertJsonPath('has_dietary_conflict', false)
            ->assertJsonCount(2, 'servings');
    }

    public function test_upc_a_barcode_is_padded_to_gtin13(): void
    {
        $this->fakeFatSecretBarcodeResponse();

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=41570054161');

        $response->assertOk()
            ->assertJsonPath('barcode', '0041570054161');
    }

    public function test_unauthenticated_scan_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertUnauthorized();
    }

    public function test_scan_requires_barcode_parameter(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['barcode']);
    }

    public function test_scan_rejects_invalid_barcode_length(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=1234567');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['barcode']);
    }

    public function test_scan_returns_not_found_when_fatsecret_reports_no_food(): void
    {
        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/food/barcode/find-by-id/v2*' => Http::response([
                'error' => [
                    'code' => 211,
                    'message' => 'No food item detected',
                ],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertNotFound()
            ->assertJsonPath('message', 'No product found for this barcode.');
    }

    public function test_scan_tries_next_barcode_region_when_earlier_regions_return_not_found(): void
    {
        $barcodeResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/barcode-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/food/barcode/find-by-id/v2*' => function ($request) use ($barcodeResponse) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                if (($query['region'] ?? null) === 'BG') {
                    return Http::response([
                        'error' => [
                            'code' => 211,
                            'message' => 'No food item detected',
                        ],
                    ], 200);
                }

                return Http::response($barcodeResponse, 200);
            },
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=3800012345678');

        $response->assertOk()
            ->assertJsonPath('food_name', 'Whole Grain Cheerios');
    }

    public function test_scan_returns_not_found_when_all_barcode_regions_fail(): void
    {
        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/food/barcode/find-by-id/v2*' => Http::response([
                'error' => [
                    'code' => 211,
                    'message' => 'No food item detected',
                ],
            ], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertNotFound()
            ->assertJsonPath('message', 'No product found for this barcode.');
    }

    public function test_scan_returns_bad_gateway_when_fatsecret_fails(): void
    {
        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/food/barcode/find-by-id/v2*' => Http::response([], 500),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Food search is temporarily unavailable.');
    }

    public function test_scan_flags_allergen_conflict_from_fatsecret_attributes(): void
    {
        $this->fakeFatSecretBarcodeResponse(withNutAllergen: true);

        $user = User::factory()->create();
        UserDietaryPreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'dietary_preferences' => [],
                'allergies' => ['nut_free'],
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertOk()
            ->assertJsonPath('has_allergen', true)
            ->assertJsonPath('has_dietary_conflict', false);
    }

    public function test_scan_flags_dietary_conflict_for_vegan_user(): void
    {
        $this->fakeFatSecretBarcodeResponse(foodName: 'Chicken Breast');

        $user = User::factory()->create();
        UserDietaryPreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'dietary_preferences' => ['vegan'],
                'allergies' => [],
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertOk()
            ->assertJsonPath('has_allergen', false)
            ->assertJsonPath('has_dietary_conflict', true);
    }

    public function test_scan_returns_false_flags_when_user_has_no_preferences(): void
    {
        $this->fakeFatSecretBarcodeResponse();

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/food/scan?barcode=0041570054161');

        $response->assertOk()
            ->assertJsonPath('has_allergen', false)
            ->assertJsonPath('has_dietary_conflict', false);
    }

    private function fakeFatSecretBarcodeResponse(
        bool $withNutAllergen = false,
        string $foodName = 'Whole Grain Cheerios',
    ): void {
        $barcodeResponse = json_decode(
            file_get_contents(base_path('tests/Fixtures/fatsecret/barcode-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $barcodeResponse['food']['food_name'] = $foodName;

        if ($withNutAllergen) {
            $allergens = &$barcodeResponse['food']['food_attributes']['allergens']['allergen'];

            foreach ($allergens as &$allergen) {
                if ($allergen['name'] === 'Nuts') {
                    $allergen['value'] = '1';
                }
            }

            unset($allergen);
        }

        Http::fake([
            'oauth.fatsecret.com/connect/token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            'platform.fatsecret.com/rest/food/barcode/find-by-id/v2*' => Http::response($barcodeResponse, 200),
        ]);
    }
}
