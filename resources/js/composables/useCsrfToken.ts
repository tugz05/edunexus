import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Returns the current CSRF token from Inertia shared props (preferred) or the meta tag.
 * Use this for API requests so the token is always the one Laravel last sent.
 */
export function useCsrfToken() {
    const page = usePage();

    const token = computed(() => {
        const fromProps = (page.props as { csrf_token?: string }).csrf_token;
        if (fromProps) return fromProps;
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta?.getAttribute('content') ?? '';
    });

    return { csrfToken: token };
}
