<?php

namespace App\Models;

use App\Enums\FoodExternalSource;
use Database\Factories\FoodLogItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meal_entry_id',
    'food_name',
    'brand_name',
    'external_food_id',
    'external_source',
    'quantity',
    'serving_unit',
    'serving_description',
    'calories',
    'protein_g',
    'carbs_g',
    'fat_g',
    'source_metadata',
])]
class FoodLogItem extends Model
{
    /** @use HasFactory<FoodLogItemFactory> */
    use HasFactory;

    public function mealEntry(): BelongsTo
    {
        return $this->belongsTo(MealEntry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_source' => FoodExternalSource::class,
            'quantity' => 'decimal:3',
            'protein_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'source_metadata' => 'array',
        ];
    }
}
