<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Services\AiRecommenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected AiRecommenderService $recommenderService;

    public function __construct(AiRecommenderService $recommenderService)
    {
        $this->recommenderService = $recommenderService;
    }

    /**
     * Get dashboard statistics for student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get subject counts
        $subjectCounts = ContentItem::select('subject', DB::raw('count(*) as count'))
            ->groupBy('subject')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'subject' => $item->subject,
                    'count' => $item->count,
                ];
            });

        // Get recommended content (top 3 from AI recommendations)
        $recommendations = $this->recommenderService->getPersonalizedRecommendations($user);
        $recommendedContent = $recommendations->take(3)->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'subject' => $item->subject,
                'difficulty' => $item->difficulty,
                'type' => $item->type,
                'tags' => $item->tags->map(function ($tag) {
                    return $tag->name;
                })->toArray(),
                'creator' => $item->creator ? $item->creator->name : 'Unknown',
            ];
        });

        return response()->json([
            'data' => [
                'subject_counts' => $subjectCounts,
                'recommended_content' => $recommendedContent,
            ],
        ]);
    }
}

