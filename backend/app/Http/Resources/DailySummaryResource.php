<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailySummaryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'targets' => $this->resource['targets'],
            'consumed' => $this->resource['consumed'],
            'remaining' => $this->resource['remaining'],
            'meals' => $this->resource['meals'],
        ];
    }
}
