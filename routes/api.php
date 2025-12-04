<?php

use App\Http\Controllers\Api\Shared\ContentController;
use App\Http\Controllers\Api\Student\PreferencesController;
use App\Http\Controllers\Api\Teacher\ContentManagementController;
use App\Http\Controllers\Api\Teacher\TagController;
use Illuminate\Support\Facades\Route;

// Shared content routes (auth only)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/content', [ContentController::class, 'index']);
    Route::get('/content/{id}', [ContentController::class, 'show']);
    Route::post('/assistant/chat', [\App\Http\Controllers\Api\Shared\AssistantController::class, 'chat']);
});

// Student routes
Route::middleware(['web', 'auth', 'student'])->prefix('student')->group(function () {
    Route::get('/preferences', [PreferencesController::class, 'show']);
    Route::put('/preferences', [PreferencesController::class, 'update']);
    Route::get('/recommendations', [\App\Http\Controllers\Api\Student\RecommendationController::class, 'index']);
    Route::get('/saved', [\App\Http\Controllers\Api\Student\SavedContentController::class, 'index']);
    Route::post('/saved/{contentItem}', [\App\Http\Controllers\Api\Student\SavedContentController::class, 'store']);
    Route::delete('/saved/{contentItem}', [\App\Http\Controllers\Api\Student\SavedContentController::class, 'destroy']);
});

// Teacher routes
Route::middleware(['web', 'auth', 'teacher'])->prefix('teacher')->group(function () {
    Route::post('/content', [ContentManagementController::class, 'store']);
    Route::put('/content/{id}', [ContentManagementController::class, 'update']);
    Route::delete('/content/{id}', [ContentManagementController::class, 'destroy']);
    
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    
    Route::get('/analytics', [\App\Http\Controllers\Api\Teacher\AnalyticsController::class, 'index']);
});

