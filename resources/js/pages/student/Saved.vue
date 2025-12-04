<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Bookmark, FileText, Video, Link as LinkIcon, HelpCircle } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
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

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Saved Content',
        href: '/student/saved',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const savedContent = ref<ContentItem[]>([]);

const typeIcons = {
    video: Video,
    pdf: FileText,
    link: LinkIcon,
    quiz: HelpCircle,
};

const getTypeIcon = (type: string) => {
    return typeIcons[type as keyof typeof typeIcons] || FileText;
};

const fetchSavedContent = async () => {
    loading.value = true;
    error.value = null;

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

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to load saved content');
        }

        const result = await response.json();
        savedContent.value = result.data;
    } catch (err: any) {
        error.value = err.message || 'Failed to load saved content';
        console.error('Error fetching saved content:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchSavedContent();
});
</script>

<template>
    <Head title="Saved Content" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="mb-4 flex items-center gap-3">
                    <Bookmark class="h-8 w-8 text-brand-primary" />
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        Saved Content
                    </h1>
                </div>
                <p class="text-gray-600">
                    Your bookmarked learning resources
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
                <div class="text-gray-500">Loading saved content...</div>
            </div>

            <!-- Saved Content Grid -->
            <div
                v-else-if="savedContent.length > 0"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="item in savedContent"
                    :key="item.id"
                    :href="`/student/content/${item.id}`"
                    class="group rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-primary-light">
                            <component
                                :is="getTypeIcon(item.type)"
                                class="h-6 w-6 text-brand-primary"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-brand-primary">
                                {{ item.title }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                <span>{{ item.subject }}</span>
                                <span>•</span>
                                <span>{{ item.difficulty }}</span>
                                <span>•</span>
                                <span class="capitalize">{{ item.type }}</span>
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="item.description"
                        class="mb-4 line-clamp-2 text-sm text-gray-600"
                    >
                        {{ item.description }}
                    </p>

                    <!-- Tags -->
                    <div
                        v-if="item.tags && item.tags.length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in item.tags.slice(0, 3)"
                            :key="tag.id"
                            class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="rounded-lg bg-white p-12 text-center"
            >
                <Bookmark class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">
                    No saved content yet
                </h3>
                <p class="mb-6 text-gray-600">
                    Start exploring content and save items you want to revisit later.
                </p>
                <Link href="/student/content">
                    <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                        Browse Content
                    </Button>
                </Link>
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
                        <Bookmark class="h-8 w-8 text-brand-primary" />
                        <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                            Saved Content
                        </h1>
                    </div>
                    <p class="text-gray-600">
                        Your bookmarked learning resources
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
                    <div class="text-gray-500">Loading saved content...</div>
                </div>

                <!-- Saved Content Grid -->
                <div
                    v-else-if="savedContent.length > 0"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="item in savedContent"
                        :key="item.id"
                        :href="`/student/content/${item.id}`"
                        class="group rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-primary-light">
                                <component
                                    :is="getTypeIcon(item.type)"
                                    class="h-6 w-6 text-brand-primary"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-brand-primary">
                                    {{ item.title }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                    <span>{{ item.subject }}</span>
                                    <span>•</span>
                                    <span>{{ item.difficulty }}</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ item.type }}</span>
                                </div>
                            </div>
                        </div>

                        <p
                            v-if="item.description"
                            class="mb-4 line-clamp-2 text-sm text-gray-600"
                        >
                            {{ item.description }}
                        </p>

                        <!-- Tags -->
                        <div
                            v-if="item.tags && item.tags.length > 0"
                            class="flex flex-wrap gap-2"
                        >
                            <span
                                v-for="tag in item.tags.slice(0, 3)"
                                :key="tag.id"
                                class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="rounded-lg bg-white p-12 text-center"
                >
                    <Bookmark class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">
                        No saved content yet
                    </h3>
                    <p class="mb-6 text-gray-600">
                        Start exploring content and save items you want to revisit later.
                    </p>
                    <Link href="/student/content">
                        <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                            Browse Content
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
