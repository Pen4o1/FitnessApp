<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailySummaryRequest;
use App\Http\Resources\DailySummaryResource;
use App\Models\User;
use App\Services\DailyLogService;

class DailyLogController extends Controller
{
    public function __construct(
        private readonly DailyLogService $dailyLogService,
    ) {}

    public function show(DailySummaryRequest $request): DailySummaryResource
    {
        /** @var User $user */
        $user = $request->user();
        $date = $request->validated('date') ?? now()->toDateString();

        return new DailySummaryResource(
            $this->dailyLogService->getSummary($user, $date),
        );
    }
}
