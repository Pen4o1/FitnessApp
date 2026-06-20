<?php

namespace App\Models;

use Database\Factories\SavedMealPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedMealPlan extends Model
{
    /** @use HasFactory<SavedMealPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_date',
        'meals_count',
        'total_calories',
        'total_protein_g',
        'total_carbs_g',
        'total_fat_g',
        'within_target',
        'plan_data',
        'logged_to_diary_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'meals_count' => 'integer',
            'total_calories' => 'integer',
            'total_protein_g' => 'decimal:2',
            'total_carbs_g' => 'decimal:2',
            'total_fat_g' => 'decimal:2',
            'within_target' => 'boolean',
            'plan_data' => 'array',
            'logged_to_diary_at' => 'datetime',
        ];
    }
}
