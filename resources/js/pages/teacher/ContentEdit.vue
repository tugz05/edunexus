<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { ArrowLeft, Save, Upload, X, Plus as PlusIcon } from 'lucide-vue-next';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Content Manager',
        href: '/teacher/content',
    },
    {
        title: 'Edit Content',
        href: `/teacher/content/${props.id}/edit`,
    },
];

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const tags = ref<ContentTag[]>([]);
const contentItem = ref<ContentItem | null>(null);
const newTagName = ref('');
const creatingTag = ref(false);

const form = ref({
    title: '',
    description: '',
    type: 'video' as 'video' | 'pdf' | 'link' | 'quiz',
    url: '',
    file: null as File | null,
    subject: '',
    difficulty: 'Beginner' as 'Beginner' | 'Intermediate' | 'Advanced',
    tagIds: [] as number[],
});

const fileInput = ref<HTMLInputElement | null>(null);
const filePreview = ref<string | null>(null);
const existingFilePath = ref<string | null>(null);

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
            file: null,
            subject: contentItem.value.subject,
            difficulty: contentItem.value.difficulty,
            tagIds: contentItem.value.tags?.map(tag => tag.id) || [],
        };
        
        // Store existing file path if available
        existingFilePath.value = (contentItem.value as any).file_path || null;
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

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.value.file = target.files[0];
        
        // Create preview URL
        if (form.value.type === 'video') {
            filePreview.value = URL.createObjectURL(target.files[0]);
        } else if (form.value.type === 'pdf') {
            filePreview.value = URL.createObjectURL(target.files[0]);
        }
    }
};

const clearFile = () => {
    form.value.file = null;
    filePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const createTag = async () => {
    if (!newTagName.value.trim() || creatingTag.value) {
        return;
    }

    const tagName = newTagName.value.trim();
    
    // Check if tag already exists
    if (tags.value.some(tag => tag.name.toLowerCase() === tagName.toLowerCase())) {
        error.value = 'Tag already exists';
        return;
    }

    creatingTag.value = true;
    const previousError = error.value;
    error.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/api/teacher/tags', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: tagName,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to create tag');
        }

        const result = await response.json();
        
        // Add new tag to the list
        tags.value.push(result.data);
        
        // Automatically select the new tag
        if (!form.value.tagIds.includes(result.data.id)) {
            form.value.tagIds.push(result.data.id);
        }
        
        // Clear input
        newTagName.value = '';
        error.value = previousError; // Restore previous error if any
    } catch (err: any) {
        error.value = err.message || 'Failed to create tag';
        console.error('Error creating tag:', err);
    } finally {
        creatingTag.value = false;
    }
};

const handleTagKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        createTag();
    }
};

const submitForm = async () => {
    saving.value = true;
    error.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Create FormData for file upload
        const formData = new FormData();
        formData.append('title', form.value.title);
        formData.append('description', form.value.description || '');
        formData.append('type', form.value.type);
        formData.append('subject', form.value.subject);
        formData.append('difficulty', form.value.difficulty);
        
        // Add URL if provided (or if type is link/quiz)
        if (form.value.url || ['link', 'quiz'].includes(form.value.type)) {
            formData.append('url', form.value.url || '');
        }
        
        // Add file if uploaded
        if (form.value.file) {
            formData.append('file', form.value.file);
        }
        
        // Add tags (only if there are tags selected)
        if (form.value.tagIds && form.value.tagIds.length > 0) {
            form.value.tagIds.forEach(tagId => {
                formData.append('tags[]', tagId.toString());
            });
        } else {
            // Send empty array to clear tags
            formData.append('tags', '[]');
        }

        // Laravel expects PUT method via _method spoofing
        formData.append('_method', 'PUT');

        const response = await fetch(`/api/teacher/content/${props.id}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: formData,
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
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
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

                <!-- URL or File Upload -->
                <div>
                    <Label for="url">
                        <span v-if="form.type === 'link' || form.type === 'quiz'">URL *</span>
                        <span v-else>URL or File Upload</span>
                    </Label>
                    
                    <!-- File Upload for Video and PDF -->
                    <div
                        v-if="form.type === 'video' || form.type === 'pdf'"
                        class="mt-1 space-y-2"
                    >
                        <div class="flex gap-2">
                            <Input
                                id="url"
                                v-model="form.url"
                                type="url"
                                placeholder="Or enter URL (e.g., https://example.com/video.mp4)"
                                class="flex-1"
                            />
                            <span class="flex items-center text-sm text-gray-500">OR</span>
                        </div>
                        <div>
                            <input
                                ref="fileInput"
                                type="file"
                                :accept="form.type === 'video' ? 'video/*' : 'application/pdf'"
                                @change="handleFileChange"
                                class="hidden"
                            />
                            <div class="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="fileInput?.click()"
                                    class="flex-1"
                                >
                                    <Upload class="mr-2 h-4 w-4" />
                                    Upload {{ form.type === 'video' ? 'Video' : 'PDF' }} File
                                </Button>
                                <Button
                                    v-if="form.file"
                                    type="button"
                                    variant="outline"
                                    @click="clearFile"
                                    size="icon"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <p
                                v-if="form.file"
                                class="mt-2 text-sm text-gray-600"
                            >
                                Selected: {{ form.file.name }} ({{ (form.file.size / 1024 / 1024).toFixed(2) }} MB)
                            </p>
                            <p
                                v-else-if="existingFilePath"
                                class="mt-2 text-sm text-gray-500"
                            >
                                Current file is uploaded. Upload a new file to replace it.
                            </p>
                        </div>
                    </div>
                    
                    <!-- URL Input for Link and Quiz -->
                    <Input
                        v-else
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
                    
                    <!-- Create New Tag -->
                    <div class="mt-2 mb-3 flex gap-2">
                        <Input
                            v-model="newTagName"
                            @keypress="handleTagKeyPress"
                            placeholder="Enter new tag name and press Enter"
                            class="flex-1"
                            :disabled="creatingTag"
                        />
                        <Button
                            type="button"
                            @click="createTag"
                            :disabled="!newTagName.trim() || creatingTag"
                            variant="outline"
                            size="icon"
                        >
                            <PlusIcon class="h-4 w-4" />
                        </Button>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-50 transition-colors"
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

    <!-- Desktop Layout -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="mx-auto w-full max-w-3xl">
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
                        <Label for="title-desktop">Title *</Label>
                        <Input
                            id="title-desktop"
                            v-model="form.title"
                            required
                            placeholder="Enter content title"
                            class="mt-1"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <Label for="description-desktop">Description</Label>
                        <textarea
                            id="description-desktop"
                            v-model="form.description"
                            rows="4"
                            placeholder="Enter content description"
                            class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                    </div>

                    <!-- Type -->
                    <div>
                        <Label for="type-desktop">Type *</Label>
                        <select
                            id="type-desktop"
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

                    <!-- URL or File Upload -->
                    <div>
                        <Label for="url-desktop">
                            <span v-if="form.type === 'link' || form.type === 'quiz'">URL *</span>
                            <span v-else>URL or File Upload</span>
                        </Label>
                        
                        <!-- File Upload for Video and PDF -->
                        <div
                            v-if="form.type === 'video' || form.type === 'pdf'"
                            class="mt-1 space-y-2"
                        >
                            <div class="flex gap-2">
                                <Input
                                    id="url-desktop"
                                    v-model="form.url"
                                    type="url"
                                    placeholder="Or enter URL (e.g., https://example.com/video.mp4)"
                                    class="flex-1"
                                />
                                <span class="flex items-center text-sm text-gray-500">OR</span>
                            </div>
                            <div>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    :accept="form.type === 'video' ? 'video/*' : 'application/pdf'"
                                    @change="handleFileChange"
                                    class="hidden"
                                />
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="fileInput?.click()"
                                        class="flex-1"
                                    >
                                        <Upload class="mr-2 h-4 w-4" />
                                        Upload {{ form.type === 'video' ? 'Video' : 'PDF' }} File
                                    </Button>
                                    <Button
                                        v-if="form.file"
                                        type="button"
                                        variant="outline"
                                        @click="clearFile"
                                        size="icon"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <p
                                    v-if="form.file"
                                    class="mt-2 text-sm text-gray-600"
                                >
                                    Selected: {{ form.file.name }} ({{ (form.file.size / 1024 / 1024).toFixed(2) }} MB)
                                </p>
                                <p
                                    v-else-if="existingFilePath"
                                    class="mt-2 text-sm text-gray-500"
                                >
                                    Current file is uploaded. Upload a new file to replace it.
                                </p>
                            </div>
                        </div>
                        
                        <!-- URL Input for Link and Quiz -->
                        <Input
                            v-else
                            id="url-desktop"
                            v-model="form.url"
                            type="url"
                            required
                            placeholder="https://example.com"
                            class="mt-1"
                        />
                    </div>

                    <!-- Subject -->
                    <div>
                        <Label for="subject-desktop">Subject *</Label>
                        <Input
                            id="subject-desktop"
                            v-model="form.subject"
                            required
                            placeholder="e.g., Mathematics, Science"
                            class="mt-1"
                        />
                    </div>

                    <!-- Difficulty -->
                    <div>
                        <Label for="difficulty-desktop">Difficulty *</Label>
                        <select
                            id="difficulty-desktop"
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
                        
                        <!-- Create New Tag -->
                        <div class="mt-2 mb-3 flex gap-2">
                            <Input
                                v-model="newTagName"
                                @keypress="handleTagKeyPress"
                                placeholder="Enter new tag name and press Enter"
                                class="flex-1"
                                :disabled="creatingTag"
                            />
                            <Button
                                type="button"
                                @click="createTag"
                                :disabled="!newTagName.trim() || creatingTag"
                                variant="outline"
                                size="icon"
                            >
                                <PlusIcon class="h-4 w-4" />
                            </Button>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="tag in tags"
                                :key="tag.id"
                                class="flex items-center gap-2 rounded-full border border-gray-300 px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-50 transition-colors"
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
    </AppLayout>
</template>
