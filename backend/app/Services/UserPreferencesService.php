<?php

namespace App\Services;

use App\Enums\AllergyRestriction;
use App\Enums\DietaryPreference;
use App\Models\User;
use App\Models\UserDietaryPreference;
use App\Support\UserPreferencesNormalizer;

class UserPreferencesService
{
    /**
     * @return array{dietary_preferences: list<string>, allergies: list<string>}
     */
    public function getPreferences(User $user): array
    {
        $record = $user->dietaryPreferences;

        if ($record === null) {
            return [
                'dietary_preferences' => [],
                'allergies' => [],
            ];
        }

        $preferences = $record->preferences;

        return [
            'dietary_preferences' => UserPreferencesNormalizer::normalizeEnumArray(
                $preferences['dietary_preferences'] ?? [],
                DietaryPreference::class,
            ),
            'allergies' => UserPreferencesNormalizer::normalizeEnumArray(
                $preferences['allergies'] ?? [],
                AllergyRestriction::class,
            ),
        ];
    }

    /**
     * @param  array{dietary_preferences: list<string>, allergies: list<string>}  $data
     */
    public function updatePreferences(User $user, array $data): UserDietaryPreference
    {
        $existing = $user->dietaryPreferences?->preferences ?? [];

        return UserDietaryPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'preferences' => array_merge($existing, [
                    'dietary_preferences' => $data['dietary_preferences'],
                    'allergies' => $data['allergies'],
                ]),
            ],
        );
    }
}
