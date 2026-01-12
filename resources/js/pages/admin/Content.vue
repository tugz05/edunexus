<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Trash2, FileText, Video, Link as LinkIcon, HelpCircle, Search } from 'lucide-vue-next';
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
    return (user.value?.role as 'student' | 'teacher' | 'admin') || 'admin';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin/home' },
    { title: 'Content Management', href: '/admin/content' },
];

const loading = ref(false);
const error = ref<string | null>(null);
const contentItems = ref<ContentItem[]>([]);
const deletingId = ref<number | null>(null);
const searchQuery = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

const typeIcons = {
    video: Video,
    pdf: FileText,
    link: LinkIcon,
    quiz: HelpCircle,
};

const getTypeIcon = (type: string) => {
    return typeIcons[type as keyof typeof typeIcons] || FileText;
};

const fetchContent = async (pageNum = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (searchQuery.value.trim()) params.append('search', searchQuery.value.trim());
        params.append('page', pageNum.toString());

        const response = await fetch(`/api/admin/content?${params.toString()}`, {
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
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    fetchContent(1);
};

const clearSearch = () => {
    searchQuery.value = '';
    fetchContent(1);
};

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this content item?')) return;

    deletingId.value = id;
    error.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/api/admin/content/${id}`, {
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

        fetchContent(currentPage.value);
    } catch (err: any) {
        error.value = err.message || 'Failed to delete content';
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchContent();
});
</script>

<template>
    <Head title="Content Management" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-brand-primary">Content Management</h1>
                    <p class="mt-2 text-gray-600">Manage all platform content</p>
                </div>

                <div class="mb-6">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search content..."
                            class="w-full pl-10 pr-20"
                            @keyup.enter="handleSearch"
                        />
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-2">
                            <Button v-if="searchQuery" @click="clearSearch" variant="ghost" size="sm">Clear</Button>
                            <Button @click="handleSearch" size="sm" class="bg-brand-primary">Search</Button>
                        </div>
                    </div>
                </div>

                <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">{{ error }}</div>
                <div v-if="loading" class="text-center py-12 text-gray-500">Loading...</div>
                
                <div v-else-if="contentItems.length > 0" class="space-y-4">
                    <div
                        v-for="item in contentItems"
                        :key="item.id"
                        class="rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3 flex-1">
                                <component :is="getTypeIcon(item.type)" class="h-8 w-8 text-brand-primary shrink-0" />
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ item.title }}</h3>
                                    <p v-if="item.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                                        {{ item.description }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span>{{ item.subject }}</span>
                                        <span>•</span>
                                        <span>{{ item.difficulty }}</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ item.type }}</span>
                                        <span v-if="item.creator">• By {{ item.creator.name }}</span>
                                    </div>
                                </div>
                            </div>
                            <Button
                                @click="handleDelete(item.id)"
                                variant="outline"
                                size="icon"
                                :disabled="deletingId === item.id"
                                class="text-red-600 hover:text-red-700"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg bg-white p-12 text-center">
                    <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-600">No content found</p>
                </div>

                <div v-if="lastPage > 1" class="mt-6 flex justify-center gap-2">
                    <Button @click="fetchContent(currentPage - 1)" :disabled="currentPage === 1" variant="outline">Previous</Button>
                    <span class="flex items-center px-4 text-sm text-gray-600">Page {{ currentPage }} of {{ lastPage }}</span>
                    <Button @click="fetchContent(currentPage + 1)" :disabled="currentPage === lastPage" variant="outline">Next</Button>
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
                    <h1 class="text-2xl font-bold text-brand-primary">Content Management</h1>
                    <p class="mt-2 text-gray-600">Manage all platform content</p>
                </div>

                <div class="mb-6">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search content..."
                            class="w-full pl-10 pr-20"
                            @keyup.enter="handleSearch"
                        />
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-2">
                            <Button v-if="searchQuery" @click="clearSearch" variant="ghost" size="sm">Clear</Button>
                            <Button @click="handleSearch" size="sm" class="bg-brand-primary">Search</Button>
                        </div>
                    </div>
                </div>

                <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">{{ error }}</div>
                <div v-if="loading" class="text-center py-12 text-gray-500">Loading...</div>
                
                <div v-else-if="contentItems.length > 0" class="rounded-lg bg-white shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Content</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creator</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="item in contentItems" :key="item.id">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <component :is="getTypeIcon(item.type)" class="h-6 w-6 text-brand-primary" />
                                        <div>
                                            <div class="font-medium text-gray-900">{{ item.title }}</div>
                                            <div v-if="item.description" class="text-sm text-gray-500 line-clamp-1">
                                                {{ item.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ item.subject }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize text-gray-700">
                                        {{ item.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ item.creator?.name || 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <Button
                                        @click="handleDelete(item.id)"
                                        variant="outline"
                                        size="sm"
                                        :disabled="deletingId === item.id"
                                        class="text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="rounded-lg bg-white p-12 text-center">
                    <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-600">No content found</p>
                </div>

                <div v-if="lastPage > 1" class="mt-6 flex justify-center gap-2">
                    <Button @click="fetchContent(currentPage - 1)" :disabled="currentPage === 1" variant="outline">Previous</Button>
                    <span class="flex items-center px-4 text-sm text-gray-600">Page {{ currentPage }} of {{ lastPage }}</span>
                    <Button @click="fetchContent(currentPage + 1)" :disabled="currentPage === lastPage" variant="outline">Next</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
