<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Settings, Menu, Bell, LogOut, User } from 'lucide-vue-next';
import { getInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

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

const handleLogout = () => {
    router.flushAll();
};
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

            <!-- User Avatar with Dropdown Menu -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button class="flex h-10 w-10 items-center justify-center rounded-full transition-opacity hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                        <Avatar class="h-10 w-10 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="user?.avatar"
                                :src="user.avatar"
                                :alt="user?.name"
                            />
                            <AvatarFallback class="rounded-full bg-brand-primary text-white text-sm">
                                {{ getInitials(user?.name || 'U') }}
                            </AvatarFallback>
                        </Avatar>
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="w-56"
                    side="bottom"
                    :side-offset="8"
                >
                    <DropdownMenuLabel class="p-0 font-normal">
                        <div class="flex flex-col gap-1 px-2 py-1.5">
                            <p class="text-sm font-medium text-gray-900">
                                {{ user?.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ user?.email }}
                            </p>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem :as-child="true">
                        <Link
                            :href="userRole === 'student' ? '/student/profile' : '/settings/profile'"
                            class="flex w-full items-center"
                        >
                            <User class="mr-2 h-4 w-4" />
                            Profile
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem :as-child="true">
                        <Link
                            :href="editProfile()"
                            class="flex w-full items-center"
                        >
                            <Settings class="mr-2 h-4 w-4" />
                            Settings
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem :as-child="true">
                        <Link
                            :href="logout()"
                            @click="handleLogout"
                            class="flex w-full items-center text-red-600 focus:text-red-600"
                            as="button"
                        >
                            <LogOut class="mr-2 h-4 w-4" />
                            Log out
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
