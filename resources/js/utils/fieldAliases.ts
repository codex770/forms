export type CanonicalFieldKey =
    | 'first_name'
    | 'last_name'
    | 'birthday'
    | 'postal_code'
    | 'email'
    | 'phone'
    | 'birth_year';

type AliasMap = Record<CanonicalFieldKey, string[]>;

const ALIASES: AliasMap = {
    first_name: ['first_name', 'fname', 'vorname'],
    last_name: ['last_name', 'lname', 'nachname'],
    birthday: ['birthday', 'bday', 'geburtstag', 'date_of_birth', 'dob'],
    postal_code: ['plz', 'zip', 'zip_code', 'postal_code', 'postcode'],
    email: ['email', 'email_address', 'e_mail'],
    phone: ['phone', 'phone_number', 'tel', 'telephone', 'mobile'],
    birth_year: ['birth_year', 'birthYear', 'year_of_birth'],
};

const REVERSE = new Map<string, CanonicalFieldKey>();
for (const [canonical, keys] of Object.entries(ALIASES) as Array<
    [CanonicalFieldKey, string[]]
>) {
    for (const k of keys) {
        REVERSE.set(k.toLowerCase(), canonical);
    }
}

export function toCanonicalFieldKey(key: string): string {
    const k = String(key || '').trim();
    if (!k) return k;
    return REVERSE.get(k.toLowerCase()) ?? k;
}

export function resolveAliasedValue(
    data: Record<string, any>,
    key: string,
): any {
    const canonical = toCanonicalFieldKey(key);
    // If key is canonical, try its aliases; otherwise just read the key directly.
    const aliasKeys =
        (ALIASES as Partial<Record<string, string[]>>)[canonical] ?? [];

    // Prefer exact key first if it exists
    if (data && typeof data === 'object' && key in data) {
        const v = (data as any)[key];
        if (v !== null && v !== undefined && v !== '') return v;
    }

    for (const k of aliasKeys) {
        if (data && typeof data === 'object' && k in data) {
            const v = (data as any)[k];
            if (v !== null && v !== undefined && v !== '') return v;
        }
    }
    return null;
}

export function dedupeFieldsByCanonicalKey<
    T extends { key: string; label?: string; type?: any },
>(fields: T[]): T[] {
    const out: T[] = [];
    const seen = new Set<string>();
    for (const f of fields || []) {
        const canonical = toCanonicalFieldKey(f.key);
        if (seen.has(canonical)) continue;
        seen.add(canonical);
        out.push({ ...f, key: canonical });
    }
    return out;
}

