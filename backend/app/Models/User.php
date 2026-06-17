<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\Gender;

#[Fillable([
    'first_name',
    'last_name',
    'email',
    'password',
    'gender',
    'birthdate',
    'current_weight_kg',
    'height_cm',
    'google_id',
    'avatar_path',
    'profile_completed_at',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function activeNutritionTarget(): HasOne
    {
        return $this->hasOne(UserNutritionTarget::class)->where('is_active', true);
    }

    public function nutritionTargets(): HasMany
    {
        return $this->hasMany(UserNutritionTarget::class);
    }

    public function dietaryPreferences(): HasOne
    {
        return $this->hasOne(UserDietaryPreference::class);
    }

    public function bodyWeightLogs(): HasMany
    {
        return $this->hasMany(BodyWeightLog::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'birthdate' => 'date',
            'current_weight_kg' => 'decimal:2',
            'gender' => Gender::class,
            'password' => 'hashed',
        ];
    }
}
