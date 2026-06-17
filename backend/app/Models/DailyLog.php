<?php

namespace App\Models;

use Database\Factories\DailyLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'log_date',
    'total_calories',
    'total_protein_g',
    'total_carbs_g',
    'total_fat_g',
    'notes',
])]
class DailyLog extends Model
{
    /** @use HasFactory<DailyLogFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealEntries(): HasMany
    {
        return $this->hasMany(MealEntry::class);
    }

    public function recalculateTotals(): void
    {
        $totals = $this->mealEntries()
            ->join('food_log_items', 'food_log_items.meal_entry_id', '=', 'meal_entries.id')
            ->selectRaw('COALESCE(SUM(food_log_items.calories), 0) as total_calories')
            ->selectRaw('COALESCE(SUM(food_log_items.protein_g), 0) as total_protein_g')
            ->selectRaw('COALESCE(SUM(food_log_items.carbs_g), 0) as total_carbs_g')
            ->selectRaw('COALESCE(SUM(food_log_items.fat_g), 0) as total_fat_g')
            ->first();

        $this->update([
            'total_calories' => (int) ($totals->total_calories ?? 0),
            'total_protein_g' => $totals->total_protein_g ?? 0,
            'total_carbs_g' => $totals->total_carbs_g ?? 0,
            'total_fat_g' => $totals->total_fat_g ?? 0,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'total_protein_g' => 'decimal:2',
            'total_carbs_g' => 'decimal:2',
            'total_fat_g' => 'decimal:2',
        ];
    }
}
