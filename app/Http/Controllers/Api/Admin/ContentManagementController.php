<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentManagementController extends Controller
{
    /**
     * Get all content items with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContentItem::with(['creator', 'tags']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('subject', 'like', '%' . $search . '%');
            });
        }

        // Role filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Creator filter
        if ($request->filled('creator_id')) {
            $query->where('created_by', $request->creator_id);
        }

        $contentItems = $query->latest()->paginate($request->get('per_page', 15));

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
     * Delete a content item.
     */
    public function destroy(int $id): JsonResponse
    {
        $contentItem = ContentItem::findOrFail($id);
        
        // Delete associated file if exists
        if ($contentItem->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($contentItem->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($contentItem->file_path);
        }
        
        $contentItem->delete();

        return response()->json([
            'message' => 'Content item deleted successfully!',
        ]);
    }
}
