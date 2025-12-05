<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { BarChart3, Eye, Bookmark, CheckCircle, FileText } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface TopContentItem {
    item: {
        id: number;
        title: string;
        subject: string;
        difficulty: string;
        type: string;
    };
    views: number;
    saves: number;
    completions: number;
    total_interactions: number;
}

interface AnalyticsData {
    total_content: number;
    total_views: number;
    total_saves: number;
    total_completions: number;
    top_content: TopContentItem[];
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'teacher';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics Dashboard',
        href: '/teacher/analytics',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const analytics = ref<AnalyticsData | null>(null);

const fetchAnalytics = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/teacher/analytics', {
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
        analytics.value = result.data;
    } catch (err: any) {
        error.value = err.message || 'Failed to load analytics';
        console.error('Error fetching analytics:', err);
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
            <!-- Header -->
            <div class="mb-6">
                <div class="mb-4 flex items-center gap-3">
                    <BarChart3 class="h-8 w-8 text-brand-primary" />
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        Analytics Dashboard
                    </h1>
                </div>
                <p class="text-gray-600">
                    Track your content performance and student engagement
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
                <div class="text-gray-500">Loading analytics...</div>
            </div>

            <!-- Analytics Content -->
            <div
                v-else-if="analytics"
                class="space-y-6"
            >
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Content -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Total Content
                                </p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ analytics.total_content }}
                                </p>
                            </div>
                            <div class="rounded-full bg-brand-primary-light p-3">
                                <FileText class="h-6 w-6 text-brand-primary" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Views -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Total Views
                                </p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ analytics.total_views }}
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3">
                                <Eye class="h-6 w-6 text-blue-600" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Saves -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Total Saves
                                </p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ analytics.total_saves }}
                                </p>
                            </div>
                            <div class="rounded-full bg-brand-secondary-light p-3">
                                <Bookmark class="h-6 w-6 text-brand-secondary" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Completions -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Completions
                                </p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ analytics.total_completions }}
                                </p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3">
                                <CheckCircle class="h-6 w-6 text-green-600" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Content Table -->
                <div class="rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Top Performing Content
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Content
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Subject
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Views
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Saves
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Completions
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="(content, index) in analytics.top_content"
                                    :key="content.item.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary-light text-sm font-semibold text-brand-primary">
                                                {{ index + 1 }}
                                            </span>
                                            <div>
                                                <Link
                                                    :href="`/teacher/content/${content.item.id}/edit`"
                                                    class="text-sm font-medium text-gray-900 hover:text-brand-primary"
                                                >
                                                    {{ content.item.title }}
                                                </Link>
                                                <p class="text-xs text-gray-500 capitalize">
                                                    {{ content.item.type }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ content.item.subject }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-600">
                                        {{ content.views }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-600">
                                        {{ content.saves }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-600">
                                        {{ content.completions }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="rounded-full bg-brand-primary-light px-3 py-1 text-sm font-semibold text-brand-primary">
                                            {{ content.total_interactions }}
                                        </span>
                                    </td>
                                </tr>
                                <tr
                                    v-if="analytics.top_content.length === 0"
                                >
                                    <td
                                        colspan="6"
                                        class="px-6 py-8 text-center text-gray-500"
                                    >
                                        No content data available yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Bottom Navigation (Mobile Only) -->
        <BottomNav
            v-if="isMobile"
            :role="userRole"
        />
    </template>

    <!-- Desktop Layout -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="mx-auto w-full max-w-7xl">
                <!-- Header -->
                <div class="mb-6">
                    <div class="mb-4 flex items-center gap-3">
                        <BarChart3 class="h-8 w-8 text-brand-primary" />
                        <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                            Analytics Dashboard
                        </h1>
                    </div>
                    <p class="text-gray-600">
                        Track your content performance and student engagement
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
                    <div class="text-gray-500">Loading analytics...</div>
                </div>

                <!-- Analytics Content -->
                <div
                    v-else-if="analytics"
                    class="space-y-6"
                >
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Total Content -->
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Total Content
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ analytics.total_content }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-brand-primary-light p-3">
                                    <FileText class="h-6 w-6 text-brand-primary" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Views -->
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Total Views
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ analytics.total_views }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-blue-100 p-3">
                                    <Eye class="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Saves -->
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Total Saves
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ analytics.total_saves }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-brand-secondary-light p-3">
                                    <Bookmark class="h-6 w-6 text-brand-secondary" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Completions -->
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">
                                        Completions
                                    </p>
                                    <p class="mt-2 text-3xl font-bold text-gray-900">
                                        {{ analytics.total_completions }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-green-100 p-3">
                                    <CheckCircle class="h-6 w-6 text-green-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Content Table -->
                    <div class="rounded-lg bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Top Performing Content
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Content
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Subject
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Views
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Saves
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Completions
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="(content, index) in analytics.top_content"
                                        :key="content.item.id"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary-light text-sm font-semibold text-brand-primary">
                                                    {{ index + 1 }}
                                                </span>
                                                <div>
                                                    <Link
                                                        :href="`/teacher/content/${content.item.id}/edit`"
                                                        class="text-sm font-medium text-gray-900 hover:text-brand-primary"
                                                    >
                                                        {{ content.item.title }}
                                                    </Link>
                                                    <p class="text-xs text-gray-500 capitalize">
                                                        {{ content.item.type }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ content.item.subject }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-600">
                                            {{ content.views }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-600">
                                            {{ content.saves }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-600">
                                            {{ content.completions }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="rounded-full bg-brand-primary-light px-3 py-1 text-sm font-semibold text-brand-primary">
                                                {{ content.total_interactions }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="analytics.top_content.length === 0"
                                    >
                                        <td
                                            colspan="6"
                                            class="px-6 py-8 text-center text-gray-500"
                                        >
                                            No content data available yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
