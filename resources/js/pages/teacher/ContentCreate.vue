<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { ArrowLeft, Save, Upload, X, Plus as PlusIcon } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';
import VueOfficeDocx from '@vue-office/docx';
import VueOfficeExcel from '@vue-office/excel';
import VueOfficePptx from '@vue-office/pptx';
import '@vue-office/docx/lib/index.css';
import '@vue-office/excel/lib/index.css';

interface ContentTag {
    id: number;
    name: string;
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
    {
        title: 'Create Content',
        href: '/teacher/content/create',
    },
];

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const tags = ref<ContentTag[]>([]);
const newTagName = ref('');
const creatingTag = ref(false);

const form = ref({
    title: '',
    description: '',
    type: 'video' as 'video' | 'pdf' | 'link' | 'quiz' | 'document' | 'presentation' | 'spreadsheet',
    url: '',
    file: null as File | null,
    subject: '',
    difficulty: 'Beginner' as 'Beginner' | 'Intermediate' | 'Advanced',
    tagIds: [] as number[],
});

const fileInput = ref<HTMLInputElement | null>(null);
const filePreview = ref<string | null>(null);
const officePreviewBlobUrl = ref<string | null>(null);

const typeOptions = [
    { value: 'video', label: 'Video' },
    { value: 'pdf', label: 'PDF' },
    { value: 'document', label: 'Document (Word)' },
    { value: 'presentation', label: 'Presentation (PowerPoint)' },
    { value: 'spreadsheet', label: 'Spreadsheet (Excel)' },
    { value: 'link', label: 'Link' },
    { value: 'quiz', label: 'Quiz' },
];

const difficultyOptions = [
    { value: 'Beginner', label: 'Beginner' },
    { value: 'Intermediate', label: 'Intermediate' },
    { value: 'Advanced', label: 'Advanced' },
];

const fetchTags = async () => {
    loading.value = true;
    error.value = null;

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
        error.value = err.message || 'Failed to load tags';
        console.error('Error fetching tags:', err);
    } finally {
        loading.value = false;
    }
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.value.file = target.files[0];

        if (form.value.type === 'video' || form.value.type === 'pdf') {
            filePreview.value = URL.createObjectURL(target.files[0]);
            if (officePreviewBlobUrl.value) {
                URL.revokeObjectURL(officePreviewBlobUrl.value);
                officePreviewBlobUrl.value = null;
            }
        } else if (form.value.type === 'document' || form.value.type === 'presentation' || form.value.type === 'spreadsheet') {
            if (officePreviewBlobUrl.value) URL.revokeObjectURL(officePreviewBlobUrl.value);
            officePreviewBlobUrl.value = URL.createObjectURL(target.files[0]);
            filePreview.value = null;
        } else {
            filePreview.value = null;
            officePreviewBlobUrl.value = null;
        }
    }
};

const clearFile = () => {
    form.value.file = null;
    filePreview.value = null;
    if (officePreviewBlobUrl.value) {
        URL.revokeObjectURL(officePreviewBlobUrl.value);
        officePreviewBlobUrl.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const isOfficeType = (type: string) =>
    type === 'document' || type === 'presentation' || type === 'spreadsheet';

const officeFileUrl = computed(() => {
    if (officePreviewBlobUrl.value) return officePreviewBlobUrl.value;
    if (!form.value.url || typeof window === 'undefined') return '';
    try {
        return new URL(form.value.url, window.location.origin).toString();
    } catch {
        return form.value.url;
    }
});

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
        // Get CSRF token from meta tag - ensure it's always retrieved
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaToken?.getAttribute('content') || '';

        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page and try again.');
        }

        // Create FormData for file upload
        const formData = new FormData();
        formData.append('title', form.value.title);
        formData.append('description', form.value.description || '');
        formData.append('type', form.value.type);
        formData.append('subject', form.value.subject);
        formData.append('difficulty', form.value.difficulty);

        // Add CSRF token to FormData (required for all requests with FormData)
        formData.append('_token', csrfToken);

        // Handle URL based on content type
        if (['link', 'quiz'].includes(form.value.type)) {
            // For link and quiz types, URL is always required
            formData.append('url', form.value.url || '');
        } else if (form.value.url) {
            // For other types, URL is optional but send if provided
            formData.append('url', form.value.url);
        }

        // Add file if uploaded (for video, pdf, and Office document types)
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

        const response = await fetch('/api/teacher/content', {
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
                'Failed to create content';
            throw new Error(errorMessage);
        }

        // Redirect to content manager
        router.visit('/teacher/content');
    } catch (err: any) {
        error.value = err.message || 'Failed to create content';
        console.error('Error creating content:', err);
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchTags();
});

onUnmounted(() => {
    if (officePreviewBlobUrl.value) {
        URL.revokeObjectURL(officePreviewBlobUrl.value);
    }
});
</script>

<template>
    <Head title="Create Content" />

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
                    Create Content
                </h1>
                <p class="mt-2 text-gray-600">
                    Add a new learning resource
                </p>
            </div>

            <!-- Error Message -->
            <div
                v-if="error"
                class="mb-6 rounded-lg bg-red-50 p-4 text-red-800"
            >
                {{ error }}
            </div>

            <!-- Form -->
            <form
                @submit.prevent="submitForm"
                class="space-y-6 rounded-lg bg-white p-6 shadow-sm md:p-8"
            >
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

                    <!-- File Upload for Video, PDF and Office documents -->
                    <div
                        v-if="form.type === 'video' || form.type === 'pdf' || form.type === 'document' || form.type === 'presentation' || form.type === 'spreadsheet'"
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
                                :accept="form.type === 'video'
                                    ? 'video/*'
                                    : form.type === 'pdf'
                                        ? 'application/pdf'
                                        : form.type === 'document'
                                            ? '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                            : form.type === 'presentation'
                                                ? '.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'
                                                : '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'"
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
                                    <span v-if="form.type === 'video'">Upload Video File</span>
                                    <span v-else-if="form.type === 'pdf'">Upload PDF File</span>
                                    <span v-else-if="form.type === 'document'">Upload Document File</span>
                                    <span v-else-if="form.type === 'presentation'">Upload Presentation File</span>
                                    <span v-else>Upload Spreadsheet File</span>
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

                    <div
                        v-if="loading"
                        class="text-sm text-gray-500"
                    >
                        Loading tags...
                    </div>
                    <div
                        v-else
                        class="flex flex-wrap gap-2"
                    >
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

                <!-- Office document preview (vue-office) -->
                <div
                    v-if="isOfficeType(form.type) && officeFileUrl"
                    class="rounded-lg border border-gray-200 bg-white overflow-hidden"
                >
                    <Label class="block mb-2">Preview</Label>
                    <div class="min-h-[400px] border border-gray-100">
                        <VueOfficeDocx
                            v-if="form.type === 'document'"
                            :src="officeFileUrl"
                            style="height: 100%; min-height: 400px;"
                        />
                        <VueOfficePptx
                            v-else-if="form.type === 'presentation'"
                            :src="officeFileUrl"
                            style="height: 100%; min-height: 400px;"
                        />
                        <VueOfficeExcel
                            v-else-if="form.type === 'spreadsheet'"
                            :src="officeFileUrl"
                            style="height: 100%; min-height: 400px;"
                        />
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
                        {{ saving ? 'Creating...' : 'Create Content' }}
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
                        Create Content
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Add a new learning resource
                    </p>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mb-6 rounded-lg bg-red-50 p-4 text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Form -->
                <form
                    @submit.prevent="submitForm"
                    class="space-y-6 rounded-lg bg-white p-6 shadow-sm md:p-8"
                >
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

                        <!-- File Upload for Video, PDF and Office documents -->
                        <div
                            v-if="form.type === 'video' || form.type === 'pdf' || form.type === 'document' || form.type === 'presentation' || form.type === 'spreadsheet'"
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
                                    :accept="form.type === 'video'
                                        ? 'video/*'
                                        : form.type === 'pdf'
                                            ? 'application/pdf'
                                            : form.type === 'document'
                                                ? '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                                : form.type === 'presentation'
                                                    ? '.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'
                                                    : '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'"
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
                                        <span v-if="form.type === 'video'">Upload Video File</span>
                                        <span v-else-if="form.type === 'pdf'">Upload PDF File</span>
                                        <span v-else-if="form.type === 'document'">Upload Document File</span>
                                        <span v-else-if="form.type === 'presentation'">Upload Presentation File</span>
                                        <span v-else>Upload Spreadsheet File</span>
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

                        <div
                            v-if="loading"
                            class="text-sm text-gray-500"
                        >
                            Loading tags...
                        </div>
                        <div
                            v-else
                            class="flex flex-wrap gap-2"
                        >
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

                    <!-- Office document preview (vue-office) -->
                    <div
                        v-if="isOfficeType(form.type) && officeFileUrl"
                        class="rounded-lg border border-gray-200 bg-white overflow-hidden"
                    >
                        <Label class="block mb-2">Preview</Label>
                        <div class="min-h-[400px] border border-gray-100">
                            <VueOfficeDocx
                                v-if="form.type === 'document'"
                                :src="officeFileUrl"
                                style="height: 100%; min-height: 400px;"
                            />
                            <VueOfficePptx
                                v-else-if="form.type === 'presentation'"
                                :src="officeFileUrl"
                                style="height: 100%; min-height: 400px;"
                            />
                            <VueOfficeExcel
                                v-else-if="form.type === 'spreadsheet'"
                                :src="officeFileUrl"
                                style="height: 100%; min-height: 400px;"
                            />
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
                            {{ saving ? 'Creating...' : 'Create Content' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
