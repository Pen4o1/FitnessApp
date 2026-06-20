<?php

namespace Tests\Unit\Support;

use App\Support\DietaryFoodFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DietaryFoodFilterTest extends TestCase
{
    private DietaryFoodFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new DietaryFoodFilter();
    }

    public function test_allows_food_with_no_restrictions(): void
    {
        $this->assertTrue($this->filter->allows('Chicken Breast', [], []));
    }

    public function test_rejects_empty_food_name(): void
    {
        $this->assertFalse($this->filter->allows('', [], []));
    }

    #[DataProvider('allergyCases')]
    public function test_rejects_foods_matching_allergy_keywords(string $foodName, string $allergy): void
    {
        $this->assertFalse($this->filter->allows($foodName, [], [$allergy]));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function allergyCases(): array
    {
        return [
            'gluten bread' => ['Whole Wheat Bread', 'gluten_free'],
            'nut peanut butter' => ['Peanut Butter', 'nut_free'],
            'dairy cheese' => ['Cheddar Cheese', 'dairy_free'],
            'soy tofu' => ['Tofu', 'soy_free'],
        ];
    }

    #[DataProvider('dietaryPreferenceCases')]
    public function test_rejects_foods_matching_dietary_preference_keywords(string $foodName, string $preference): void
    {
        $this->assertFalse($this->filter->allows($foodName, [$preference], []));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dietaryPreferenceCases(): array
    {
        return [
            'vegan chicken' => ['Chicken Breast', 'vegan'],
            'vegetarian salmon' => ['Salmon', 'vegetarian'],
            'keto oatmeal' => ['Oatmeal', 'keto'],
            'paleo bread' => ['White Bread', 'paleo'],
        ];
    }

    public function test_allows_compatible_food_with_restrictions(): void
    {
        $this->assertTrue($this->filter->allows('Apple', ['vegan'], ['nut_free']));
    }
}
