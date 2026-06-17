<?php

namespace App\Enums;

enum DietType: string
{
    case Omnivore = 'omnivore';
    case Vegetarian = 'vegetarian';
    case Vegan = 'vegan';
    case Pescatarian = 'pescatarian';
    case Other = 'other';
}
