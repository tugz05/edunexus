<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Shared\ListContentRequest;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * List content items with optional filters.
     */
    public function index(ListContentRequest $request): JsonResponse
    {
        $query = ContentItem::with(['creator', 'tags']);

        // Apply filters
        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->tag . '%');
            });
        }

        $contentItems = $query->latest()->paginate(15);

        return response()->json([
            'data' => ContentItemResource::collection($contentItems),
            'meta' => [
                'current_page' => $contentItems->currentPage(),
                'last_page' => $contentItems->lastPage(),
                'per_page' => $contentItems->perPage(),
                'total' => $contentItems->total(),
            ],
        ]);
    }

    /**
     * Show a single content item.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $contentItem = ContentItem::with(['creator', 'tags'])->findOrFail($id);

        return response()->json([
            'data' => new ContentItemResource($contentItem),
        ]);
    }
}
