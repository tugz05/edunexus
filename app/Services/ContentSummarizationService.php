<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Log;

class ContentSummarizationService
{
    protected GeminiClient $geminiClient;

    public function __construct(GeminiClient $geminiClient)
    {
        $this->geminiClient = $geminiClient;
    }

    /**
     * Summarize a content item using Gemini.
     *
     * @param ContentItem $item
     * @param User|null $user
     * @return string|null
     */
    public function summarizeContentItem(ContentItem $item, ?User $user = null): ?string
    {
        // Build prompt with content details
        $contentInfo = "Title: {$item->title}\n";
        $contentInfo .= "Subject: {$item->subject}\n";
        $contentInfo .= "Difficulty: {$item->difficulty}\n";
        $contentInfo .= "Type: {$item->type}\n";
        
        if ($item->description) {
            $contentInfo .= "Description: {$item->description}\n";
        }

        if ($item->tags && $item->tags->isNotEmpty()) {
            $tags = $item->tags->pluck('name')->implode(', ');
            $contentInfo .= "Tags: {$tags}\n";
        }

        // Build instruction based on user context
        $instruction = "";
        if ($user && $user->role === 'student' && $user->learningPreference) {
            $prefs = $user->learningPreference;
            $gradeLevel = $prefs->grade_level ?? 'student';
            $instruction = "for a {$gradeLevel} student";
            
            if ($prefs->learning_style) {
                $instruction .= " with a {$prefs->learning_style} learning style";
            }
        } else {
            $instruction = "for a student";
        }

        $prompt = "Summarize this learning resource {$instruction} in 2–3 simple, clear sentences. ";
        $prompt .= "Focus on what the student will learn and why it's useful.\n\n";
        $prompt .= $contentInfo;

        $summary = $this->geminiClient->summarize($contentInfo, $instruction);

        if ($summary) {
            return $summary;
        }

        // Fallback: use truncated description
        Log::info('Gemini summarization failed, using fallback');
        if ($item->description) {
            $truncated = substr($item->description, 0, 200);
            return strlen($item->description) > 200 ? $truncated . '...' : $truncated;
        }

        return null;
    }

    /**
     * Batch summarize multiple content items.
     *
     * @param \Illuminate\Database\Eloquent\Collection $items
     * @param User|null $user
     * @return array
     */
    public function batchSummarize(\Illuminate\Database\Eloquent\Collection $items, ?User $user = null): array
    {
        $summaries = [];

        foreach ($items as $item) {
            $summaries[$item->id] = $this->summarizeContentItem($item, $user);
        }

        return $summaries;
    }
}

