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

interface Props {
    webformId: string;
    formName: string;
    station: string;
    submissions: {
        data?: ContactSubmission[];
        links?: any[];
        meta?: any;
    };
    totalCount: number;
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

// Column visibility
const visibleColumns = ref<Set<string>>(new Set(['checkbox', 'status', 'id', 'name', 'email', 'message', 'date', 'actions']));
const showColumnSettings = ref(false);

// Sorting
const sortColumn = ref<string>(props.sortColumn || 'created_at');
const sortDirection = ref<'asc' | 'desc'>((props.sortDirection as 'asc' | 'desc') || 'desc');

// Presets
const presets = ref<any[]>([]);
const showPresetDialog = ref(false);
const presetName = ref('');
const savingPreset = ref(false);
const loadingPreset = ref(false);

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
    return props.submissions.data?.length > 0 && 
           selectedRows.value.size === props.submissions.data.length;
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

// Column visibility toggle
const toggleColumn = (column: string) => {
    if (visibleColumns.value.has(column)) {
        visibleColumns.value.delete(column);
    } else {
        visibleColumns.value.add(column);
    }
    saveColumnPreferences();
};

// Save column preferences
const saveColumnPreferences = async () => {
    try {
        await fetch('/api/preferences', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                category: props.webformId,
                preference_name: 'column-visibility',
                visible_columns: Array.from(visibleColumns.value),
                is_default: false
            })
        });
    } catch (error) {
        console.error('Error saving column preferences:', error);
    }
};

// Load preferences
const loadPreferences = async () => {
    try {
        const response = await fetch(`/api/preferences?category=${props.webformId}`);
        const data = await response.json();
        
        if (data.success) {
            presets.value = data.preferences || [];
            
            // Load default preference or column visibility preference
            const defaultPref = data.preferences.find((p: any) => p.is_default) 
                || data.preferences.find((p: any) => p.preference_name === 'column-visibility');
            
            if (defaultPref) {
                if (defaultPref.visible_columns) {
                    visibleColumns.value = new Set(defaultPref.visible_columns);
                }
                if (defaultPref.sort_config) {
                    sortColumn.value = defaultPref.sort_config.column || 'created_at';
                    sortDirection.value = defaultPref.sort_config.direction || 'desc';
                }
            }
        }
    } catch (error) {
        console.error('Error loading preferences:', error);
    }
};

// Save preset
const savePreset = async () => {
    if (!presetName.value.trim()) {
        alert('Please enter a preset name');
        return;
    }
    
    savingPreset.value = true;
    try {
        const response = await fetch('/api/preferences', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                category: props.webformId, // Store webform_id in category field
                preference_name: presetName.value,
                visible_columns: Array.from(visibleColumns.value),
                sort_config: {
                    column: sortColumn.value,
                    direction: sortDirection.value
                },
                saved_filters: {
                    search: searchQuery.value,
                    status: selectedStatus.value,
                    date_from: dateFrom.value,
                    date_to: dateTo.value,
                    age_min: ageMin.value,
                    age_max: ageMax.value,
                    birth_year_min: birthYearMin.value,
                    birth_year_max: birthYearMax.value,
                    zip_code: zipCode.value,
                    city: city.value,
                    gender: gender.value,
                    radius: radius.value,
                    radius_lat: radiusLat.value,
                    radius_lng: radiusLng.value,
                },
                is_default: false
            })
        });
        
        const data = await response.json();
        if (data.success) {
            presetName.value = '';
            showPresetDialog.value = false;
            await loadPreferences();
            alert('Preset saved successfully!');
        }
    } catch (error) {
        console.error('Error saving preset:', error);
        alert('Error saving preset');
    } finally {
        savingPreset.value = false;
    }
};

// Load preset
const loadPreset = async (preference: any) => {
    loadingPreset.value = true;
    try {
        // 1. Update visible columns
        if (preference.visible_columns) {
            visibleColumns.value = new Set(preference.visible_columns);
        }
        
        // 2. Update sorting
        if (preference.sort_config) {
            sortColumn.value = preference.sort_config.column || 'created_at';
            sortDirection.value = preference.sort_config.direction || 'desc';
        }
        
        // 3. Update filters
        if (preference.saved_filters) {
            const filters = preference.saved_filters;
            searchQuery.value = filters.search || '';
            selectedStatus.value = filters.status || 'all';
            dateFrom.value = filters.date_from || '';
            dateTo.value = filters.date_to || '';
            ageMin.value = filters.age_min || '';
            ageMax.value = filters.age_max || '';
            birthYearMin.value = filters.birth_year_min || '';
            birthYearMax.value = filters.birth_year_max || '';
            zipCode.value = filters.zip_code || '';
            city.value = filters.city || '';
            gender.value = filters.gender || '';
            radius.value = filters.radius || '';
            radiusLat.value = filters.radius_lat || '';
            radiusLng.value = filters.radius_lng || '';
        }
        
        // 4. Apply filters by navigating with updated query params
        // This will reload the page with the new filters and sorting
        await applyFilters();
        
        // Show success message
        console.log('Preset loaded successfully:', preference.preference_name);
    } catch (error) {
        console.error('Error loading preset:', error);
        alert('Error loading preset. Please try again.');
    } finally {
        loadingPreset.value = false;
    }
};

// Delete preset
const deletePreset = async (preferenceId: number) => {
    if (!confirm('Are you sure you want to delete this preset?')) return;
    
    try {
        const response = await fetch(`/api/preferences/${preferenceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        
        const data = await response.json();
        if (data.success) {
            await loadPreferences();
        }
    } catch (error) {
        console.error('Error deleting preset:', error);
    }
};

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

// Handle preset button click
const handlePresetButtonClick = () => {
    if (presets.value.length === 0) {
        // No presets, show save dialog
        presetName.value = '';
        showPresetDialog.value = true;
    } else {
        // Has presets, toggle list
        showPresetDialog.value = !showPresetDialog.value;
    }
};

onMounted(() => {
    // Auto-apply filters on mount if they exist
    if (searchQuery.value || selectedStatus.value !== 'all') {
        applyFilters();
    }
    // Load user preferences
    loadPreferences();
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
                    <!-- TODO: View Settings Button - Temporarily Hidden (not working properly yet) -->
                    <!-- 
                    <div class="relative">
                        <Button variant="outline" size="sm" @click="showColumnSettings = !showColumnSettings">
                            <Settings class="mr-2 h-4 w-4" />
                            View Settings
                            <Badge v-if="presets.length > 0" variant="secondary" class="ml-2">
                                {{ presets.length }}
                            </Badge>
                        </Button>
                        
                        <div v-if="showColumnSettings" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 border rounded-lg shadow-xl z-50">
                            <div v-if="presets.length > 0" class="border-b p-3">
                                <div class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <BookmarkCheck class="h-4 w-4" />
                                    Saved Views
                                </div>
                                <div class="max-h-40 overflow-y-auto space-y-1">
                                    <div 
                                        v-for="preset in presets" 
                                        :key="preset.id"
                                        class="flex items-center justify-between p-2 hover:bg-muted rounded cursor-pointer group"
                                    >
                                        <div class="flex-1 min-w-0" @click="loadPreset(preset); showColumnSettings = false">
                                            <div class="text-sm font-medium truncate">{{ preset.preference_name }}</div>
                                            <div class="text-xs text-muted-foreground">Click to load this view</div>
                                        </div>
                                        <Button 
                                            variant="ghost" 
                                            size="sm"
                                            @click.stop="deletePreset(preset.id)"
                                            class="h-7 w-7 p-0 text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 border-b">
                                <div class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <Columns class="h-4 w-4" />
                                    Show/Hide Columns
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('checkbox')"
                                        @update:checked="() => toggleColumn('checkbox')"
                                    />
                                    <span class="text-sm">Checkbox</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('status')"
                                        @update:checked="() => toggleColumn('status')"
                                    />
                                    <span class="text-sm">Status</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('id')"
                                        @update:checked="() => toggleColumn('id')"
                                    />
                                    <span class="text-sm">ID</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('name')"
                                        @update:checked="() => toggleColumn('name')"
                                    />
                                    <span class="text-sm">Name</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('email')"
                                        @update:checked="() => toggleColumn('email')"
                                    />
                                    <span class="text-sm">Email</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('message')"
                                        @update:checked="() => toggleColumn('message')"
                                    />
                                    <span class="text-sm">Message</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('date')"
                                        @update:checked="() => toggleColumn('date')"
                                    />
                                    <span class="text-sm">Date</span>
                                </label>
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <Checkbox 
                                        :checked="visibleColumns.has('actions')"
                                        @update:checked="() => toggleColumn('actions')"
                                    />
                                    <span class="text-sm">Actions</span>
                                </label>
                            </div>
                        </div>

                        <div class="p-3 bg-muted/30">
                            <Button 
                                variant="default" 
                                size="sm" 
                                class="w-full"
                                @click="showPresetDialog = true; showColumnSettings = false"
                            >
                                <Bookmark class="mr-2 h-4 w-4" />
                                Save Current View As...
                            </Button>
                            <p class="text-xs text-muted-foreground mt-2 text-center">
                                Save your column selection and filters for quick access later
                            </p>
                        </div>
                    </div>
                    -->
                    
                    <Badge variant="secondary" class="h-8 px-3">{{ station }}</Badge>
                </div>
            </div>

            <!-- Save Preset Dialog -->
            <div v-if="showPresetDialog && (presets.length === 0 || presetName !== '')" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100]" @click.self="showPresetDialog = false; presetName = ''">
                <Card class="w-96" @click.stop>
                    <CardHeader>
                        <CardTitle>Save Preset</CardTitle>
                        <CardDescription>Save your current view settings as a preset</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium">Preset Name</label>
                                <Input
                                    v-model="presetName"
                                    placeholder="e.g., My Custom View"
                                    @keyup.enter="savePreset"
                                />
                            </div>
                            <div class="flex gap-2 justify-end">
                                <Button variant="outline" @click="showPresetDialog = false; presetName = ''">
                                    Cancel
                                </Button>
                                <Button @click="savePreset" :disabled="savingPreset || !presetName.trim()">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ savingPreset ? 'Saving...' : 'Save Preset' }}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

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
                                <TableHead v-if="visibleColumns.has('name')">Name</TableHead>
                                <TableHead v-if="visibleColumns.has('email')">Email</TableHead>
                                <TableHead v-if="visibleColumns.has('message')" class="hidden md:table-cell">Message Preview</TableHead>
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
                                        @update:checked="() => toggleRowSelection(submission.id)"
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

                                <!-- Name -->
                                <TableCell v-if="visibleColumns.has('name')">
                                    <div v-if="editingRow === submission.id" class="flex items-center gap-2">
                                        <Input 
                                            v-model="editingData.name"
                                            class="h-8"
                                            placeholder="Name"
                                        />
                                    </div>
                                    <div v-else class="font-medium text-gray-900">
                                        {{ getDisplayName(submission.data) }}
                                    </div>
                                </TableCell>

                                <!-- Email -->
                                <TableCell v-if="visibleColumns.has('email')">
                                    <div v-if="editingRow === submission.id" class="flex items-center gap-2">
                                        <Input 
                                            v-model="editingData.email"
                                            class="h-8"
                                            placeholder="Email"
                                        />
                                    </div>
                                    <div v-else class="text-sm text-gray-500 truncate max-w-xs">
                                        {{ getDisplayEmail(submission.data) }}
                                    </div>
                                </TableCell>

                                <!-- Message Preview -->
                                <TableCell v-if="visibleColumns.has('message')" class="hidden md:table-cell">
                                    <div v-if="editingRow === submission.id" class="flex items-center gap-2">
                                        <Input 
                                            v-model="editingData.description"
                                            class="h-8"
                                            placeholder="Description"
                                        />
                                    </div>
                                    <p v-else class="text-sm text-gray-700 max-w-xs truncate">
                                        {{ getMessageText(submission.data) }}
                                    </p>
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

