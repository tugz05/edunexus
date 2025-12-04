<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';

interface ContentItem {
    id: number;
    title: string;
    description: string | null;
    type: 'video' | 'pdf' | 'link' | 'quiz';
    url: string;
    subject: string;
    difficulty: 'Beginner' | 'Intermediate' | 'Advanced';
    tags?: Array<{ id: number; name: string }>;
}

interface ContentTag {
    id: number;
    name: string;
}

const props = defineProps<{
    id: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'teacher';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const tags = ref<ContentTag[]>([]);
const contentItem = ref<ContentItem | null>(null);

const form = ref({
    title: '',
    description: '',
    type: 'video' as 'video' | 'pdf' | 'link' | 'quiz',
    url: '',
    subject: '',
    difficulty: 'Beginner' as 'Beginner' | 'Intermediate' | 'Advanced',
    tagIds: [] as number[],
});

const typeOptions = [
    { value: 'video', label: 'Video' },
    { value: 'pdf', label: 'PDF' },
    { value: 'link', label: 'Link' },
    { value: 'quiz', label: 'Quiz' },
];

const difficultyOptions = [
    { value: 'Beginner', label: 'Beginner' },
    { value: 'Intermediate', label: 'Intermediate' },
    { value: 'Advanced', label: 'Advanced' },
];

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

        // Populate form
        form.value = {
            title: contentItem.value.title,
            description: contentItem.value.description || '',
            type: contentItem.value.type,
            url: contentItem.value.url,
            subject: contentItem.value.subject,
            difficulty: contentItem.value.difficulty,
            tagIds: contentItem.value.tags?.map(tag => tag.id) || [],
        };
    } catch (err: any) {
        error.value = err.message || 'Failed to load content';
        console.error('Error fetching content:', err);
    } finally {
        loading.value = false;
    }
};

const fetchTags = async () => {
    try {
        const response = await fetch('/api/teacher/tags', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to load tags');
        }

        const result = await response.json();
        tags.value = result.data;
    } catch (err: any) {
        console.error('Error fetching tags:', err);
    }
};

const submitForm = async () => {
    saving.value = true;
    error.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(`/api/teacher/content/${props.id}`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                title: form.value.title,
                description: form.value.description || null,
                type: form.value.type,
                url: form.value.url,
                subject: form.value.subject,
                difficulty: form.value.difficulty,
                tags: form.value.tagIds,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const errorMessage = errorData.message ||
                (errorData.errors && Object.values(errorData.errors).flat()[0]) ||
                'Failed to update content';
            throw new Error(errorMessage);
        }

        // Redirect to content manager
        router.visit('/teacher/content');
    } catch (err: any) {
        error.value = err.message || 'Failed to update content';
        console.error('Error updating content:', err);
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await Promise.all([fetchContent(), fetchTags()]);
});
</script>

<template>
    <Head :title="contentItem?.title || 'Edit Content'" />
    
    <div :class="['min-h-screen bg-gray-100', isMobile ? 'pt-16 pb-20' : 'py-6']">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <Link
                href="/teacher/content"
                class="mb-6 inline-flex items-center gap-2 text-gray-600 hover:text-brand-primary"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Content Manager
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-brand-primary md:text-3xl">
                    Edit Content
                </h1>
                <p class="mt-2 text-gray-600">
                    Update learning resource details
                </p>
            </div>

            <!-- Loading State -->
            <div
                v-if="loading"
                class="flex items-center justify-center py-12 rounded-lg bg-white"
            >
                <div class="text-gray-500">Loading content...</div>
            </div>

            <!-- Error Message -->
            <div
                v-else-if="error && !contentItem"
                class="mb-6 rounded-lg bg-red-50 p-4 text-red-800"
            >
                {{ error }}
            </div>

            <!-- Form -->
            <form
                v-else
                @submit.prevent="submitForm"
                class="space-y-6 rounded-lg bg-white p-6 shadow-sm md:p-8"
            >
                <!-- Error Message -->
                <div
                    v-if="error"
                    class="rounded-lg bg-red-50 p-4 text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Title -->
                <div>
                    <Label for="title">Title *</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        required
                        placeholder="Enter content title"
                        class="mt-1"
                    />
                </div>

                <!-- Description -->
                <div>
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Enter content description"
                        class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>

                <!-- Type -->
                <div>
                    <Label for="type">Type *</Label>
                    <select
                        id="type"
                        v-model="form.type"
                        required
                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option
                            v-for="option in typeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- URL -->
                <div>
                    <Label for="url">URL *</Label>
                    <Input
                        id="url"
                        v-model="form.url"
                        type="url"
                        required
                        placeholder="https://example.com"
                        class="mt-1"
                    />
                </div>

                <!-- Subject -->
                <div>
                    <Label for="subject">Subject *</Label>
                    <Input
                        id="subject"
                        v-model="form.subject"
                        required
                        placeholder="e.g., Mathematics, Science"
                        class="mt-1"
                    />
                </div>

                <!-- Difficulty -->
                <div>
                    <Label for="difficulty">Difficulty *</Label>
                    <select
                        id="difficulty"
                        v-model="form.difficulty"
                        required
                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option
                            v-for="option in difficultyOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <!-- Tags -->
                <div>
                    <Label>Tags</Label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-50"
                            :class="form.tagIds.includes(tag.id) ? 'bg-brand-primary-light border-brand-primary' : ''"
                        >
                            <input
                                type="checkbox"
                                :value="tag.id"
                                v-model="form.tagIds"
                                class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary"
                            />
                            <span>{{ tag.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <Link href="/teacher/content">
                        <Button
                            type="button"
                            variant="outline"
                        >
                            Cancel
                        </Button>
                    </Link>
                    <Button
                        type="submit"
                        :disabled="saving"
                        class="bg-brand-primary hover:bg-brand-primary-hover"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom Navigation (Mobile Only) -->
    <BottomNav
        v-if="isMobile"
        :role="userRole"
    />
</template>
