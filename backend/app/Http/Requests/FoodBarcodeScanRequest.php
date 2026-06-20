<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FoodBarcodeScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $barcode = $this->query('barcode');

        if (! is_string($barcode)) {
            return;
        }

        $this->merge([
            'barcode' => preg_replace('/\D/', '', $barcode) ?? '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'regex:/^\d{8,13}$/'],
        ];
    }
}
