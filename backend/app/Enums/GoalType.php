<?php

namespace App\Enums;

enum GoalType: string
{
    case Lose = 'lose';
    case Maintain = 'maintain';
    case Gain = 'gain';

    public function calorieAdjustmentFactor(): float
    {
        return match ($this) {
            self::Lose => 0.8,
            self::Maintain => 1.0,
            self::Gain => 1.2,
        };
    }
}
