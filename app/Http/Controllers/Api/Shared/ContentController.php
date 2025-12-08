<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Shared\ListContentRequest;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Services\ContentSummarizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    protected ContentSummarizationService $summarizationService;

    public function __construct(ContentSummarizationService $summarizationService)
    {
        $this->summarizationService = $summarizationService;
    }
    /**
     * List content items with optional filters.
     */
    public function index(ListContentRequest $request): JsonResponse
    {
        $query = ContentItem::with(['creator', 'tags']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('subject', 'like', '%' . $search . '%');
            });
        }

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

        // Optionally generate summary for students
        $user = $request->user();
        if ($user && $user->role === 'student' && $request->boolean('include_summary', true)) {
            $summary = $this->summarizationService->summarizeContentItem($contentItem, $user);
            if ($summary) {
                $contentItem->ai_summary = $summary;
            }
        }

        return response()->json([
            'data' => new ContentItemResource($contentItem),
        ]);
    }

    /**
     * Get summary for a content item.
     */
    public function summary(Request $request, int $id): JsonResponse
    {
        $contentItem = ContentItem::findOrFail($id);
        $user = $request->user();

        $summary = $this->summarizationService->summarizeContentItem($contentItem, $user);

        if (!$summary) {
            // Fallback to truncated description
            $summary = $contentItem->description 
                ? (strlen($contentItem->description) > 200 
                    ? substr($contentItem->description, 0, 200) . '...' 
                    : $contentItem->description)
                : 'No summary available.';
        }

        return response()->json([
            'data' => [
                'id' => $contentItem->id,
                'summary' => $summary,
            ],
        ]);
    }
}
