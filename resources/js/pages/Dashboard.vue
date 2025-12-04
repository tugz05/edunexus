<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Bookmark,
    Cloud,
    FileSearch,
} from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
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

// Sample data - replace with actual data from backend
const learningResources = [
    {
        id: 1,
        title: 'Science',
        count: '44.5k',
        color: 'bg-blue-100',
        icon: FileSearch,
    },
    {
        id: 2,
        title: 'Mathematics',
        count: '66.8k',
        color: 'bg-purple-100',
        icon: FileSearch,
    },
    {
        id: 3,
        title: 'English',
        count: '38.9k',
        color: 'bg-orange-100',
        icon: FileSearch,
    },
];

const recommendedResources = [
    {
        id: 1,
        title: 'Survival of the fittest',
        subtitle: 'DepEd.JPENHS',
        downloads: '15K',
        tags: ['Grade 12', 'Science'],
    },
];
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
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Science Card -->
                        <div
                            class="rounded-2xl bg-blue-100 p-4"
                        >
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-200">
                                <FileSearch
                                    class="h-6 w-6 text-blue-700"
                                />
                            </div>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ learningResources[0].count }}
                            </p>
                            <p class="text-sm font-medium text-gray-700">
                                {{ learningResources[0].title }}
                            </p>
                        </div>

                        <!-- Mathematics Card -->
                        <div
                            class="rounded-2xl bg-[#F3E8FF] p-4"
                        >
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-purple-200">
                                <FileSearch
                                    class="h-6 w-6 text-purple-700"
                                />
                            </div>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ learningResources[1].count }}
                            </p>
                            <p class="text-sm font-medium text-gray-700">
                                {{ learningResources[1].title }}
                            </p>
                        </div>

                        <!-- English Card -->
                        <div
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
                                {{ learningResources[2].title }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recommended Resources Section -->
                <div class="px-4 pb-6">
                    <h2 class="mb-4 text-xl font-bold text-[#5B21B6]">
                        Recommended Resources
                    </h2>

                    <div class="grid gap-4">
                        <div
                            v-for="resource in recommendedResources"
                            :key="resource.id"
                            class="relative rounded-2xl bg-white p-4 shadow-sm"
                        >
                            <!-- Bookmark Icon -->
                            <button
                                class="absolute right-4 top-4 text-gray-400 transition-colors hover:text-[#5B21B6]"
                            >
                                <Bookmark class="h-5 w-5" />
                            </button>

                            <div class="flex gap-4">
                                <!-- Icon -->
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 border-purple-200 bg-purple-50"
                                >
                                    <BookOpen class="h-6 w-6 text-purple-600" />
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <h3 class="mb-1 text-base font-bold text-gray-900">
                                        {{ resource.title }}
                                    </h3>
                                    <p class="mb-2 text-sm text-gray-500">
                                        {{ resource.subtitle }}
                                    </p>

                                    <!-- Download Count -->
                                    <div class="mb-3 flex items-center gap-1 text-sm text-gray-600">
                                        <Cloud class="h-4 w-4" />
                                        <span>{{ resource.downloads }}</span>
                                    </div>

                                    <!-- Tags -->
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="tag in resource.tags"
                                            :key="tag"
                                            class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700"
                                        >
                                            {{ tag }}
                                        </span>
                                        <button
                                            class="rounded-full bg-pink-100 px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-pink-200"
                                        >
                                            Download
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation Bar (Mobile Only) -->
            <BottomNav :role="userRole" />
        </div>
    </template>

    <!-- Desktop Layout (Vue Starter Kit Layout) -->
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
                    <div class="grid grid-cols-3 gap-6">
                        <!-- Science Card -->
                        <div
                            class="rounded-xl bg-blue-100 p-6 transition-transform hover:scale-105"
                        >
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-200">
                                <FileSearch
                                    class="h-7 w-7 text-blue-700"
                                />
                            </div>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ learningResources[0].count }}
                            </p>
                            <p class="mt-2 text-base font-medium text-gray-700">
                                {{ learningResources[0].title }}
                            </p>
                        </div>

                        <!-- Mathematics Card -->
                        <div
                            class="rounded-xl bg-[#F3E8FF] p-6 transition-transform hover:scale-105"
                        >
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-200">
                                <FileSearch
                                    class="h-7 w-7 text-purple-700"
                                />
                            </div>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ learningResources[1].count }}
                            </p>
                            <p class="mt-2 text-base font-medium text-gray-700">
                                {{ learningResources[1].title }}
                            </p>
                        </div>

                        <!-- English Card -->
                        <div
                            class="rounded-xl bg-orange-100 p-6 transition-transform hover:scale-105"
                        >
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-200">
                                <FileSearch
                                    class="h-7 w-7 text-orange-700"
                                />
                            </div>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ learningResources[2].count }}
                            </p>
                            <p class="mt-2 text-base font-medium text-gray-700">
                                {{ learningResources[2].title }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recommended Resources Section -->
                <div>
                    <h2 class="mb-4 text-2xl font-bold text-[#5B21B6]">
                        Recommended Resources
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="resource in recommendedResources"
                            :key="resource.id"
                            class="relative rounded-xl bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <!-- Bookmark Icon -->
                            <button
                                class="absolute right-6 top-6 text-gray-400 transition-colors hover:text-[#5B21B6]"
                            >
                                <Bookmark class="h-6 w-6" />
                            </button>

                            <div class="flex gap-4">
                                <!-- Icon -->
                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-purple-200 bg-purple-50"
                                >
                                    <BookOpen class="h-7 w-7 text-purple-600" />
                                </div>

                                <!-- Content -->
                                <div class="flex-1">
                                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                                        {{ resource.title }}
                                    </h3>
                                    <p class="mb-2 text-base text-gray-500">
                                        {{ resource.subtitle }}
                                    </p>

                                    <!-- Download Count -->
                                    <div class="mb-3 flex items-center gap-1 text-base text-gray-600">
                                        <Cloud class="h-5 w-5" />
                                        <span>{{ resource.downloads }}</span>
                                    </div>

                                    <!-- Tags -->
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="tag in resource.tags"
                                            :key="tag"
                                            class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700"
                                        >
                                            {{ tag }}
                                        </span>
                                        <button
                                            class="rounded-full bg-pink-100 px-4 py-2 text-sm font-medium text-gray-900 transition-colors hover:bg-pink-200"
                                        >
                                            Download
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
