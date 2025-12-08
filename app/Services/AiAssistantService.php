<?php

namespace App\Services;

use App\Http\Resources\ContentItemResource;
use App\Models\AssistantConversation;
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

        // Get conversation history (excluding the current message which hasn't been saved yet)
        $conversationHistory = $this->getConversationHistory($user);

        // Try to generate reply with Gemini (with conversation history)
        $geminiReply = $this->generateGeminiReply($user, $message, $relevantContent, $conversationHistory);

        // Check if Gemini failed due to quota (will be null)
        if ($geminiReply) {
            // Extract content IDs from Gemini reply if mentioned
            $suggestedContentIds = $this->extractContentIdsFromReply($geminiReply, $relevantContent);

            // Use Gemini-suggested content if found, otherwise use keyword-matched content
            $suggestedContent = $suggestedContentIds->isNotEmpty()
                ? $relevantContent->whereIn('id', $suggestedContentIds)
                : $relevantContent;

            // Extract external suggestions (Google-based resources) from Gemini reply
            $externalSuggestions = $this->extractExternalSuggestions($geminiReply);

            return [
                'reply' => $geminiReply,
                'suggestions' => ContentItemResource::collection($suggestedContent),
                'external_suggestions' => $externalSuggestions,
            ];
        }

        // Fallback to keyword-based logic with conversation history
        // Note: This could be due to quota exceeded, API error, or other issues
        Log::info('Gemini reply generation failed, using fallback with conversation history', [
            'user_id' => $user->id,
            'message_length' => strlen($message),
        ]);

        $reply = $this->generateReply($message, $keywords, $userRole, $preferences, $conversationHistory);
        $suggestedContent = $relevantContent;

        // Add a note about quota if this is likely a quota issue
        // (We can't definitively know, but if we have API key and it's failing, it might be quota)
        if (config('gemini.api_key')) {
            $reply = "⚠️ Note: AI-powered responses are currently unavailable (quota limit reached). " .
                     "Using basic response mode. " . $reply;
        }

        return [
            'reply' => $reply,
            'suggestions' => ContentItemResource::collection($suggestedContent),
            'external_suggestions' => [],
        ];
    }

    /**
     * Get conversation history for the user.
     *
     * @param User $user
     * @param int $limit Maximum number of message pairs to include (each pair = user + assistant)
     * @return Collection
     */
    protected function getConversationHistory(User $user, int $limit = 10): Collection
    {
        // Get recent conversation history (last N message pairs = 2*N messages)
        // Order by created_at ascending to maintain chronological order
        return AssistantConversation::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->limit($limit * 2) // Get pairs of messages (user + assistant)
            ->get();
    }

    /**
     * Generate reply using Gemini API with conversation history.
     *
     * @param User $user
     * @param string $message
     * @param Collection $relevantContent
     * @param Collection $conversationHistory
     * @return string|null
     */
    protected function generateGeminiReply(User $user, string $message, Collection $relevantContent, Collection $conversationHistory = null): ?string
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
        } else {
            $contentContext = "Note: No relevant resources found in our library for this query.\n\n";
        }

        // Build conversation history for Gemini
        $historyMessages = [];
        if ($conversationHistory && $conversationHistory->isNotEmpty()) {
            foreach ($conversationHistory as $conv) {
                $historyMessages[] = [
                    'role' => $conv->role === 'user' ? 'user' : 'model',
                    'parts' => [['text' => $conv->message]],
                ];
            }
        }

        // Build system instruction and content context
        $systemContext = $systemInstruction . $contentContext;
        $systemContext .= "\n\nIMPORTANT INSTRUCTIONS:\n";
        $systemContext .= "1. If relevant resources exist in our library above, prioritize suggesting those by their ID and title.\n";
        $systemContext .= "2. If no relevant resources exist in our library, or if the user needs additional external resources, you MUST suggest high-quality Google-based educational resources.\n";
        $systemContext .= "3. When suggesting external resources, provide:\n";
        $systemContext .= "   - The resource title/name\n";
        $systemContext .= "   - A brief description (1-2 sentences)\n";
        $systemContext .= "   - The full URL (must be a valid, accessible link)\n";
        $systemContext .= "   - Why it's relevant to the user's query\n";
        $systemContext .= "4. Format external suggestions clearly in your response, using markdown links if appropriate.\n";
        $systemContext .= "5. You can suggest resources from: Khan Academy, Coursera, edX, YouTube Education, Google Scholar, educational websites, official documentation, etc.\n";
        $systemContext .= "6. Always provide helpful, accurate, and educational resources that align with the user's learning goals.\n\n";
        $systemContext .= "Provide a helpful, clear response. Include both library resources (if available) and external Google-based resources (when needed or when library resources are insufficient).";

        // Add current user message
        $currentMessage = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        return $this->geminiClient->generateTextWithHistory($systemContext, $historyMessages, $currentMessage);
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
     * Extract external suggestions (Google-based resources) from Gemini reply.
     *
     * @param string $reply
     * @return array
     */
    protected function extractExternalSuggestions(string $reply): array
    {
        $externalSuggestions = [];

        // Pattern to match URLs in the reply
        $urlPattern = '/(https?:\/\/[^\s\)]+)/i';
        preg_match_all($urlPattern, $reply, $urlMatches);

        if (!empty($urlMatches[1])) {
            foreach ($urlMatches[1] as $url) {
                // Clean up URL (remove trailing punctuation)
                $url = rtrim($url, '.,;:!?)');

                // Extract title/description before the URL (look for text before the URL)
                $urlPos = stripos($reply, $url);
                if ($urlPos !== false) {
                    // Get text before URL (up to 200 chars)
                    $beforeUrl = substr($reply, max(0, $urlPos - 200), $urlPos);
                    // Try to extract a title (look for patterns like "Title:" or bold text)
                    $title = $this->extractTitleFromContext($beforeUrl, $url);

                    $externalSuggestions[] = [
                        'title' => $title ?: 'External Resource',
                        'url' => $url,
                        'description' => $this->extractDescriptionFromContext($beforeUrl, $url),
                    ];
                }
            }
        }

        // Also look for common educational platforms mentioned without URLs
        $platforms = [
            'khan academy' => 'https://www.khanacademy.org',
            'coursera' => 'https://www.coursera.org',
            'edx' => 'https://www.edx.org',
            'youtube' => 'https://www.youtube.com',
            'google scholar' => 'https://scholar.google.com',
        ];

        foreach ($platforms as $platform => $baseUrl) {
            if (stripos($reply, $platform) !== false && !$this->urlExistsInSuggestions($externalSuggestions, $baseUrl)) {
                // Check if there's a specific topic mentioned
                $topic = $this->extractTopicFromContext($reply, $platform);
                $externalSuggestions[] = [
                    'title' => ucfirst($platform) . ($topic ? " - {$topic}" : ''),
                    'url' => $baseUrl,
                    'description' => "Explore {$platform} for educational resources" . ($topic ? " on {$topic}" : ''),
                ];
            }
        }

        return array_unique($externalSuggestions, SORT_REGULAR);
    }

    /**
     * Extract title from context before URL.
     *
     * @param string $context
     * @param string $url
     * @return string|null
     */
    protected function extractTitleFromContext(string $context, string $url): ?string
    {
        // Look for patterns like "Title:", "Resource:", "Check out:", etc.
        $patterns = [
            '/\[([^\]]+)\]\(/i', // Markdown link format [title](url)
            '/(?:title|resource|check out|visit|see|explore)[:]\s*([^\n\.]+)/i',
            '/(?:^|\n)\s*[-*]\s*([^\n\.]+?)(?:\s*[-–—]|$)/i', // List item
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $context, $matches)) {
                $title = trim($matches[1]);
                if (strlen($title) > 5 && strlen($title) < 100) {
                    return $title;
                }
            }
        }

        return null;
    }

    /**
     * Extract description from context.
     *
     * @param string $context
     * @param string $url
     * @return string|null
     */
    protected function extractDescriptionFromContext(string $context, string $url): ?string
    {
        // Get sentence or phrase containing the URL context
        $sentences = preg_split('/[.!?]\s+/', $context);
        foreach ($sentences as $sentence) {
            if (stripos($sentence, $url) !== false || strlen($sentence) > 20) {
                $desc = trim($sentence);
                if (strlen($desc) > 20 && strlen($desc) < 200) {
                    return $desc;
                }
            }
        }

        return null;
    }

    /**
     * Extract topic from context around platform mention.
     *
     * @param string $reply
     * @param string $platform
     * @return string|null
     */
    protected function extractTopicFromContext(string $reply, string $platform): ?string
    {
        $platformPos = stripos($reply, $platform);
        if ($platformPos === false) {
            return null;
        }

        // Get context around platform mention
        $context = substr($reply, max(0, $platformPos - 50), 100);

        // Look for topic keywords
        $topicPatterns = [
            '/\b(math|mathematics|algebra|calculus|geometry)\b/i',
            '/\b(science|biology|chemistry|physics)\b/i',
            '/\b(programming|coding|computer science|software)\b/i',
            '/\b(history|social studies|geography)\b/i',
            '/\b(language|english|literature|writing)\b/i',
        ];

        foreach ($topicPatterns as $pattern) {
            if (preg_match($pattern, $context, $matches)) {
                return ucfirst(strtolower($matches[1]));
            }
        }

        return null;
    }

    /**
     * Check if URL already exists in suggestions.
     *
     * @param array $suggestions
     * @param string $url
     * @return bool
     */
    protected function urlExistsInSuggestions(array $suggestions, string $url): bool
    {
        foreach ($suggestions as $suggestion) {
            if (isset($suggestion['url']) && stripos($suggestion['url'], $url) !== false) {
                return true;
            }
        }
        return false;
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
     * Generate a reply message based on keywords, user role, and conversation history.
     *
     * @param string $originalMessage
     * @param array $keywords
     * @param string $userRole
     * @param mixed $preferences
     * @param Collection|null $conversationHistory
     * @return string
     */
    protected function generateReply(string $originalMessage, array $keywords, string $userRole, $preferences, $conversationHistory = null): string
    {
        // Try to extract context from conversation history
        $contextualReply = $this->generateContextualReply($originalMessage, $conversationHistory, $userRole);
        if ($contextualReply) {
            return $contextualReply;
        }

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
     * Generate a contextual reply based on conversation history.
     *
     * @param string $currentMessage
     * @param Collection|null $conversationHistory
     * @param string $userRole
     * @return string|null
     */
    protected function generateContextualReply(string $currentMessage, $conversationHistory, string $userRole): ?string
    {
        if (!$conversationHistory || $conversationHistory->isEmpty()) {
            return null;
        }

        $messageLower = strtolower($currentMessage);

        // Check for name-related queries
        if (preg_match('/\b(name|who are you|your name|introduce)\b/i', $currentMessage)) {
            // Look for name in conversation history
            foreach ($conversationHistory as $conv) {
                if ($conv->role === 'user') {
                    $msg = strtolower($conv->message);
                    // Check if user mentioned their name
                    if (preg_match('/\bmy name is (.+?)(?:\.|$|\s)/i', $conv->message, $matches)) {
                        $name = trim($matches[1]);
                        return "Hello {$name}! I'm EduNexus JPENHS, your AI learning assistant. How can I help you with your studies today?";
                    }
                }
            }
            return "I'm EduNexus JPENHS, your AI learning assistant. I'm here to help you with your learning journey. What would you like to learn about today?";
        }

        // Check for "tell me my name" or similar
        if (preg_match('/\b(tell me|what is|remember|do you know)\b.*\b(my name|name)\b/i', $currentMessage)) {
            foreach ($conversationHistory as $conv) {
                if ($conv->role === 'user') {
                    if (preg_match('/\bmy name is (.+?)(?:\.|$|\s)/i', $conv->message, $matches)) {
                        $name = trim($matches[1]);
                        return "Yes, I remember! Your name is {$name}. How can I assist you today, {$name}?";
                    }
                }
            }
            return "I don't think you've told me your name yet. What's your name?";
        }

        // Check for communication skills or topic suggestions
        if (preg_match('/\b(communication|speaking|writing|presentation|public speaking)\b/i', $currentMessage)) {
            if ($userRole === 'student') {
                return "Great! Improving communication skills is important. I can suggest resources on public speaking, writing, and presentation skills. Would you like me to recommend some learning materials?";
            }
        }

        if (preg_match('/\b(suggest|recommend|topic|subject|what should|what can)\b/i', $currentMessage)) {
            if ($userRole === 'student') {
                return "I'd be happy to suggest topics! Based on your learning preferences, I can recommend resources in various subjects. What area are you most interested in exploring?";
            }
        }

        // Check for greetings
        if (preg_match('/\b(hello|hi|hey|greetings|good morning|good afternoon|good evening)\b/i', $currentMessage)) {
            $greeting = "Hello! ";
            // Check if we know the user's name
            foreach ($conversationHistory as $conv) {
                if ($conv->role === 'user') {
                    if (preg_match('/\bmy name is (.+?)(?:\.|$|\s)/i', $conv->message, $matches)) {
                        $name = trim($matches[1]);
                        $greeting = "Hello {$name}! ";
                        break;
                    }
                }
            }
            return $greeting . "I'm EduNexus JPENHS, your AI learning assistant. How can I help you today?";
        }

        return null;
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

