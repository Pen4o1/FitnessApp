<?php

namespace App\Models;

use App\Enums\WeightLogSource;
use Database\Factories\BodyWeightLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'weight_kg',
    'recorded_at',
    'source',
])]
class BodyWeightLog extends Model
{
    /** @use HasFactory<BodyWeightLogFactory> */
    use HasFactory;

    public $timestamps = false;

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
            'weight_kg' => 'decimal:2',
            'recorded_at' => 'datetime',
            'source' => WeightLogSource::class,
            'created_at' => 'datetime',
        ];
    }
}
