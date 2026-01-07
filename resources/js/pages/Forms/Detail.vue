<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { dashboard as userDashboard } from '@/routes/user';
import { show as contactShow, toggleRead as contactToggleRead, destroy as contactDestroy } from '@/routes/contact';
import { 
    ArrowLeft,
    Search, 
    Eye, 
    Trash2, 
    CheckCircle2,
    AlertCircle,
    FileText,
    Download,
    Edit,
    Save,
    X,
    Filter,
    ChevronDown,
    ChevronUp,
    XCircle,
    Settings,
    Columns,
    Bookmark,
    BookmarkCheck
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { useFieldPreferences } from '@/composables/useFieldPreferences';
import { groupFieldsByCategory, formatFieldValue } from '@/utils/fieldDetection';

interface ContactSubmission {
    id: number;
    category: string;
    data: {
        name?: string;
        email?: string;
        description?: string;
        [key: string]: any;
    };
    ip_address: string;
    created_at: string;
    updated_at: string;
    reads_with_users: Array<{
        id: number;
        user_id: number;
        read_at: string;
        user: {
            id: number;
            name: string;
        };
    }>;
}

type FieldInfo = {
    key: string;
    type: 'string' | 'boolean' | 'object' | 'integer' | 'float' | 'date';
    label: string;
};

interface Props {
    webformId: string;
    formName: string;
    station: string;
    submissionForm?: string;
    submissions: {
        data?: ContactSubmission[];
        links?: any[];
        meta?: any;
    };
    totalCount: number;
    availableFields?: FieldInfo[]; // Form-specific fields (primary)
    typeFields?: FieldInfo[]; // For reference/inheritance
    stationFields?: FieldInfo[]; // For reference/inheritance
    newFields?: string[]; // New field keys for notification
    smartDefaults?: string[]; // Smart defaults for this type
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
        age_min?: string;
        age_max?: string;
        birth_year_min?: string;
        birth_year_max?: string;
        zip_code?: string;
        city?: string;
        gender?: string;
        radius?: string;
        radius_lat?: string;
        radius_lng?: string;
    };
    sortColumn?: string;
    sortDirection?: string;
}

const props = defineProps<Props>();

// New fields from backend
const newFields = computed(() => props.newFields || []);

const authUser = computed(() => usePage().props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Form Center',
        href: userDashboard().url,
    },
    {
        title: props.formName,
        href: `/forms/${props.webformId}`,
    },
];

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const ageMin = ref(props.filters.age_min || '');
const ageMax = ref(props.filters.age_max || '');
const birthYearMin = ref(props.filters.birth_year_min || '');
const birthYearMax = ref(props.filters.birth_year_max || '');
const zipCode = ref(props.filters.zip_code || '');
const city = ref(props.filters.city || '');
const gender = ref(props.filters.gender || '');
const radius = ref(props.filters.radius || '');
const radiusLat = ref(props.filters.radius_lat || '');
const radiusLng = ref(props.filters.radius_lng || '');
const showAdvancedFilters = ref(false);
const selectedRows = ref<Set<number>>(new Set());
const editingRow = ref<number | null>(null);
const editingData = ref<any>({});

// Build category path for preferences
// DEFAULT: Type level (station:type) - most efficient, applies to all forms of that type
// OVERRIDE: Form level (station:type:webform_id) - for special cases
const categoryPath = computed(() => {
    // Default to TYPE level for preference storage (efficient)
    if (props.submissionForm) {
        return `${props.station}:${props.submissionForm}`; // TYPE level
    }
    return props.webformId; // Fallback to form level
});

// Form-level category (for reference, but preferences default to type level)
const formCategoryPath = computed(() => {
    if (props.submissionForm) {
        return `${props.station}:${props.submissionForm}:${props.webformId}`;
    }
    return props.webformId;
});

// Field preferences composable - uses TYPE level by default
// Pass smart defaults from backend for optimal first-time experience
const {
    visibleFields,
    loadPreferences,
    savePreferences,
    getFieldValue,
    getFieldLabel,
    toggleField,
    setAllFields,
    loading: loadingPrefs,
    saving: savingPrefs,
    // Removed preferences (presets) - using single auto-saving preference
} = useFieldPreferences('list', categoryPath.value, props.smartDefaults || []); // TYPE level category + smart defaults

// Clear new fields notification
const clearNewFields = async () => {
    try {
        await fetch(`/forms/${props.webformId}/clear-new-fields`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
    } catch (error) {
        console.error('Error clearing new fields:', error);
    }
};

// Column visibility - combine system columns with user-selected fields
const visibleColumns = computed(() => {
    const cols = new Set<string>(['checkbox', 'status', 'id', 'actions']); // System columns always visible
    visibleFields.value.forEach(field => cols.add(field));
    return cols;
});

const showColumnSettings = ref(false);

// Sorting
const sortColumn = ref<string>(props.sortColumn || 'created_at');
const sortDirection = ref<'asc' | 'desc'>((props.sortDirection as 'asc' | 'desc') || 'desc');

// Removed preset system - using single preference only (auto-saves on checkbox change)

// Available fields from backend - PRIMARY: Form-specific fields only
// This ensures we only show fields that exist in THIS form, not union of all forms
const allAvailableFields = computed(() => {
    // PRIORITY: Use form-specific fields (availableFields) as primary source
    // These are fields detected from THIS specific form only
    if (props.availableFields && props.availableFields.length > 0) {
        return props.availableFields;
    }
    
    // FALLBACK: If no form-specific fields, use type-level (for new forms)
    if (props.typeFields && props.typeFields.length > 0) {
        return props.typeFields;
    }
    
    // FALLBACK: Use station-level fields
    return props.stationFields || [];
});

// Check if a field is new (recently detected)
const isNewField = (fieldKey: string): boolean => {
    return props.newFields?.includes(fieldKey) || false;
};

const groupedFields = computed(() => groupFieldsByCategory(allAvailableFields.value));

// Computed property for visible fields (ensures reactivity)
const visibleFieldList = computed(() => {
    return allAvailableFields.value.filter(f => visibleFields.value.has(f.key));
});

// Computed property that returns the Set directly for template use
// This ensures Vue tracks changes and auto-unwraps properly
const visibleFieldsSet = computed(() => {
    return visibleFields.value || new Set<string>();
});

// Create a computed getter/setter for each field's checked state
// This is needed for v-model:checked to work with reka-ui Checkbox
const getFieldChecked = (fieldKey: string) => {
    return computed({
        get: () => visibleFields.value?.has(fieldKey) ?? false,
        set: (value: boolean | 'indeterminate') => {
            if (typeof value === 'boolean') {
                const newSet = new Set(visibleFields.value);
                if (value) {
                    newSet.add(fieldKey);
                } else {
                    newSet.delete(fieldKey);
                }
                visibleFields.value = newSet;
            }
        }
    });
};

// Check if current user has read the submission
const isReadByCurrentUser = (submission: ContactSubmission): boolean => {
    return submission.reads_with_users.some(read => read.user_id === authUser.value?.id);
};

// Get initials for avatar - handles full name or separate first/last names
const getInitials = (name: string | undefined, data?: any): string => {
    // If we have data, try to get initials from first and last name
    if (data) {
        if (data.fname && data.lname) {
            return `${data.fname[0]}${data.lname[0]}`.toUpperCase();
        }
        if (data.first_name && data.last_name) {
            return `${data.first_name[0]}${data.last_name[0]}`.toUpperCase();
        }
    }
    // Fallback to name string
    if (!name || typeof name !== 'string') return '??';
    const parts = name.split(' ').filter(p => p.length > 0);
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

// Get display name - handles multiple name field variations
const getDisplayName = (data: any): string => {
    // Try to combine first and last name
    if (data?.fname && data?.lname) {
        return `${data.fname} ${data.lname}`;
    }
    if (data?.first_name && data?.last_name) {
        return `${data.first_name} ${data.last_name}`;
    }
    // Try single name fields
    return data?.name 
        || data?.fname 
        || data?.first_name 
        || data?.full_name 
        || data?.contact_name 
        || data?.email 
        || 'Unknown';
};

// Get display email
const getDisplayEmail = (data: any): string => {
    return data?.email || data?.email_address || 'No email provided';
};

// Get message text - handles multiple message field variations
const getMessageText = (data: any): string => {
    // Prefer message_long, then message_short, then other fields
    return data?.message_long 
        || data?.message_short 
        || data?.description 
        || data?.message 
        || data?.content 
        || data?.text 
        || 'No message content';
};

// Toggle row selection
const toggleRowSelection = (id: number) => {
    if (selectedRows.value.has(id)) {
        selectedRows.value.delete(id);
    } else {
        selectedRows.value.add(id);
    }
};

// Toggle all rows
const toggleAllRows = () => {
    if (selectedRows.value.size === props.submissions.data?.length) {
        selectedRows.value.clear();
    } else {
        props.submissions.data?.forEach(submission => {
            selectedRows.value.add(submission.id);
        });
    }
};

// Check if all rows are selected
const allRowsSelected = computed(() => {
    const total = props.submissions.data?.length ?? 0;
    return total > 0 && selectedRows.value.size === total;
});

// Start editing a row
const startEditing = (submission: ContactSubmission) => {
    editingRow.value = submission.id;
    editingData.value = { ...submission.data };
};

// Cancel editing
const cancelEditing = () => {
    editingRow.value = null;
    editingData.value = {};
};

// Save edited row
const saveEditing = async (submission: ContactSubmission) => {
    try {
        // TODO: Implement API call to update submission data
        // For now, just cancel editing
        cancelEditing();
    } catch (error) {
        console.error('Error saving:', error);
    }
};

// Apply filters
const applyFilters = () => {
    const params: any = {};
    
    // Filters
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value !== 'all') params.status = selectedStatus.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;
    if (ageMin.value) params.age_min = ageMin.value;
    if (ageMax.value) params.age_max = ageMax.value;
    if (birthYearMin.value) params.birth_year_min = birthYearMin.value;
    if (birthYearMax.value) params.birth_year_max = birthYearMax.value;
    if (zipCode.value) params.zip_code = zipCode.value;
    if (city.value) params.city = city.value;
    if (gender.value) params.gender = gender.value;
    if (radius.value && radiusLat.value && radiusLng.value) {
        params.radius = radius.value;
        params.radius_lat = radiusLat.value;
        params.radius_lng = radiusLng.value;
    }
    
    // Sorting
    if (sortColumn.value) params.sort_column = sortColumn.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;

    router.get(`/forms/${props.webformId}`, params, {
        preserveState: true,
        replace: true
    });
};

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    ageMin.value = '';
    ageMax.value = '';
    birthYearMin.value = '';
    birthYearMax.value = '';
    zipCode.value = '';
    city.value = '';
    gender.value = '';
    radius.value = '';
    radiusLat.value = '';
    radiusLng.value = '';
    applyFilters();
};

// Bulk delete
const bulkDelete = () => {
    if (selectedRows.value.size === 0) return;
    
    if (confirm(`Are you sure you want to delete ${selectedRows.value.size} submission(s)?`)) {
        // TODO: Implement bulk delete API call
        selectedRows.value.clear();
    }
};

// Bulk mark as read
const bulkMarkAsRead = () => {
    if (selectedRows.value.size === 0) return;
    
    // TODO: Implement bulk mark as read API call
    selectedRows.value.clear();
};

// Export selected
const exportSelected = () => {
    if (selectedRows.value.size === 0) return;
    
    // TODO: Implement export functionality
    console.log('Exporting:', Array.from(selectedRows.value));
};

// Toggle read status
const toggleRead = (submission: ContactSubmission) => {
    router.post(contactToggleRead(submission.id).url, {}, {
        preserveScroll: true
    });
};

// Delete submission
const deleteSubmission = (submission: ContactSubmission) => {
    if (confirm('Are you sure you want to permanently delete this submission?')) {
        router.delete(contactDestroy(submission.id).url);
    }
};

// Handle checkbox change - toggle approach (simpler, matches Contact/Show.vue pattern)
const handleFieldCheckboxChange = async (fieldKey: string) => {
    console.log('=== CHECKBOX CLICKED ===');
    console.log('Field:', fieldKey);
    console.log('visibleFields ref:', visibleFields);
    if (!visibleFields || !visibleFields.value) {
        console.error('❌ visibleFields is not initialized!');
        return;
    }
    console.log('Current state before:', visibleFields.value.has(fieldKey));
    
    try {
        // Toggle the field
        const newSet = new Set(visibleFields.value);
        if (newSet.has(fieldKey)) {
            newSet.delete(fieldKey);
            console.log('Removed field:', fieldKey);
        } else {
            newSet.add(fieldKey);
            console.log('Added field:', fieldKey);
        }
        visibleFields.value = newSet;
        
        console.log('Current state after:', visibleFields.value.has(fieldKey));
        console.log('All visible fields:', Array.from(visibleFields.value));
        console.log('visibleFields.size:', visibleFields.value.size);
        
        // Auto-save
        console.log('Calling saveColumnPreferences...');
        const saveResult = await saveColumnPreferences();
        console.log('Save result:', saveResult);
        
        if (saveResult) {
            console.log('✅ Preferences saved successfully');
        } else {
            console.error('❌ Failed to save preferences');
        }
    } catch (error) {
        console.error('ERROR in handleFieldCheckboxChange:', error);
        console.error('Error stack:', (error as Error).stack);
        alert('Error: ' + (error as Error).message);
    }
};

// Removed setFieldVisibility - using handleFieldCheckboxChange directly

// Save column preferences - saves as single default preference (auto-save on change)
const saveColumnPreferences = async (): Promise<boolean> => {
    console.log('=== saveColumnPreferences called ===');
    
    // Ensure we have the latest value (handle both ref and unwrapped cases)
    const currentFields = visibleFields.value || visibleFields;
    if (!currentFields) {
        console.error('❌ visibleFields is undefined in saveColumnPreferences!');
        return false;
    }
    
    // Get ONLY the fields that are currently checked (in visibleFields)
    const checkedFields = Array.from(currentFields);
    console.log('✅ Current visibleFields state:', checkedFields);
    console.log('✅ visibleFields.size:', currentFields.size);
    console.log('✅ visibleFields type:', currentFields instanceof Set ? 'Set' : typeof currentFields);
    
    // Only save fields that exist in availableFields (prevent saving non-existent fields)
    const validFields = checkedFields.filter(fieldKey => 
        allAvailableFields.value.some(f => f.key === fieldKey)
    );
    
    console.log('Valid fields to save:', validFields);
    console.log('All available fields:', allAvailableFields.value.map(f => f.key));
    
    if (validFields.length === 0) {
        console.warn('⚠️ No valid fields to save - skipping');
        return false;
    }
    
    console.log('Calling savePreferences with:', {
        fields: validFields,
        preferenceName: 'list-view-columns',
        asDefault: true,
        category: categoryPath.value
    });
    
    // Save as single default preference (is_default: true)
    // This replaces any existing preference for this category
    try {
        const result = await savePreferences(validFields, {
            preferenceName: 'list-view-columns', // Default preference name
            asDefault: true, // Always save as default (single preference per category)
        });
        console.log('savePreferences returned:', result);
        return result;
    } catch (error) {
        console.error('❌ Error in savePreferences:', error);
        throw error;
    }
};

// Load preferences (wrapper to use composable)
const loadPreferencesWrapper = async () => {
    await loadPreferences();
};

// Removed preset functions - using single auto-saving preference

// Toggle sort
const toggleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'desc';
    }
    // TODO: Apply sorting to table
};

// Removed preset button handler - using single auto-saving preference

// Click handler wrapper to keep template clean and typed
const onFieldCheckboxClick = (fieldKey: string, e?: MouseEvent) => {
    e?.preventDefault();
    e?.stopPropagation();
    handleFieldCheckboxChange(fieldKey).catch(err => {
        console.error('Error in handleFieldCheckboxChange:', err);
    });
};

onMounted(async () => {
    // Test: Verify visibleFields is initialized
    console.log('=== MOUNTED ===');
    console.log('visibleFields ref:', visibleFields);
    console.log('visibleFields.value:', visibleFields?.value);
    
    // Auto-apply filters on mount if they exist
    if (searchQuery.value || selectedStatus.value !== 'all') {
        applyFilters();
    }
    
    // Load user preferences
    await loadPreferencesWrapper();
    
    // Test: Verify functions are accessible
    console.log('handleFieldCheckboxChange function:', typeof handleFieldCheckboxChange);
    console.log('saveColumnPreferences function:', typeof saveColumnPreferences);
    console.log('visibleFields after load:', visibleFields?.value ? Array.from(visibleFields.value) : 'undefined');
    console.log('visibleFields.size after load:', visibleFields?.value?.size || 0);
    console.log('visibleFieldsSet computed:', Array.from(visibleFieldsSet.value));
    console.log('visibleFieldsSet.size:', visibleFieldsSet.value.size);
    console.log('allAvailableFields:', allAvailableFields.value);
    
    // Verify checkbox state for a few fields
    if (visibleFieldsSet.value.size > 0 && allAvailableFields.value.length > 0) {
        const testFields = ['email', 'fname', 'lname', 'sid', 'zip'];
        testFields.forEach(fieldKey => {
            const isVisible = visibleFieldsSet.value.has(fieldKey);
            console.log(`Field "${fieldKey}": visibleFieldsSet.has()=${isVisible}`);
        });
    }
    
    // If no preferences loaded, use smart defaults
    if (visibleFields && visibleFields.value && visibleFields.value.size === 0 && allAvailableFields.value.length > 0) {
        console.log('No preferences found, using smart defaults');
        const defaults = props.smartDefaults || allAvailableFields.value.slice(0, 4).map(f => f.key);
        if (visibleFields.value) {
            visibleFields.value = new Set(defaults);
            console.log('Set defaults:', Array.from(visibleFields.value));
        }
    }
});
</script>

<template>
    <Head :title="`${formName} - Submissions`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="userDashboard().url">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Form Center
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                            <FileText class="h-6 w-6" />
                            {{ formName }}
                        </h1>
                        <p class="text-muted-foreground mt-1">
                            {{ totalCount }} total {{ totalCount === 1 ? 'submission' : 'submissions' }} • Station: {{ station }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Form ID: {{ webformId }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="h-8 px-3">{{ station }}</Badge>
                </div>
            </div>

            <!-- Column Visibility Controls - Simple and Clear -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <Columns class="h-5 w-5" />
                            Show/Hide Columns
                        </CardTitle>
                        <div class="flex gap-2">
                            <Button variant="ghost" size="sm" @click="async () => { 
                                const newSet = new Set(visibleFields);
                                allAvailableFields.forEach((f: FieldInfo) => {
                                    newSet.add(f.key);
                                });
                                visibleFields = newSet;
                                await saveColumnPreferences();
                            }" class="h-8 text-xs">
                                Select All
                            </Button>
                            <Button variant="ghost" size="sm" @click="async () => {
                                visibleFields = new Set();
                                await saveColumnPreferences();
                            }" class="h-8 text-xs">
                                Clear All
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <label 
                            v-for="field in allAvailableFields" 
                            :key="field.key"
                            class="flex items-center gap-2 cursor-pointer hover:bg-muted p-2 rounded transition-colors"
                        >
                            <input
                                type="checkbox"
                                :checked="visibleFields.has(field.key)"
                                @change="async () => {
                                    const newSet = new Set(visibleFields);
                                    if (newSet.has(field.key)) {
                                        newSet.delete(field.key);
                                    } else {
                                        newSet.add(field.key);
                                    }
                                    visibleFields = newSet;
                                    await saveColumnPreferences();
                                }"
                                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                            />
                            <span class="text-sm font-medium select-none">{{ field.label }}</span>
                            <Badge 
                                v-if="isNewField(field.key)" 
                                variant="secondary" 
                                class="ml-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                            >
                                New
                            </Badge>
                        </label>
                    </div>
                    <div class="mt-3 text-xs text-muted-foreground border-t pt-3">
                        <div class="flex items-center gap-2">
                            <span v-if="savingPrefs" class="text-blue-600 animate-pulse">Saving...</span>
                            <span v-else-if="visibleFields.size > 0" class="text-green-600">✓ Saved</span>
                        </div>
                        <div class="mt-1">
                            Preference level: <strong>Type</strong> (applies to all {{ submissionForm || 'forms' }} forms)
                        </div>
                    </div>
                </CardContent>
            </Card>


            <!-- Filters -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Filters
                        </CardTitle>
                        <div class="flex gap-2">
                            <Button 
                                variant="ghost" 
                                size="sm"
                                @click="showAdvancedFilters = !showAdvancedFilters"
                            >
                                {{ showAdvancedFilters ? 'Hide' : 'Show' }} Advanced
                                <ChevronDown v-if="!showAdvancedFilters" class="ml-2 h-4 w-4" />
                                <ChevronUp v-else class="ml-2 h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" @click="clearFilters">
                                <XCircle class="mr-2 h-4 w-4" />
                                Clear All
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Basic Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <Input
                                v-model="searchQuery"
                                placeholder="Search submissions..."
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Status</label>
                            <select 
                                v-model="selectedStatus"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="all">All Messages</option>
                                <option value="unread">Unread</option>
                                <option value="read">Read</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Date Range</label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="dateFrom"
                                    type="date"
                                    placeholder="From"
                                    class="flex-1"
                                />
                                <Input
                                    v-model="dateTo"
                                    type="date"
                                    placeholder="To"
                                    class="flex-1"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="border-t pt-4 mt-4">
                        <h3 class="text-sm font-semibold mb-4">Advanced Filters</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Age Range -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Age Range</label>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="ageMin"
                                        type="number"
                                        placeholder="Min"
                                        min="0"
                                        max="120"
                                    />
                                    <Input
                                        v-model="ageMax"
                                        type="number"
                                        placeholder="Max"
                                        min="0"
                                        max="120"
                                    />
                                </div>
                            </div>

                            <!-- Birth Year Range -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Birth Year Range</label>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="birthYearMin"
                                        type="number"
                                        placeholder="From Year"
                                        :min="1900"
                                        :max="new Date().getFullYear()"
                                    />
                                    <Input
                                        v-model="birthYearMax"
                                        type="number"
                                        placeholder="To Year"
                                        :min="1900"
                                        :max="new Date().getFullYear()"
                                    />
                                </div>
                            </div>

                            <!-- ZIP Code -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">ZIP Code</label>
                                <Input
                                    v-model="zipCode"
                                    placeholder="ZIP/Postal Code"
                                />
                            </div>

                            <!-- City -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">City / Place</label>
                                <Input
                                    v-model="city"
                                    placeholder="City or place of residence"
                                />
                            </div>

                            <!-- Gender -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Gender</label>
                                <select 
                                    v-model="gender"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="m">Male</option>
                                    <option value="f">Female</option>
                                    <option value="d">Diverse</option>
                                </select>
                            </div>

                            <!-- Radius/Distance -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Radius Search (km)</label>
                                <div class="space-y-2">
                                    <Input
                                        v-model="radius"
                                        type="number"
                                        placeholder="Radius in km"
                                        min="0"
                                        step="0.1"
                                    />
                                    <div class="flex gap-2">
                                        <Input
                                            v-model="radiusLat"
                                            type="number"
                                            placeholder="Latitude"
                                            step="0.000001"
                                        />
                                        <Input
                                            v-model="radiusLng"
                                            type="number"
                                            placeholder="Longitude"
                                            step="0.000001"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Apply Button -->
                    <div class="mt-4 flex justify-end">
                        <Button @click="applyFilters">
                            <Search class="mr-2 h-4 w-4" />
                            Apply Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions -->
            <div v-if="selectedRows.size > 0" class="flex items-center gap-2 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <span class="text-sm font-medium">
                    {{ selectedRows.size }} {{ selectedRows.size === 1 ? 'submission' : 'submissions' }} selected
                </span>
                <div class="flex gap-2 ml-auto">
                    <Button variant="outline" size="sm" @click="bulkMarkAsRead">
                        Mark as Read
                    </Button>
                    <Button variant="outline" size="sm" @click="exportSelected">
                        <Download class="mr-2 h-4 w-4" />
                        Export
                    </Button>
                    <Button variant="destructive" size="sm" @click="bulkDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                    <Button variant="ghost" size="sm" @click="selectedRows.clear()">
                        Clear Selection
                    </Button>
                </div>
            </div>

            <!-- Submissions Table -->
            <Card>
                <CardContent class="p-0">
                    <div v-if="!submissions.data || submissions.data.length === 0" class="text-center py-12">
                        <FileText class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No submissions found</h3>
                        <p class="mt-2 text-gray-500">No submissions match your current filters.</p>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead v-if="visibleColumns.has('checkbox')" class="w-12">
                                    <Checkbox 
                                        :checked="allRowsSelected"
                                        @update:checked="toggleAllRows"
                                    />
                                </TableHead>
                                <TableHead v-if="visibleColumns.has('status')" class="w-12">Status</TableHead>
                                <TableHead v-if="visibleColumns.has('id')" class="w-16">ID</TableHead>
                                <!-- Dynamic field columns -->
                                <TableHead 
                                    v-for="field in visibleFieldList" 
                                    :key="`header-${field.key}`"
                                    :class="field.type === 'object' ? 'hidden lg:table-cell' : ''"
                                >
                                    {{ field.label }}
                                </TableHead>
                                <TableHead v-if="visibleColumns.has('date')" class="w-32">Date</TableHead>
                                <TableHead v-if="visibleColumns.has('actions')" class="w-48 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="submission in submissions.data || []" 
                                :key="submission.id"
                                :class="[
                                    'transition-colors hover:bg-gray-50/50',
                                    !isReadByCurrentUser(submission) ? 'bg-blue-50/30 border-l-4 border-l-blue-500' : '',
                                    editingRow === submission.id ? 'bg-yellow-50/50' : ''
                                ]"
                            >
                                <!-- Checkbox -->
                                <TableCell v-if="visibleColumns.has('checkbox')">
                                    <Checkbox 
                                        :checked="selectedRows.has(submission.id)"
                                        @update:checked="() => {
                                            console.log('Row checkbox clicked:', submission.id);
                                            toggleRowSelection(submission.id);
                                        }"
                                    />
                                </TableCell>

                                <!-- Status -->
                                <TableCell v-if="visibleColumns.has('status')">
                                    <div class="flex items-center justify-center">
                                        <CheckCircle2 
                                            v-if="isReadByCurrentUser(submission)" 
                                            class="h-4 w-4 text-green-600" 
                                            title="Read"
                                        />
                                        <AlertCircle 
                                            v-else 
                                            class="h-4 w-4 text-blue-600" 
                                            title="Unread"
                                        />
                                    </div>
                                </TableCell>

                                <!-- ID -->
                                <TableCell v-if="visibleColumns.has('id')" class="font-mono text-sm text-gray-500">
                                    #{{ submission.id }}
                                </TableCell>

                                <!-- Dynamic field columns -->
                                <TableCell 
                                    v-for="field in visibleFieldList" 
                                    :key="`cell-${submission.id}-${field.key}`"
                                    :class="field.type === 'object' ? 'hidden lg:table-cell' : ''"
                                >
                                    <div v-if="editingRow === submission.id" class="flex items-center gap-2">
                                        <Input 
                                            v-model="editingData[field.key]"
                                            class="h-8"
                                            :placeholder="field.label"
                                        />
                                    </div>
                                    <div v-else class="text-sm">
                                        <span v-if="field.type === 'object'" class="font-mono text-xs">
                                            {{ formatFieldValue(getFieldValue(submission.data, field.key), field.type) }}
                                        </span>
                                        <span v-else-if="field.type === 'date'" class="text-gray-700">
                                            {{ formatFieldValue(getFieldValue(submission.data, field.key), field.type) }}
                                        </span>
                                        <span v-else class="text-gray-700 truncate max-w-xs">
                                            {{ formatFieldValue(getFieldValue(submission.data, field.key), field.type) }}
                                        </span>
                                    </div>
                                </TableCell>

                                <!-- Date -->
                                <TableCell v-if="visibleColumns.has('date')" class="text-sm text-gray-500">
                                    <div class="flex flex-col">
                                        <span>{{ new Date(submission.created_at).toLocaleDateString() }}</span>
                                        <span class="text-xs">{{ new Date(submission.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell v-if="visibleColumns.has('actions')">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Edit/Save -->
                                        <template v-if="editingRow === submission.id">
                                            <Button 
                                                @click="saveEditing(submission)"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 w-8 p-0 text-green-600"
                                            >
                                                <Save class="h-4 w-4" />
                                            </Button>
                                            <Button 
                                                @click="cancelEditing"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 w-8 p-0 text-red-600"
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </template>
                                        <template v-else>
                                            <!-- View -->
                                            <Link :href="`${contactShow(submission.id).url}?from=forms&webform_id=${props.webformId}`">
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>

                                            <!-- Edit -->
                                            <Button 
                                                @click="startEditing(submission)"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>

                                            <!-- Toggle Read -->
                                            <Button 
                                                @click="toggleRead(submission)"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                            >
                                                <CheckCircle2 
                                                    v-if="isReadByCurrentUser(submission)" 
                                                    class="h-4 w-4 text-green-600" 
                                                />
                                                <AlertCircle 
                                                    v-else 
                                                    class="h-4 w-4 text-blue-600" 
                                                />
                                            </Button>

                                            <!-- Delete -->
                                            <Button 
                                                @click="deleteSubmission(submission)"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 w-8 p-0 text-red-600 hover:text-red-800"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </template>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="submissions.links && submissions.links.length > 3" class="flex items-center justify-center">
                <nav class="flex items-center space-x-1">
                    <template v-for="(link, index) in submissions.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm border border-gray-300 hover:bg-gray-50 rounded',
                                link.active ? 'bg-blue-50 border-blue-500 text-blue-600' : 'text-gray-700'
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            :class="[
                                'px-3 py-2 text-sm border border-gray-300 rounded',
                                link.active ? 'bg-blue-50 border-blue-500 text-blue-600' : 'text-gray-400 bg-gray-100'
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>

