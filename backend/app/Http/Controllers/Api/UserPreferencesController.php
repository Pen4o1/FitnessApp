<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPreferencesRequest;
use App\Http\Resources\UserPreferencesResource;
use App\Models\User;
use App\Services\UserPreferencesService;
use Illuminate\Http\Request;

class UserPreferencesController extends Controller
{
    public function __construct(
        private readonly UserPreferencesService $userPreferencesService,
    ) {}

    public function show(Request $request): UserPreferencesResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserPreferencesResource($this->userPreferencesService->getPreferences($user));
    }

    public function update(UpdateUserPreferencesRequest $request): UserPreferencesResource
    {
        /** @var User $user */
        $user = $request->user();

        $record = $this->userPreferencesService->updatePreferences($user, $request->validated());

        return new UserPreferencesResource([
            'dietary_preferences' => $record->preferences['dietary_preferences'] ?? [],
            'allergies' => $record->preferences['allergies'] ?? [],
        ]);
    }
}
