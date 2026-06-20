<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LogMealPlanRequest;
use App\Http\Resources\SavedMealPlanResource;
use App\Http\Resources\SavedMealPlanSummaryResource;
use App\Models\User;
use App\Services\MealPlanLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavedMealPlanController extends Controller
{
    public function __construct(
        private readonly MealPlanLogService $mealPlanLogService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $limit = min((int) $request->query('limit', 20), 50);

        $plans = $this->mealPlanLogService->listForUser($user, $limit);

        return SavedMealPlanSummaryResource::collection($plans);
    }

    public function show(Request $request, int $savedMealPlan): SavedMealPlanResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $plan = $this->mealPlanLogService->findForUser($user, $savedMealPlan);

        if ($plan === null) {
            return response()->json([
                'message' => 'Meal plan not found.',
            ], 404);
        }

        return new SavedMealPlanResource($plan);
    }

    public function save(LogMealPlanRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $savedPlan = $this->mealPlanLogService->savePlan(
            $user,
            $request->validated('date'),
            $request->input('plan'),
        );

        return (new SavedMealPlanResource($savedPlan))
            ->response()
            ->setStatusCode(201);
    }

    public function logToDiary(LogMealPlanRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->mealPlanLogService->logDishesToDiary(
            $user,
            $request->validated('date'),
            $request->input('plan'),
        );

        return response()->json([
            'message' => 'Meal plan logged to your diary.',
        ], 201);
    }
}
