<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

/**
 * Keeps the meta[name="csrf-token"] in sync with the token from Inertia shared props.
 * This ensures the token is always current after every navigation and that any code
 * reading from the meta tag gets the correct value.
 */
const page = usePage();

watchEffect(() => {
    const token = (page.props as { csrf_token?: string }).csrf_token;
    if (token) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', token);
        }
    }
});
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
