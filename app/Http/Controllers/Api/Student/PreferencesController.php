<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Student\UpdatePreferencesRequest;
use App\Models\LearningPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    /**
     * Display the current student's learning preferences.
     * Creates default preferences if they don't exist.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $preferences = $user->learningPreference;

        // Create default preferences if they don't exist
        if (! $preferences) {
            $preferences = LearningPreference::create([
                'user_id' => $user->id,
                'grade_level' => null,
                'subjects' => [],
                'preferred_difficulty' => null,
                'learning_style' => null,
                'goals' => null,
            ]);
        }

        return response()->json([
            'data' => $preferences,
        ]);
    }

    /**
     * Update the student's learning preferences.
     */
    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = $request->user();

        $preferences = $user->learningPreference;

        // Create if doesn't exist
        if (! $preferences) {
            $preferences = new LearningPreference();
            $preferences->user_id = $user->id;
        }

        // Update preferences
        $preferences->grade_level = $request->input('grade_level');
        $preferences->subjects = $request->input('subjects', []);
        $preferences->preferred_difficulty = $request->input('preferred_difficulty');
        $preferences->learning_style = $request->input('learning_style');
        $preferences->goals = $request->input('goals');
        $preferences->save();

        return response()->json([
            'message' => 'Learning preferences updated successfully.',
            'data' => $preferences,
        ]);
    }
}
