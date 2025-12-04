<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Settings } from 'lucide-vue-next';
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Student Home
                </h1>
                <p class="mt-2 text-gray-600">
                    Welcome to your student dashboard.
                </p>
            </div>
            <Link href="/student/profile">
                <Button
                    class="flex items-center gap-2 bg-brand-primary hover:bg-brand-primary-hover"
                >
                    <Settings class="h-4 w-4" />
                    Edit Learning Preferences
                </Button>
            </Link>
        </div>
    </div>
</template>

