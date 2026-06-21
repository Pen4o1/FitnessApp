<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FatSecretApiException;
use App\Exceptions\FatSecretFoodNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FoodBarcodeScanRequest;
use App\Http\Requests\FoodSearchRequest;
use App\Http\Requests\StoreFoodLogRequest;
use App\Http\Resources\FoodBarcodeScanResource;
use App\Http\Resources\FoodLogResponseResource;
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

    public function searchByBarcode(FoodBarcodeScanRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->foodService->searchByBarcode(
                $request->validated('barcode'),
                $user,
            );
        } catch (FatSecretFoodNotFoundException) {
            return response()->json([
                'message' => 'No product found for this barcode.',
            ], 404);
        } catch (FatSecretApiException) {
            return response()->json([
                'message' => 'Food search is temporarily unavailable.',
            ], 502);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'No product found for this barcode.',
            ], 404);
        }

        return (new FoodBarcodeScanResource($result))->response();
    }

    public function store(StoreFoodLogRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $foodLogItem = $this->dailyLogService->logFood($user, $request->validated());
        $summary = $this->dailyLogService->getSummary($user, $request->validated('date'));

        return (new FoodLogResponseResource([
            'item' => $foodLogItem,
            'consumed' => $summary['consumed'],
            'remaining' => $summary['remaining'],
        ]))
            ->response()
            ->setStatusCode(201);
    }
}
