export function useApi() {
    const client = useSanctumClient();

    async function get<T = void>(endpoint: string, options: Record<string, unknown> = {}): Promise<T> {
        return client(endpoint, { method: 'GET', ...options });
    }

    // `body` can be a plain object, `FormData`, or any other runtime payload.
    // We intentionally keep it `unknown` to avoid cascading casts in pages.
    async function post<T = void>(endpoint: string, body?: unknown, options: Record<string, unknown> = {}): Promise<T> {
        return client(endpoint, { method: 'POST', body: body as never, ...options });
    }

    async function put<T = void>(endpoint: string, body?: unknown, options: Record<string, unknown> = {}): Promise<T> {
        return client(endpoint, { method: 'PUT', body: body as never, ...options });
    }

    async function patch<T = void>(endpoint: string, body?: unknown, options: Record<string, unknown> = {}): Promise<T> {
        return client(endpoint, { method: 'PATCH', body: body as never, ...options });
    }

    async function del<T = void>(endpoint: string, options: Record<string, unknown> = {}): Promise<T> {
        return client(endpoint, { method: 'DELETE', ...options });
    }

    async function getBlob(endpoint: string, options: Record<string, unknown> = {}): Promise<{ data: Blob }> {
        const blob = await client(endpoint, {
            method: 'GET',
            ...options,
            responseType: 'blob',
        }) as Blob;
        return { data: blob };
    }

    async function postBlob(endpoint: string, body?: unknown, options: Record<string, unknown> = {}): Promise<{ data: Blob; headers?: Headers }> {
        const result = await $fetch.raw(endpoint, {
            method: 'POST',
            body: body as never,
            ...options,
            responseType: 'blob',
            credentials: 'include',
        });

        return {
            data: result._data as Blob,
            headers: result.headers,
        };
    }

    async function uploadFileApi<T = void>(endpoint: string, body: FormData): Promise<T> {
        const config = useRuntimeConfig();
        const url = endpoint.startsWith('http') ? endpoint : `${config.public.API_URL}${endpoint}`;

        const csrfCookie = document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='));
        const csrfToken = csrfCookie ? decodeURIComponent(csrfCookie.split('=')[1] ?? '') : '';

        const response = await fetch(url, {
            method: 'POST',
            body,
            credentials: 'include',
            headers: {
                'X-XSRF-TOKEN': csrfToken,
            },
        });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({})) as Record<string, unknown>;
            const error = new Error(response.statusText) as unknown as { response?: { _data?: Record<string, unknown>; status: number }; statusCode: number };
            error.response = { _data: errorData, status: response.status };
            error.statusCode = response.status;
            throw error;
        }
        return response.json();
    }

    return {
        get,
        post,
        put,
        patch,
        delete: del,
        getBlob,
        postBlob,
        uploadFileApi,
        client,
    };
}
