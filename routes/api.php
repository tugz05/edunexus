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
    Route::get('/content/{id}/summary', [ContentController::class, 'summary']);
    Route::post('/assistant/chat', [\App\Http\Controllers\Api\Shared\AssistantController::class, 'chat']);
    Route::get('/assistant/history', [\App\Http\Controllers\Api\Shared\AssistantController::class, 'history']);
    Route::delete('/assistant/history', [\App\Http\Controllers\Api\Shared\AssistantController::class, 'clearHistory']);
});

// Student routes
Route::middleware(['web', 'auth', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\Student\DashboardController::class, 'index']);
    Route::get('/preferences', [PreferencesController::class, 'show']);
    Route::put('/preferences', [PreferencesController::class, 'update']);
    Route::get('/recommendations', [\App\Http\Controllers\Api\Student\RecommendationController::class, 'index']);
    Route::get('/saved', [\App\Http\Controllers\Api\Student\SavedContentController::class, 'index']);
    Route::post('/saved/{contentItem}', [\App\Http\Controllers\Api\Student\SavedContentController::class, 'store']);
    Route::delete('/saved/{contentItem}', [\App\Http\Controllers\Api\Student\SavedContentController::class, 'destroy']);
});

// Teacher routes
Route::middleware(['web', 'auth', 'teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\Teacher\DashboardController::class, 'index']);
    Route::get('/content', [ContentManagementController::class, 'index']);
    Route::post('/content', [ContentManagementController::class, 'store']);
    Route::put('/content/{id}', [ContentManagementController::class, 'update']);
    Route::delete('/content/{id}', [ContentManagementController::class, 'destroy']);

    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);

    Route::get('/analytics', [\App\Http\Controllers\Api\Teacher\AnalyticsController::class, 'index']);
});

// Admin routes
Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
    
    // User management
    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'show']);
    Route::post('/users', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\Admin\UserManagementController::class, 'destroy']);
    
    // Content management
    Route::get('/content', [\App\Http\Controllers\Api\Admin\ContentManagementController::class, 'index']);
    Route::delete('/content/{id}', [\App\Http\Controllers\Api\Admin\ContentManagementController::class, 'destroy']);
});
