<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FatSecretApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FoodSearchRequest;
use App\Http\Requests\StoreFoodLogRequest;
use App\Http\Resources\FoodLogItemResource;
use App\Http\Resources\FoodSearchResultResource;
use App\Models\User;
use App\Services\DailyLogService;
use App\Services\FoodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FoodController extends Controller
{
    public function __construct(
        private readonly FoodService $foodService,
        private readonly DailyLogService $dailyLogService,
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

    public function store(StoreFoodLogRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $foodLogItem = $this->dailyLogService->logFood($user, $request->validated());

        return (new FoodLogItemResource($foodLogItem))
            ->response()
            ->setStatusCode(201);
    }
}
