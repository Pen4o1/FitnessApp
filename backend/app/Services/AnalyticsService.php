<?php

namespace App\Services;

use App\Models\BodyWeightLog;
use App\Models\DailyLog;
use App\Models\User;

class AnalyticsService
{
    /**
     * @return list<array{
     *     date: string,
     *     calories: int,
     *     protein: float,
     *     carbs: float,
     *     fat: float,
     *     weight: float|null
     * }>
     */
    public function getWeeklySummary(User $user): array
    {
        $endDate = now()->startOfDay();
        $startDate = $endDate->copy()->subDays(6);

        $dailyLogs = DailyLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('log_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn (DailyLog $log): string => $log->log_date->toDateString());

        $weightsByDate = BodyWeightLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('recorded_at', [$startDate, $endDate->copy()->endOfDay()])
            ->orderBy('recorded_at')
            ->get()
            ->reduce(function (array $weights, BodyWeightLog $log): array {
                $weights[$log->recorded_at->toDateString()] = (float) $log->weight_kg;

                return $weights;
            }, []);

        $days = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $dailyLog = $dailyLogs->get($dateString);

            $days[] = [
                'date' => $dateString,
                'calories' => $dailyLog?->total_calories ?? 0,
                'protein' => (float) ($dailyLog?->total_protein_g ?? 0),
                'carbs' => (float) ($dailyLog?->total_carbs_g ?? 0),
                'fat' => (float) ($dailyLog?->total_fat_g ?? 0),
                'weight' => $weightsByDate[$dateString] ?? null,
            ];
        }

        return $days;
    }
}
