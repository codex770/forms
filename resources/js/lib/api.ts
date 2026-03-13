export async function postJson(url: string, body?: any, options?: { method?: string }) {
    const token =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';

    const res = await fetch(url, {
        method: options?.method ?? 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    });

    if (res.status === 419) {
        // CSRF token mismatch; reload to get fresh token
        window.location.reload();
        throw new Error('CSRF token expired. Page reloaded.');
    }

    if (!res.ok) {
        const text = await res.text();
        throw new Error(`Request failed: ${res.status} ${text}`);
    }

    return res.json();
}
