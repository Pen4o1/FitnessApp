<?php

namespace App\Models;

use App\Enums\MealType;
use Database\Factories\MealEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'daily_log_id',
    'meal_type',
    'name',
    'logged_at',
])]
class MealEntry extends Model
{
    /** @use HasFactory<MealEntryFactory> */
    use HasFactory;

    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(DailyLog::class);
    }

    public function foodLogItems(): HasMany
    {
        return $this->hasMany(FoodLogItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal_type' => MealType::class,
            'logged_at' => 'datetime',
        ];
    }
}
