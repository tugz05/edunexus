<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\LearningPreference;
use App\Models\User;
use App\Models\UserContentInteraction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AiRecommenderService
{
    /**
     * Get personalized content recommendations for a user.
     *
     * @param User $user
     * @return Collection
     */
    public function getPersonalizedRecommendations(User $user): Collection
    {
        // Get user's learning preferences
        $preferences = $user->learningPreference;

        if (!$preferences) {
            // If no preferences, return popular content
            return ContentItem::with(['creator', 'tags'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // Start with all content items
        $query = ContentItem::with(['creator', 'tags']);

        // Filter by subject if preferences have subjects
        if ($preferences->subjects && count($preferences->subjects) > 0) {
            $query->whereIn('subject', $preferences->subjects);
        }

        // Filter by difficulty if preference is set
        if ($preferences->preferred_difficulty) {
            $query->where('difficulty', $preferences->preferred_difficulty);
        }

        // Get candidate items
        $candidateItems = $query->get();

        // Score and rank items
        $scoredItems = $candidateItems->map(function ($item) use ($user, $preferences) {
            $score = $this->calculateScore($item, $user, $preferences);
            return [
                'item' => $item,
                'score' => $score,
                'reason' => $this->generateReason($item, $preferences),
            ];
        })->sortByDesc('score');

        // Get user's previous interactions to down-rank already viewed/saved items
        $userInteractionIds = UserContentInteraction::where('user_id', $user->id)
            ->pluck('content_item_id')
            ->toArray();

        // Down-rank items with many interactions
        $finalItems = $scoredItems->map(function ($scored) use ($userInteractionIds) {
            $item = $scored['item'];
            $score = $scored['score'];

            // Reduce score for items user has already interacted with
            if (in_array($item->id, $userInteractionIds)) {
                $score = $score * 0.3; // Reduce by 70%
            }

            return [
                'item' => $item,
                'score' => $score,
                'reason' => $scored['reason'],
            ];
        })->sortByDesc('score');

        // Rerank with LLM (placeholder for future integration)
        $rerankedItems = $this->rerankWithLLM($preferences, $finalItems->take(20));

        // Return top 10 recommendations
        return $rerankedItems->take(10)->map(function ($scored) {
            return $scored['item'];
        });
    }

    /**
     * Calculate a recommendation score for a content item.
     *
     * @param ContentItem $item
     * @param User $user
     * @param LearningPreference $preferences
     * @return float
     */
    protected function calculateScore(ContentItem $item, User $user, LearningPreference $preferences): float
    {
        $score = 1.0;

        // Boost if subject matches
        if ($preferences->subjects && in_array($item->subject, $preferences->subjects)) {
            $score += 2.0;
        }

        // Boost if difficulty matches
        if ($preferences->preferred_difficulty && $item->difficulty === $preferences->preferred_difficulty) {
            $score += 1.5;
        }

        // Boost if learning style matches (if content type aligns with learning style)
        if ($preferences->learning_style) {
            $styleBoost = $this->getLearningStyleBoost($item, $preferences->learning_style);
            $score += $styleBoost;
        }

        // Boost if tags or description match goals
        if ($preferences->goals) {
            $goalBoost = $this->getGoalBoost($item, $preferences->goals);
            $score += $goalBoost;
        }

        return $score;
    }

    /**
     * Get boost score based on learning style alignment.
     *
     * @param ContentItem $item
     * @param string $learningStyle
     * @return float
     */
    protected function getLearningStyleBoost(ContentItem $item, string $learningStyle): float
    {
        $boost = 0.0;

        switch ($learningStyle) {
            case 'visual':
                // Videos are great for visual learners
                if ($item->type === 'video') {
                    $boost = 1.0;
                }
                break;
            case 'reading':
                // PDFs and links are good for reading learners
                if (in_array($item->type, ['pdf', 'link'])) {
                    $boost = 1.0;
                }
                break;
            case 'practice':
                // Quizzes are great for practice learners
                if ($item->type === 'quiz') {
                    $boost = 1.0;
                }
                break;
            case 'mixed':
                // All types are good for mixed learners
                $boost = 0.5;
                break;
        }

        return $boost;
    }

    /**
     * Get boost score based on goals matching.
     *
     * @param ContentItem $item
     * @param string $goals
     * @return float
     */
    protected function getGoalBoost(ContentItem $item, string $goals): float
    {
        $boost = 0.0;
        $goalKeywords = strtolower($goals);
        $itemText = strtolower($item->title . ' ' . ($item->description ?? ''));

        // Simple keyword matching
        $keywords = explode(' ', $goalKeywords);
        $matches = 0;

        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 3 && str_contains($itemText, $keyword)) {
                $matches++;
            }
        }

        // Boost based on number of keyword matches
        if ($matches > 0) {
            $boost = min($matches * 0.5, 2.0); // Max boost of 2.0
        }

        // Also check tags
        if ($item->tags) {
            foreach ($item->tags as $tag) {
                if (str_contains($goalKeywords, strtolower($tag->name))) {
                    $boost += 0.5;
                }
            }
        }

        return $boost;
    }

    /**
     * Generate a human-readable reason for the recommendation.
     *
     * @param ContentItem $item
     * @param LearningPreference $preferences
     * @return string
     */
    protected function generateReason(ContentItem $item, LearningPreference $preferences): string
    {
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
     * @param ContentItem $item
     * @param string $learningStyle
     * @return string|null
     */
    protected function getLearningStyleReason(ContentItem $item, string $learningStyle): ?string
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

    /**
     * Rerank recommendations using LLM (placeholder for future integration).
     *
     * @param LearningPreference $userProfile
     * @param Collection $candidateItems
     * @return Collection
     */
    protected function rerankWithLLM($userProfile, $candidateItems): Collection
    {
        // TODO: Integrate with LLM API (e.g., OpenAI, Anthropic) for advanced reranking
        // This would involve:
        // 1. Sending user profile and candidate items to LLM
        // 2. Receiving reranked and scored items
        // 3. Returning the reranked collection
        //
        // Example implementation:
        // $llmResponse = $this->callLLMAPI($userProfile, $candidateItems);
        // return $this->parseLLMResponse($llmResponse);

        // For now, just return the candidate items as-is
        return $candidateItems;
    }
}

