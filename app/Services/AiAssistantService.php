<?php

namespace App\Services;

use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    protected GeminiClient $geminiClient;

    public function __construct(GeminiClient $geminiClient)
    {
        $this->geminiClient = $geminiClient;
    }
    /**
     * Generate an AI assistant reply based on user message.
     *
     * @param User $user
     * @param string $message
     * @return array
     */
    public function reply(User $user, string $message): array
    {
        $userRole = $user->role;
        $preferences = $user->learningPreference;

        // Find relevant content items first (for context)
        $messageLower = strtolower($message);
        $keywords = $this->extractKeywords($messageLower);
        $relevantContent = $this->findRelevantContent($keywords, $user, $preferences);

        // Try to generate reply with Gemini
        $geminiReply = $this->generateGeminiReply($user, $message, $relevantContent);

        if ($geminiReply) {
            // Extract content IDs from Gemini reply if mentioned
            $suggestedContentIds = $this->extractContentIdsFromReply($geminiReply, $relevantContent);
            
            // Use Gemini-suggested content if found, otherwise use keyword-matched content
            $suggestedContent = $suggestedContentIds->isNotEmpty()
                ? $relevantContent->whereIn('id', $suggestedContentIds)
                : $relevantContent;

            return [
                'reply' => $geminiReply,
                'suggestions' => ContentItemResource::collection($suggestedContent),
            ];
        }

        // Fallback to keyword-based logic
        Log::info('Gemini reply generation failed, using fallback');
        $reply = $this->generateReply($message, $keywords, $userRole, $preferences);
        $suggestedContent = $relevantContent;

        return [
            'reply' => $reply,
            'suggestions' => ContentItemResource::collection($suggestedContent),
        ];
    }

    /**
     * Generate reply using Gemini API.
     *
     * @param User $user
     * @param string $message
     * @param Collection $relevantContent
     * @return string|null
     */
    protected function generateGeminiReply(User $user, string $message, Collection $relevantContent): ?string
    {
        $userRole = $user->role;
        $preferences = $user->learningPreference;

        // Build system instruction
        $systemInstruction = "You are EduNexus JPENHS, an AI learning assistant for students and teachers.\n\n";
        
        if ($userRole === 'student') {
            $systemInstruction .= "Your role: Help students learn by answering questions clearly and concisely at an age-appropriate level. ";
            $systemInstruction .= "Encourage understanding and optionally reference learning resources from our library.\n\n";
            
            if ($preferences) {
                $systemInstruction .= "Student Profile:\n";
                $systemInstruction .= "- Grade Level: " . ($preferences->grade_level ?? 'Not specified') . "\n";
                $systemInstruction .= "- Subjects: " . (is_array($preferences->subjects) ? implode(', ', $preferences->subjects) : ($preferences->subjects ?? 'Not specified')) . "\n";
                $systemInstruction .= "- Preferred Difficulty: " . ($preferences->preferred_difficulty ?? 'Not specified') . "\n";
                $systemInstruction .= "- Learning Style: " . ($preferences->learning_style ?? 'Not specified') . "\n";
                $systemInstruction .= "- Goals: " . ($preferences->goals ?? 'Not specified') . "\n\n";
            }
        } else {
            $systemInstruction .= "Your role: Help teachers by suggesting teaching strategies and useful content items for their students.\n\n";
        }

        // Build context from relevant content
        $contentContext = "";
        if ($relevantContent->isNotEmpty()) {
            $contentContext = "Here are some relevant resources from our library:\n";
            foreach ($relevantContent as $item) {
                $tags = $item->tags ? $item->tags->pluck('name')->implode(', ') : 'None';
                $contentContext .= "- ID: {$item->id}, Title: {$item->title}, Subject: {$item->subject}, Difficulty: {$item->difficulty}, Type: {$item->type}, Description: " . substr($item->description ?? '', 0, 200) . ", Tags: {$tags}\n";
            }
            $contentContext .= "\nWhen you suggest resources, reference these items by their ID and title.\n\n";
        }

        // Build full prompt
        $prompt = $systemInstruction . $contentContext . "User Message: {$message}\n\n";
        $prompt .= "Provide a helpful, clear response. If you mention specific resources, use their IDs and titles from the list above.";

        return $this->geminiClient->generateText($prompt);
    }

    /**
     * Extract content IDs mentioned in Gemini reply.
     *
     * @param string $reply
     * @param Collection $availableContent
     * @return Collection
     */
    protected function extractContentIdsFromReply(string $reply, Collection $availableContent): Collection
    {
        $mentionedIds = collect();

        // Try to find content IDs mentioned in the reply
        // Look for patterns like "ID: 1" or "content #1" or just numbers that match available IDs
        foreach ($availableContent as $item) {
            // Check if item title or ID is mentioned in reply
            if (stripos($reply, (string)$item->id) !== false || 
                stripos($reply, $item->title) !== false) {
                $mentionedIds->push($item->id);
            }
        }

        return $mentionedIds;
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

