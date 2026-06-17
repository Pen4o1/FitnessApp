<?php

namespace App\Enums;

enum ActivityLevel: string
{
    case Sedentary = 'sedentary';
    case LightlyActive = 'lightly_active';
    case ModeratelyActive = 'moderately_active';
    case VeryActive = 'very_active';
    case ExtraActive = 'extra_active';

    public function multiplier(): float
    {
        return match ($this) {
            self::Sedentary => 1.2,
            self::LightlyActive => 1.375,
            self::ModeratelyActive => 1.55,
            self::VeryActive => 1.725,
            self::ExtraActive => 1.9,
        };
    }
}
