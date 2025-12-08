<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Send, MessageCircle, FileText, Video, Link as LinkIcon, HelpCircle, Sparkles, Trash2 } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';
import FormattedText from '@/components/FormattedText.vue';

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
    isTyping?: boolean;
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'teacher';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'AI Assistant',
        href: '/teacher/assistant',
    },
];

const messages = ref<Message[]>([]);
const inputMessage = ref('');
const sending = ref(false);
const error = ref<string | null>(null);
const loadingHistory = ref(false);
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

const loadConversationHistory = async () => {
    loadingHistory.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch('/api/assistant/history', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const result = await response.json();
            if (result.data && result.data.length > 0) {
                // Load conversation history
                messages.value = result.data.map((conv: any) => ({
                    role: conv.role,
                    text: conv.message,
                    suggestions: conv.suggested_content_ids ? [] : undefined,
                }));
                await scrollToBottom();
            } else {
                // No history, show welcome message
                messages.value = [{
                    role: 'assistant',
                    text: "Hello! I'm your AI teaching assistant. I can help you find learning resources for your students, suggest content based on topics, and assist with curriculum planning. What can I help you with today?",
                }];
            }
        } else {
            // On error, show welcome message
            messages.value = [{
                role: 'assistant',
                text: "Hello! I'm your AI teaching assistant. I can help you find learning resources for your students, suggest content based on topics, and assist with curriculum planning. What can I help you with today?",
            }];
        }
    } catch (err) {
        console.error('Error loading conversation history:', err);
        // On error, show welcome message
        messages.value = [{
            role: 'assistant',
            text: "Hello! I'm your AI teaching assistant. I can help you find learning resources for your students, suggest content based on topics, and assist with curriculum planning. What can I help you with today?",
        }];
    } finally {
        loadingHistory.value = false;
    }
};

const clearHistory = async () => {
    if (!confirm('Are you sure you want to clear all conversation history?')) {
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch('/api/assistant/history', {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            // Reset to welcome message
            messages.value = [{
                role: 'assistant',
                text: "Hello! I'm your AI teaching assistant. I can help you find learning resources for your students, suggest content based on topics, and assist with curriculum planning. What can I help you with today?",
            }];
        }
    } catch (err) {
        console.error('Error clearing conversation history:', err);
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

        // Add assistant reply with typing animation
        const assistantMessage: Message = {
            role: 'assistant',
            text: result.reply,
            suggestions: result.suggestions || [],
            isTyping: true,
        };
        messages.value.push(assistantMessage);

        await scrollToBottom();

        // Mark as not typing after a short delay (typing animation handled by FormattedText)
        setTimeout(() => {
            assistantMessage.isTyping = false;
        }, 100);
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
    loadConversationHistory();
});
</script>

<template>
    <Head title="AI Assistant" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="flex h-screen flex-col bg-gray-100 pt-16 pb-20">
            <div class="mx-auto flex w-full max-w-4xl h-full flex-col px-4 py-6">
            <!-- Header -->
            <div class="mb-4 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <MessageCircle class="h-6 w-6 text-brand-primary" />
                        <h1 class="text-xl font-bold text-brand-primary md:text-2xl">
                            AI Teaching Assistant
                        </h1>
                    </div>
                    <Button
                        v-if="messages.length > 1"
                        @click="clearHistory"
                        variant="ghost"
                        size="sm"
                        class="text-gray-500 hover:text-red-600"
                    >
                        <Trash2 class="h-4 w-4 mr-1" />
                        Clear
                    </Button>
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    Get help finding resources for your students
                </p>
            </div>

            <!-- Chat Messages -->
            <div
                ref="chatContainer"
                class="flex-1 min-h-0 space-y-4 overflow-y-auto rounded-xl bg-gradient-to-b from-gray-50 to-white p-4 shadow-inner"
            >
                <div
                    v-for="(message, index) in messages"
                    :key="index"
                    class="flex"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[85%] rounded-xl px-4 py-3 shadow-sm"
                        :class="message.role === 'user'
                            ? 'bg-gradient-to-br from-brand-primary to-brand-primary/90 text-white'
                            : 'bg-white border border-gray-200 text-gray-900'"
                    >
                        <div v-if="message.role === 'user'" class="text-sm leading-relaxed whitespace-pre-wrap">
                            {{ message.text }}
                        </div>
                        <FormattedText
                            v-else
                            :text="message.text"
                            :show-typing="message.isTyping ?? false"
                            class="text-sm"
                        />

                        <!-- Suggested Content -->
                        <div
                            v-if="message.suggestions && message.suggestions.length > 0"
                            class="mt-4 pt-4 border-t border-gray-200 space-y-2"
                        >
                            <p class="mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Resources mentioned by the assistant:
                            </p>
                            <div class="grid grid-cols-1 gap-2">
                                <Link
                                    v-for="suggestion in message.suggestions"
                                    :key="suggestion.id"
                                    :href="`/student/content/${suggestion.id}`"
                                    class="group rounded-lg border border-gray-200 bg-white p-3 transition-all hover:border-brand-primary hover:bg-brand-primary-light hover:shadow-md"
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
                    <div class="rounded-xl bg-white border border-gray-200 px-4 py-3 shadow-sm">
                        <div class="flex items-center gap-2">
                            <Sparkles class="h-4 w-4 animate-pulse text-brand-primary" />
                            <span class="text-sm text-gray-600 font-medium">Thinking...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div
                v-if="error"
                class="mt-2 flex-shrink-0 rounded-lg bg-red-50 p-2 text-sm text-red-800"
            >
                {{ error }}
            </div>

            <!-- Input Area -->
            <div class="mt-4 flex-shrink-0 flex gap-2 rounded-xl bg-white p-2 shadow-md border border-gray-200">
                <Input
                    v-model="inputMessage"
                    @keypress="handleKeyPress"
                    placeholder="Type your message..."
                    class="flex-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                    :disabled="sending"
                />
                <Button
                    @click="sendMessage"
                    :disabled="sending || !inputMessage.trim()"
                    class="bg-brand-primary hover:bg-brand-primary-hover shadow-sm"
                    size="icon"
                >
                    <Send class="h-4 w-4" />
                </Button>
            </div>
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
            <div class="mx-auto flex w-full max-w-4xl h-full flex-col">
                <!-- Header -->
                <div class="mb-4 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <MessageCircle class="h-6 w-6 text-brand-primary" />
                            <h1 class="text-xl font-bold text-brand-primary md:text-2xl">
                                AI Teaching Assistant
                            </h1>
                        </div>
                        <Button
                            v-if="messages.length > 1"
                            @click="clearHistory"
                            variant="ghost"
                            size="sm"
                            class="text-gray-500 hover:text-red-600"
                        >
                            <Trash2 class="h-4 w-4 mr-1" />
                            Clear History
                        </Button>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        Get help finding resources for your students
                    </p>
                </div>

                <!-- Chat Messages -->
                <div
                    ref="chatContainer"
                    class="flex-1 min-h-0 space-y-4 overflow-y-auto rounded-xl bg-gradient-to-b from-gray-50 to-white p-4 shadow-inner"
                >
                    <div
                        v-for="(message, index) in messages"
                        :key="index"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[85%] rounded-xl px-4 py-3 shadow-sm"
                            :class="message.role === 'user'
                                ? 'bg-gradient-to-br from-brand-primary to-brand-primary/90 text-white'
                                : 'bg-white border border-gray-200 text-gray-900'"
                        >
                            <div v-if="message.role === 'user'" class="text-sm leading-relaxed whitespace-pre-wrap">
                                {{ message.text }}
                            </div>
                            <FormattedText
                                v-else
                                :text="message.text"
                                :show-typing="message.isTyping ?? false"
                                class="text-sm"
                            />

                            <!-- Suggested Content -->
                            <div
                                v-if="message.suggestions && message.suggestions.length > 0"
                                class="mt-4 pt-4 border-t border-gray-200 space-y-2"
                            >
                                <p class="mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Resources mentioned by the assistant:
                                </p>
                                <div class="grid grid-cols-1 gap-2">
                                    <Link
                                        v-for="suggestion in message.suggestions"
                                        :key="suggestion.id"
                                        :href="`/student/content/${suggestion.id}`"
                                        class="group rounded-lg border border-gray-200 bg-white p-3 transition-all hover:border-brand-primary hover:bg-brand-primary-light hover:shadow-md"
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
                        <div class="rounded-xl bg-white border border-gray-200 px-4 py-3 shadow-sm">
                            <div class="flex items-center gap-2">
                                <Sparkles class="h-4 w-4 animate-pulse text-brand-primary" />
                                <span class="text-sm text-gray-600 font-medium">Thinking...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="mt-2 flex-shrink-0 rounded-lg bg-red-50 p-2 text-sm text-red-800"
                >
                    {{ error }}
                </div>

                <!-- Input Area -->
                <div class="mt-4 flex-shrink-0 flex gap-2 rounded-xl bg-white p-2 shadow-md border border-gray-200">
                    <Input
                        v-model="inputMessage"
                        @keypress="handleKeyPress"
                        placeholder="Type your message..."
                        class="flex-1 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                        :disabled="sending"
                    />
                    <Button
                        @click="sendMessage"
                        :disabled="sending || !inputMessage.trim()"
                        class="bg-brand-primary hover:bg-brand-primary-hover shadow-sm"
                        size="icon"
                    >
                        <Send class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
