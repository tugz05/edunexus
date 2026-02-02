/**
 * Central API client that automatically attaches the CSRF token and credentials
 * to every request. Use this for fetch() calls to API routes to avoid CSRF errors.
 * The meta tag is kept in sync with the current token by CsrfTokenSync.vue.
 *
 * Usage:
 *   import { apiFetch } from '@/lib/apiClient';
 *   const res = await apiFetch('/api/admin/users', { method: 'POST', body: JSON.stringify(data) });
 */

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
}

export interface ApiFetchOptions extends Omit<RequestInit, 'headers'> {
    headers?: Record<string, string>;
    /** If true, do not add CSRF token (e.g. for GET requests that don't need it) */
    skipCsrf?: boolean;
    /** If true, add _token to JSON body for POST/PUT/PATCH */
    bodyAsJson?: boolean;
}

/**
 * Fetch with CSRF token and credentials attached. For POST/PUT/PATCH/DELETE,
 * the X-CSRF-TOKEN header is always set. For JSON bodies, _token is added if bodyAsJson is true.
 */
export async function apiFetch(url: string, options: ApiFetchOptions = {}): Promise<Response> {
    const { headers = {}, skipCsrf = false, bodyAsJson = false, body, ...rest } = options;

    const token = getCsrfToken();
    const method = (options.method ?? 'GET').toUpperCase();
    const isStateChange = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

    const newHeaders: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...headers,
    };

    if (isStateChange && !skipCsrf && token) {
        newHeaders['X-CSRF-TOKEN'] = token;
    }

    let finalBody = body;
    if (isStateChange && token && bodyAsJson && body && typeof body === 'string') {
        try {
            const parsed = JSON.parse(body) as Record<string, unknown>;
            if (!('_token' in parsed)) {
                finalBody = JSON.stringify({ ...parsed, _token: token });
            }
        } catch {
            // leave body as-is
        }
    }

    return fetch(url, {
        ...rest,
        headers: newHeaders,
        body: finalBody ?? body,
        credentials: 'same-origin',
    });
}
