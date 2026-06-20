<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DailyLogController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\MealPlannerController;
use App\Http\Controllers\Api\SavedMealPlanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserPreferencesController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/user/preferences', [UserPreferencesController::class, 'show']);
    Route::put('/user/preferences', [UserPreferencesController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/foods/search', [FoodController::class, 'search']);
    Route::get('/food/scan', [FoodController::class, 'searchByBarcode']);
    Route::post('/foods/log', [FoodController::class, 'store']);
    Route::get('/daily-summary', [DailyLogController::class, 'show']);
    Route::get('/analytics/weekly', [AnalyticsController::class, 'getWeeklySummary']);
    Route::get('/meal-planner/generate', [MealPlannerController::class, 'generateDailyPlan']);
    Route::post('/meal-planner/save', [SavedMealPlanController::class, 'save']);
    Route::post('/meal-planner/log', [SavedMealPlanController::class, 'logToDiary']);
    Route::get('/meal-plans', [SavedMealPlanController::class, 'index']);
    Route::get('/meal-plans/{savedMealPlan}', [SavedMealPlanController::class, 'show']);
});
