export async function postJson(url: string, body?: any) {
    const token =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';

    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!res.ok) {
        const text = await res.text();
        throw new Error(`Request failed: ${res.status} ${text}`);
    }

    return res.json();
}
