<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'gender' => $this->gender?->value,
            'birthdate' => $this->birthdate?->toDateString(),
            'current_weight_kg' => $this->current_weight_kg !== null ? (float) $this->current_weight_kg : null,
            'height_cm' => $this->height_cm,
            'profile_completed_at' => $this->profile_completed_at,
            'email_verified_at' => $this->email_verified_at,
            'nutrition_target' => new UserNutritionTargetResource($this->whenLoaded('activeNutritionTarget')),
        ];
    }
}
