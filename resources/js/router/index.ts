import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';

// Import layouts
import StudentLayout from '@/layouts/StudentLayout.vue';
import TeacherLayout from '@/layouts/TeacherLayout.vue';

// Import student pages
import StudentHome from '@/pages/student/Home.vue';
import StudentContent from '@/pages/student/Content.vue';
import StudentContentDetail from '@/pages/student/ContentDetail.vue';
import StudentRecommendations from '@/pages/student/Recommendations.vue';
import StudentAssistant from '@/pages/student/Assistant.vue';
import StudentSaved from '@/pages/student/Saved.vue';
import StudentProfile from '@/pages/student/Profile.vue';
import StudentCommunity from '@/pages/student/Community.vue';
import StudentMessages from '@/pages/student/Messages.vue';

// Import teacher pages
import TeacherHome from '@/pages/teacher/Home.vue';
import TeacherContent from '@/pages/teacher/Content.vue';
import TeacherContentCreate from '@/pages/teacher/ContentCreate.vue';
import TeacherContentEdit from '@/pages/teacher/ContentEdit.vue';
import TeacherAssistant from '@/pages/teacher/Assistant.vue';
import TeacherAnalytics from '@/pages/teacher/Analytics.vue';
import TeacherCommunity from '@/pages/teacher/Community.vue';
import TeacherMessages from '@/pages/teacher/Messages.vue';
import TeacherSaved from '@/pages/teacher/Saved.vue';

// Helper function to get user from Inertia (if available)
// This assumes Inertia page props are accessible
function getUserRole(): string | null {
    // TODO: Integrate with your auth system
    // For now, this is a placeholder that should be replaced with actual user role retrieval
    // You might access it via: window.$page?.props?.auth?.user?.role
    if (typeof window !== 'undefined' && (window as any).$page?.props?.auth?.user?.role) {
        return (window as any).$page.props.auth.user.role;
    }
    return null;
}

// Helper function to check if user is authenticated
function isAuthenticated(): boolean {
    if (typeof window !== 'undefined' && (window as any).$page?.props?.auth?.user) {
        return true;
    }
    return false;
}

const routes: RouteRecordRaw[] = [
    // Student routes
    {
        path: '/student',
        component: StudentLayout,
        meta: { requiresAuth: true, role: 'student' },
        children: [
            {
                path: 'home',
                name: 'student.home',
                component: StudentHome,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'content',
                name: 'student.content',
                component: StudentContent,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'content/:id',
                name: 'student.content.detail',
                component: StudentContentDetail,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'recommendations',
                name: 'student.recommendations',
                component: StudentRecommendations,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'assistant',
                name: 'student.assistant',
                component: StudentAssistant,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'saved',
                name: 'student.saved',
                component: StudentSaved,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'community',
                name: 'student.community',
                component: StudentCommunity,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'messages',
                name: 'student.messages',
                component: StudentMessages,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: 'profile',
                name: 'student.profile',
                component: StudentProfile,
                meta: { requiresAuth: true, role: 'student' },
            },
            {
                path: '',
                redirect: '/student/home',
            },
        ],
    },
    // Teacher routes
    {
        path: '/teacher',
        component: TeacherLayout,
        meta: { requiresAuth: true, role: 'teacher' },
        children: [
            {
                path: 'home',
                name: 'teacher.home',
                component: TeacherHome,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'content',
                name: 'teacher.content',
                component: TeacherContent,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'content/create',
                name: 'teacher.content.create',
                component: TeacherContentCreate,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'content/:id/edit',
                name: 'teacher.content.edit',
                component: TeacherContentEdit,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'assistant',
                name: 'teacher.assistant',
                component: TeacherAssistant,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'analytics',
                name: 'teacher.analytics',
                component: TeacherAnalytics,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'community',
                name: 'teacher.community',
                component: TeacherCommunity,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'messages',
                name: 'teacher.messages',
                component: TeacherMessages,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: 'saved',
                name: 'teacher.saved',
                component: TeacherSaved,
                meta: { requiresAuth: true, role: 'teacher' },
            },
            {
                path: '',
                redirect: '/teacher/home',
            },
        ],
    },
    // Default redirect
    {
        path: '/',
        redirect: () => {
            const role = getUserRole();
            if (role === 'student') return '/student/home';
            if (role === 'teacher') return '/teacher/home';
            return '/'; // Fallback to landing page
        },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global navigation guard
router.beforeEach((to, from, next) => {
    // Check if route requires authentication
    if (to.meta.requiresAuth) {
        if (!isAuthenticated()) {
            // Redirect to login if not authenticated
            // TODO: Update this to your actual login route
            next('/login');
            return;
        }

        // Check role if specified
        const requiredRole = to.meta.role as string | undefined;
        if (requiredRole) {
            const userRole = getUserRole();
            if (userRole !== requiredRole) {
                // Redirect to appropriate home based on role
                if (userRole === 'student') {
                    next('/student/home');
                } else if (userRole === 'teacher') {
                    next('/teacher/home');
                } else {
                    // Unknown role, redirect to login
                    next('/login');
                }
                return;
            }
        }
    }

    next();
});

export default router;

