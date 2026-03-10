/**
 * Internal fields that should not be visible to end-user.
 */

const TECHNICAL_FIELDS = new Set<string>([
    // System / Database IDs
    'sid',
    'submissionid',
    'serial',
    'entityid',
    'entitytype',
    'uid',
    'userid',
    'uuid',

    // System metadata / state fields (Drupal)
    'created',
    'changed',
    'completed',
    'indraft',
    'locked',
    'lockes',
    'sticky',
    'langcode',
    'currentpage',

    // Tracking / security fields
    'randhashid',
    'token',
    'remoteaddr',

    // Form infrastructure fields
    'webformid',
    'station',
    'uri',
    'segments',

    // File system metadata
    'file',
    'fileid',
    'fileuri',
    'fileuuid',
    'filename',
    'filemime',
    'filedata',

    // Other internal fields
    'notes',
    'datei',
    'dataprivacyselect',

    // Coordinates / internal tracking
    'latitude',
    'longitude',

    // Common internal flags/fields mentioned by client
    'profile',
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
