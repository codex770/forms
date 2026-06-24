import { useI18n } from '@/utils/i18n';
import { isTechnicalFieldKey } from './technicalFields';

/**
 * Utility functions for field detection and formatting
 */

export interface FieldInfo {
    key: string;
    type: 'string' | 'integer' | 'float' | 'boolean' | 'date' | 'object';
    label: string;
}

/**
 * Detect field type from a value
 */
export function detectFieldType(value: any): FieldInfo['type'] {
    if (value === null || value === undefined) {
        return 'string';
    }

    if (typeof value === 'string') {
        if (value.match(/^\d{4}-\d{2}-\d{2}/) || !isNaN(Date.parse(value))) {
            return 'date';
        }
        return 'string';
    }

    if (typeof value === 'number') {
        return Number.isInteger(value) ? 'integer' : 'float';
    }

    if (typeof value === 'boolean') {
        return 'boolean';
    }

    if (typeof value === 'object') {
        return 'object';
    }

    return 'string';
}

/**
 * Format field value for display
 */
export function formatFieldValue(value: any, type?: FieldInfo['type']): string {
    if (value === null || value === undefined) {
        return 'N/A';
    }

    if (
        type === 'date' ||
        (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}/))
    ) {
        try {
            const date = new Date(value);
            return date.toLocaleDateString();
        } catch {
            return String(value);
        }
    }

    if (type === 'object') {
        return JSON.stringify(value, null, 2);
    }

    if (type === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return String(value);
}

/**
 * Group fields by category for better organization
 * Uses exact matching to prevent duplicates
 */
export function groupFieldsByCategory(
    fields: FieldInfo[],
): Record<string, FieldInfo[]> {
    const groups: Record<string, FieldInfo[]> = {
        'Contact Information': [],
        'Message Content': [],
        Location: [],
        'Personal Details': [],
        Other: [],
    };

    const contactFields = [
        'fname',
        'lname',
        'first_name',
        'last_name',
        'name',
        'email',
        'email_address',
        'phone',
        'phone_number',
        'tel',
    ];
    const messageFields = [
        'message',
        'message_long',
        'message_short',
        'description',
        'content',
        'text',
        'comment',
    ];
    const locationFields = [
        'city',
        'zip',
        'zip_code',
        'postal_code',
        'plz',
        'place',
        'location',
        'address',
        'street',
    ];
    const personalFields = [
        'age',
        'birth_year',
        'birthday',
        'bday',
        'gender',
        'sex',
        'dob',
    ];

    const categorized = new Set<string>();

    fields.forEach((field) => {
        if (
            isTechnicalFieldKey(field.key) ||
            isTechnicalFieldKey(field.label)
        ) {
            return;
        }

        if (categorized.has(field.key)) {
            return;
        }

        const key = field.key.toLowerCase();
        let categorized_flag = false;

        if (contactFields.includes(key)) {
            groups['Contact Information'].push(field);
            categorized_flag = true;
        } else if (messageFields.includes(key)) {
            groups['Message Content'].push(field);
            categorized_flag = true;
        } else if (locationFields.includes(key)) {
            groups['Location'].push(field);
            categorized_flag = true;
        } else if (personalFields.includes(key)) {
            groups['Personal Details'].push(field);
            categorized_flag = true;
        } else {
            if (
                contactFields.some(
                    (f) =>
                        key === f ||
                        key.startsWith(f + '_') ||
                        key.endsWith('_' + f),
                )
            ) {
                groups['Contact Information'].push(field);
                categorized_flag = true;
            } else if (
                messageFields.some(
                    (f) =>
                        key === f ||
                        key.startsWith(f + '_') ||
                        key.endsWith('_' + f),
                )
            ) {
                groups['Message Content'].push(field);
                categorized_flag = true;
            } else if (
                locationFields.some(
                    (f) =>
                        key === f ||
                        key.startsWith(f + '_') ||
                        key.endsWith('_' + f),
                )
            ) {
                groups['Location'].push(field);
                categorized_flag = true;
            } else if (
                personalFields.some(
                    (f) =>
                        key === f ||
                        key.startsWith(f + '_') ||
                        key.endsWith('_' + f),
                )
            ) {
                groups['Personal Details'].push(field);
                categorized_flag = true;
            } else {
                groups['Other'].push(field);
                categorized_flag = true;
            }
        }

        if (categorized_flag) {
            categorized.add(field.key);
        }
    });

    Object.keys(groups).forEach((key) => {
        if (groups[key].length === 0) {
            delete groups[key];
        }
    });

    return groups;
}

/**
 * Get human-readable label for a field key.
 * Uses tField from useI18n: checks fieldOverrides first, then auto-humanizes.
 */
export function getFieldLabel(key: string): string {
    const { tField } = useI18n();
    return tField(key);
}
