<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\LearningPreference;
use App\Models\User;
use App\Models\UserContentInteraction;
use App\Services\Gemini\GeminiClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiRecommenderService
{
    protected GeminiClient $geminiClient;

    public function __construct(GeminiClient $geminiClient)
    {
        $this->geminiClient = $geminiClient;
    }
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
        if ($preferences->subjects && is_array($preferences->subjects) && count($preferences->subjects) > 0) {
            $query->whereIn('subject', $preferences->subjects);
        }

        // Filter by difficulty if preference is set
        if ($preferences->preferred_difficulty) {
            $query->where('difficulty', $preferences->preferred_difficulty);
        }

        // Get candidate items
        $candidateItems = $query->get();

        // If no items match the strict filters, relax the filters and get broader results
        if ($candidateItems->isEmpty()) {
            Log::info('No items found with strict filters, relaxing criteria', [
                'user_id' => $user->id,
                'subjects' => $preferences->subjects,
                'difficulty' => $preferences->preferred_difficulty,
            ]);

            // Try with just subject filter
            $relaxedQuery = ContentItem::with(['creator', 'tags']);
            if ($preferences->subjects && is_array($preferences->subjects) && count($preferences->subjects) > 0) {
                $relaxedQuery->whereIn('subject', $preferences->subjects);
            }
            $candidateItems = $relaxedQuery->get();

            // If still empty, try with just difficulty
            if ($candidateItems->isEmpty() && $preferences->preferred_difficulty) {
                $relaxedQuery = ContentItem::with(['creator', 'tags'])
                    ->where('difficulty', $preferences->preferred_difficulty);
                $candidateItems = $relaxedQuery->get();
            }

            // If still empty, return all content (fallback)
            if ($candidateItems->isEmpty()) {
                Log::info('No items found with relaxed filters, returning all content');
                $candidateItems = ContentItem::with(['creator', 'tags'])->get();
            }
        }

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

        // Get top candidates for Gemini annotation (ensure we have at least some items)
        $topCandidates = $finalItems->take(10);

        // If we have no candidates at all, return empty collection
        if ($topCandidates->isEmpty()) {
            Log::warning('No recommendations available for user', ['user_id' => $user->id]);
            return new Collection([]);
        }

        // Annotate with Gemini-generated reasons
        $annotatedItems = $this->annotateWithGemini($user, $topCandidates);

        // Return top 10 recommendations with reasons attached
        $items = $annotatedItems->map(function ($scored) {
            $item = $scored['item'];
            // Attach reason as a dynamic attribute
            $item->recommendation_reason = $scored['reason'] ?? "Recommended based on your preferences";
            return $item;
        });

        // Convert to Eloquent Collection
        return new Collection($items->all());
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
     * Annotate recommendations with Gemini-generated reasons.
     *
     * @param User $user
     * @param SupportCollection $items
     * @return SupportCollection
     */
    protected function annotateWithGemini(User $user, SupportCollection $items): SupportCollection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $preferences = $user->learningPreference;

        // Build prompt with user context and candidate items
        $userContext = "Student Profile:\n";
        $userContext .= "- Grade Level: " . ($preferences->grade_level ?? 'Not specified') . "\n";
        $userContext .= "- Subjects: " . (is_array($preferences->subjects) ? implode(', ', $preferences->subjects) : ($preferences->subjects ?? 'Not specified')) . "\n";
        $userContext .= "- Preferred Difficulty: " . ($preferences->preferred_difficulty ?? 'Not specified') . "\n";
        $userContext .= "- Learning Style: " . ($preferences->learning_style ?? 'Not specified') . "\n";
        $userContext .= "- Goals: " . ($preferences->goals ?? 'Not specified') . "\n\n";

        $userContext .= "Candidate Content Items:\n";
        foreach ($items as $index => $scored) {
            $item = $scored['item'];
            $tags = $item->tags ? $item->tags->pluck('name')->implode(', ') : 'None';
            $userContext .= ($index + 1) . ". ID: {$item->id}, Title: {$item->title}, Subject: {$item->subject}, Difficulty: {$item->difficulty}, Type: {$item->type}, Description: " . substr($item->description ?? '', 0, 150) . ", Tags: {$tags}\n";
        }

        $prompt = "You are a recommendation engine for an educational app called EduNexus. ";
        $prompt .= "For each content item below, provide a short, personalized explanation (1-2 sentences) explaining why this specific content would be beneficial for this student.\n\n";
        $prompt .= $userContext . "\n";
        $prompt .= "Respond with a JSON array where each entry has: { \"id\": <content_id>, \"reason\": \"<short explanation for this specific student>\" }\n";
        $prompt .= "Make the reasons natural, encouraging, and specific to the student's profile. Focus on how the content aligns with their learning style, difficulty preference, and goals.";

        $schema = [
            [
                'id' => 0,
                'reason' => 'string',
            ],
        ];

        // Try to get Gemini-generated reasons
        $geminiReasons = $this->geminiClient->generateJson($prompt, $schema);

        if ($geminiReasons && is_array($geminiReasons)) {
            // Create a map of ID to reason
            $reasonMap = [];
            foreach ($geminiReasons as $entry) {
                if (isset($entry['id']) && isset($entry['reason'])) {
                    $reasonMap[$entry['id']] = $entry['reason'];
                }
            }

            // Merge Gemini reasons back into items
            return $items->map(function ($scored) use ($reasonMap) {
                $item = $scored['item'];
                if (isset($reasonMap[$item->id])) {
                    $scored['reason'] = $reasonMap[$item->id];
                }
                // If Gemini didn't provide a reason for this item, keep the original
                return $scored;
            });
        }

        // Fallback: return items with original rule-based reasons
        Log::info('Gemini annotation failed, using fallback reasons');
        return $items;
    }
}

