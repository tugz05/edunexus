<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Teacher\StoreContentRequest;
use App\Http\Requests\Api\Teacher\UpdateContentRequest;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use Illuminate\Http\JsonResponse;

class ContentManagementController extends Controller
{
    /**
     * Store a new content item.
     */
    public function store(StoreContentRequest $request): JsonResponse
    {
        $contentItem = ContentItem::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'url' => $request->url,
            'subject' => $request->subject,
            'difficulty' => $request->difficulty,
            'created_by' => $request->user()->id,
        ]);

        // Attach tags if provided
        if ($request->filled('tags')) {
            $contentItem->tags()->attach($request->tags);
        }

        $contentItem->load(['creator', 'tags']);

        return response()->json([
            'message' => 'Content item created successfully!',
            'data' => new ContentItemResource($contentItem),
        ], 201);
    }

    /**
     * Update a content item.
     */
    public function update(UpdateContentRequest $request, int $id): JsonResponse
    {
        $contentItem = ContentItem::findOrFail($id);

        $contentItem->update($request->only([
            'title',
            'description',
            'type',
            'url',
            'subject',
            'difficulty',
        ]));

        // Sync tags if provided
        if ($request->has('tags')) {
            $contentItem->tags()->sync($request->tags);
        }

        $contentItem->load(['creator', 'tags']);

        return response()->json([
            'message' => 'Content item updated successfully!',
            'data' => new ContentItemResource($contentItem),
        ]);
    }

    /**
     * Delete a content item.
     */
    public function destroy(int $id): JsonResponse
    {
        $contentItem = ContentItem::findOrFail($id);
        $contentItem->delete();

        return response()->json([
            'message' => 'Content item deleted successfully!',
        ]);
    }
}
