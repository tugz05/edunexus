<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\SavedContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedContentController extends Controller
{
    /**
     * Get all saved content for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $savedContent = SavedContent::where('user_id', $user->id)
            ->with(['contentItem.creator', 'contentItem.tags'])
            ->latest()
            ->get()
            ->map(function ($saved) {
                return new ContentItemResource($saved->contentItem);
            });

        return response()->json([
            'data' => $savedContent,
        ]);
    }

    /**
     * Save/bookmark a content item.
     */
    public function store(Request $request, int $contentItem): JsonResponse
    {
        $user = $request->user();

        // Check if content item exists
        $contentItemModel = ContentItem::findOrFail($contentItem);

        // Check if already saved
        $existing = SavedContent::where('user_id', $user->id)
            ->where('content_item_id', $contentItem)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Content already saved',
            ], 200);
        }

        // Create saved content
        SavedContent::create([
            'user_id' => $user->id,
            'content_item_id' => $contentItem,
        ]);

        // Also create a "saved" interaction
        \App\Models\UserContentInteraction::firstOrCreate([
            'user_id' => $user->id,
            'content_item_id' => $contentItem,
            'action_type' => 'saved',
        ]);

        return response()->json([
            'message' => 'Content saved successfully!',
        ], 201);
    }

    /**
     * Remove a saved/bookmarked content item.
     */
    public function destroy(Request $request, int $contentItem): JsonResponse
    {
        $user = $request->user();

        $savedContent = SavedContent::where('user_id', $user->id)
            ->where('content_item_id', $contentItem)
            ->firstOrFail();

        $savedContent->delete();

        return response()->json([
            'message' => 'Content removed from saved items',
        ]);
    }
}
