<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\UserContentInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get analytics data for the authenticated teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all content created by this teacher
        $teacherContent = ContentItem::where('created_by', $user->id)->pluck('id');

        // Total content count
        $totalContent = $teacherContent->count();

        // Get all interactions for teacher's content
        $interactions = UserContentInteraction::whereIn('content_item_id', $teacherContent)->get();

        // Total views
        $totalViews = $interactions->where('action_type', 'viewed')->count();

        // Total saves
        $totalSaves = $interactions->where('action_type', 'saved')->count();

        // Top content with interaction counts
        $topContent = ContentItem::whereIn('id', $teacherContent)
            ->with(['creator', 'tags'])
            ->withCount([
                'userInteractions as views_count' => function ($query) {
                    $query->where('action_type', 'viewed');
                },
                'userInteractions as saves_count' => function ($query) {
                    $query->where('action_type', 'saved');
                },
                'userInteractions as completions_count' => function ($query) {
                    $query->where('action_type', 'completed');
                },
            ])
            ->orderByDesc('views_count')
            ->orderByDesc('saves_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'item' => new ContentItemResource($item),
                    'views' => $item->views_count ?? 0,
                    'saves' => $item->saves_count ?? 0,
                    'completions' => $item->completions_count ?? 0,
                    'total_interactions' => ($item->views_count ?? 0) + ($item->saves_count ?? 0) + ($item->completions_count ?? 0),
                ];
            });

        return response()->json([
            'data' => [
                'total_content' => $totalContent,
                'total_views' => $totalViews,
                'total_saves' => $totalSaves,
                'total_completions' => $interactions->where('action_type', 'completed')->count(),
                'top_content' => $topContent,
            ],
        ]);
    }
}
