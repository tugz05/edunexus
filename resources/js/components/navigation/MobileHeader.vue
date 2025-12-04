<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Settings, Menu, Bell } from 'lucide-vue-next';
import { getInitials } from '@/composables/useInitials';

const page = usePage();
const user = computed(() => {
    try {
        if (page && page.props && page.props.auth) {
            return page.props.auth.user || null;
        }
        return null;
    } catch {
        return null;
    }
});
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || null;
});

const shouldShow = computed(() => {
    return userRole.value !== null && user.value !== null;
});
</script>

<template>
    <header
        v-if="shouldShow"
        class="fixed top-0 left-0 right-0 z-50 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm md:hidden"
    >
        <!-- Left: Logo/App Name -->
        <Link
            href="/dashboard"
            class="flex items-center gap-2"
        >
            <h1 class="text-lg font-semibold text-brand-primary">
                EduNexus
            </h1>
        </Link>

        <!-- Right: Actions -->
        <div class="flex items-center gap-2">
            <!-- Notifications (optional) -->
            <button
                class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100"
                aria-label="Notifications"
            >
                <Bell class="h-5 w-5" />
            </button>

            <!-- Settings/Preferences Button (for students) -->
            <Link
                v-if="userRole === 'student'"
                href="/student/profile"
                class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100"
                aria-label="Settings"
            >
                <Settings class="h-5 w-5" />
            </Link>

            <!-- User Avatar -->
            <Link href="/settings/profile">
                <Avatar class="h-10 w-10 overflow-hidden rounded-full cursor-pointer transition-opacity hover:opacity-80">
                    <AvatarImage
                        v-if="user?.avatar"
                        :src="user.avatar"
                        :alt="user?.name"
                    />
                    <AvatarFallback class="rounded-full bg-brand-primary text-white text-sm">
                        {{ getInitials(user?.name || 'U') }}
                    </AvatarFallback>
                </Avatar>
            </Link>
        </div>
    </header>
</template>
