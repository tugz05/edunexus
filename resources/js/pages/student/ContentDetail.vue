<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Bookmark, ExternalLink, ArrowLeft, FileText, Video, Link as LinkIcon, HelpCircle, Sparkles, Maximize2, Minimize2, Play, Download, CheckCircle2, XCircle } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';
import VueOfficeDocx from '@vue-office/docx';
import VueOfficeExcel from '@vue-office/excel';
import VueOfficePptx from '@vue-office/pptx';
import '@vue-office/docx/lib/index.css';
import '@vue-office/excel/lib/index.css';

interface ContentItem {
    id: number;
    title: string;
    description: string | null;
    type: 'video' | 'pdf' | 'link' | 'quiz' | 'document' | 'presentation' | 'spreadsheet';
    url: string;
    subject: string;
    difficulty: string;
    tags?: Array<{ id: number; name: string }>;
    creator?: {
        id: number;
        name: string;
    };
    summary?: string;
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
const summary = ref<string | null>(null);
const loadingSummary = ref(false);

// Interactive viewer states
const viewMode = ref<'embedded' | 'external'>('embedded');
const isFullscreen = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const pdfViewerRef = ref<HTMLIFrameElement | null>(null);

// Quiz states
const quizAnswers = ref<Record<string, string>>({});
const quizSubmitted = ref(false);
const quizScore = ref<number | null>(null);

const typeIcons = {
    video: Video,
    pdf: FileText,
    document: FileText,
    presentation: FileText,
    spreadsheet: FileText,
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

        // If summary is included in response, use it
        if (result.data.summary) {
            summary.value = result.data.summary;
        } else {
            // Otherwise fetch summary separately
            await fetchSummary();
        }

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

const fetchSummary = async () => {
    if (!contentItem.value || userRole.value !== 'student') {
        return;
    }

    loadingSummary.value = true;
    try {
        const response = await fetch(`/api/content/${contentItem.value.id}/summary`, {
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
            summary.value = result.data.summary;
        }
    } catch (err) {
        console.error('Error fetching summary:', err);
    } finally {
        loadingSummary.value = false;
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

// View mode toggle
const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'embedded' ? 'external' : 'embedded';
};

// Fullscreen toggle
const toggleFullscreen = () => {
    if (!isFullscreen.value) {
        const element = document.documentElement;
        if (element.requestFullscreen) {
            element.requestFullscreen();
        }
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
        isFullscreen.value = false;
    }
};

// Check if URL is a video file
const isVideoFile = (url: string) => {
    return /\.(mp4|webm|ogg|mov|avi)$/i.test(url);
};

// Check if URL is a PDF file
const isPdfFile = (url: string) => {
    return /\.pdf$/i.test(url) || url.toLowerCase().includes('pdf');
};

// Absolute URL for vue-office viewers (docx, xlsx, pptx)
const officeFileUrl = computed(() => {
    if (!contentItem.value?.url || typeof window === 'undefined') return '';
    try {
        return new URL(contentItem.value.url, window.location.origin).toString();
    } catch {
        return contentItem.value.url;
    }
});

const isOfficeType = (type: string) =>
    type === 'document' || type === 'presentation' || type === 'spreadsheet';

// Get YouTube embed URL
const getYouTubeEmbedUrl = (url: string) => {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    if (match && match[2].length === 11) {
        return `https://www.youtube.com/embed/${match[2]}?rel=0`;
    }
    return null;
};

// Get Vimeo embed URL
const getVimeoEmbedUrl = (url: string) => {
    const regExp = /vimeo.com\/(\d+)/;
    const match = url.match(regExp);
    if (match) {
        return `https://player.vimeo.com/video/${match[1]}`;
    }
    return null;
};

// Quiz handlers
const handleQuizAnswer = (questionId: string, answer: string) => {
    quizAnswers.value[questionId] = answer;
};

const submitQuiz = () => {
    // Simple quiz submission - in a real app, this would send to backend
    quizSubmitted.value = true;
    // Calculate score (mock implementation)
    const totalQuestions = Object.keys(quizAnswers.value).length;
    const correctAnswers = Object.values(quizAnswers.value).filter(a => a).length;
    quizScore.value = totalQuestions > 0 ? Math.round((correctAnswers / totalQuestions) * 100) : 0;
};

// Listen for fullscreen changes
const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

onMounted(() => {
    fetchContent();
    document.addEventListener('fullscreenchange', handleFullscreenChange);
});

// Cleanup
onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
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

                <!-- AI Summary -->
                <div
                    v-if="summary"
                    class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4"
                >
                    <div class="flex items-start gap-2">
                        <Sparkles class="h-5 w-5 shrink-0 text-blue-600 mt-0.5" />
                        <div class="flex-1">
                            <p class="text-xs text-blue-600 font-medium mb-1">AI-generated summary</p>
                            <p class="text-sm text-gray-800">
                                {{ summary }}
                            </p>
                        </div>
                    </div>
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

                <!-- Interactive Content Viewer -->
                <div class="mb-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ contentItem.type === 'video' ? 'Watch Video' : contentItem.type === 'pdf' ? 'View PDF' : contentItem.type === 'document' ? 'View Document' : contentItem.type === 'presentation' ? 'View Presentation' : contentItem.type === 'spreadsheet' ? 'View Spreadsheet' : contentItem.type === 'quiz' ? 'Take Quiz' : 'View Content' }}
                        </h2>
                        <div class="flex items-center gap-2">
                            <Button
                                @click="toggleViewMode"
                                variant="outline"
                                size="sm"
                            >
                                <component
                                    :is="viewMode === 'embedded' ? ExternalLink : Maximize2"
                                    class="mr-2 h-4 w-4"
                                />
                                {{ viewMode === 'embedded' ? 'Open in New Tab' : 'View Embedded' }}
                            </Button>
                            <Button
                                v-if="viewMode === 'embedded' && (contentItem.type === 'video' || contentItem.type === 'pdf' || isOfficeType(contentItem.type))"
                                @click="toggleFullscreen"
                                variant="outline"
                                size="sm"
                            >
                                <component
                                    :is="isFullscreen ? Minimize2 : Maximize2"
                                    class="h-4 w-4"
                                />
                            </Button>
                        </div>
                    </div>

                    <!-- Video Viewer -->
                    <div
                        v-if="contentItem.type === 'video' && viewMode === 'embedded'"
                        class="relative mb-4 overflow-hidden rounded-lg bg-black"
                        :class="isFullscreen ? 'fixed inset-0 z-50' : 'aspect-video'"
                    >
                        <!-- YouTube Embed -->
                        <div
                            v-if="getYouTubeEmbedUrl(contentItem.url)"
                            class="absolute inset-0"
                        >
                            <iframe
                                :src="getYouTubeEmbedUrl(contentItem.url)"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                class="h-full w-full"
                            />
                        </div>
                        <!-- Vimeo Embed -->
                        <div
                            v-else-if="getVimeoEmbedUrl(contentItem.url)"
                            class="absolute inset-0"
                        >
                            <iframe
                                :src="getVimeoEmbedUrl(contentItem.url)"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                                class="h-full w-full"
                            />
                        </div>
                        <!-- Direct Video File -->
                        <video
                            v-else
                            ref="videoRef"
                            :src="contentItem.url"
                            controls
                            class="h-full w-full"
                        >
                            Your browser does not support the video tag.
                        </video>
                    </div>

                    <!-- PDF Viewer -->
                    <div
                        v-else-if="contentItem.type === 'pdf' && viewMode === 'embedded'"
                        class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                        :class="isFullscreen ? 'fixed inset-0 z-50' : 'h-[600px]'"
                    >
                        <iframe
                            ref="pdfViewerRef"
                            :src="`${contentItem.url}#toolbar=1&navpanes=1&scrollbar=1`"
                            class="h-full w-full"
                            frameborder="0"
                        >
                            <div class="flex h-full items-center justify-center p-8">
                                <div class="text-center">
                                    <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                    <p class="mb-4 text-gray-600">
                                        Unable to display PDF. Please download or open in a new tab.
                                    </p>
                                    <Button
                                        :href="contentItem.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Download class="mr-2 h-4 w-4" />
                                        Download PDF
                                    </Button>
                                </div>
                            </div>
                        </iframe>
                    </div>

                    <!-- Office Document Viewer (vue-office) -->
                    <div
                        v-else-if="contentItem.type === 'document' && viewMode === 'embedded'"
                        class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                        :class="isFullscreen ? 'fixed inset-0 z-50' : 'min-h-[500px]'"
                    >
                        <VueOfficeDocx
                            v-if="officeFileUrl"
                            :src="officeFileUrl"
                            class="vue-office-docx"
                            style="height: 100%; min-height: 500px;"
                        />
                    </div>
                    <div
                        v-else-if="contentItem.type === 'presentation' && viewMode === 'embedded'"
                        class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                        :class="isFullscreen ? 'fixed inset-0 z-50' : 'min-h-[500px]'"
                    >
                        <VueOfficePptx
                            v-if="officeFileUrl"
                            :src="officeFileUrl"
                            class="vue-office-pptx"
                            style="height: 100%; min-height: 500px;"
                        />
                    </div>
                    <div
                        v-else-if="contentItem.type === 'spreadsheet' && viewMode === 'embedded'"
                        class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                        :class="isFullscreen ? 'fixed inset-0 z-50' : 'min-h-[500px]'"
                    >
                        <VueOfficeExcel
                            v-if="officeFileUrl"
                            :src="officeFileUrl"
                            class="vue-office-excel"
                            style="height: 100%; min-height: 500px;"
                        />
                    </div>

                    <!-- Link Preview/Embed -->
                    <div
                        v-else-if="contentItem.type === 'link' && viewMode === 'embedded'"
                        class="mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                    >
                        <div class="border-b border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <LinkIcon class="h-5 w-5 text-gray-500" />
                                    <span class="text-sm font-medium text-gray-700">External Link Preview</span>
                                </div>
                                <Button
                                    :href="contentItem.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    size="sm"
                                    variant="outline"
                                >
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    Open Full Site
                                </Button>
                            </div>
                        </div>
                        <div class="aspect-video">
                            <iframe
                                :src="contentItem.url"
                                class="h-full w-full"
                                frameborder="0"
                                sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                            >
                                <div class="flex h-full items-center justify-center p-8">
                                    <div class="text-center">
                                        <LinkIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                        <p class="mb-4 text-gray-600">
                                            Preview not available. Click the button above to open the link.
                                        </p>
                                        <Button
                                            :href="contentItem.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <ExternalLink class="mr-2 h-4 w-4" />
                                            Open Link
                                        </Button>
                                    </div>
                                </div>
                            </iframe>
                        </div>
                    </div>

                    <!-- Quiz Interface -->
                    <div
                        v-else-if="contentItem.type === 'quiz' && viewMode === 'embedded'"
                        class="mb-4 rounded-lg border border-gray-200 bg-white p-6"
                    >
                        <div
                            v-if="!quizSubmitted"
                            class="space-y-6"
                        >
                            <div class="mb-4">
                                <h3 class="mb-2 text-lg font-semibold text-gray-900">
                                    Interactive Quiz
                                </h3>
                                <p class="text-sm text-gray-600">
                                    Answer the questions below based on the content you've reviewed.
                                </p>
                            </div>

                            <!-- Sample Quiz Questions (in a real app, these would come from the backend) -->
                            <div
                                v-for="(question, index) in [
                                    { id: 'q1', text: 'What is the main topic covered in this content?', options: ['Option A', 'Option B', 'Option C', 'Option D'] },
                                    { id: 'q2', text: 'Which concept is most important?', options: ['Concept 1', 'Concept 2', 'Concept 3', 'Concept 4'] },
                                    { id: 'q3', text: 'What would you rate your understanding?', options: ['Excellent', 'Good', 'Fair', 'Needs Improvement'] }
                                ]"
                                :key="question.id"
                                class="rounded-lg border border-gray-200 p-4"
                            >
                                <p class="mb-3 font-medium text-gray-900">
                                    {{ index + 1 }}. {{ question.text }}
                                </p>
                                <div class="space-y-2">
                                    <label
                                        v-for="option in question.options"
                                        :key="option"
                                        class="flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 p-3 transition-colors hover:bg-gray-50"
                                        :class="quizAnswers[question.id] === option ? 'border-brand-primary bg-brand-primary-light' : ''"
                                    >
                                        <input
                                            type="radio"
                                            :name="question.id"
                                            :value="option"
                                            :checked="quizAnswers[question.id] === option"
                                            @change="handleQuizAnswer(question.id, option)"
                                            class="h-4 w-4 text-brand-primary focus:ring-brand-primary"
                                        />
                                        <span class="text-sm text-gray-700">{{ option }}</span>
                                    </label>
                                </div>
                            </div>

                            <Button
                                @click="submitQuiz"
                                class="w-full bg-brand-primary hover:bg-brand-primary-hover"
                                :disabled="Object.keys(quizAnswers).length === 0"
                            >
                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                Submit Quiz
                            </Button>
                        </div>

                        <!-- Quiz Results -->
                        <div
                            v-else
                            class="text-center"
                        >
                            <div
                                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full"
                                :class="quizScore && quizScore >= 70 ? 'bg-green-100' : 'bg-yellow-100'"
                            >
                                <component
                                    :is="quizScore && quizScore >= 70 ? CheckCircle2 : XCircle"
                                    class="h-8 w-8"
                                    :class="quizScore && quizScore >= 70 ? 'text-green-600' : 'text-yellow-600'"
                                />
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-900">
                                Quiz Completed!
                            </h3>
                            <p class="mb-4 text-3xl font-bold text-brand-primary">
                                {{ quizScore }}%
                            </p>
                            <p class="mb-6 text-gray-600">
                                {{ quizScore && quizScore >= 70 ? 'Great job! You have a good understanding of the content.' : 'Keep reviewing the content to improve your understanding.' }}
                            </p>
                            <div class="flex gap-3">
                                <Button
                                    @click="() => { quizSubmitted = false; quizAnswers = {}; quizScore = null; }"
                                    variant="outline"
                                    class="flex-1"
                                >
                                    Retake Quiz
                                </Button>
                                <Button
                                    :href="contentItem.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex-1 bg-brand-primary hover:bg-brand-primary-hover"
                                >
                                    Review Content
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- External Link Button (when viewMode is external) -->
                    <div
                        v-else
                        class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-6 text-center"
                    >
                        <ExternalLink class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <p class="mb-4 text-gray-600">
                            Click the button below to open this content in a new tab.
                        </p>
                        <Button
                            :href="contentItem.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="bg-brand-primary hover:bg-brand-primary-hover"
                        >
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Open {{ contentItem.type === 'video' ? 'Video' : contentItem.type === 'pdf' ? 'PDF' : contentItem.type === 'document' ? 'Document' : contentItem.type === 'presentation' ? 'Presentation' : contentItem.type === 'spreadsheet' ? 'Spreadsheet' : contentItem.type === 'quiz' ? 'Quiz' : 'Content' }}
                        </Button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        v-if="viewMode === 'external'"
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

                    <!-- AI Summary -->
                    <div
                        v-if="summary"
                        class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4"
                    >
                        <div class="flex items-start gap-2">
                            <Sparkles class="h-5 w-5 shrink-0 text-blue-600 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs text-blue-600 font-medium mb-1">AI-generated summary</p>
                                <p class="text-sm text-gray-800">
                                    {{ summary }}
                                </p>
                            </div>
                        </div>
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

                    <!-- Interactive Content Viewer -->
                    <div class="mb-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ contentItem.type === 'video' ? 'Watch Video' : contentItem.type === 'pdf' ? 'View PDF' : contentItem.type === 'document' ? 'View Document' : contentItem.type === 'presentation' ? 'View Presentation' : contentItem.type === 'spreadsheet' ? 'View Spreadsheet' : contentItem.type === 'quiz' ? 'Take Quiz' : 'View Content' }}
                            </h2>
                            <div class="flex items-center gap-2">
                                <Button
                                    @click="toggleViewMode"
                                    variant="outline"
                                    size="sm"
                                >
                                    <component
                                        :is="viewMode === 'embedded' ? ExternalLink : Maximize2"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ viewMode === 'embedded' ? 'Open in New Tab' : 'View Embedded' }}
                                </Button>
                                <Button
                                    v-if="viewMode === 'embedded' && (contentItem.type === 'video' || contentItem.type === 'pdf' || isOfficeType(contentItem.type))"
                                    @click="toggleFullscreen"
                                    variant="outline"
                                    size="sm"
                                >
                                    <component
                                        :is="isFullscreen ? Minimize2 : Maximize2"
                                        class="h-4 w-4"
                                    />
                                </Button>
                            </div>
                        </div>

                        <!-- Video Viewer -->
                        <div
                            v-if="contentItem.type === 'video' && viewMode === 'embedded'"
                            class="relative mb-4 overflow-hidden rounded-lg bg-black"
                            :class="isFullscreen ? 'fixed inset-0 z-50' : 'aspect-video'"
                        >
                            <!-- YouTube Embed -->
                            <div
                                v-if="getYouTubeEmbedUrl(contentItem.url)"
                                class="absolute inset-0"
                            >
                                <iframe
                                    :src="getYouTubeEmbedUrl(contentItem.url)"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    class="h-full w-full"
                                />
                            </div>
                            <!-- Vimeo Embed -->
                            <div
                                v-else-if="getVimeoEmbedUrl(contentItem.url)"
                                class="absolute inset-0"
                            >
                                <iframe
                                    :src="getVimeoEmbedUrl(contentItem.url)"
                                    frameborder="0"
                                    allow="autoplay; fullscreen; picture-in-picture"
                                    allowfullscreen
                                    class="h-full w-full"
                                />
                            </div>
                            <!-- Direct Video File -->
                            <video
                                v-else
                                ref="videoRef"
                                :src="contentItem.url"
                                controls
                                class="h-full w-full"
                            >
                                Your browser does not support the video tag.
                            </video>
                        </div>

                        <!-- PDF Viewer -->
                        <div
                            v-else-if="contentItem.type === 'pdf' && viewMode === 'embedded'"
                            class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                            :class="isFullscreen ? 'fixed inset-0 z-50' : 'h-[600px]'"
                        >
                            <iframe
                                ref="pdfViewerRef"
                                :src="`${contentItem.url}#toolbar=1&navpanes=1&scrollbar=1`"
                                class="h-full w-full"
                                frameborder="0"
                            >
                                <div class="flex h-full items-center justify-center p-8">
                                    <div class="text-center">
                                        <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                        <p class="mb-4 text-gray-600">
                                            Unable to display PDF. Please download or open in a new tab.
                                        </p>
                                        <Button
                                            :href="contentItem.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Download class="mr-2 h-4 w-4" />
                                            Download PDF
                                        </Button>
                                    </div>
                                </div>
                            </iframe>
                        </div>

                        <!-- Office Document Viewer (vue-office) -->
                        <div
                            v-else-if="contentItem.type === 'document' && viewMode === 'embedded'"
                            class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                            :class="isFullscreen ? 'fixed inset-0 z-50' : 'min-h-[500px]'"
                        >
                            <VueOfficeDocx
                                v-if="officeFileUrl"
                                :src="officeFileUrl"
                                class="vue-office-docx"
                                style="height: 100%; min-height: 500px;"
                            />
                        </div>
                        <div
                            v-else-if="contentItem.type === 'presentation' && viewMode === 'embedded'"
                            class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                            :class="isFullscreen ? 'fixed inset-0 z-50' : 'min-h-[500px]'"
                        >
                            <VueOfficePptx
                                v-if="officeFileUrl"
                                :src="officeFileUrl"
                                class="vue-office-pptx"
                                style="height: 100%; min-height: 500px;"
                            />
                        </div>
                        <div
                            v-else-if="contentItem.type === 'spreadsheet' && viewMode === 'embedded'"
                            class="relative mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                            :class="isFullscreen ? 'fixed inset-0 z-50' : 'min-h-[500px]'"
                        >
                            <VueOfficeExcel
                                v-if="officeFileUrl"
                                :src="officeFileUrl"
                                class="vue-office-excel"
                                style="height: 100%; min-height: 500px;"
                            />
                        </div>

                        <!-- Link Preview/Embed -->
                        <div
                            v-else-if="contentItem.type === 'link' && viewMode === 'embedded'"
                            class="mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white"
                        >
                            <div class="border-b border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <LinkIcon class="h-5 w-5 text-gray-500" />
                                        <span class="text-sm font-medium text-gray-700">External Link Preview</span>
                                    </div>
                                    <Button
                                        :href="contentItem.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        size="sm"
                                        variant="outline"
                                    >
                                        <ExternalLink class="mr-2 h-4 w-4" />
                                        Open Full Site
                                    </Button>
                                </div>
                            </div>
                            <div class="aspect-video">
                                <iframe
                                    :src="contentItem.url"
                                    class="h-full w-full"
                                    frameborder="0"
                                    sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                >
                                    <div class="flex h-full items-center justify-center p-8">
                                        <div class="text-center">
                                            <LinkIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                            <p class="mb-4 text-gray-600">
                                                Preview not available. Click the button above to open the link.
                                            </p>
                                            <Button
                                                :href="contentItem.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <ExternalLink class="mr-2 h-4 w-4" />
                                                Open Link
                                            </Button>
                                        </div>
                                    </div>
                                </iframe>
                            </div>
                        </div>

                        <!-- Quiz Interface -->
                        <div
                            v-else-if="contentItem.type === 'quiz' && viewMode === 'embedded'"
                            class="mb-4 rounded-lg border border-gray-200 bg-white p-6"
                        >
                            <div
                                v-if="!quizSubmitted"
                                class="space-y-6"
                            >
                                <div class="mb-4">
                                    <h3 class="mb-2 text-lg font-semibold text-gray-900">
                                        Interactive Quiz
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Answer the questions below based on the content you've reviewed.
                                    </p>
                                </div>

                                <!-- Sample Quiz Questions (in a real app, these would come from the backend) -->
                                <div
                                    v-for="(question, index) in [
                                        { id: 'q1', text: 'What is the main topic covered in this content?', options: ['Option A', 'Option B', 'Option C', 'Option D'] },
                                        { id: 'q2', text: 'Which concept is most important?', options: ['Concept 1', 'Concept 2', 'Concept 3', 'Concept 4'] },
                                        { id: 'q3', text: 'What would you rate your understanding?', options: ['Excellent', 'Good', 'Fair', 'Needs Improvement'] }
                                    ]"
                                    :key="question.id"
                                    class="rounded-lg border border-gray-200 p-4"
                                >
                                    <p class="mb-3 font-medium text-gray-900">
                                        {{ index + 1 }}. {{ question.text }}
                                    </p>
                                    <div class="space-y-2">
                                        <label
                                            v-for="option in question.options"
                                            :key="option"
                                            class="flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 p-3 transition-colors hover:bg-gray-50"
                                            :class="quizAnswers[question.id] === option ? 'border-brand-primary bg-brand-primary-light' : ''"
                                        >
                                            <input
                                                type="radio"
                                                :name="question.id"
                                                :value="option"
                                                :checked="quizAnswers[question.id] === option"
                                                @change="handleQuizAnswer(question.id, option)"
                                                class="h-4 w-4 text-brand-primary focus:ring-brand-primary"
                                            />
                                            <span class="text-sm text-gray-700">{{ option }}</span>
                                        </label>
                                    </div>
                                </div>

                                <Button
                                    @click="submitQuiz"
                                    class="w-full bg-brand-primary hover:bg-brand-primary-hover"
                                    :disabled="Object.keys(quizAnswers).length === 0"
                                >
                                    <CheckCircle2 class="mr-2 h-4 w-4" />
                                    Submit Quiz
                                </Button>
                            </div>

                            <!-- Quiz Results -->
                            <div
                                v-else
                                class="text-center"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full"
                                    :class="quizScore && quizScore >= 70 ? 'bg-green-100' : 'bg-yellow-100'"
                                >
                                    <component
                                        :is="quizScore && quizScore >= 70 ? CheckCircle2 : XCircle"
                                        class="h-8 w-8"
                                        :class="quizScore && quizScore >= 70 ? 'text-green-600' : 'text-yellow-600'"
                                    />
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-900">
                                    Quiz Completed!
                                </h3>
                                <p class="mb-4 text-3xl font-bold text-brand-primary">
                                    {{ quizScore }}%
                                </p>
                                <p class="mb-6 text-gray-600">
                                    {{ quizScore && quizScore >= 70 ? 'Great job! You have a good understanding of the content.' : 'Keep reviewing the content to improve your understanding.' }}
                                </p>
                                <div class="flex gap-3">
                                    <Button
                                        @click="() => { quizSubmitted = false; quizAnswers = {}; quizScore = null; }"
                                        variant="outline"
                                        class="flex-1"
                                    >
                                        Retake Quiz
                                    </Button>
                                    <Button
                                        :href="contentItem.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex-1 bg-brand-primary hover:bg-brand-primary-hover"
                                    >
                                        Review Content
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- External Link Button (when viewMode is external) -->
                        <div
                            v-else
                            class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-6 text-center"
                        >
                            <ExternalLink class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                            <p class="mb-4 text-gray-600">
                                Click the button below to open this content in a new tab.
                            </p>
                            <Button
                                :href="contentItem.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="bg-brand-primary hover:bg-brand-primary-hover"
                            >
                                <ExternalLink class="mr-2 h-4 w-4" />
                                Open {{ contentItem.type === 'video' ? 'Video' : contentItem.type === 'pdf' ? 'PDF' : contentItem.type === 'document' ? 'Document' : contentItem.type === 'presentation' ? 'Presentation' : contentItem.type === 'spreadsheet' ? 'Spreadsheet' : contentItem.type === 'quiz' ? 'Quiz' : 'Content' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            v-if="viewMode === 'external'"
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
