<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Bookmark, ExternalLink, ArrowLeft, FileText, Video, Link as LinkIcon, HelpCircle } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface ContentItem {
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
}

const props = defineProps<{
    id: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (!contentItem.value) return [];
    return [
        {
            title: 'Content Library',
            href: '/student/content',
        },
        {
            title: contentItem.value.title,
            href: `/student/content/${props.id}`,
        },
    ];
});

const loading = ref(false);
const error = ref<string | null>(null);
const contentItem = ref<ContentItem | null>(null);
const isSaved = ref(false);
const togglingBookmark = ref(false);

const typeIcons = {
    video: Video,
    pdf: FileText,
    link: LinkIcon,
    quiz: HelpCircle,
};

const getTypeIcon = (type: string) => {
    return typeIcons[type as keyof typeof typeIcons] || FileText;
};

const fetchContent = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/api/content/${props.id}`, {
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
            throw new Error(errorData.message || 'Failed to load content');
        }

        const result = await response.json();
        contentItem.value = result.data;

        // Check if content is already saved
        await checkIfSaved();
    } catch (err: any) {
        error.value = err.message || 'Failed to load content';
        console.error('Error fetching content:', err);
    } finally {
        loading.value = false;
    }
};

const checkIfSaved = async () => {
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
            isSaved.value = result.data.some((item: ContentItem) => item.id === parseInt(props.id));
        }
    } catch (err) {
        console.error('Error checking saved status:', err);
    }
};

const handleBookmark = async () => {
    if (!contentItem.value || togglingBookmark.value) {
        return;
    }

    togglingBookmark.value = true;
    error.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const method = isSaved.value ? 'DELETE' : 'POST';
        const url = `/api/student/saved/${contentItem.value.id}`;

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
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to update bookmark');
        }

        isSaved.value = !isSaved.value;
    } catch (err: any) {
        error.value = err.message || 'Failed to update bookmark';
        console.error('Error toggling bookmark:', err);
    } finally {
        togglingBookmark.value = false;
    }
};

onMounted(() => {
    fetchContent();
});
</script>

<template>
    <Head :title="contentItem?.title || 'Content Detail'" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <Link
                href="/student/content"
                class="mb-6 inline-flex items-center gap-2 text-gray-600 hover:text-brand-primary"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Content Library
            </Link>

            <!-- Loading State -->
            <div
                v-if="loading"
                class="flex items-center justify-center py-12"
            >
                <div class="text-gray-500">Loading content...</div>
            </div>

            <!-- Error State -->
            <div
                v-else-if="error"
                class="rounded-lg bg-red-50 p-4 text-red-800"
            >
                {{ error }}
            </div>

            <!-- Content Detail -->
            <div
                v-else-if="contentItem"
                class="rounded-lg bg-white p-6 shadow-sm md:p-8"
            >
                <!-- Header -->
                <div class="mb-6 flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <component
                            :is="getTypeIcon(contentItem.type)"
                            class="h-10 w-10 text-brand-primary"
                        />
                        <div>
                            <h1 class="mb-2 text-2xl font-bold text-gray-900 md:text-3xl">
                                {{ contentItem.title }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                <span class="font-medium">{{ contentItem.subject }}</span>
                                <span>•</span>
                                <span>{{ contentItem.difficulty }}</span>
                                <span>•</span>
                                <span class="capitalize">{{ contentItem.type }}</span>
                                <span
                                    v-if="contentItem.creator"
                                >
                                    • Created by {{ contentItem.creator.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <Button
                        @click="handleBookmark"
                        variant="outline"
                        size="icon"
                        class="shrink-0"
                    >
                        <Bookmark class="h-5 w-5" />
                    </Button>
                </div>

                <!-- Description -->
                <div
                    v-if="contentItem.description"
                    class="mb-6"
                >
                    <h2 class="mb-2 text-lg font-semibold text-gray-900">
                        Description
                    </h2>
                    <p class="text-gray-700 whitespace-pre-wrap">
                        {{ contentItem.description }}
                    </p>
                </div>

                <!-- Tags -->
                <div
                    v-if="contentItem.tags && contentItem.tags.length > 0"
                    class="mb-6"
                >
                    <h2 class="mb-2 text-lg font-semibold text-gray-900">
                        Tags
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in contentItem.tags"
                            :key="tag.id"
                            class="rounded-full bg-brand-primary-light px-3 py-1 text-sm font-medium text-brand-primary"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        :href="contentItem.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex-1 bg-brand-primary hover:bg-brand-primary-hover"
                    >
                        <ExternalLink class="mr-2 h-4 w-4" />
                        Open Content
                    </Button>
                    <Button
                        @click="handleBookmark"
                        variant="outline"
                        class="flex-1"
                        :disabled="togglingBookmark"
                        :class="isSaved ? 'bg-brand-secondary-light text-brand-secondary border-brand-secondary' : ''"
                    >
                        <Bookmark
                            class="mr-2 h-4 w-4"
                            :class="isSaved ? 'fill-current' : ''"
                        />
                        {{ isSaved ? 'Saved' : 'Save for Later' }}
                    </Button>
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
            <div class="mx-auto w-full max-w-4xl">
                <!-- Back Button -->
                <Link
                    href="/student/content"
                    class="mb-6 inline-flex items-center gap-2 text-gray-600 hover:text-brand-primary"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Content Library
                </Link>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex items-center justify-center py-12"
                >
                    <div class="text-gray-500">Loading content...</div>
                </div>

                <!-- Error State -->
                <div
                    v-else-if="error"
                    class="rounded-lg bg-red-50 p-4 text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Content Detail -->
                <div
                    v-else-if="contentItem"
                    class="rounded-lg bg-white p-6 shadow-sm md:p-8"
                >
                    <!-- Header -->
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <component
                                :is="getTypeIcon(contentItem.type)"
                                class="h-10 w-10 text-brand-primary"
                            />
                            <div>
                                <h1 class="mb-2 text-2xl font-bold text-gray-900 md:text-3xl">
                                    {{ contentItem.title }}
                                </h1>
                                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                    <span class="font-medium">{{ contentItem.subject }}</span>
                                    <span>•</span>
                                    <span>{{ contentItem.difficulty }}</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ contentItem.type }}</span>
                                    <span
                                        v-if="contentItem.creator"
                                    >
                                        • Created by {{ contentItem.creator.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <Button
                            @click="handleBookmark"
                            variant="outline"
                            size="icon"
                            class="shrink-0"
                            :disabled="togglingBookmark"
                            :class="isSaved ? 'bg-brand-secondary-light text-brand-secondary border-brand-secondary' : ''"
                        >
                            <Bookmark
                                class="h-5 w-5"
                                :class="isSaved ? 'fill-current' : ''"
                            />
                        </Button>
                    </div>

                    <!-- Description -->
                    <div
                        v-if="contentItem.description"
                        class="mb-6"
                    >
                        <h2 class="mb-2 text-lg font-semibold text-gray-900">
                            Description
                        </h2>
                        <p class="text-gray-700 whitespace-pre-wrap">
                            {{ contentItem.description }}
                        </p>
                    </div>

                    <!-- Tags -->
                    <div
                        v-if="contentItem.tags && contentItem.tags.length > 0"
                        class="mb-6"
                    >
                        <h2 class="mb-2 text-lg font-semibold text-gray-900">
                            Tags
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in contentItem.tags"
                                :key="tag.id"
                                class="rounded-full bg-brand-primary-light px-3 py-1 text-sm font-medium text-brand-primary"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            :href="contentItem.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 bg-brand-primary hover:bg-brand-primary-hover"
                        >
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Open Content
                        </Button>
                        <Button
                            @click="handleBookmark"
                            variant="outline"
                            class="flex-1"
                            :disabled="togglingBookmark"
                            :class="isSaved ? 'bg-brand-secondary-light text-brand-secondary border-brand-secondary' : ''"
                        >
                            <Bookmark
                                class="mr-2 h-4 w-4"
                                :class="isSaved ? 'fill-current' : ''"
                            />
                            {{ isSaved ? 'Saved' : 'Save for Later' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
