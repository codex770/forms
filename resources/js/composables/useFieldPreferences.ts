import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { FieldInfo } from '@/utils/fieldDetection';

interface Preference {
    id: number;
    category: string | null;
    preference_name: string;
    visible_columns: string[];
    sort_config?: {
        column: string;
        direction: 'asc' | 'desc';
    };
    saved_filters?: Record<string, any>;
    is_default: boolean;
}

interface FieldPreferencesProps {
    smartDefaults?: string[]; // Smart defaults from backend
}

/**
 * Composable for managing field preferences
 */
export function useFieldPreferences(
    viewType: 'list' | 'detail',
    category: string | null = null,
    smartDefaults: string[] = []
) {
    const preferences = ref<Preference[]>([]);
    const loading = ref(false);
    const saving = ref(false);
    const visibleFields = ref<Set<string>>(new Set());
    const inheritedFrom = ref<string | null>(null);

    const preferenceName = computed(() => {
        return viewType === 'list' ? 'list-view-columns' : 'detail-view-layout';
    });

    /**
     * Load preferences from API
     * Only loads the single default preference (is_default: true)
     */
    const loadPreferences = async () => {
        loading.value = true;
        try {
            const params = new URLSearchParams();
            if (category) {
                params.append('category', category);
            }
            params.append('preference_name', preferenceName.value);
            params.append('is_default', '1'); // Only get default preference

            const response = await fetch(`/api/preferences?${params.toString()}`);
            const data = await response.json();

            if (data.success) {
                // Get inherited preferences (single default preference)
                if (category) {
                    const inheritedParams = new URLSearchParams();
                    inheritedParams.append('category', category);
                    inheritedParams.append('preference_name', preferenceName.value);

                    const inheritedResponse = await fetch(`/api/preferences/inherited?${inheritedParams.toString()}`);
                    const inheritedData = await inheritedResponse.json();

                    if (inheritedData.success && inheritedData.preference) {
                        const savedFields = inheritedData.preference.visible_columns || [];
                        // Check if preference is "unreasonable" (has too many fields - likely accidental "select all")
                        // Reasonable threshold: smart defaults * 2, or max 10 fields
                        const maxReasonable = Math.max(smartDefaults.length * 2, 10);
                        
                        if (savedFields.length > maxReasonable) {
                            console.warn(`⚠️ Ignoring unreasonable preference with ${savedFields.length} fields (max reasonable: ${maxReasonable})`);
                            console.log('Using smart defaults instead:', smartDefaults);
                            // Use smart defaults instead
                            if (smartDefaults.length > 0) {
                                visibleFields.value = new Set(smartDefaults);
                            } else {
                                visibleFields.value = new Set(['fname', 'lname', 'email', 'message_long']);
                            }
                        } else {
                            // Use inherited preference (single default)
                            console.log('Loaded inherited preference:', savedFields);
                            visibleFields.value = new Set(savedFields);
                            inheritedFrom.value = inheritedData.inherited_from;
                        }
                    } else {
                        // No preference found - use smart defaults from backend
                        console.log('No preference found, using smart defaults:', smartDefaults);
                        if (smartDefaults.length > 0) {
                            visibleFields.value = new Set(smartDefaults);
                        } else {
                            // Fallback to hardcoded defaults
                            console.log('No smart defaults, using fallback');
                            visibleFields.value = new Set(['fname', 'lname', 'email', 'message_long']);
                        }
                    }
                } else {
                    // No category - check for global default preference
                    const prefs = data.preferences || [];
                    const defaultPref = prefs.find((p: Preference) => p.is_default);
                    
                    if (defaultPref) {
                        const savedFields = defaultPref.visible_columns || [];
                        // Check if preference is "unreasonable" (has too many fields)
                        const maxReasonable = Math.max(smartDefaults.length * 2, 10);
                        
                        if (savedFields.length > maxReasonable) {
                            console.warn(`⚠️ Ignoring unreasonable preference with ${savedFields.length} fields (max reasonable: ${maxReasonable})`);
                            // Use smart defaults instead
                            if (smartDefaults.length > 0) {
                                visibleFields.value = new Set(smartDefaults);
                            } else {
                                visibleFields.value = new Set(['fname', 'lname', 'email', 'message_long']);
                            }
                        } else {
                            visibleFields.value = new Set(savedFields);
                        }
                    } else {
                        // No preference found - use smart defaults
                        if (smartDefaults.length > 0) {
                            visibleFields.value = new Set(smartDefaults);
                        } else {
                            visibleFields.value = new Set(['fname', 'lname', 'email', 'message_long']);
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error loading preferences:', error);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get default preference category (TYPE level if available)
     * This ensures preferences are saved at TYPE level by default for efficiency
     */
    const getDefaultCategory = (): string | null => {
        if (!category) return null;
        
        // If category is form-level (station:type:form), default to TYPE level
        const parts = category.split(':');
        if (parts.length >= 3) {
            // Return TYPE level: station:type
            return `${parts[0]}:${parts[1]}`;
        }
        
        // If already type-level or station-level, use as-is
        return category;
    };

    /**
     * Save preferences to API
     * Defaults to TYPE level for efficiency (applies to all forms of that type)
     */
    const savePreferences = async (
        fields: string[],
        options: {
            asDefault?: boolean;
            preferenceName?: string;
            sortConfig?: { column: string; direction: 'asc' | 'desc' };
            savedFilters?: Record<string, any>;
            useFormLevel?: boolean; // Set to true to override and save at form level
        } = {}
    ) => {
        saving.value = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Default to TYPE level unless explicitly overridden
            const saveCategory = options.useFormLevel ? category : getDefaultCategory();

            const payload = {
                category: saveCategory,
                preference_name: options.preferenceName || preferenceName.value,
                visible_columns: fields,
                sort_config: options.sortConfig,
                saved_filters: options.savedFilters,
                is_default: options.asDefault || false,
            };

            console.log('Making network request to /api/preferences');
            console.log('Payload:', payload);
            
            const response = await fetch('/api/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            console.log('Network response status:', response.status);
            const data = await response.json();
            console.log('Network response data:', data);

            if (data.success) {
                // Don't reload preferences - visibleFields is already updated in the component
                // Reloading would overwrite the user's current selection with server data
                // The server has been updated, so we're in sync
                console.log('✅ Preferences saved successfully - keeping current visibleFields state');
                return true;
            }

            console.error('Save failed - response:', data);
            return false;
        } catch (error) {
            console.error('Error saving preferences:', error);
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

        return data[fieldName] ?? null;
    };

    /**
     * Get human-readable label for a field
     */
    const getFieldLabel = (fieldName: string): string => {
        const labels: Record<string, string> = {
            'fname': 'First Name',
            'lname': 'Last Name',
            'first_name': 'First Name',
            'last_name': 'Last Name',
            'name': 'Name',
            'email': 'Email',
            'email_address': 'Email Address',
            'phone': 'Phone',
            'message_long': 'Message (Long)',
            'message_short': 'Message (Short)',
            'message': 'Message',
            'description': 'Description',
            'city': 'City',
            'zip': 'ZIP Code',
            'zip_code': 'ZIP Code',
            'postal_code': 'Postal Code',
            'plz': 'PLZ',
            'gender': 'Gender',
            'age': 'Age',
            'birth_year': 'Birth Year',
            'birthday': 'Birthday',
            'bday': 'Birthday',
        };

        if (labels[fieldName]) {
            return labels[fieldName];
        }

        // Convert snake_case or camelCase to Title Case
        return fieldName
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
        const newSet = new Set(visibleFields.value);
        if (newSet.has(fieldName)) {
            newSet.delete(fieldName);
        } else {
            newSet.add(fieldName);
        }
        visibleFields.value = newSet; // Assign new Set to trigger reactivity
    };

    /**
     * Set all fields visibility
     * Creates a new Set to ensure Vue reactivity detects the change
     */
    const setAllFields = (fields: string[], visible: boolean) => {
        const newSet = new Set(visibleFields.value);
        if (visible) {
            fields.forEach(field => newSet.add(field));
        } else {
            fields.forEach(field => newSet.delete(field));
        }
        visibleFields.value = newSet; // Assign new Set to trigger reactivity
    };

    return {
        preferences,
        loading,
        saving,
        visibleFields,
        inheritedFrom,
        loadPreferences,
        savePreferences,
        getFieldValue,
        getFieldLabel,
        toggleField,
        setAllFields,
    };
}

