<?php

namespace App\Http\Requests;

use App\Enums\AllergyRestriction;
use App\Enums\DietaryPreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dietary_preferences' => $this->input('dietary_preferences', []),
            'allergies' => $this->input('allergies', []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dietary_preferences' => ['array'],
            'dietary_preferences.*' => ['string', Rule::enum(DietaryPreference::class)],
            'allergies' => ['array'],
            'allergies.*' => ['string', Rule::enum(AllergyRestriction::class)],
        ];
    }
}
