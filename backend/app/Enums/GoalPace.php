<?php

namespace App\Enums;

enum GoalPace: string
{
    case Slow = 'slow';
    case Moderate = 'moderate';
    case Aggressive = 'aggressive';

    public function calorieAdjustmentFactor(GoalType $goalType): float
    {
        return match ($goalType) {
            GoalType::Maintain => 1.0,
            GoalType::Lose => match ($this) {
                self::Slow => 0.9,
                self::Moderate => 0.8,
                self::Aggressive => 0.7,
            },
            GoalType::Gain => match ($this) {
                self::Slow => 1.1,
                self::Moderate => 1.2,
                self::Aggressive => 1.3,
            },
        };
    }
}
