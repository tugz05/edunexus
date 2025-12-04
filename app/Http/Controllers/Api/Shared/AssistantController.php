<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
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

        $response = $this->assistantService->reply($user, $message);

        return response()->json([
            'reply' => $response['reply'],
            'suggestions' => $response['suggestions'],
        ]);
    }
}
