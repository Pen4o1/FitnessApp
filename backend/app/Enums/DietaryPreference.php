<?php

namespace App\Enums;

enum DietaryPreference: string
{
    case Vegan = 'vegan';
    case Vegetarian = 'vegetarian';
    case Keto = 'keto';
    case Paleo = 'paleo';
}
