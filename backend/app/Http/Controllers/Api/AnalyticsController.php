<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WeeklySummaryResource;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    public function getWeeklySummary(Request $request): WeeklySummaryResource
    {
        /** @var User $user */
        $user = $request->user();

        return new WeeklySummaryResource(
            $this->analyticsService->getWeeklySummary($user),
        );
    }
}
