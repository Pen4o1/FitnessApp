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
        $this->assertCount(1, $result['servings']);
        $this->assertSame('g', $result['servings'][0]['unit']);
    }

    public function test_normalize_food_includes_count_based_servings_for_eggs(): void
    {
        $service = new FoodService(Mockery::mock(FatSecretClient::class));

        $result = $service->normalizeFood([
            'food_id' => '3442',
            'food_name' => 'Egg',
            'servings' => [
                'serving' => [
                    [
                        'serving_id' => '50341',
                        'serving_description' => '1 large',
                        'metric_serving_amount' => '50.000',
                        'metric_serving_unit' => 'g',
                        'calories' => '72',
                        'carbohydrate' => '0.36',
                        'protein' => '6.29',
                        'fat' => '4.75',
                        'is_default' => 'true',
                    ],
                    [
                        'serving_id' => '50342',
                        'serving_description' => '1 medium',
                        'metric_serving_amount' => '44.000',
                        'metric_serving_unit' => 'g',
                        'calories' => '63',
                        'carbohydrate' => '0.32',
                        'protein' => '5.54',
                        'fat' => '4.18',
                    ],
                    [
                        'serving_id' => '50343',
                        'serving_description' => '100 g',
                        'metric_serving_amount' => '100.000',
                        'metric_serving_unit' => 'g',
                        'calories' => '143',
                        'carbohydrate' => '0.72',
                        'protein' => '12.56',
                        'fat' => '9.51',
                    ],
                ],
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('1 large', $result['serving_description']);
        $this->assertSame(72, $result['calories']);
        $this->assertCount(3, $result['servings']);
        $this->assertSame('serving', $result['servings'][0]['unit']);
        $this->assertSame('1 large', $result['servings'][0]['description']);
        $this->assertSame('g', $result['servings'][2]['unit']);
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
        $this->assertSame('1/2 cup', $result['serving_description']);
        $this->assertSame(50, $result['calories']);
        $this->assertSame('serving', $result['servings'][0]['unit']);
        $this->assertSame(100, $result['servings'][1]['calories']);
        $this->assertSame('g', $result['servings'][1]['unit']);
    }

    public function test_normalize_food_includes_non_gram_servings(): void
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

        $this->assertNotNull($result);
        $this->assertSame('1 cup', $result['serving_description']);
        $this->assertSame(100, $result['calories']);
        $this->assertSame('serving', $result['servings'][0]['unit']);
    }
}
