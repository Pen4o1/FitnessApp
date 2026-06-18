<?php

namespace App\Support;

use BackedEnum;

class UserPreferencesNormalizer
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return list<string>
     */
    public static function normalizeEnumArray(mixed $values, string $enumClass): array
    {
        if (! is_array($values)) {
            return [];
        }

        $allowed = array_map(
            fn (BackedEnum $case) => $case->value,
            $enumClass::cases(),
        );

        $normalized = [];

        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if (in_array($value, $allowed, true)) {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
