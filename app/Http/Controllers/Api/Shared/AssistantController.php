<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\AssistantConversation;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    protected AiAssistantService $assistantService;

    public function __construct(AiAssistantService $assistantService)
    {
        $this->assistantService = $assistantService;
    }

    /**
     * Handle a chat message from the user.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $message = $request->input('message');

        // Get AI response (service will use conversation history)
        $response = $this->assistantService->reply($user, $message);

        // Save user message after getting response (so it's not included in history for this request)
        AssistantConversation::create([
            'user_id' => $user->id,
            'role' => 'user',
            'message' => $message,
        ]);

        // Extract suggested content IDs
        $suggestedContentIds = $response['suggestions']->map(function ($item) {
            return $item->id;
        })->toArray();

        // Save assistant reply
        AssistantConversation::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'message' => $response['reply'],
            'suggested_content_ids' => !empty($suggestedContentIds) ? $suggestedContentIds : null,
        ]);

        return response()->json([
            'reply' => $response['reply'],
            'suggestions' => $response['suggestions'],
            'external_suggestions' => $response['external_suggestions'] ?? [],
        ]);
    }

    /**
     * Get conversation history for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $conversations = AssistantConversation::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'role' => $conversation->role,
                    'message' => $conversation->message,
                    'suggested_content_ids' => $conversation->suggested_content_ids,
                    'created_at' => $conversation->created_at->toISOString(),
                ];
            });

        return response()->json([
            'data' => $conversations,
        ]);
    }

    /**
     * Clear conversation history for the authenticated user.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        AssistantConversation::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Conversation history cleared successfully.',
        ]);
    }
}
