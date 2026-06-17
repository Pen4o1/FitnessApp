<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FatSecretApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FoodSearchRequest;
use App\Http\Resources\FoodSearchResultResource;
use App\Services\FoodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FoodController extends Controller
{
    public function __construct(
        private readonly FoodService $foodService,
    ) {}

    public function search(FoodSearchRequest $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $results = $this->foodService->search(
                $request->validated('q'),
                (int) $request->validated('page', 0),
                (int) $request->validated('per_page', 20),
            );
        } catch (FatSecretApiException) {
            return response()->json([
                'message' => 'Food search is temporarily unavailable.',
            ], 502);
        }

        return FoodSearchResultResource::collection($results);
    }
}
