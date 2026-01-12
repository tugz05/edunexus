<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Button } from '@/components/ui/button';
import {
    Users,
    BookOpen,
    GraduationCap,
    UserCog,
    FileText,
    TrendingUp,
    ArrowRight,
    BarChart3,
} from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface DashboardStats {
    total_users: number;
    total_students: number;
    total_teachers: number;
    total_admins: number;
    total_content: number;
    recent_users: number;
    recent_content: number;
    content_by_type?: Record<string, number>;
    users_by_role?: Record<string, number>;
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher' | 'admin') || 'admin';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/home',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const stats = ref<DashboardStats | null>(null);

const fetchDashboardData = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/admin/dashboard', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to load dashboard data');
        }

        const result = await response.json();
        stats.value = result.data;
    } catch (err: any) {
        error.value = err.message || 'Failed to load dashboard data';
        console.error('Error fetching dashboard:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <Head title="Admin Dashboard" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        Admin Dashboard
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Overview of the platform
                    </p>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mb-4 rounded-lg bg-red-50 p-4 text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex items-center justify-center py-12"
                >
                    <div class="text-gray-500">Loading dashboard...</div>
                </div>

                <!-- Stats Grid -->
                <div
                    v-else-if="stats"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6"
                >
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_users }}</p>
                            </div>
                            <Users class="h-12 w-12 text-brand-primary opacity-20" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Students</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_students }}</p>
                            </div>
                            <GraduationCap class="h-12 w-12 text-blue-500 opacity-20" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Teachers</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_teachers }}</p>
                            </div>
                            <UserCog class="h-12 w-12 text-green-500 opacity-20" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Content</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_content }}</p>
                            </div>
                            <BookOpen class="h-12 w-12 text-purple-500 opacity-20" />
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-6">
                    <Link href="/admin/users">
                        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Manage Users</h3>
                                    <p class="mt-1 text-sm text-gray-600">View and manage all users</p>
                                </div>
                                <ArrowRight class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </Link>

                    <Link href="/admin/content">
                        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Manage Content</h3>
                                    <p class="mt-1 text-sm text-gray-600">View and manage all content</p>
                                </div>
                                <ArrowRight class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </Link>

                    <Link href="/admin/analytics">
                        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Analytics</h3>
                                    <p class="mt-1 text-sm text-gray-600">View platform analytics</p>
                                </div>
                                <BarChart3 class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Recent Activity -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Recent Activity</h2>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <TrendingUp class="h-8 w-8 text-green-500" />
                            <div>
                                <p class="font-medium text-gray-900">{{ stats.recent_users }} new users</p>
                                <p class="text-sm text-gray-600">In the last 7 days</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <FileText class="h-8 w-8 text-blue-500" />
                            <div>
                                <p class="font-medium text-gray-900">{{ stats.recent_content }} new content</p>
                                <p class="text-sm text-gray-600">In the last 7 days</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation (Mobile Only) -->
        <BottomNav :role="userRole" />
    </template>

    <!-- Desktop Layout -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="mx-auto w-full max-w-7xl">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        Admin Dashboard
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Overview of the platform
                    </p>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mb-4 rounded-lg bg-red-50 p-4 text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex items-center justify-center py-12"
                >
                    <div class="text-gray-500">Loading dashboard...</div>
                </div>

                <!-- Stats Grid -->
                <div
                    v-else-if="stats"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6"
                >
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_users }}</p>
                            </div>
                            <Users class="h-12 w-12 text-brand-primary opacity-20" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Students</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_students }}</p>
                            </div>
                            <GraduationCap class="h-12 w-12 text-blue-500 opacity-20" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Teachers</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_teachers }}</p>
                            </div>
                            <UserCog class="h-12 w-12 text-green-500 opacity-20" />
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Content</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_content }}</p>
                            </div>
                            <BookOpen class="h-12 w-12 text-purple-500 opacity-20" />
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                    <Link href="/admin/users">
                        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Manage Users</h3>
                                    <p class="mt-1 text-sm text-gray-600">View and manage all users</p>
                                </div>
                                <ArrowRight class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </Link>

                    <Link href="/admin/content">
                        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Manage Content</h3>
                                    <p class="mt-1 text-sm text-gray-600">View and manage all content</p>
                                </div>
                                <ArrowRight class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </Link>

                    <Link href="/admin/analytics">
                        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Analytics</h3>
                                    <p class="mt-1 text-sm text-gray-600">View platform analytics</p>
                                </div>
                                <BarChart3 class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Recent Activity -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Recent Activity</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-4">
                            <TrendingUp class="h-8 w-8 text-green-500" />
                            <div>
                                <p class="font-medium text-gray-900">{{ stats.recent_users }} new users</p>
                                <p class="text-sm text-gray-600">In the last 7 days</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <FileText class="h-8 w-8 text-blue-500" />
                            <div>
                                <p class="font-medium text-gray-900">{{ stats.recent_content }} new content</p>
                                <p class="text-sm text-gray-600">In the last 7 days</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
