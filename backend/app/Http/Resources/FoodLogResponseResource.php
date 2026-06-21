<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodLogResponseResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param  array{item: mixed, consumed: array<string, mixed>, remaining: array<string, mixed>}  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{item: mixed, consumed: array<string, mixed>, remaining: array<string, mixed>} $payload */
        $payload = $this->resource;

        return [
            'item' => (new FoodLogItemResource($payload['item']))->resolve($request),
            'consumed' => $payload['consumed'],
            'remaining' => $payload['remaining'],
        ];
    }
}
