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

    public function test_rejects_recipe_with_peanut_ingredient_for_nut_free(): void
    {
        $this->assertFalse($this->filter->allowsRecipe(
            'Fruit Bowl',
            ['Banana', 'Peanut Butter'],
            [],
            ['nut_free'],
        ));
    }

    public function test_allows_recipe_when_ingredients_match_restrictions(): void
    {
        $this->assertTrue($this->filter->allowsRecipe(
            'Garden Bowl',
            ['Broccoli', 'Quinoa'],
            ['vegan'],
            ['nut_free'],
        ));
    }

    public function test_conflicts_with_allergies_detects_keyword_match(): void
    {
        $this->assertTrue($this->filter->conflictsWithAllergies('Peanut Butter', ['nut_free']));
    }

    public function test_conflicts_with_allergies_returns_false_when_no_allergies(): void
    {
        $this->assertFalse($this->filter->conflictsWithAllergies('Peanut Butter', []));
    }

    public function test_conflicts_with_dietary_preferences_detects_keyword_match(): void
    {
        $this->assertTrue($this->filter->conflictsWithDietaryPreferences('Chicken Breast', ['vegan']));
    }

    public function test_conflicts_with_dietary_preferences_returns_false_when_no_preferences(): void
    {
        $this->assertFalse($this->filter->conflictsWithDietaryPreferences('Chicken Breast', []));
    }
}
