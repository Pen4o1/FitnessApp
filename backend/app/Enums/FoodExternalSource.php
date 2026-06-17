<?php

namespace App\Enums;

enum FoodExternalSource: string
{
    case Fatsecret = 'fatsecret';
    case Barcode = 'barcode';
    case Manual = 'manual';
    case Custom = 'custom';
}
