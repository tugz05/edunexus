<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Services\AiRecommenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    protected AiRecommenderService $recommenderService;

    public function __construct(AiRecommenderService $recommenderService)
    {
        $this->recommenderService = $recommenderService;
    }

    /**
     * Get personalized content recommendations for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get recommendations (now includes Gemini-generated reasons attached to items)
            $recommendations = $this->recommenderService->getPersonalizedRecommendations($user);

            // Format recommendations with reasons
            $formattedRecommendations = $recommendations->map(function ($item) {
                // Use Gemini-generated reason if available, otherwise fallback
                $reason = $item->recommendation_reason ?? "Recommended based on your preferences";
                
                return [
                    'item' => new ContentItemResource($item),
                    'reason' => $reason,
                ];
            });

            return response()->json([
                'data' => $formattedRecommendations,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching recommendations', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to load recommendations. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'data' => [],
            ], 500);
        }
    }

    /**
     * Generate a human-readable reason for the recommendation.
     *
     * @param \App\Models\ContentItem $item
     * @param \App\Models\LearningPreference|null $preferences
     * @return string
     */
    protected function generateReason($item, $preferences): string
    {
        if (!$preferences) {
            return "Recommended based on popular content";
        }

        $reasons = [];

        // Subject match
        if ($preferences->subjects && in_array($item->subject, $preferences->subjects)) {
            $reasons[] = "matches your interest in {$item->subject}";
        }

        // Difficulty match
        if ($preferences->preferred_difficulty && $item->difficulty === $preferences->preferred_difficulty) {
            $reasons[] = "matches your preferred difficulty level ({$item->difficulty})";
        }

        // Learning style match
        if ($preferences->learning_style) {
            $styleReason = $this->getLearningStyleReason($item, $preferences->learning_style);
            if ($styleReason) {
                $reasons[] = $styleReason;
            }
        }

        // Default reason if no specific matches
        if (empty($reasons)) {
            return "Recommended based on popular content in your area";
        }

        return "Recommended because it " . implode(' and ', $reasons);
    }

    /**
     * Get learning style reason text.
     *
     * @param \App\Models\ContentItem $item
     * @param string $learningStyle
     * @return string|null
     */
    protected function getLearningStyleReason($item, string $learningStyle): ?string
    {
        switch ($learningStyle) {
            case 'visual':
                if ($item->type === 'video') {
                    return "is a video, perfect for visual learners";
                }
                break;
            case 'reading':
                if (in_array($item->type, ['pdf', 'link'])) {
                    return "is great for reading-based learning";
                }
                break;
            case 'practice':
                if ($item->type === 'quiz') {
                    return "is a quiz, ideal for hands-on practice";
                }
                break;
        }

        return null;
    }
}
