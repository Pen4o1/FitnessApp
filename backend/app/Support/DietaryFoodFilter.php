<?php

namespace App\Support;

use App\Enums\AllergyRestriction;
use App\Enums\DietaryPreference;

class DietaryFoodFilter
{
    /**
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     */
    public function allows(string $foodName, array $dietaryPreferences, array $allergies): bool
    {
        $normalized = strtolower(trim($foodName));

        if ($normalized === '') {
            return false;
        }

        foreach ($allergies as $allergy) {
            if ($this->matchesAny($normalized, $this->allergyKeywords($allergy))) {
                return false;
            }
        }

        foreach ($dietaryPreferences as $preference) {
            if ($this->matchesAny($normalized, $this->dietaryPreferenceKeywords($preference))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $ingredientStrings
     * @param  list<string>  $dietaryPreferences
     * @param  list<string>  $allergies
     */
    public function allowsRecipe(string $recipeName, array $ingredientStrings, array $dietaryPreferences, array $allergies): bool
    {
        $combined = strtolower(trim($recipeName));

        foreach ($ingredientStrings as $ingredient) {
            if (! is_string($ingredient) || $ingredient === '') {
                continue;
            }

            $combined .= ' '.strtolower(trim($ingredient));
        }

        $combined = trim($combined);

        if ($combined === '') {
            return false;
        }

        foreach ($allergies as $allergy) {
            if ($this->matchesAny($combined, $this->allergyKeywords($allergy))) {
                return false;
            }
        }

        foreach ($dietaryPreferences as $preference) {
            if ($this->matchesAny($combined, $this->dietaryPreferenceKeywords($preference))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function allergyKeywords(string $allergy): array
    {
        return match ($allergy) {
            AllergyRestriction::GlutenFree->value => [
                'wheat', 'bread', 'pasta', 'barley', 'rye', 'flour', 'gluten', 'bagel', 'biscuit', 'cracker',
            ],
            AllergyRestriction::NutFree->value => [
                'almond', 'peanut', 'walnut', 'cashew', 'pecan', 'hazelnut', 'pistachio', 'macadamia', 'nut',
            ],
            AllergyRestriction::DairyFree->value => [
                'milk', 'cheese', 'yogurt', 'butter', 'cream', 'whey', 'dairy', 'cheddar', 'mozzarella', 'gouda',
            ],
            AllergyRestriction::SoyFree->value => [
                'soy', 'tofu', 'tempeh', 'edamame', 'miso',
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function dietaryPreferenceKeywords(string $preference): array
    {
        return match ($preference) {
            DietaryPreference::Vegan->value => [
                'beef', 'pork', 'chicken', 'turkey', 'lamb', 'bacon', 'ham', 'sausage', 'meat', 'fish',
                'salmon', 'tuna', 'shrimp', 'crab', 'egg', 'milk', 'cheese', 'yogurt', 'butter', 'cream', 'honey',
            ],
            DietaryPreference::Vegetarian->value => [
                'beef', 'pork', 'chicken', 'turkey', 'lamb', 'bacon', 'ham', 'sausage', 'meat', 'fish',
                'salmon', 'tuna', 'shrimp', 'crab',
            ],
            DietaryPreference::Keto->value => [
                'bread', 'rice', 'pasta', 'potato', 'corn', 'sugar', 'cereal', 'oatmeal', 'wheat', 'flour',
            ],
            DietaryPreference::Paleo->value => [
                'bread', 'rice', 'pasta', 'wheat', 'flour', 'corn', 'bean', 'lentil', 'peanut', 'dairy',
                'milk', 'cheese', 'yogurt', 'sugar',
            ],
            default => [],
        };
    }

    /**
     * @param  list<string>  $keywords
     */
    private function matchesAny(string $normalized, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
