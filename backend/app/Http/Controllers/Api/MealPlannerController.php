<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FatSecretApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Models\User;
use App\Services\MealPlannerService;
use Illuminate\Http\JsonResponse;

class MealPlannerController extends Controller
{
    public function __construct(
        private readonly MealPlannerService $mealPlannerService,
    ) {}

    public function generateDailyPlan(GenerateMealPlanRequest $request): JsonResponse|MealPlanResource
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $mealsCount = (int) $request->input('meals_count', 4);

            $plan = $this->mealPlannerService->generateDailyPlan(
                $user,
                $mealsCount,
            );
        } catch (FatSecretApiException) {
            return response()->json([
                'message' => 'Food search is temporarily unavailable.',
            ], 502);
        }

        if ($plan === null) {
            return response()->json([
                'message' => 'Complete your profile to set nutrition targets.',
            ], 422);
        }

        return new MealPlanResource($plan);
    }
}
