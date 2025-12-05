<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BottomNav from '@/components/navigation/BottomNav.vue';

const page = usePage();
const user = computed(() => {
    try {
        return page.props?.auth?.user || null;
    } catch {
        return null;
    }
});
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || null;
});

// Only show if user is authenticated with a role
const shouldShow = computed(() => {
    return userRole.value !== null && user.value !== null;
});
</script>

<template>
    <BottomNav
        v-if="shouldShow && userRole"
        :role="userRole"
    />
</template>