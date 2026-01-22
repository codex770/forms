/**
 * Internal fields that should not be visible to end-user.
 */

const TECHNICAL_FIELDS = new Set<string>([
    'entityid',
    'entitytype',
    'indraft',
    'langcode',
    'lockes',
    'notes',
    'randhashid',
    'remoteaddr',
    'segments',
    'serial',
    'sid',
    'sticky',
    'token',
    'uid',
    'uri',
    'userid',
    'uuid',
]);

function normalizeKey(key: string): string {
    return String(key || '')
        .trim()
        .toLowerCase()
        .replace(/[\s_-]+/g, '')
        .replace(/[^a-z0-9]/g, '');
}

export function isTechnicalFieldKey(key: string): boolean {
    return TECHNICAL_FIELDS.has(normalizeKey(key));
}

export function filterOutTechnicalFields<T extends { key: string; label?: string }>(
    fields: T[],
): T[] {
    return (fields || []).filter(
        (f) => !isTechnicalFieldKey(f.key) && !isTechnicalFieldKey(f.label || ''),
    );
}
