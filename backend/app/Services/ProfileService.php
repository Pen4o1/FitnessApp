<?php

namespace App\Services;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\GoalPace;
use App\Enums\GoalType;
use App\Enums\WeightLogSource;
use App\Models\BodyWeightLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function __construct(
        private readonly NutritionTargetService $nutritionTargetService,
    ) {}

    /**
     * @param  array{
     *     gender: string,
     *     birthdate: string,
     *     current_weight_kg: float,
     *     height_cm: int,
     *     activity_level: string,
     *     goal_type: string,
     *     goal_pace: string,
     *     target_weight_kg: float,
     * }  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $previousWeight = $user->current_weight_kg;

            $user->update([
                'gender' => $data['gender'],
                'birthdate' => $data['birthdate'],
                'current_weight_kg' => $data['current_weight_kg'],
                'height_cm' => $data['height_cm'],
                'profile_completed_at' => now(),
            ]);

            if ($previousWeight !== null && (float) $previousWeight !== (float) $data['current_weight_kg']) {
                BodyWeightLog::create([
                    'user_id' => $user->id,
                    'weight_kg' => $data['current_weight_kg'],
                    'recorded_at' => now(),
                    'source' => WeightLogSource::GoalUpdate,
                ]);
            }

            $this->nutritionTargetService->recalculateAndSave($user, [
                'activity_level' => ActivityLevel::from($data['activity_level']),
                'goal_type' => GoalType::from($data['goal_type']),
                'goal_pace' => GoalPace::from($data['goal_pace']),
                'target_weight_kg' => (float) $data['target_weight_kg'],
                'current_weight_kg' => (float) $data['current_weight_kg'],
                'height_cm' => (int) $data['height_cm'],
                'birthdate' => Carbon::parse($data['birthdate']),
                'gender' => Gender::from($data['gender']),
            ]);

            return $user->fresh(['activeNutritionTarget']);
        });
    }
}
