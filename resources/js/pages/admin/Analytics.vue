<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { BarChart3, TrendingUp, Users, BookOpen, GraduationCap, UserCog } from 'lucide-vue-next';
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
    { title: 'Admin Dashboard', href: '/admin/home' },
    { title: 'Analytics', href: '/admin/analytics' },
];

const loading = ref(false);
const error = ref<string | null>(null);
const stats = ref<DashboardStats | null>(null);

const fetchAnalytics = async () => {
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
            throw new Error(errorData.message || 'Failed to load analytics');
        }

        const result = await response.json();
        stats.value = result.data;
    } catch (err: any) {
        error.value = err.message || 'Failed to load analytics';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAnalytics();
});
</script>

<template>
    <Head title="Analytics" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-brand-primary">Analytics</h1>
                    <p class="mt-2 text-gray-600">Platform statistics and insights</p>
                </div>

                <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">{{ error }}</div>
                <div v-if="loading" class="text-center py-12 text-gray-500">Loading...</div>

                <div v-else-if="stats" class="space-y-6">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ stats.total_users }}</p>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <p class="text-sm text-gray-600">Total Content</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ stats.total_content }}</p>
                        </div>
                    </div>

                    <!-- User Breakdown -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Users by Role</h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <GraduationCap class="h-5 w-5 text-blue-500" />
                                    <span class="text-gray-700">Students</span>
                                </div>
                                <span class="font-semibold text-gray-900">{{ stats.total_students }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <UserCog class="h-5 w-5 text-green-500" />
                                    <span class="text-gray-700">Teachers</span>
                                </div>
                                <span class="font-semibold text-gray-900">{{ stats.total_teachers }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Users class="h-5 w-5 text-purple-500" />
                                    <span class="text-gray-700">Admins</span>
                                </div>
                                <span class="font-semibold text-gray-900">{{ stats.total_admins }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Recent Activity (7 days)</h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">New Users</span>
                                <span class="font-semibold text-gray-900">{{ stats.recent_users }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">New Content</span>
                                <span class="font-semibold text-gray-900">{{ stats.recent_content }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <BottomNav :role="userRole" />
    </template>

    <!-- Desktop Layout -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="mx-auto w-full max-w-7xl">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-brand-primary">Analytics</h1>
                    <p class="mt-2 text-gray-600">Platform statistics and insights</p>
                </div>

                <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">{{ error }}</div>
                <div v-if="loading" class="text-center py-12 text-gray-500">Loading...</div>

                <div v-else-if="stats" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Stats Overview -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Overview</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Total Users</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_users }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total Content</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_content }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- User Breakdown -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Users by Role</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <GraduationCap class="h-6 w-6 text-blue-500" />
                                    <span class="text-gray-700">Students</span>
                                </div>
                                <span class="text-2xl font-bold text-gray-900">{{ stats.total_students }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <UserCog class="h-6 w-6 text-green-500" />
                                    <span class="text-gray-700">Teachers</span>
                                </div>
                                <span class="text-2xl font-bold text-gray-900">{{ stats.total_teachers }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <Users class="h-6 w-6 text-purple-500" />
                                    <span class="text-gray-700">Admins</span>
                                </div>
                                <span class="text-2xl font-bold text-gray-900">{{ stats.total_admins }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Recent Activity (7 days)</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <TrendingUp class="h-6 w-6 text-green-500" />
                                    <span class="text-gray-700">New Users</span>
                                </div>
                                <span class="text-2xl font-bold text-gray-900">{{ stats.recent_users }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <BookOpen class="h-6 w-6 text-blue-500" />
                                    <span class="text-gray-700">New Content</span>
                                </div>
                                <span class="text-2xl font-bold text-gray-900">{{ stats.recent_content }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Content by Type -->
                    <div v-if="stats.content_by_type" class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Content by Type</h2>
                        <div class="space-y-3">
                            <div
                                v-for="(count, type) in stats.content_by_type"
                                :key="type"
                                class="flex items-center justify-between"
                            >
                                <span class="capitalize text-gray-700">{{ type }}</span>
                                <span class="font-semibold text-gray-900">{{ count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
