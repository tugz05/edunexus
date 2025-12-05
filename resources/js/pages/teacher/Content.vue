<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Plus, Edit, Trash2, FileText, Video, Link as LinkIcon, HelpCircle } from 'lucide-vue-next';
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
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'teacher';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Content Manager',
        href: '/teacher/content',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const contentItems = ref<ContentItem[]>([]);
const deletingId = ref<number | null>(null);

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
        const response = await fetch('/api/content', {
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
        contentItems.value = result.data;
    } catch (err: any) {
        error.value = err.message || 'Failed to load content';
        console.error('Error fetching content:', err);
    } finally {
        loading.value = false;
    }
};

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this content item?')) {
        return;
    }

    deletingId.value = id;
    error.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(`/api/teacher/content/${id}`, {
            method: 'DELETE',
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
            throw new Error(errorData.message || 'Failed to delete content');
        }

        // Remove from list
        contentItems.value = contentItems.value.filter(item => item.id !== id);
    } catch (err: any) {
        error.value = err.message || 'Failed to delete content';
        console.error('Error deleting content:', err);
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchContent();
});
</script>

<template>
    <Head title="Content Manager" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        Content Manager
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Manage all learning content
                    </p>
                </div>
                <Link href="/teacher/content/create">
                    <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Content
                    </Button>
                </Link>
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
                <div class="text-gray-500">Loading content...</div>
            </div>

            <!-- Content List -->
            <div
                v-else-if="contentItems.length > 0"
                class="space-y-4"
            >
                <div
                    v-for="item in contentItems"
                    :key="item.id"
                    class="rounded-lg bg-white p-6 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4 flex-1">
                            <component
                                :is="getTypeIcon(item.type)"
                                class="h-8 w-8 text-brand-primary shrink-0"
                            />
                            <div class="flex-1">
                                <h3 class="mb-1 text-lg font-semibold text-gray-900">
                                    {{ item.title }}
                                </h3>
                                <p
                                    v-if="item.description"
                                    class="mb-2 line-clamp-2 text-sm text-gray-600"
                                >
                                    {{ item.description }}
                                </p>
                                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                    <span>{{ item.subject }}</span>
                                    <span>•</span>
                                    <span>{{ item.difficulty }}</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ item.type }}</span>
                                    <span
                                        v-if="item.tags && item.tags.length > 0"
                                        class="flex items-center gap-1"
                                    >
                                        • {{ item.tags.length }} tag(s)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-4">
                            <Link :href="`/teacher/content/${item.id}/edit`">
                                <Button
                                    variant="outline"
                                    size="icon"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Button
                                @click="handleDelete(item.id)"
                                variant="outline"
                                size="icon"
                                :disabled="deletingId === item.id"
                                class="text-red-600 hover:text-red-700 hover:bg-red-50"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="rounded-lg bg-white p-12 text-center"
            >
                <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <p class="mb-4 text-gray-600">No content items yet.</p>
                <Link href="/teacher/content/create">
                    <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Your First Content
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
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                            Content Manager
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Manage all learning content
                        </p>
                    </div>
                    <Link href="/teacher/content/create">
                        <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Content
                        </Button>
                    </Link>
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
                    <div class="text-gray-500">Loading content...</div>
                </div>

                <!-- Content List -->
                <div
                    v-else-if="contentItems.length > 0"
                    class="space-y-4"
                >
                    <div
                        v-for="item in contentItems"
                        :key="item.id"
                        class="rounded-lg bg-white p-6 shadow-sm"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4 flex-1">
                                <component
                                    :is="getTypeIcon(item.type)"
                                    class="h-8 w-8 text-brand-primary shrink-0"
                                />
                                <div class="flex-1">
                                    <h3 class="mb-1 text-lg font-semibold text-gray-900">
                                        {{ item.title }}
                                    </h3>
                                    <p
                                        v-if="item.description"
                                        class="mb-2 line-clamp-2 text-sm text-gray-600"
                                    >
                                        {{ item.description }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                        <span>{{ item.subject }}</span>
                                        <span>•</span>
                                        <span>{{ item.difficulty }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ item.type }}</span>
                                        <span
                                            v-if="item.tags && item.tags.length > 0"
                                            class="flex items-center gap-1"
                                        >
                                            • {{ item.tags.length }} tag(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 ml-4">
                                <Link :href="`/teacher/content/${item.id}/edit`">
                                    <Button
                                        variant="outline"
                                        size="icon"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button
                                    @click="handleDelete(item.id)"
                                    variant="outline"
                                    size="icon"
                                    :disabled="deletingId === item.id"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="rounded-lg bg-white p-12 text-center"
                >
                    <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="mb-4 text-gray-600">No content items yet.</p>
                    <Link href="/teacher/content/create">
                        <Button class="bg-brand-primary hover:bg-brand-primary-hover">
                            <Plus class="mr-2 h-4 w-4" />
                            Create Your First Content
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
