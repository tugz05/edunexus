<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Sparkles, FileText, Video, Link as LinkIcon, HelpCircle, BookOpen, Bookmark } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface Recommendation {
    item: {
        id: number;
        title: string;
        description: string | null;
        type: 'video' | 'pdf' | 'link' | 'quiz';
        url: string;
        subject: string;
        difficulty: string;
        tags?: Array<{ id: number; name: string }>;
        creator?: {
            id: number;
            name: string;
        };
    };
    reason: string;
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'AI Recommendations',
        href: '/student/recommendations',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const recommendations = ref<Recommendation[]>([]);
const savedIds = ref<Set<number>>(new Set());
const togglingBookmarks = ref<Set<number>>(new Set());

const userPreferences = computed(() => {
    return user.value?.learning_preference || null;
});

const typeIcons = {
    video: Video,
    pdf: FileText,
    link: LinkIcon,
    quiz: HelpCircle,
};

const getTypeIcon = (type: string) => {
    return typeIcons[type as keyof typeof typeIcons] || FileText;
};

const fetchRecommendations = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/student/recommendations', {
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
            throw new Error(errorData.message || 'Failed to load recommendations');
        }

        const result = await response.json();
        recommendations.value = result.data || [];

        // Log if no recommendations
        if (recommendations.value.length === 0) {
            console.warn('No recommendations returned from API');
        }

        // Fetch saved content IDs
        await fetchSavedIds();
    } catch (err: any) {
        error.value = err.message || 'Failed to load recommendations';
        console.error('Error fetching recommendations:', err);
    } finally {
        loading.value = false;
    }
};

const fetchSavedIds = async () => {
    try {
        const response = await fetch('/api/student/saved', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const result = await response.json();
            savedIds.value = new Set(result.data.map((item: any) => item.id));
        }
    } catch (err) {
        console.error('Error fetching saved IDs:', err);
    }
};

const handleBookmark = async (event: Event, itemId: number) => {
    event.preventDefault();
    event.stopPropagation();

    if (togglingBookmarks.value.has(itemId)) {
        return;
    }

    togglingBookmarks.value.add(itemId);
    const isSaved = savedIds.value.has(itemId);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const method = isSaved ? 'DELETE' : 'POST';
        const url = `/api/student/saved/${itemId}`;

        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to update bookmark');
        }

        if (isSaved) {
            savedIds.value.delete(itemId);
        } else {
            savedIds.value.add(itemId);
        }
    } catch (err: any) {
        console.error('Error toggling bookmark:', err);
    } finally {
        togglingBookmarks.value.delete(itemId);
    }
};

onMounted(() => {
    fetchRecommendations();
});
</script>

<template>
    <Head title="AI Recommendations" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="mb-4 flex items-center gap-3">
                    <Sparkles class="h-8 w-8 text-brand-primary" />
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        AI Recommendations
                    </h1>
                </div>
                <p class="text-gray-600">
                    Personalized learning resources tailored to your preferences
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
                <div class="text-center">
                    <Sparkles class="mx-auto mb-4 h-12 w-12 animate-pulse text-brand-primary" />
                    <p class="text-gray-500">Analyzing your preferences and finding the best content for you...</p>
                </div>
            </div>

            <!-- Recommendations Grid -->
            <div
                v-else-if="recommendations.length > 0"
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="rec in recommendations"
                    :key="rec.item.id"
                    class="group relative rounded-lg bg-white p-6 shadow-sm transition-all hover:shadow-md"
                >
                    <!-- Bookmark Button -->
                    <button
                        @click="handleBookmark($event, rec.item.id)"
                        class="absolute right-4 top-4 z-10 rounded-full bg-white p-2 shadow-sm transition-colors hover:bg-gray-50"
                        :class="savedIds.has(rec.item.id) ? 'text-brand-secondary' : 'text-gray-400'"
                        :disabled="togglingBookmarks.has(rec.item.id)"
                    >
                        <Bookmark
                            class="h-5 w-5"
                            :class="savedIds.has(rec.item.id) ? 'fill-current' : ''"
                        />
                    </button>

                    <Link
                        :href="`/student/content/${rec.item.id}`"
                        class="block"
                    >
                        <!-- Header with Icon -->
                        <div class="mb-4 flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-primary-light">
                                <component
                                    :is="getTypeIcon(rec.item.type)"
                                    class="h-6 w-6 text-brand-primary"
                                />
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-2 text-lg font-semibold text-gray-900 group-hover:text-brand-primary">
                                    {{ rec.item.title }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                    <span class="font-medium">{{ rec.item.subject }}</span>
                                    <span>•</span>
                                    <span>{{ rec.item.difficulty }}</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ rec.item.type }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p
                            v-if="rec.item.description"
                            class="mb-4 line-clamp-2 text-sm text-gray-600"
                        >
                            {{ rec.item.description }}
                        </p>

                        <!-- Reason Badge -->
                        <div class="mb-4 rounded-lg bg-brand-secondary-light p-3">
                            <div class="flex items-start gap-2">
                                <Sparkles class="h-4 w-4 shrink-0 text-brand-secondary mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1">Why this recommendation</p>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ rec.reason }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div
                            v-if="rec.item.tags && rec.item.tags.length > 0"
                            class="flex flex-wrap gap-2"
                        >
                            <span
                                v-for="tag in rec.item.tags.slice(0, 3)"
                                :key="tag.id"
                                class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="rounded-lg bg-white p-12 text-center"
            >
                <Sparkles class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">
                    No recommendations available
                </h3>
                <p class="mb-6 text-gray-600">
                    <span v-if="!userPreferences">
                        Complete your learning preferences to get personalized recommendations.
                    </span>
                    <span v-else>
                        There are currently no content items available that match your preferences. Please check back later or contact your teacher to add more content.
                    </span>
                </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link v-if="!userPreferences" href="/student/profile">
                        <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                            <BookOpen class="mr-2 h-4 w-4" />
                            Set Learning Preferences
                        </Button>
                    </Link>
                    <Link href="/student/content">
                        <Button variant="outline">
                            <BookOpen class="mr-2 h-4 w-4" />
                            Browse Content Library
                        </Button>
                    </Link>
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
                    <div class="mb-4 flex items-center gap-3">
                        <Sparkles class="h-8 w-8 text-brand-primary" />
                        <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                            AI Recommendations
                        </h1>
                    </div>
                    <p class="text-gray-600">
                        Personalized learning resources tailored to your preferences
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
                    <div class="text-center">
                        <Sparkles class="mx-auto mb-4 h-12 w-12 animate-pulse text-brand-primary" />
                        <p class="text-gray-500">Analyzing your preferences and finding the best content for you...</p>
                    </div>
                </div>

                <!-- Recommendations Grid -->
                <div
                    v-else-if="recommendations.length > 0"
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="rec in recommendations"
                        :key="rec.item.id"
                        class="group relative rounded-lg bg-white p-6 shadow-sm transition-all hover:shadow-md"
                    >
                        <!-- Bookmark Button -->
                        <button
                            @click="handleBookmark($event, rec.item.id)"
                            class="absolute right-4 top-4 z-10 rounded-full bg-white p-2 shadow-sm transition-colors hover:bg-gray-50"
                            :class="savedIds.has(rec.item.id) ? 'text-brand-secondary' : 'text-gray-400'"
                            :disabled="togglingBookmarks.has(rec.item.id)"
                        >
                            <Bookmark
                                class="h-5 w-5"
                                :class="savedIds.has(rec.item.id) ? 'fill-current' : ''"
                            />
                        </button>

                        <Link
                            :href="`/student/content/${rec.item.id}`"
                            class="block"
                        >
                            <!-- Header with Icon -->
                            <div class="mb-4 flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-primary-light">
                                    <component
                                        :is="getTypeIcon(rec.item.type)"
                                        class="h-6 w-6 text-brand-primary"
                                    />
                                </div>
                                <div class="flex-1">
                                    <h3 class="mb-2 text-lg font-semibold text-gray-900 group-hover:text-brand-primary">
                                        {{ rec.item.title }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                        <span class="font-medium">{{ rec.item.subject }}</span>
                                        <span>•</span>
                                        <span>{{ rec.item.difficulty }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ rec.item.type }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <p
                                v-if="rec.item.description"
                                class="mb-4 line-clamp-2 text-sm text-gray-600"
                            >
                                {{ rec.item.description }}
                            </p>

                            <!-- Reason Badge -->
                            <div class="mb-4 rounded-lg bg-brand-secondary-light p-3">
                                <div class="flex items-start gap-2">
                                    <Sparkles class="h-4 w-4 shrink-0 text-brand-secondary mt-0.5" />
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 mb-1">Why this recommendation</p>
                                        <p class="text-sm font-medium text-gray-800">
                                            {{ rec.reason }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags -->
                            <div
                                v-if="rec.item.tags && rec.item.tags.length > 0"
                                class="flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="tag in rec.item.tags.slice(0, 3)"
                                    :key="tag.id"
                                    class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="rounded-lg bg-white p-12 text-center"
                >
                    <Sparkles class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">
                        No recommendations available
                    </h3>
                    <p class="mb-6 text-gray-600">
                        <span v-if="!userPreferences">
                            Complete your learning preferences to get personalized recommendations.
                        </span>
                        <span v-else>
                            There are currently no content items available that match your preferences. Please check back later or contact your teacher to add more content.
                        </span>
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link v-if="!userPreferences" href="/student/profile">
                            <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                                <BookOpen class="mr-2 h-4 w-4" />
                                Set Learning Preferences
                            </Button>
                        </Link>
                        <Link href="/student/content">
                            <Button variant="outline">
                                <BookOpen class="mr-2 h-4 w-4" />
                                Browse Content Library
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
