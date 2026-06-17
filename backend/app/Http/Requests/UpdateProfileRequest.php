<?php

namespace App\Http\Requests;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\GoalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gender' => ['required', Rule::enum(Gender::class)],
            'birthdate' => ['required', 'date', 'before:today', 'before_or_equal:'.now()->subYears(13)->toDateString()],
            'current_weight_kg' => ['required', 'numeric', 'min:20', 'max:500'],
            'height_cm' => ['required', 'integer', 'min:100', 'max:250'],
            'activity_level' => ['required', Rule::enum(ActivityLevel::class)],
            'goal_type' => ['required', Rule::enum(GoalType::class)],
            'target_weight_kg' => ['required', 'numeric', 'min:20', 'max:500'],
        ];
    }
}
