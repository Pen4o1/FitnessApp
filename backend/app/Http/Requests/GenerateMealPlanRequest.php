<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('include_snack') && is_string($this->input('include_snack'))) {
            $normalized = strtolower($this->input('include_snack'));

            if (in_array($normalized, ['true', '1'], true)) {
                $this->merge(['include_snack' => true]);
            } elseif (in_array($normalized, ['false', '0'], true)) {
                $this->merge(['include_snack' => false]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'include_snack' => ['sometimes', 'boolean'],
        ];
    }
}
