<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\UserContentInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all content created by this teacher
        $teacherContent = ContentItem::where('created_by', $user->id)->pluck('id');

        // Get subject counts for teacher's content (exclude NULL/empty subjects)
        $subjectCounts = ContentItem::where('created_by', $user->id)
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->select('subject', DB::raw('count(*) as count'))
            ->groupBy('subject')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'subject' => $item->subject ?? 'Unknown',
                    'count' => (int) $item->count,
                ];
            })
            ->values()
            ->toArray(); // Convert to array to ensure proper JSON encoding

        // Get total stats
        $totalContent = $teacherContent->count();
        $interactions = UserContentInteraction::whereIn('content_item_id', $teacherContent)->get();
        $totalViews = $interactions->where('action_type', 'viewed')->count();
        $totalSaves = $interactions->where('action_type', 'saved')->count();
        $totalCompletions = $interactions->where('action_type', 'completed')->count();

        // Log for debugging
        \Log::info('Teacher Dashboard Data', [
            'user_id' => $user->id,
            'total_content' => $totalContent,
            'subject_counts' => $subjectCounts,
        ]);

        return response()->json([
            'data' => [
                'subject_counts' => $subjectCounts,
                'total_content' => $totalContent,
                'total_views' => $totalViews,
                'total_saves' => $totalSaves,
                'total_completions' => $totalCompletions,
            ],
        ]);
    }
}

