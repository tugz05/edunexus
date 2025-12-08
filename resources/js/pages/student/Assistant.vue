<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Send, MessageCircle, FileText, Video, Link as LinkIcon, HelpCircle, Sparkles } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface Message {
    role: 'user' | 'assistant';
    text: string;
    suggestions?: Array<{
        id: number;
        title: string;
        description: string | null;
        type: 'video' | 'pdf' | 'link' | 'quiz';
        url: string;
        subject: string;
        difficulty: string;
        tags?: Array<{ id: number; name: string }>;
    }>;
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'AI Assistant',
        href: '/student/assistant',
    },
];

const messages = ref<Message[]>([
    {
        role: 'assistant',
        text: "Hello! I'm your AI learning assistant. I can help you find learning resources, answer questions, and guide you through your studies. What would you like to learn about today?",
    },
]);
const inputMessage = ref('');
const sending = ref(false);
const error = ref<string | null>(null);
const chatContainer = ref<HTMLElement | null>(null);

const typeIcons = {
    video: Video,
    pdf: FileText,
    link: LinkIcon,
    quiz: HelpCircle,
};

const getTypeIcon = (type: string) => {
    return typeIcons[type as keyof typeof typeIcons] || FileText;
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const sendMessage = async () => {
    if (!inputMessage.value.trim() || sending.value) {
        return;
    }

    const userMessage = inputMessage.value.trim();
    inputMessage.value = '';
    error.value = null;

    // Add user message to chat
    messages.value.push({
        role: 'user',
        text: userMessage,
    });

    await scrollToBottom();

    sending.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/api/assistant/chat', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message: userMessage,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to get response');
        }

        const result = await response.json();

        // Add assistant reply
        messages.value.push({
            role: 'assistant',
            text: result.reply,
            suggestions: result.suggestions || [],
        });

        await scrollToBottom();
    } catch (err: any) {
        error.value = err.message || 'Failed to send message';
        messages.value.push({
            role: 'assistant',
            text: "I'm sorry, I encountered an error. Please try again.",
        });
        console.error('Error sending message:', err);
    } finally {
        sending.value = false;
    }
};

const handleKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};

onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <Head title="AI Assistant" />

    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="flex h-screen flex-col bg-gray-100 pt-16 pb-20">
            <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col px-4 py-6">
            <!-- Header -->
            <div class="mb-4">
                <div class="flex items-center gap-3">
                    <MessageCircle class="h-6 w-6 text-brand-primary" />
                    <h1 class="text-xl font-bold text-brand-primary md:text-2xl">
                        AI Learning Assistant
                    </h1>
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    Ask me anything about learning resources
                </p>
            </div>

            <!-- Chat Messages -->
            <div
                ref="chatContainer"
                class="flex-1 space-y-4 overflow-y-auto rounded-lg bg-white p-4 shadow-sm"
            >
                <div
                    v-for="(message, index) in messages"
                    :key="index"
                    class="flex"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[80%] rounded-lg px-4 py-2"
                        :class="message.role === 'user'
                            ? 'bg-brand-primary text-white'
                            : 'bg-gray-100 text-gray-900'"
                    >
                        <p class="whitespace-pre-wrap">{{ message.text }}</p>

                        <!-- Suggested Content -->
                        <div
                            v-if="message.suggestions && message.suggestions.length > 0"
                            class="mt-4 space-y-2"
                        >
                            <p class="mb-2 text-sm font-semibold">
                                Resources mentioned by the assistant:
                            </p>
                            <div class="grid grid-cols-1 gap-2">
                                <Link
                                    v-for="suggestion in message.suggestions"
                                    :key="suggestion.id"
                                    :href="`/student/content/${suggestion.id}`"
                                    class="group rounded-lg border border-gray-200 bg-white p-3 transition-colors hover:border-brand-primary hover:bg-brand-primary-light"
                                >
                                    <div class="flex items-start gap-3">
                                        <component
                                            :is="getTypeIcon(suggestion.type)"
                                            class="h-5 w-5 shrink-0 text-brand-primary"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <h4 class="mb-1 text-sm font-semibold text-gray-900 group-hover:text-brand-primary">
                                                {{ suggestion.title }}
                                            </h4>
                                            <p
                                                v-if="suggestion.description"
                                                class="mb-1 line-clamp-1 text-xs text-gray-600"
                                            >
                                                {{ suggestion.description }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                <span>{{ suggestion.subject }}</span>
                                                <span>•</span>
                                                <span>{{ suggestion.difficulty }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div
                    v-if="sending"
                    class="flex justify-start"
                >
                    <div class="rounded-lg bg-gray-100 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <Sparkles class="h-4 w-4 animate-pulse text-brand-primary" />
                            <span class="text-sm text-gray-600">Thinking...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div
                v-if="error"
                class="mt-2 rounded-lg bg-red-50 p-2 text-sm text-red-800"
            >
                {{ error }}
            </div>

            <!-- Input Area -->
            <div class="mt-4 flex gap-2">
                <Input
                    v-model="inputMessage"
                    @keypress="handleKeyPress"
                    placeholder="Type your message..."
                    class="flex-1"
                    :disabled="sending"
                />
                <Button
                    @click="sendMessage"
                    :disabled="sending || !inputMessage.trim()"
                    class="bg-brand-primary hover:bg-brand-primary-hover"
                >
                    <Send class="h-4 w-4" />
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
            <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col">
                <!-- Header -->
                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <MessageCircle class="h-6 w-6 text-brand-primary" />
                        <h1 class="text-xl font-bold text-brand-primary md:text-2xl">
                            AI Learning Assistant
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        Ask me anything about learning resources
                    </p>
                </div>

                <!-- Chat Messages -->
                <div
                    ref="chatContainer"
                    class="flex-1 space-y-4 overflow-y-auto rounded-lg bg-white p-4 shadow-sm"
                >
                    <div
                        v-for="(message, index) in messages"
                        :key="index"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] rounded-lg px-4 py-2"
                            :class="message.role === 'user'
                                ? 'bg-brand-primary text-white'
                                : 'bg-gray-100 text-gray-900'"
                        >
                            <p class="whitespace-pre-wrap">{{ message.text }}</p>

                            <!-- Suggested Content -->
                            <div
                                v-if="message.suggestions && message.suggestions.length > 0"
                                class="mt-4 space-y-2"
                            >
                                <p class="mb-2 text-sm font-semibold">
                                    Resources mentioned by the assistant:
                                </p>
                                <div class="grid grid-cols-1 gap-2">
                                    <Link
                                        v-for="suggestion in message.suggestions"
                                        :key="suggestion.id"
                                        :href="`/student/content/${suggestion.id}`"
                                        class="group rounded-lg border border-gray-200 bg-white p-3 transition-colors hover:border-brand-primary hover:bg-brand-primary-light"
                                    >
                                        <div class="flex items-start gap-3">
                                            <component
                                                :is="getTypeIcon(suggestion.type)"
                                                class="h-5 w-5 shrink-0 text-brand-primary"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <h4 class="mb-1 text-sm font-semibold text-gray-900 group-hover:text-brand-primary">
                                                    {{ suggestion.title }}
                                                </h4>
                                                <p
                                                    v-if="suggestion.description"
                                                    class="mb-1 line-clamp-1 text-xs text-gray-600"
                                                >
                                                    {{ suggestion.description }}
                                                </p>
                                                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                    <span>{{ suggestion.subject }}</span>
                                                    <span>•</span>
                                                    <span>{{ suggestion.difficulty }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div
                        v-if="sending"
                        class="flex justify-start"
                    >
                        <div class="rounded-lg bg-gray-100 px-4 py-2">
                            <div class="flex items-center gap-2">
                                <Sparkles class="h-4 w-4 animate-pulse text-brand-primary" />
                                <span class="text-sm text-gray-600">Thinking...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mt-2 rounded-lg bg-red-50 p-2 text-sm text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Input Area -->
                <div class="mt-4 flex gap-2">
                    <Input
                        v-model="inputMessage"
                        @keypress="handleKeyPress"
                        placeholder="Type your message..."
                        class="flex-1"
                        :disabled="sending"
                    />
                    <Button
                        @click="sendMessage"
                        :disabled="sending || !inputMessage.trim()"
                        class="bg-brand-primary hover:bg-brand-primary-hover"
                    >
                        <Send class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
