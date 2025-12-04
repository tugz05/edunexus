<script setup lang="ts">
import BottomNav from '@/components/navigation/BottomNav.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Top Header (Desktop Only) -->
        <header class="sticky top-0 z-40 hidden w-full border-b border-gray-200 bg-white shadow-sm md:block">
            <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-semibold text-brand-primary">
                        EduNexus
                    </h1>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Add any header actions here (notifications, profile, etc.) -->
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto pt-16 pb-20 md:pt-0 md:pb-6">
            <router-view />
        </main>

        <!-- Bottom Navigation -->
        <BottomNav role="student" />
    </div>
</template>

