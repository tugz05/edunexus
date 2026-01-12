<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalContent = ContentItem::count();
        
        // Recent users (last 7 days)
        $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();
        
        // Recent content (last 7 days)
        $recentContent = ContentItem::where('created_at', '>=', now()->subDays(7))->count();
        
        // Content by type
        $contentByType = ContentItem::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        
        // Users by role
        $usersByRole = User::selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'total_admins' => $totalAdmins,
                'total_content' => $totalContent,
                'recent_users' => $recentUsers,
                'recent_content' => $recentContent,
                'content_by_type' => $contentByType,
                'users_by_role' => $usersByRole,
            ],
        ]);
    }
}
