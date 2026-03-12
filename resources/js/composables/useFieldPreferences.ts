import { ref, computed } from 'vue';
import {
    resolveAliasedValue,
    toCanonicalFieldKey,
} from '@/utils/fieldAliases';

/**
 * Composable for managing field preferences (global form-level config).
 * Uses /forms/{webformId}/column-config API.
 */
export function useFieldPreferences(
    webformId: string | null,
    viewType: 'list' | 'detail',
    smartDefaults: string[] = [],
    options: {
        preferenceName?: string;
    } = {},
) {
    const loading = ref(false);
    const saving = ref(false);
    const visibleFieldOrder = ref<string[]>([]);
    const inheritedFrom = ref<string | null>(null);

    const visibleFieldSet = computed(() => new Set(visibleFieldOrder.value));

    const normalizeOrderedKeys = (keys: string[]) => {
        const out: string[] = [];
        const seen = new Set<string>();
        for (const k of keys || []) {
            const canonical = toCanonicalFieldKey(k);
            if (!canonical) continue;
            if (seen.has(canonical)) continue;
            seen.add(canonical);
            out.push(canonical);
        }
        return out;
    };

    const applySmartDefaults = () => {
        if (smartDefaults.length > 0) {
            visibleFieldOrder.value = normalizeOrderedKeys(smartDefaults);
        } else {
            visibleFieldOrder.value = normalizeOrderedKeys([
                'first_name',
                'last_name',
                'email',
                'message_long',
            ]);
        }
    };

    /**
     * Load column config from form-level API
     */
    const loadPreferences = async () => {
        loading.value = true;
        try {
            if (!webformId) {
                applySmartDefaults();
                return;
            }

            const response = await fetch(`/forms/${webformId}/column-config`);
            const data = await response.json();

            if (data.success && Array.isArray(data.visible_columns) && data.visible_columns.length > 0) {
                const savedFields = normalizeOrderedKeys(data.visible_columns);
                const maxReasonable = Math.max(smartDefaults.length * 2, 10);
                if (savedFields.length <= maxReasonable) {
                    visibleFieldOrder.value = savedFields;
                    return;
                }
            }
            applySmartDefaults();
        } catch (error) {
            console.error('Error loading column config:', error);
            applySmartDefaults();
        } finally {
            loading.value = false;
        }
    };

    /**
     * Save column config to form-level API
     */
    const savePreferences = async (
        fields: string[],
        options: {
            asDefault?: boolean;
            preferenceName?: string;
            sortConfig?: { column: string; direction: 'asc' | 'desc' };
            savedFilters?: Record<string, any>;
            useFormLevel?: boolean;
        } = {}
    ) => {
        saving.value = true;
        try {
            if (!webformId) return false;

            const ordered = normalizeOrderedKeys(fields);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/forms/${webformId}/column-config`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ visible_columns: ordered }),
            });

            const data = await response.json();

            if (data.success) {
                visibleFieldOrder.value = ordered;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error saving column config:', error);
            return false;
        } finally {
            saving.value = false;
        }
    };

    /**
     * Get field value from JSON data
     */
    const getFieldValue = (data: Record<string, any>, fieldName: string): any => {
        // Handle nested paths like 'address.city'
        if (fieldName.includes('.')) {
            const parts = fieldName.split('.');
            let value = data;
            for (const part of parts) {
                if (value && typeof value === 'object' && part in value) {
                    value = value[part];
                } else {
                    return null;
                }
            }
            return value;
        }

        return resolveAliasedValue(data, fieldName);
    };

    /**
     * Get human-readable label for a field (German).
     */
    const getFieldLabel = (fieldName: string): string => {
        const canonical = toCanonicalFieldKey(fieldName);
        // Labels from client form field reference (TITLE column)
        const labels: Record<string, string> = {
            'station': 'Station',
            'gender': 'Anrede',
            'sex': 'Anrede',
            'first_name': 'Vorname',
            'last_name': 'Nachname',
            'name': 'Name',
            'address': 'Straße & Hausnummer',
            'street': 'Straße & Hausnummer',
            'zip': 'Postleitzahl',
            'zip_code': 'Postleitzahl',
            'postal_code': 'Postleitzahl',
            'plz': 'Postleitzahl',
            'city': 'Stadt',
            'phone': 'Telefon',
            'email': 'E-Mail',
            'email_address': 'E-Mail',
            'birthday': 'Geburtsdatum',
            'birth_year': 'Geburtsjahr',
            'message_long': 'Nachricht (lang)',
            'message_short': 'Nachricht (kurz)',
            'message': 'Nachricht',
            'description': 'Beschreibung',
            'age': 'Alter',
        };

        if (labels[canonical]) {
            return labels[canonical];
        }

        return canonical
            .replace(/[_-]/g, ' ')
            .replace(/([a-z])([A-Z])/g, '$1 $2')
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    };

    /**
     * Toggle field visibility
     * Creates a new Set to ensure Vue reactivity detects the change
     */
    const toggleField = (fieldName: string) => {
        const key = toCanonicalFieldKey(fieldName);
        const next = visibleFieldOrder.value.slice();
        const idx = next.indexOf(key);
        if (idx >= 0) {
            next.splice(idx, 1);
        } else {
            next.push(key);
        }
        visibleFieldOrder.value = next;
    };

    /**
     * Set all fields visibility
     * Creates a new Set to ensure Vue reactivity detects the change
     */
    const setAllFields = (fields: string[], visible: boolean) => {
        if (visible) {
            visibleFieldOrder.value = normalizeOrderedKeys(fields);
        } else {
            visibleFieldOrder.value = [];
        }
    };

    const moveField = (fieldKey: string, toIndex: number) => {
        const key = toCanonicalFieldKey(fieldKey);
        const next = visibleFieldOrder.value.slice();
        const fromIndex = next.indexOf(key);
        if (fromIndex < 0) return;
        const clamped = Math.max(0, Math.min(toIndex, next.length - 1));
        if (fromIndex === clamped) return;
        const [item] = next.splice(fromIndex, 1);
        next.splice(clamped, 0, item);
        visibleFieldOrder.value = next;
    };

    return {
        loading,
        saving,
        visibleFieldOrder,
        visibleFieldSet,
        inheritedFrom,
        loadPreferences,
        savePreferences,
        getFieldValue,
        getFieldLabel,
        toggleField,
        setAllFields,
        moveField,
    };
}

