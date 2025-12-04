<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Home,
    BookOpen,
    Sparkles,
    MessageCircle,
    Bookmark,
    FolderKanban,
    Plus,
    BarChart3,
} from 'lucide-vue-next';

interface Props {
    role: 'student' | 'teacher';
}

const props = defineProps<Props>();
const page = usePage();

// Student navigation items (3rd item is center button)
const studentNavItems = [
    {
        name: 'Home',
        icon: Home,
        path: '/dashboard', // Main dashboard route
    },
    {
        name: 'Content',
        icon: BookOpen,
        path: '/student/content',
    },
    {
        name: 'AI Recommendations',
        icon: Sparkles,
        path: '/student/recommendations',
    },
    {
        name: 'AI Assistant',
        icon: MessageCircle,
        path: '/student/assistant',
    },
    {
        name: 'Saved',
        icon: Bookmark,
        path: '/student/saved',
    },
];

// Teacher navigation items (3rd item is center button)
const teacherNavItems = [
    {
        name: 'Home',
        icon: Home,
        path: '/dashboard', // Main dashboard route
    },
    {
        name: 'Content Manager',
        icon: FolderKanban,
        path: '/teacher/content',
    },
    {
        name: 'Add Content',
        icon: Plus,
        path: '/teacher/content/create',
    },
    {
        name: 'AI Assistant',
        icon: MessageCircle,
        path: '/teacher/assistant',
    },
    {
        name: 'Analytics',
        icon: BarChart3,
        path: '/teacher/analytics',
    },
];

const roleBasedNavItems = computed(() => {
    return props.role === 'student' ? studentNavItems : teacherNavItems;
});

const isActive = (path: string) => {
    // For Inertia.js - check current URL
    try {
        const current = (page && page.url) ? page.url : window.location.pathname;
        // Special handling for dashboard route
        if (path === '/dashboard') {
            return current === '/dashboard' || current === '/';
        }
        return current === path || current.startsWith(path + '/');
    } catch {
        const current = window.location.pathname;
        return current === path || current.startsWith(path + '/');
    }
};
</script>

<template>
    <nav
        class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around rounded-t-2xl border-t border-gray-200 bg-white px-2 py-2 shadow-lg md:hidden"
    >
        <template
            v-for="(item, index) in roleBasedNavItems"
            :key="`${props.role}-${index}`"
        >
            <!-- Center button (3rd item - special styling) -->
            <Link
                v-if="index === 2"
                :href="item.path"
                :class="[
                    'flex h-14 w-14 items-center justify-center rounded-full transition-transform',
                    isActive(item.path)
                        ? 'bg-brand-primary text-white shadow-lg'
                        : 'bg-[#1E3A8A] text-white shadow-md',
                    'hover:scale-105 active:scale-95',
                ]"
                :aria-label="item.name"
            >
                <component
                    :is="item.icon"
                    class="h-7 w-7"
                />
            </Link>

            <!-- Regular navigation items -->
            <Link
                v-else
                :href="item.path"
                :class="[
                    'flex flex-col items-center gap-1 rounded-lg px-3 py-2 transition-colors',
                    isActive(item.path)
                        ? 'text-brand-primary'
                        : 'text-gray-400 hover:bg-gray-50 hover:text-gray-600',
                ]"
                :aria-label="item.name"
            >
                <component
                    :is="item.icon"
                    class="h-6 w-6"
                />
                <span class="text-xs font-medium">{{ item.name }}</span>
            </Link>
        </template>
    </nav>
</template>

