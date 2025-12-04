<?php

namespace App\Services;

use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Support\Collection;

class AiAssistantService
{
    /**
     * Generate an AI assistant reply based on user message.
     *
     * @param User $user
     * @param string $message
     * @return array
     */
    public function reply(User $user, string $message): array
    {
        // TODO: Replace keyword logic with an LLM call (send message, preferences, and content metadata to an external AI API).
        // Example LLM integration:
        // $llmResponse = $this->callLLMAPI([
        //     'message' => $message,
        //     'user_preferences' => $user->learningPreference,
        //     'user_role' => $user->role,
        //     'available_content' => ContentItem::all()->toArray(),
        // ]);
        // return $this->parseLLMResponse($llmResponse);

        $messageLower = strtolower($message);
        $keywords = $this->extractKeywords($messageLower);
        $userRole = $user->role;
        $preferences = $user->learningPreference;

        // Generate reply based on keywords and user role
        $reply = $this->generateReply($message, $keywords, $userRole, $preferences);

        // Find relevant content items
        $suggestedContent = $this->findRelevantContent($keywords, $user, $preferences);

        return [
            'reply' => $reply,
            'suggestions' => ContentItemResource::collection($suggestedContent),
        ];
    }

    /**
     * Extract keywords from the message.
     *
     * @param string $message
     * @return array
     */
    protected function extractKeywords(string $message): array
    {
        $keywords = [];
        $subjectKeywords = [
            'math' => ['math', 'mathematics', 'algebra', 'geometry', 'calculus', 'fraction', 'fractions', 'equation', 'equations'],
            'science' => ['science', 'biology', 'chemistry', 'physics', 'experiment', 'experiments', 'lab', 'laboratory'],
            'english' => ['english', 'reading', 'writing', 'grammar', 'literature', 'essay', 'essays', 'vocabulary'],
            'history' => ['history', 'historical', 'past', 'ancient', 'civilization', 'war', 'wars'],
            'art' => ['art', 'drawing', 'painting', 'creative', 'design'],
        ];

        foreach ($subjectKeywords as $subject => $terms) {
            foreach ($terms as $term) {
                if (str_contains($message, $term)) {
                    $keywords[] = $subject;
                    break;
                }
            }
        }

        // Extract difficulty keywords
        if (str_contains($message, 'beginner') || str_contains($message, 'easy') || str_contains($message, 'basic')) {
            $keywords[] = 'Beginner';
        } elseif (str_contains($message, 'intermediate') || str_contains($message, 'medium')) {
            $keywords[] = 'Intermediate';
        } elseif (str_contains($message, 'advanced') || str_contains($message, 'hard') || str_contains($message, 'difficult')) {
            $keywords[] = 'Advanced';
        }

        // Extract content type keywords
        if (str_contains($message, 'video') || str_contains($message, 'watch') || str_contains($message, 'watch')) {
            $keywords[] = 'video';
        } elseif (str_contains($message, 'pdf') || str_contains($message, 'document') || str_contains($message, 'read')) {
            $keywords[] = 'pdf';
        } elseif (str_contains($message, 'quiz') || str_contains($message, 'test') || str_contains($message, 'practice')) {
            $keywords[] = 'quiz';
        }

        return array_unique($keywords);
    }

    /**
     * Generate a reply message based on keywords and user role.
     *
     * @param string $originalMessage
     * @param array $keywords
     * @param string $userRole
     * @param mixed $preferences
     * @return string
     */
    protected function generateReply(string $originalMessage, array $keywords, string $userRole, $preferences): string
    {
        $subjects = array_filter($keywords, function ($k) {
            return in_array($k, ['math', 'science', 'english', 'history', 'art']);
        });

        if (empty($keywords) && empty($subjects)) {
            if ($userRole === 'teacher') {
                return "I'm here to help you find resources for your students. Could you tell me what subject or topic you're looking for?";
            }
            return "I'm here to help you learn! Could you tell me what subject or topic you're interested in?";
        }

        $reply = "";

        if (!empty($subjects)) {
            $subject = ucfirst(array_values($subjects)[0]);
            if ($userRole === 'teacher') {
                $reply = "It looks like you're asking about {$subject}. Here are some resources that might help your students:";
            } else {
                $reply = "It looks like you're asking about {$subject}. Here are some resources that might help:";
            }
        } else {
            if ($userRole === 'teacher') {
                $reply = "Based on your question, here are some resources that might help your students:";
            } else {
                $reply = "Based on your question, here are some resources that might help:";
            }
        }

        // Add helpful context if preferences exist
        if ($preferences && $preferences->preferred_difficulty) {
            $reply .= " I've filtered these to match your preferred difficulty level.";
        }

        return $reply;
    }

    /**
     * Find relevant content items based on keywords and user preferences.
     *
     * @param array $keywords
     * @param User $user
     * @param mixed $preferences
     * @return Collection
     */
    protected function findRelevantContent(array $keywords, User $user, $preferences): Collection
    {
        $query = ContentItem::with(['creator', 'tags']);

        // Filter by subject keywords
        $subjects = array_filter($keywords, function ($k) {
            return in_array($k, ['math', 'science', 'english', 'history', 'art']);
        });

        if (!empty($subjects)) {
            $subjectMap = [
                'math' => 'Mathematics',
                'science' => 'Science',
                'english' => 'English',
                'history' => 'History',
                'art' => 'Art',
            ];
            $subjectValues = array_map(function ($k) use ($subjectMap) {
                return $subjectMap[$k] ?? $k;
            }, $subjects);
            $query->whereIn('subject', $subjectValues);
        }

        // Filter by difficulty if keyword found
        $difficulties = array_filter($keywords, function ($k) {
            return in_array($k, ['Beginner', 'Intermediate', 'Advanced']);
        });
        if (!empty($difficulties)) {
            $query->whereIn('difficulty', $difficulties);
        } elseif ($preferences && $preferences->preferred_difficulty) {
            // Use user preference if no difficulty keyword
            $query->where('difficulty', $preferences->preferred_difficulty);
        }

        // Filter by content type if keyword found
        $types = array_filter($keywords, function ($k) {
            return in_array($k, ['video', 'pdf', 'link', 'quiz']);
        });
        if (!empty($types)) {
            $query->whereIn('type', $types);
        }

        // If user has preferences with subjects, prioritize those
        if ($preferences && $preferences->subjects && count($preferences->subjects) > 0) {
            if (empty($subjects)) {
                $query->whereIn('subject', $preferences->subjects);
            }
        }

        // Exclude content user has already interacted with (if student)
        if ($user->role === 'student') {
            $interactedIds = $user->contentInteractions()->pluck('content_item_id')->toArray();
            if (!empty($interactedIds)) {
                $query->whereNotIn('id', $interactedIds);
            }
        }

        return $query->limit(5)->get();
    }
}

