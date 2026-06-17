<?php

namespace Tests\Unit\Services;

use App\Services\FatSecret\FatSecretClient;
use App\Services\FoodService;
use Mockery;
use Tests\TestCase;

class FoodServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_normalize_food_uses_exact_100g_serving(): void
    {
        $service = new FoodService(Mockery::mock(FatSecretClient::class));

        $result = $service->normalizeFood([
            'food_id' => '1',
            'food_name' => 'Chicken Breast',
            'brand_name' => '',
            'servings' => [
                'serving' => [
                    'serving_description' => '100 g',
                    'metric_serving_amount' => '100.000',
                    'metric_serving_unit' => 'g',
                    'calories' => '195',
                    'carbohydrate' => '0',
                    'protein' => '29.55',
                    'fat' => '7.57',
                ],
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertSame(195, $result['calories']);
        $this->assertSame(29.55, $result['protein_g']);
        $this->assertSame(0.0, $result['carbs_g']);
        $this->assertSame(7.57, $result['fat_g']);
    }

    public function test_normalize_food_scales_from_50g_serving(): void
    {
        $service = new FoodService(Mockery::mock(FatSecretClient::class));

        $result = $service->normalizeFood([
            'food_id' => '2',
            'food_name' => 'Greek Yogurt',
            'servings' => [
                'serving' => [
                    'serving_description' => '1/2 cup',
                    'metric_serving_amount' => '50.000',
                    'metric_serving_unit' => 'g',
                    'calories' => '50',
                    'carbohydrate' => '4',
                    'protein' => '8',
                    'fat' => '1',
                    'is_default' => 'true',
                ],
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertSame(100, $result['calories']);
        $this->assertSame(16.0, $result['protein_g']);
        $this->assertSame(8.0, $result['carbs_g']);
        $this->assertSame(2.0, $result['fat_g']);
    }

    public function test_normalize_food_skips_non_gram_servings(): void
    {
        $service = new FoodService(Mockery::mock(FatSecretClient::class));

        $result = $service->normalizeFood([
            'food_id' => '3',
            'food_name' => 'Unknown Food',
            'servings' => [
                'serving' => [
                    'serving_description' => '1 cup',
                    'metric_serving_amount' => '240.000',
                    'metric_serving_unit' => 'ml',
                    'calories' => '100',
                    'carbohydrate' => '10',
                    'protein' => '5',
                    'fat' => '2',
                ],
            ],
        ]);

        $this->assertNull($result);
    }
}
