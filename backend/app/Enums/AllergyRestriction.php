<?php

namespace App\Enums;

enum AllergyRestriction: string
{
    case GlutenFree = 'gluten_free';
    case NutFree = 'nut_free';
    case DairyFree = 'dairy_free';
    case SoyFree = 'soy_free';
}
