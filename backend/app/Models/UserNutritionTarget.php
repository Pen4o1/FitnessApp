<?php

namespace App\Models;

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use Database\Factories\UserNutritionTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'activity_level',
    'goal_type',
    'target_weight_kg',
    'calorie_target',
    'protein_target_g',
    'carbs_target_g',
    'fat_target_g',
    'bmr',
    'tdee',
    'estimated_days_to_goal',
    'is_active',
    'effective_from',
])]
class UserNutritionTarget extends Model
{
    /** @use HasFactory<UserNutritionTargetFactory> */
    use HasFactory;

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
            'activity_level' => ActivityLevel::class,
            'goal_type' => GoalType::class,
            'target_weight_kg' => 'decimal:2',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
        ];
    }
}
