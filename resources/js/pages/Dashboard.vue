<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Bookmark,
    Cloud,
    FileSearch,
    Video,
    FileText,
    Link as LinkIcon,
    HelpCircle,
} from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, onMounted } from 'vue';
import { getInitials } from '@/composables/useInitials';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isMobile = useMediaQuery('(max-width: 768px)');

// Get user role for bottom navigation
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const learningResources = ref<Array<{ subject: string; count: number }>>([]);
const recommendedResources = ref<Array<{
    id: number;
    title: string;
    description: string | null;
    subject: string;
    difficulty: string;
    type: string;
    tags: string[];
    creator: string;
}>>([]);

const typeIcons = {
    video: Video,
    pdf: FileText,
    link: LinkIcon,
    quiz: HelpCircle,
};

const getTypeIcon = (type: string) => {
    return typeIcons[type as keyof typeof typeIcons] || FileText;
};

const fetchDashboardData = async () => {
    if (userRole.value !== 'student') {
        return; // Only fetch for students
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/student/dashboard', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to load dashboard data');
        }

        const result = await response.json();
        
        // Format subject counts
        learningResources.value = result.data.subject_counts.map((item: any) => ({
            subject: item.subject,
            count: item.count,
        }));

        // Format recommended content
        recommendedResources.value = result.data.recommended_content || [];
    } catch (err: any) {
        error.value = err.message || 'Failed to load dashboard data';
        console.error('Error fetching dashboard data:', err);
    } finally {
        loading.value = false;
    }
};

const handleSearchNow = () => {
    router.visit('/student/content');
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <Head title="Dashboard" />

    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <!-- Container for Desktop -->
            <div class="mx-auto max-w-7xl">
                <!-- Top Section -->
                <div class="bg-white px-4 pt-4 pb-6">
                    <!-- Header with Greeting -->
                    <div class="mb-4">
                        <h1 class="text-2xl font-bold text-[#5B21B6]">
                            Hello
                        </h1>
                        <p class="text-2xl font-bold text-[#5B21B6]">
                            {{ user?.name || 'User' }}.
                        </p>
                    </div>

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
                        <!-- Decorative Image Placeholder (Right Side) -->
                        <div
                            class="absolute -right-4 top-0 h-full w-24 rounded-full bg-blue-400 opacity-30"
                        ></div>
                    </div>
                </div>

                <!-- Find Learning Resources Section -->
                <div class="px-4 py-6">
                    <h2 class="mb-4 text-xl font-bold text-[#5B21B6]">
                        Find Learning Resources
                    </h2>
                    <div
                        v-if="loading"
                        class="text-center text-gray-500"
                    >
                        Loading...
                    </div>
                    <div
                        v-else-if="learningResources.length > 0"
                        class="grid grid-cols-2 gap-4"
                    >
                        <!-- Dynamic Subject Cards -->
                        <div
                            v-for="(resource, index) in learningResources.slice(0, 2)"
                            :key="resource.subject"
                            :class="[
                                index === 0 ? 'bg-blue-100' : 'bg-[#F3E8FF]',
                            ]"
                            class="rounded-2xl p-4"
                        >
                            <div
                                :class="[
                                    index === 0 ? 'bg-blue-200' : 'bg-purple-200',
                                ]"
                                class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg"
                            >
                                <FileSearch
                                    :class="[
                                        index === 0 ? 'text-blue-700' : 'text-purple-700',
                                    ]"
                                    class="h-6 w-6"
                                />
                            </div>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ resource.count }}
                            </p>
                            <p class="text-sm font-medium text-gray-700">
                                {{ resource.subject }}
                            </p>
                        </div>

                        <!-- Third Card (Full Width) -->
                        <div
                            v-if="learningResources.length > 2"
                            class="col-span-2 rounded-2xl bg-orange-100 p-4"
                        >
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-orange-200">
                                <FileSearch
                                    class="h-6 w-6 text-orange-700"
                                />
                            </div>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ learningResources[2].count }}
                            </p>
                            <p class="text-sm font-medium text-gray-700">
                                {{ learningResources[2].subject }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center text-gray-500"
                    >
                        No content available yet
                    </div>
                </div>

                <!-- Recommended Resources Section -->
                <div class="px-4 pb-6">
                    <h2 class="mb-4 text-xl font-bold text-[#5B21B6]">
                        Recommended Resources
                    </h2>

                    <div
                        v-if="loading"
                        class="text-center text-gray-500"
                    >
                        Loading recommendations...
                    </div>
                    <div
                        v-else-if="recommendedResources.length > 0"
                        class="grid gap-4"
                    >
                        <Link
                            v-for="resource in recommendedResources"
                            :key="resource.id"
                            :href="`/student/content/${resource.id}`"
                            class="relative block rounded-2xl bg-white p-4 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div class="flex gap-4">
                                <!-- Icon -->
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 border-purple-200 bg-purple-50"
                                >
                                    <component
                                        :is="getTypeIcon(resource.type)"
                                        class="h-6 w-6 text-purple-600"
                                    />
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <h3 class="mb-1 text-base font-bold text-gray-900">
                                        {{ resource.title }}
                                    </h3>
                                    <p class="mb-2 text-sm text-gray-500">
                                        {{ resource.creator }}
                                    </p>

                                    <!-- Subject and Difficulty -->
                                    <div class="mb-3 flex items-center gap-1 text-sm text-gray-600">
                                        <span>{{ resource.subject }}</span>
                                        <span>•</span>
                                        <span>{{ resource.difficulty }}</span>
                                    </div>

                                    <!-- Tags -->
                                    <div
                                        v-if="resource.tags && resource.tags.length > 0"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <span
                                            v-for="tag in resource.tags.slice(0, 2)"
                                            :key="tag"
                                            class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div
                        v-else
                        class="text-center text-gray-500"
                    >
                        No recommendations available yet
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation Bar (Mobile Only) -->
            <BottomNav :role="userRole" />
        </div>
    </template>

    <!-- Desktop Layout -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6"
        >
            <!-- Desktop Dashboard Content -->
            <div class="space-y-6">
                <!-- Welcome Section -->
                <div class="flex items-center justify-between rounded-xl bg-white p-6 shadow-sm">
                    <div>
                        <h1 class="text-3xl font-bold text-[#5B21B6]">
                            Hello, {{ user?.name || 'User' }}
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Welcome back to EduNexus. Discover personalized learning resources powered by AI.
                        </p>
                    </div>
                    <Avatar class="hidden h-16 w-16 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="user?.avatar"
                            :src="user.avatar"
                            :alt="user?.name"
                        />
                        <AvatarFallback class="rounded-full bg-[#5B21B6] text-white">
                            {{ getInitials(user?.name || 'U') }}
                        </AvatarFallback>
                    </Avatar>
                </div>

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

                <!-- Find Learning Resources Section -->
                <div>
                    <h2 class="mb-4 text-2xl font-bold text-[#5B21B6]">
                        Find Learning Resources
                    </h2>
                    <div
                        v-if="loading"
                        class="text-center text-gray-500"
                    >
                        Loading...
                    </div>
                    <div
                        v-else-if="learningResources.length > 0"
                        class="grid grid-cols-3 gap-6"
                    >
                        <!-- Dynamic Subject Cards -->
                        <div
                            v-for="(resource, index) in learningResources"
                            :key="resource.subject"
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
                                {{ resource.count }}
                            </p>
                            <p class="mt-2 text-base font-medium text-gray-700">
                                {{ resource.subject }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center text-gray-500"
                    >
                        No content available yet
                    </div>
                </div>

                <!-- Recommended Resources Section -->
                <div>
                    <h2 class="mb-4 text-2xl font-bold text-[#5B21B6]">
                        Recommended Resources
                    </h2>
                    <div
                        v-if="loading"
                        class="text-center text-gray-500"
                    >
                        Loading recommendations...
                    </div>
                    <div
                        v-else-if="recommendedResources.length > 0"
                        class="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <Link
                            v-for="resource in recommendedResources"
                            :key="resource.id"
                            :href="`/student/content/${resource.id}`"
                            class="relative block rounded-xl bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div class="flex gap-4">
                                <!-- Icon -->
                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-purple-200 bg-purple-50"
                                >
                                    <component
                                        :is="getTypeIcon(resource.type)"
                                        class="h-7 w-7 text-purple-600"
                                    />
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                                        {{ resource.title }}
                                    </h3>
                                    <p class="mb-2 text-base text-gray-500">
                                        {{ resource.creator }}
                                    </p>

                                    <!-- Subject and Difficulty -->
                                    <div class="mb-3 flex items-center gap-1 text-base text-gray-600">
                                        <span>{{ resource.subject }}</span>
                                        <span>•</span>
                                        <span>{{ resource.difficulty }}</span>
                                    </div>

                                    <!-- Tags -->
                                    <div
                                        v-if="resource.tags && resource.tags.length > 0"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <span
                                            v-for="tag in resource.tags.slice(0, 3)"
                                            :key="tag"
                                            class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div
                        v-else
                        class="text-center text-gray-500"
                    >
                        No recommendations available yet
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
