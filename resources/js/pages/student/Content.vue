<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { BookOpen, Search, Filter, FileText, Video, Link as LinkIcon, HelpCircle } from 'lucide-vue-next';
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
    return (user.value?.role as 'student' | 'teacher') || 'student';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Content Library',
        href: '/student/content',
    },
];

const loading = ref(false);
const error = ref<string | null>(null);
const contentItems = ref<ContentItem[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);

// Filters
const filters = ref({
    subject: '',
    difficulty: '',
    tag: '',
    type: '',
    search: '',
});

const difficultyOptions = ['Beginner', 'Intermediate', 'Advanced'];
const typeOptions = [
    { value: 'video', label: 'Video', icon: Video },
    { value: 'pdf', label: 'PDF', icon: FileText },
    { value: 'link', label: 'Link', icon: LinkIcon },
    { value: 'quiz', label: 'Quiz', icon: HelpCircle },
];

const getTypeIcon = (type: string) => {
    const option = typeOptions.find(opt => opt.value === type);
    return option?.icon || FileText;
};

const fetchContent = async (pageNum = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.subject) params.append('subject', filters.value.subject);
        if (filters.value.difficulty) params.append('difficulty', filters.value.difficulty);
        if (filters.value.tag) params.append('tag', filters.value.tag);
        if (filters.value.type) params.append('type', filters.value.type);
        params.append('page', pageNum.toString());

        const response = await fetch(`/api/content?${params.toString()}`, {
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
        currentPage.value = result.meta.current_page;
        lastPage.value = result.meta.last_page;
    } catch (err: any) {
        error.value = err.message || 'Failed to load content';
        console.error('Error fetching content:', err);
    } finally {
        loading.value = false;
    }
};

const applyFilters = () => {
    currentPage.value = 1;
    fetchContent(1);
};

const clearFilters = () => {
    filters.value = {
        subject: '',
        difficulty: '',
        tag: '',
        type: '',
        search: '',
    };
    applyFilters();
};

onMounted(() => {
    fetchContent();
});
</script>

<template>
    <Head title="Content Library" />

    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                        Content Library
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Browse and discover learning resources
                    </p>
                </div>

                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="filters.search"
                            placeholder="Search content by title, description, or subject..."
                            class="w-full pl-10 pr-4"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Subject
                            </label>
                            <Input
                                v-model="filters.subject"
                                placeholder="Filter by subject"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Difficulty
                            </label>
                            <select
                                v-model="filters.difficulty"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All</option>
                                <option
                                    v-for="diff in difficultyOptions"
                                    :key="diff"
                                    :value="diff"
                                >
                                    {{ diff }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Type
                            </label>
                            <select
                                v-model="filters.type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All</option>
                                <option
                                    v-for="type in typeOptions"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <Button
                                @click="applyFilters"
                                class="flex-1 bg-brand-primary hover:bg-brand-primary-hover"
                            >
                                <Filter class="mr-2 h-4 w-4" />
                                Apply
                            </Button>
                            <Button
                                @click="clearFilters"
                                variant="outline"
                                class="flex-1"
                            >
                                Clear
                            </Button>
                        </div>
                    </div>
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

                <!-- Content Grid -->
                <div
                    v-else-if="contentItems.length > 0"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="item in contentItems"
                        :key="item.id"
                        :href="`/student/content/${item.id}`"
                        class="group rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <component
                                :is="getTypeIcon(item.type)"
                                class="h-8 w-8 text-brand-primary"
                            />
                            <div>
                                <h3 class="font-semibold text-gray-900 group-hover:text-brand-primary">
                                    {{ item.title }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ item.subject }} • {{ item.difficulty }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="item.description"
                            class="mb-4 line-clamp-2 text-sm text-gray-600"
                        >
                            {{ item.description }}
                        </p>
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
                    <BookOpen class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-600">
                        {{ filters.search || filters.subject || filters.difficulty || filters.type || filters.tag 
                            ? 'No content found matching your search or filters. Try adjusting your criteria.' 
                            : 'No content found. Try adjusting your filters.' }}
                    </p>
                    <Button
                        v-if="filters.search || filters.subject || filters.difficulty || filters.type || filters.tag"
                        @click="clearFilters"
                        variant="outline"
                        class="mt-4"
                    >
                        Clear All Filters
                    </Button>
                </div>

                <!-- Pagination -->
                <div
                    v-if="lastPage > 1"
                    class="mt-6 flex justify-center gap-2"
                >
                    <Button
                        @click="fetchContent(currentPage - 1)"
                        :disabled="currentPage === 1"
                        variant="outline"
                    >
                        Previous
                    </Button>
                    <span class="flex items-center px-4 text-sm text-gray-600">
                        Page {{ currentPage }} of {{ lastPage }}
                    </span>
                    <Button
                        @click="fetchContent(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        variant="outline"
                    >
                        Next
                    </Button>
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
                        Content Library
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Browse and discover learning resources
                    </p>
                </div>

                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="filters.search"
                            placeholder="Search content by title, description, or subject..."
                            class="w-full pl-10 pr-4"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Subject
                            </label>
                            <Input
                                v-model="filters.subject"
                                placeholder="Filter by subject"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Difficulty
                            </label>
                            <select
                                v-model="filters.difficulty"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All</option>
                                <option
                                    v-for="diff in difficultyOptions"
                                    :key="diff"
                                    :value="diff"
                                >
                                    {{ diff }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Type
                            </label>
                            <select
                                v-model="filters.type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All</option>
                                <option
                                    v-for="type in typeOptions"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <Button
                                @click="applyFilters"
                                class="flex-1 bg-brand-primary hover:bg-brand-primary-hover"
                            >
                                <Filter class="mr-2 h-4 w-4" />
                                Apply
                            </Button>
                            <Button
                                @click="clearFilters"
                                variant="outline"
                                class="flex-1"
                            >
                                Clear
                            </Button>
                        </div>
                    </div>
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

                <!-- Content Grid -->
                <div
                    v-else-if="contentItems.length > 0"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="item in contentItems"
                        :key="item.id"
                        :href="`/student/content/${item.id}`"
                        class="group rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <component
                                :is="getTypeIcon(item.type)"
                                class="h-8 w-8 text-brand-primary"
                            />
                            <div>
                                <h3 class="font-semibold text-gray-900 group-hover:text-brand-primary">
                                    {{ item.title }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ item.subject }} • {{ item.difficulty }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="item.description"
                            class="mb-4 line-clamp-2 text-sm text-gray-600"
                        >
                            {{ item.description }}
                        </p>
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
                    <BookOpen class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-600">
                        {{ filters.search || filters.subject || filters.difficulty || filters.type || filters.tag 
                            ? 'No content found matching your search or filters. Try adjusting your criteria.' 
                            : 'No content found. Try adjusting your filters.' }}
                    </p>
                    <Button
                        v-if="filters.search || filters.subject || filters.difficulty || filters.type || filters.tag"
                        @click="clearFilters"
                        variant="outline"
                        class="mt-4"
                    >
                        Clear All Filters
                    </Button>
                </div>

                <!-- Pagination -->
                <div
                    v-if="lastPage > 1"
                    class="mt-6 flex justify-center gap-2"
                >
                    <Button
                        @click="fetchContent(currentPage - 1)"
                        :disabled="currentPage === 1"
                        variant="outline"
                    >
                        Previous
                    </Button>
                    <span class="flex items-center px-4 text-sm text-gray-600">
                        Page {{ currentPage }} of {{ lastPage }}
                    </span>
                    <Button
                        @click="fetchContent(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        variant="outline"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
