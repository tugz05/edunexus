<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Button } from '@/components/ui/button';
import {
    FileText,
    Plus,
    Eye,
    Bookmark,
    CheckCircle,
    BarChart3,
    ArrowRight,
    TrendingUp,
    FileSearch,
} from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface ContentItem {
    id: number;
    title: string;
    description: string;
    type: string;
    subject: string;
    difficulty: string;
    created_at: string;
    tags?: Array<{ id: number; name: string }>;
}

interface DashboardStats {
    total_content: number;
    total_views: number;
    total_saves: number;
    total_completions: number;
    subject_counts?: Array<{ subject: string; count: number }>;
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'teacher';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/teacher/home',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const stats = ref<DashboardStats | null>(null);
const recentContent = ref<ContentItem[]>([]);
const subjectCounts = ref<Array<{ subject: string; count: number }>>([]);

const fetchDashboardData = async () => {
    loading.value = true;
    error.value = null;

    try {
        // Fetch dashboard stats (includes subject counts)
        const dashboardResponse = await fetch('/api/teacher/dashboard', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (dashboardResponse.ok) {
            const dashboardData = await dashboardResponse.json();
            
            stats.value = {
                total_content: dashboardData.data?.total_content || 0,
                total_views: dashboardData.data?.total_views || 0,
                total_saves: dashboardData.data?.total_saves || 0,
                total_completions: dashboardData.data?.total_completions || 0,
            };
            
            // Ensure subject_counts is an array
            const counts = dashboardData.data?.subject_counts;
            if (counts && Array.isArray(counts) && counts.length > 0) {
                subjectCounts.value = counts;
            } else {
                subjectCounts.value = [];
            }
        } else {
            const errorData = await dashboardResponse.json().catch(() => ({}));
            error.value = errorData.message || 'Failed to load dashboard data';
        }

        // Fetch recent content (last 5 items)
        const contentResponse = await fetch('/api/teacher/content?per_page=5', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (contentResponse.ok) {
            const contentData = await contentResponse.json();
            recentContent.value = contentData.data || [];
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load dashboard data';
    } finally {
        loading.value = false;
    }
};

const handleSearchNow = () => {
    router.visit('/teacher/content');
};

const getTypeIcon = (type: string) => {
    return FileText; // Default icon
};


onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <Head title="Teacher Dashboard" />

    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="mb-2 text-2xl font-bold text-brand-primary">
                        Welcome back, {{ user?.name || 'Teacher' }}!
                    </h1>
                    <p class="text-gray-600">
                        Manage your content and track student engagement
                    </p>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mb-6 rounded-lg bg-red-50 p-4 text-red-800"
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

                <!-- Dashboard Content -->
                <div
                    v-else
                    class="space-y-6"
                >
                    <!-- Promotional Banner -->
                    <div
                        class="relative overflow-hidden rounded-2xl bg-[#1E3A8A] p-6"
                    >
                        <div class="relative z-10 max-w-[60%]">
                            <p class="mb-4 text-lg font-semibold text-white">
                                We'll make you find learning resources.
                            </p>
                            <Button
                                @click="handleSearchNow"
                                class="rounded-lg bg-[#F97316] px-6 py-2 text-sm font-semibold text-white hover:bg-[#EA580C]"
                            >
                                Search Now
                            </Button>
                        </div>
                        <div
                            class="absolute -right-4 top-0 h-full w-24 rounded-full bg-blue-400 opacity-30"
                        ></div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">
                            Quick Actions
                        </h2>
                        <div class="grid grid-cols-2 gap-3">
                            <Link href="/teacher/content/create">
                                <Button class="w-full bg-brand-primary hover:bg-brand-primary-hover">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Create Content
                                </Button>
                            </Link>
                            <Link href="/teacher/analytics">
                                <Button
                                    variant="outline"
                                    class="w-full"
                                >
                                    <BarChart3 class="mr-2 h-4 w-4" />
                                    View Analytics
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Subject Counts -->
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">
                            Content by Subject
                        </h2>
                        <div
                            v-if="subjectCounts && subjectCounts.length > 0"
                            class="grid grid-cols-2 gap-4"
                        >
                            <div
                                v-for="(subject, index) in subjectCounts.slice(0, 3)"
                                :key="`subject-${subject.subject}-${index}`"
                                :class="[
                                    index === 0 ? 'bg-blue-100' : index === 1 ? 'bg-[#F3E8FF]' : 'bg-orange-100',
                                ]"
                                class="rounded-2xl p-4"
                            >
                                <div
                                    :class="[
                                        index === 0 ? 'bg-blue-200' : index === 1 ? 'bg-purple-200' : 'bg-orange-200',
                                    ]"
                                    class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg"
                                >
                                    <FileSearch
                                        :class="[
                                            index === 0 ? 'text-blue-700' : index === 1 ? 'text-purple-700' : 'text-orange-700',
                                        ]"
                                        class="h-6 w-6"
                                    />
                                </div>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ subject.count }}
                                </p>
                                <p class="text-sm font-medium text-gray-700">
                                    {{ subject.subject }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-else-if="!loading"
                            class="py-4 text-center text-gray-500"
                        >
                            <FileSearch class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                            <p>No content by subject yet</p>
                            <p class="mt-2 text-xs text-gray-400">
                                Total content: {{ stats?.total_content || 0 }}
                            </p>
                        </div>
                        <div
                            v-else
                            class="py-4 text-center text-gray-400"
                        >
                            Loading...
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Total Content -->
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-600">
                                        Total Content
                                    </p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ stats?.total_content || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-brand-primary-light p-2">
                                    <FileText class="h-5 w-5 text-brand-primary" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Views -->
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-600">
                                        Total Views
                                    </p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ stats?.total_views || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-blue-100 p-2">
                                    <Eye class="h-5 w-5 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Saves -->
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-600">
                                        Total Saves
                                    </p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ stats?.total_saves || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-brand-secondary-light p-2">
                                    <Bookmark class="h-5 w-5 text-brand-secondary" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Completions -->
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-600">
                                        Completions
                                    </p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">
                                        {{ stats?.total_completions || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-green-100 p-2">
                                    <CheckCircle class="h-5 w-5 text-green-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Content -->
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Recent Content
                            </h2>
                            <Link
                                href="/teacher/content"
                                class="text-sm text-brand-primary hover:underline"
                            >
                                View All
                            </Link>
                        </div>

                        <div
                            v-if="recentContent.length === 0"
                            class="py-8 text-center text-gray-500"
                        >
                            <FileText class="mx-auto mb-2 h-12 w-12 text-gray-400" />
                            <p>No content yet</p>
                            <Link href="/teacher/content/create">
                                <Button
                                    variant="outline"
                                    class="mt-4"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Create Your First Content
                                </Button>
                            </Link>
                        </div>

                        <div
                            v-else
                            class="space-y-3"
                        >
                            <Link
                                v-for="item in recentContent"
                                :key="item.id"
                                :href="`/teacher/content/${item.id}/edit`"
                                class="block rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-primary hover:bg-brand-primary-light"
                            >
                                <div class="flex items-start gap-3">
                                    <component
                                        :is="getTypeIcon(item.type)"
                                        class="h-5 w-5 shrink-0 text-brand-primary"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <h3 class="mb-1 text-sm font-semibold text-gray-900">
                                            {{ item.title }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <span>{{ item.subject }}</span>
                                            <span>•</span>
                                            <span>{{ item.difficulty }}</span>
                                            <span>•</span>
                                            <span class="capitalize">{{ item.type }}</span>
                                        </div>
                                    </div>
                                    <ArrowRight class="h-4 w-4 shrink-0 text-gray-400" />
                                </div>
                            </Link>
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
                    <h1 class="mb-2 text-3xl font-bold text-brand-primary">
                        Welcome back, {{ user?.name || 'Teacher' }}!
                    </h1>
                    <p class="text-gray-600">
                        Manage your content and track student engagement
                    </p>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mb-6 rounded-lg bg-red-50 p-4 text-red-800"
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

                <!-- Dashboard Content -->
                <div
                    v-else
                    class="space-y-6"
                >
                    <!-- Promotional Banner -->
                    <div
                        class="relative overflow-hidden rounded-xl bg-[#1E3A8A] p-8"
                    >
                        <div class="relative z-10 max-w-2xl">
                            <p class="mb-4 text-2xl font-semibold text-white">
                                We'll make you find learning resources.
                            </p>
                            <Button
                                @click="handleSearchNow"
                                class="rounded-lg bg-[#F97316] px-8 py-3 text-base font-semibold text-white hover:bg-[#EA580C]"
                            >
                                Search Now
                            </Button>
                        </div>
                        <div
                            class="absolute -right-8 top-0 h-full w-40 rounded-full bg-blue-400 opacity-30"
                        ></div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex gap-4">
                        <Link href="/teacher/content/create">
                            <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                                <Plus class="mr-2 h-4 w-4" />
                                Create New Content
                            </Button>
                        </Link>
                        <Link href="/teacher/analytics">
                            <Button variant="outline">
                                <BarChart3 class="mr-2 h-4 w-4" />
                                View Analytics
                            </Button>
                        </Link>
                        <Link href="/teacher/content">
                            <Button variant="outline">
                                <FileText class="mr-2 h-4 w-4" />
                                Manage Content
                            </Button>
                        </Link>
                    </div>

                    <!-- Subject Counts -->
                    <div>
                        <h2 class="mb-4 text-2xl font-bold text-[#5B21B6]">
                            Content by Subject
                        </h2>
                        <div
                            v-if="subjectCounts && subjectCounts.length > 0"
                            class="grid grid-cols-3 gap-6"
                        >
                            <div
                                v-for="(subject, index) in subjectCounts.slice(0, 3)"
                                :key="`subject-${subject.subject}-${index}`"
                                :class="[
                                    index === 0 ? 'bg-blue-100' : index === 1 ? 'bg-[#F3E8FF]' : 'bg-orange-100',
                                ]"
                                class="rounded-xl p-6 transition-transform hover:scale-105"
                            >
                                <div
                                    :class="[
                                        index === 0 ? 'bg-blue-200' : index === 1 ? 'bg-purple-200' : 'bg-orange-200',
                                    ]"
                                    class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg"
                                >
                                    <FileSearch
                                        :class="[
                                            index === 0 ? 'text-blue-700' : index === 1 ? 'text-purple-700' : 'text-orange-700',
                                        ]"
                                        class="h-7 w-7"
                                    />
                                </div>
                                <p class="text-3xl font-bold text-gray-900">
                                    {{ subject.count }}
                                </p>
                                <p class="mt-2 text-base font-medium text-gray-700">
                                    {{ subject.subject }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-else-if="!loading"
                            class="rounded-xl bg-white p-8 text-center shadow-sm"
                        >
                            <FileSearch class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                            <p class="text-gray-500">No content by subject yet</p>
                            <p class="mt-2 text-sm text-gray-400">
                                Total content: {{ stats?.total_content || 0 }}
                            </p>
                        </div>
                        <div
                            v-else
                            class="rounded-xl bg-white p-8 text-center shadow-sm"
                        >
                            <p class="text-gray-400">Loading...</p>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Total Content -->
                        <div class="rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Total Content
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ stats?.total_content || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-brand-primary-light p-3">
                                    <FileText class="h-6 w-6 text-brand-primary" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Views -->
                        <div class="rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Total Views
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ stats?.total_views || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-blue-100 p-3">
                                    <Eye class="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Saves -->
                        <div class="rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Total Saves
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ stats?.total_saves || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-brand-secondary-light p-3">
                                    <Bookmark class="h-6 w-6 text-brand-secondary" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Completions -->
                        <div class="rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Completions
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ stats?.total_completions || 0 }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-green-100 p-3">
                                    <CheckCircle class="h-6 w-6 text-green-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Content -->
                    <div class="rounded-lg bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Recent Content
                                </h2>
                                <Link
                                    href="/teacher/content"
                                    class="flex items-center gap-2 text-sm text-brand-primary hover:underline"
                                >
                                    View All
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                            </div>
                        </div>

                        <div class="p-6">
                            <div
                                v-if="recentContent.length === 0"
                                class="py-12 text-center"
                            >
                                <FileText class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                                <p class="mb-2 text-lg font-medium text-gray-900">
                                    No content yet
                                </p>
                                <p class="mb-6 text-gray-600">
                                    Get started by creating your first learning resource
                                </p>
                                <Link href="/teacher/content/create">
                                    <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Create Your First Content
                                    </Button>
                                </Link>
                            </div>

                            <div
                                v-else
                                class="space-y-4"
                            >
                                <Link
                                    v-for="item in recentContent"
                                    :key="item.id"
                                    :href="`/teacher/content/${item.id}/edit`"
                                    class="block rounded-lg border border-gray-200 p-4 transition-all hover:border-brand-primary hover:bg-brand-primary-light hover:shadow-sm"
                                >
                                    <div class="flex items-start gap-4">
                                        <component
                                            :is="getTypeIcon(item.type)"
                                            class="h-6 w-6 shrink-0 text-brand-primary"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <h3 class="mb-1 text-base font-semibold text-gray-900">
                                                {{ item.title }}
                                            </h3>
                                            <p
                                                v-if="item.description"
                                                class="mb-2 line-clamp-2 text-sm text-gray-600"
                                            >
                                                {{ item.description }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                                <span class="font-medium">{{ item.subject }}</span>
                                                <span>•</span>
                                                <span>{{ item.difficulty }}</span>
                                                <span>•</span>
                                                <span class="capitalize">{{ item.type }}</span>
                                            </div>
                                        </div>
                                        <ArrowRight class="h-5 w-5 shrink-0 text-gray-400" />
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
