<?php

namespace App\Enums;

enum WeightLogSource: string
{
    case Manual = 'manual';
    case Onboarding = 'onboarding';
    case GoalUpdate = 'goal_update';
}
