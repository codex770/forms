<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFieldPreferences } from '@/composables/useFieldPreferences';
import AppLayout from '@/layouts/AppLayout.vue';
import { postJson } from '@/lib/api';
import {
    destroy as contactDestroy,
    show as contactShow,
    toggleRead as contactToggleRead,
} from '@/routes/contact';
import { dashboard as userDashboard } from '@/routes/user';
import { type BreadcrumbItem } from '@/types';
import { autoTranslate } from '@/utils/autoTranslate';
import {
    dedupeFieldsByCanonicalKey,
    toCanonicalFieldKey,
} from '@/utils/fieldAliases';
import {
    formatFieldValue,
    groupFieldsByCategory,
} from '@/utils/fieldDetection';
import { fieldOverrides, useI18n } from '@/utils/i18n';
import { filterOutTechnicalFields } from '@/utils/technicalFields';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Columns,
    Download,
    Edit,
    Eye,
    FileSpreadsheet,
    FileText,
    Filter,
    GripVertical,
    Save,
    Search,
    Star,
    Trash2,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface ContactSubmission {
    id: number;
    category: string;
    data: {
        name?: string;
        email?: string;
        description?: string;
        [key: string]: any;
    };
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
    marks?: Array<{
        id: number;
        user_id: number;
        marked_at: string;
    }>;
    duplicate_count?: number;
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
        plz_from?: string;
        plz_to?: string;
        city?: string;
        gender?: string;
        radius?: string;
        radius_plz?: string;
        hide_duplicates?: boolean;
    };
    sortColumn?: string;
    sortDirection?: string;
    retentionDays?: number | null;
    radiusWarning?: string | null;
}

const props = defineProps<Props>();

const { t, tField, locale, humanizeKey, autoTranslateCache } = useI18n();

// New fields from backend
const newFields = computed(() => props.newFields || []);

const authUser = computed(() => usePage().props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
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
const plzFrom = ref(props.filters.plz_from || '');
const plzTo = ref(props.filters.plz_to || '');
const city = ref(props.filters.city || '');
const gender = ref(props.filters.gender || '');
const radius = ref(props.filters.radius || '');
const radiusPlz = ref(props.filters.radius_plz || '');
const hideDuplicates = ref(props.filters.hide_duplicates ?? false);
const showAdvancedFilters = ref(false);
const selectedRows = ref<Set<number>>(new Set());
const editingRow = ref<number | null>(null);
const editingData = ref<any>({});
const exportDialogOpen = ref(false);
const exportScope = ref<'current' | 'selected'>('current');

// Local copy of submission rows for in-place star/read updates (no page refetch)
const submissionRows = ref<ContactSubmission[]>([
    ...(props.submissions.data ?? []),
]);

watch(
    () => props.submissions.data,
    (data) => {
        submissionRows.value = data ? [...data] : [];
    },
);

// Field preferences composable - global form-level config (shared overview + detail)
const {
    visibleFieldOrder,
    visibleFieldSet,
    loadPreferences,
    savePreferences,
    getFieldValue,
    getFieldLabel,
    toggleField,
    setAllFields,
    moveField,
    loading: loadingPrefs,
    saving: savingPrefs,
} = useFieldPreferences(props.webformId, 'list', props.smartDefaults || [], {
    preferenceName: 'submission-visible-fields',
});

// Clear new fields notification
const clearNewFields = async () => {
    try {
        await fetch(`/forms/${props.webformId}/clear-new-fields`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
        });
    } catch (error) {
        console.error('Error clearing new fields:', error);
    }
};

// Column visibility - combine system columns with user-selected fields
const visibleColumns = computed(() => {
    const cols = new Set<string>(['checkbox', 'status', 'id', 'actions']); // System columns always visible
    visibleFieldOrder.value.forEach((field) => cols.add(field));
    return cols;
});

const showColumnSettings = ref(false);

// Sorting
const sortColumn = ref<string>(props.sortColumn || 'created_at');
const sortDirection = ref<'asc' | 'desc'>(
    (props.sortDirection as 'asc' | 'desc') || 'desc',
);

// Removed preset system - using single preference only (auto-saves on checkbox change)

// Available fields from backend - PRIMARY: Form-specific fields only
// This ensures we only show fields that exist in THIS form, not union of all forms
const allAvailableFields = computed(() => {
    let fields: FieldInfo[] = [];

    // PRIORITY: Use form-specific fields (availableFields) as primary source
    // These are fields detected from THIS specific form only
    if (props.availableFields && props.availableFields.length > 0) {
        fields = props.availableFields;
    }
    // FALLBACK: If no form-specific fields, use type-level (for new forms)
    else if (props.typeFields && props.typeFields.length > 0) {
        fields = props.typeFields;
    }
    // FALLBACK: Use station-level fields
    else {
        fields = props.stationFields || [];
    }

    // Filter out internal fields from user UI + collapse aliases to canonical keys
    return dedupeFieldsByCanonicalKey(filterOutTechnicalFields(fields));
});

// Check if a field is new (recently detected)
const isNewField = (fieldKey: string): boolean => {
    return props.newFields?.includes(fieldKey) || false;
};

const groupedFields = computed(() =>
    groupFieldsByCategory(allAvailableFields.value),
);

// Computed property for visible fields (ensures reactivity)
const visibleFieldList = computed(() => {
    const map = new Map<string, FieldInfo>();
    allAvailableFields.value.forEach((f) =>
        map.set(toCanonicalFieldKey(f.key), f),
    );

    return visibleFieldOrder.value
        .map((k) => map.get(toCanonicalFieldKey(k)))
        .filter(Boolean) as FieldInfo[];
});

// Computed property that returns the Set directly for template use
// This ensures Vue tracks changes and auto-unwraps properly
const visibleFieldsSet = computed(() => {
    return visibleFieldSet.value || new Set<string>();
});

// Create a computed getter/setter for each field's checked state
// This is needed for v-model:checked to work with reka-ui Checkbox
const getFieldChecked = (fieldKey: string) => {
    return computed({
        get: () => visibleFieldSet.value?.has(fieldKey) ?? false,
        set: (value: boolean | 'indeterminate') => {
            if (typeof value === 'boolean') {
                toggleField(fieldKey);
            }
        },
    });
};

// Save column preferences - saves as single default preference (auto-save on change)
async function saveColumnPreferences(): Promise<boolean> {
    const checkedFields = visibleFieldOrder.value.slice();

    // Only save fields that exist in availableFields (prevent saving non-existent fields)
    const validFields = checkedFields.filter((fieldKey) =>
        allAvailableFields.value.some((f: FieldInfo) => f.key === fieldKey),
    );

    if (validFields.length === 0) {
        return false;
    }

    return await savePreferences(validFields, {
        preferenceName: 'submission-visible-fields',
        asDefault: true,
    });
}

// Drag & drop ordering for selected columns
const draggingKey = ref<string | null>(null);
const onDragStart = (key: string) => {
    draggingKey.value = key;
};
const onDropOn = async (targetKey: string) => {
    if (!draggingKey.value) return;
    const from = draggingKey.value;
    draggingKey.value = null;
    if (from === targetKey) return;
    const toIndex = visibleFieldOrder.value.findIndex((k) => k === targetKey);
    if (toIndex < 0) return;
    moveField(from, toIndex);
    await saveColumnPreferences();
};

// Check if current user has read the submission
const isReadByCurrentUser = (submission: ContactSubmission): boolean => {
    return submission.reads_with_users.some(
        (read) => read.user_id === authUser.value?.id,
    );
};

const isMarkedByCurrentUser = (submission: ContactSubmission): boolean => {
    return (submission.marks || []).some(
        (m) => m.user_id === authUser.value?.id,
    );
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
    const parts = name.split(' ').filter((p) => p.length > 0);
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
    return (
        data?.name ||
        data?.fname ||
        data?.first_name ||
        data?.full_name ||
        data?.contact_name ||
        data?.email ||
        'Unknown'
    );
};

// Get display email
const getDisplayEmail = (data: any): string => {
    return data?.email || data?.email_address || t('common.no_email');
};

// Get message text - handles multiple message field variations
const getMessageText = (data: any): string => {
    return (
        data?.message_long ||
        data?.message_short ||
        data?.description ||
        data?.message ||
        data?.content ||
        data?.text ||
        t('common.no_message')
    );
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
    if (selectedRows.value.size === submissionRows.value.length) {
        selectedRows.value.clear();
    } else {
        submissionRows.value.forEach((submission) => {
            selectedRows.value.add(submission.id);
        });
    }
};

// Check if all rows are selected
const allRowsSelected = computed(() => {
    const total = submissionRows.value.length;
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
    if (plzFrom.value) params.plz_from = plzFrom.value;
    if (plzTo.value) params.plz_to = plzTo.value;
    if (city.value) params.city = city.value;
    if (gender.value) params.gender = gender.value;
    if (radius.value && radiusPlz.value) {
        params.radius = radius.value;
        params.radius_plz = radiusPlz.value;
    }
    if (hideDuplicates.value) params.hide_duplicates = '1';

    // Sorting
    if (sortColumn.value) params.sort_column = sortColumn.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;

    router.get(`/forms/${props.webformId}`, params, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            nextTick(() => {
                const firstRow = document.querySelector(
                    'tbody tr',
                ) as HTMLElement | null;
                firstRow?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            });
        },
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
    plzFrom.value = '';
    plzTo.value = '';
    city.value = '';
    gender.value = '';
    radius.value = '';
    radiusPlz.value = '';
    hideDuplicates.value = false;
    applyFilters();
};

// Bulk delete
const bulkDelete = () => {
    if (selectedRows.value.size === 0) return;

    if (
        confirm(
            t('common.confirm_bulk_delete', {
                count: selectedRows.value.size,
            }),
        )
    ) {
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

    exportScope.value = 'selected';
    exportDialogOpen.value = true;
};

const exportCurrentView = () => {
    exportScope.value = 'current';
    exportDialogOpen.value = true;
};

const buildExportUrl = (format: 'csv' | 'xlsx', ids?: number[]) => {
    const params = new URLSearchParams(window.location.search);
    visibleFieldOrder.value.forEach((f) =>
        params.append('fields[]', String(f)),
    );

    if (ids && ids.length > 0) {
        ids.forEach((id) => params.append('ids[]', String(id)));
    }

    return `/forms/${props.webformId}/export?format=${format}&${params.toString()}`;
};

const runExport = (format: 'csv' | 'xlsx') => {
    const ids =
        exportScope.value === 'selected'
            ? Array.from(selectedRows.value)
            : undefined;

    if (exportScope.value === 'selected' && (!ids || ids.length === 0)) {
        return;
    }

    const url = buildExportUrl(format, ids);
    exportDialogOpen.value = false;
    window.open(url, '_blank');
};

const printTable = () => {
    const cols = visibleFieldList.value;
    const rows = submissionRows.value;

    const ths = ['ID', ...cols.map((c)  => tField(c.key)), 'Date']
        .map((h) => `<th>${h}</th>`)
        .join('');
    const trs = rows
        .map((s) => {
            const tds = [
                `<td>#${s.id}</td>`,
                ...cols.map(
                    (c) =>
                        `<td>${String(getFieldValue(s.data, c.key) ?? '')}</td>`,
                ),
                `<td>${new Date(s.created_at).toLocaleString()}</td>`,
            ].join('');
            return `<tr>${tds}</tr>`;
        })
        .join('');

    const w = window.open('', '_blank');
    if (!w) return;
    w.document.write(`
      <html>
        <head>
          <title>Print</title>
          <style>
            body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 16px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; vertical-align: top; }
            th { background: #f5f5f5; text-align: left; }
          </style>
        </head>
        <body>
          <h2>${props.formName}</h2>
          <table>
            <thead><tr>${ths}</tr></thead>
            <tbody>${trs}</tbody>
          </table>
          <script>window.onload = () => { window.print(); }<\/script>
        </body>
      </html>
    `);
    w.document.close();
};

const toggleRead = async (submission: ContactSubmission) => {
    try {
        const data = await postJson(contactToggleRead(submission.id).url);

        if (data?.reads) {
            const idx = submissionRows.value.findIndex(
                (s) => s.id === submission.id,
            );
            if (idx >= 0) {
                submissionRows.value[idx] = {
                    ...submissionRows.value[idx],
                    reads_with_users: data.reads,
                };
            }
        }
    } catch (error) {
        console.error('Error toggling read status:', error);
        alert(
            t('common.error') +
                ': ' +
                (error instanceof Error ? error.message : String(error)),
        );
    }
};

const toggleMark = async (submission: ContactSubmission) => {
    try {
        const data = await postJson(
            `/contact-messages/${submission.id}/toggle-mark`,
        );
        const idx = submissionRows.value.findIndex(
            (s) => s.id === submission.id,
        );
        if (idx >= 0) {
            const userId = authUser.value?.id;
            submissionRows.value[idx] = {
                ...submissionRows.value[idx],
                marks:
                    data?.marked && userId
                        ? [
                              {
                                  id: 0,
                                  user_id: userId,
                                  marked_at: new Date().toISOString(),
                              },
                          ]
                        : (submissionRows.value[idx].marks ?? []).filter(
                              (m) => m.user_id !== userId,
                          ),
            };
        }
    } catch (error) {
        console.error('Error toggling mark:', error);
        alert(
            t('common.error') +
                ': ' +
                (error instanceof Error ? error.message : String(error)),
        );
    }
};

// Delete submission
const deleteSubmission = (submission: ContactSubmission) => {
    if (confirm(t('common.confirm_delete'))) {
        router.delete(contactDestroy(submission.id).url);
    }
};

// Handle checkbox change - toggle approach (simpler, matches Contact/Show.vue pattern)
const handleFieldCheckboxChange = async (fieldKey: string) => {
    try {
        toggleField(fieldKey);
        await saveColumnPreferences();
    } catch (error) {
        console.error('ERROR in handleFieldCheckboxChange:', error);
        console.error('Error stack:', (error as Error).stack);
        alert('Error: ' + (error as Error).message);
    }
};

// Removed setFieldVisibility - using handleFieldCheckboxChange directly

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
    handleFieldCheckboxChange(fieldKey).catch((err) => {
        console.error('Error in handleFieldCheckboxChange:', err);
    });
};

// Retention settings
const retentionDaysInput = ref<string>(
    props.retentionDays != null ? String(props.retentionDays) : '',
);
const retentionNotes = ref('');
const savingRetention = ref(false);
const retentionSaved = ref(false);

const saveRetentionRule = async () => {
    savingRetention.value = true;
    retentionSaved.value = false;
    try {
        const days =
            retentionDaysInput.value.trim() !== ''
                ? parseInt(retentionDaysInput.value, 10)
                : null;
        await postJson(
            `/forms/${props.webformId}/retention-rule`,
            {
                retention_days: days,
                notes: retentionNotes.value || null,
            },
            { method: 'PUT' },
        );
        retentionSaved.value = true;
        setTimeout(() => {
            retentionSaved.value = false;
        }, 3000);
    } catch (e) {
        console.error('Failed to save retention rule', e);
    } finally {
        savingRetention.value = false;
    }
};

onMounted(async () => {
    // Auto-apply filters on mount if they exist
    if (searchQuery.value || selectedStatus.value !== 'all') {
        applyFilters();
    }

    // Load user preferences
    await loadPreferencesWrapper();

    // If no preference found, apply smart defaults (canonical + ordered)
    if (
        visibleFieldOrder.value.length === 0 &&
        allAvailableFields.value.length > 0
    ) {
        const defaults =
            props.smartDefaults ||
            allAvailableFields.value.slice(0, 4).map((f: FieldInfo) => f.key);
        setAllFields(defaults, true);
        await saveColumnPreferences();
    }

    // Preload translations for unknown fields (non-English locale only)
    if (locale.value !== 'en' && allAvailableFields.value.length > 0) {
        const overrides = fieldOverrides[locale.value] ?? fieldOverrides['de'];
        const unknownFields = allAvailableFields.value
            .filter((f) => !overrides[f.key])
            .map((f) => ({ key: f.key, humanized: humanizeKey(f.key) }));

        if (unknownFields.length > 0) {
            await Promise.all(
                unknownFields.map(({ key, humanized }) =>
                    autoTranslate(humanized, locale.value).then(
                        (translated) => {
                            autoTranslateCache.value = {
                                ...autoTranslateCache.value,
                                [`${locale.value}:${key}`]: translated,
                            };
                        },
                    ),
                ),
            );
        }
    }
});
</script>

<template>
    <Head :title="`${formName} - Submissions`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="userDashboard().url">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            {{ t('forms.back') }}
                        </Button>
                    </Link>
                    <div>
                        <h1
                            class="flex items-center gap-2 text-3xl font-bold tracking-tight"
                        >
                            <FileText class="h-6 w-6" />
                            {{ formName }}
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            {{ totalCount }}
                            {{ t('forms.total_submissions') }} •
                            {{ t('forms.station') }}: {{ station }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Form ID: {{ webformId }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="h-8 px-3">{{
                        station
                    }}</Badge>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="
                            hideDuplicates = !hideDuplicates;
                            applyFilters();
                        "
                        :class="
                            hideDuplicates
                                ? 'border-orange-400 text-orange-600'
                                : ''
                        "
                        title="When enabled, duplicate submissions (same email/phone/name/PLZ/birthyear) are collapsed into a single row with a count badge."
                    >
                        <Eye class="mr-2 h-4 w-4" />
                        {{
                            hideDuplicates
                                ? t('forms.duplicates_hidden')
                                : t('forms.duplicates_shown')
                        }}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="exportCurrentView"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        {{ t('forms.export') }}
                    </Button>
                    <Button variant="outline" size="sm" @click="printTable">
                        {{ t('forms.print') }}
                    </Button>
                </div>
            </div>

            <!-- Column Visibility Controls - Simple and Clear -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <Columns class="h-5 w-5" />
                            {{ t('columns.title') }}
                        </CardTitle>
                        <div class="flex gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="
                                    async () => {
                                        setAllFields(
                                            allAvailableFields.map(
                                                (f) => f.key,
                                            ),
                                            true,
                                        );
                                        await saveColumnPreferences();
                                    }
                                "
                                class="h-8 text-xs"
                            >
                                {{ t('columns.select_all') }}
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="
                                    async () => {
                                        setAllFields([], false);
                                        await saveColumnPreferences();
                                    }
                                "
                                class="h-8 text-xs"
                            >
                                {{ t('columns.clear_all') }}
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Reorder selected columns -->
                    <div v-if="visibleFieldList.length > 1" class="mb-4">
                        <div
                            class="mb-2 text-xs font-semibold text-muted-foreground"
                        >
                            {{ t('columns.reorder') }}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="field in visibleFieldList"
                                :key="`reorder-${field.key}`"
                                class="cursor-move rounded border bg-background px-2 py-1 text-xs select-none hover:bg-muted"
                                draggable="true"
                                @dragstart="onDragStart(field.key)"
                                @dragover.prevent
                                @drop.prevent="onDropOn(field.key)"
                                :title="tField(field.key)"
                            >
                                {{ tField(field.key) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label
                            v-for="field in allAvailableFields"
                            :key="field.key"
                            class="flex cursor-pointer items-center gap-2 rounded p-2 transition-colors hover:bg-muted"
                        >
                            <input
                                type="checkbox"
                                :checked="visibleFieldSet.has(field.key)"
                                @change="
                                    async () => {
                                        await handleFieldCheckboxChange(
                                            field.key,
                                        );
                                    }
                                "
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm font-medium select-none">{{
                                tField(field.key)
                            }}</span>
                            <Badge
                                v-if="isNewField(field.key)"
                                variant="secondary"
                                class="ml-1 bg-blue-100 text-xs text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                            >
                                New
                            </Badge>
                        </label>
                    </div>
                    <div
                        class="mt-3 border-t pt-3 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                v-if="savingPrefs"
                                class="animate-pulse text-blue-600"
                                >Saving...</span
                            >
                            <span
                                v-else-if="visibleFieldOrder.length > 0"
                                class="text-green-600"
                                >✓ Saved</span
                            >
                        </div>
                        <div class="mt-1">
                            {{ t('columns.form_config') }}
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
                            {{ t('filter.title') }}
                        </CardTitle>
                        <div class="flex gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="
                                    showAdvancedFilters = !showAdvancedFilters
                                "
                            >
                                {{
                                    showAdvancedFilters
                                        ? t('filter.hide_advanced')
                                        : t('filter.advanced')
                                }}
                                <ChevronDown
                                    v-if="!showAdvancedFilters"
                                    class="ml-2 h-4 w-4"
                                />
                                <ChevronUp v-else class="ml-2 h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="clearFilters"
                            >
                                <XCircle class="mr-2 h-4 w-4" />
                                {{ t('filter.clear_all') }}
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Basic Filters -->
                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <label
                                class="flex h-5 items-center text-sm font-medium"
                            >
                                {{ t('filter.search') }}</label
                            >
                            <Input
                                v-model="searchQuery"
                                :placeholder="t('filter.search_placeholder')"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                class="flex h-5 items-center gap-1.5 text-sm font-medium"
                            >
                                <Star
                                    class="h-3.5 w-3.5 shrink-0 text-yellow-500"
                                />
                                {{ t('filter.status') }}
                            </label>

                            <Select v-model="selectedStatus">
                                <SelectTrigger class="h-9 w-full">
                                    <SelectValue
                                        :placeholder="t('filter.status_all')"
                                    />
                                </SelectTrigger>

                                <SelectContent class="w-127">
                                    <SelectItem value="all">
                                        {{ t('filter.status_all') }}
                                    </SelectItem>

                                    <SelectItem value="unread">
                                        {{ t('filter.status_unread') }}
                                    </SelectItem>

                                    <SelectItem value="read">
                                        {{ t('filter.status_read') }}
                                    </SelectItem>

                                    <SelectItem value="starred">
                                        {{ t('filter.status_starred') }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="flex h-5 items-center text-sm font-medium"
                                >{{ t('filter.date_range') }}</label
                            >
                            <div class="flex gap-2">
                                <Input
                                    v-model="dateFrom"
                                    type="date"
                                    :placeholder="t('common.from')"
                                    class="flex-1"
                                />
                                <Input
                                    v-model="dateTo"
                                    type="date"
                                    :placeholder="t('common.to')"
                                    class="flex-1"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="mt-4 border-t pt-4">
                        <h3 class="mb-4 text-sm font-semibold">
                            {{ t('filter.advanced') }}
                        </h3>
                        <div
                            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                        >
                            <!-- Age Range -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{
                                    t('filter.age_range')
                                }}</label>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="ageMin"
                                        type="number"
                                        :placeholder="t('common.min')"
                                        min="0"
                                        max="120"
                                    />
                                    <Input
                                        v-model="ageMax"
                                        type="number"
                                        :placeholder="t('common.max')"
                                        min="0"
                                        max="120"
                                    />
                                </div>
                            </div>

                            <!-- Birth Year Range -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{
                                    t('filter.birth_year')
                                }}</label>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="birthYearMin"
                                        type="number"
                                        :placeholder="t('common.from')"
                                        :min="1900"
                                        :max="new Date().getFullYear()"
                                    />
                                    <Input
                                        v-model="birthYearMax"
                                        type="number"
                                        :placeholder="t('common.to')"
                                        :min="1900"
                                        :max="new Date().getFullYear()"
                                    />
                                </div>
                            </div>

                            <!-- ZIP Code -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{
                                    t('filter.plz')
                                }}</label>
                                <Input
                                    v-model="zipCode"
                                    :placeholder="t('filter.plz')"
                                />
                            </div>

                            <!-- PLZ Range -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium"
                                    >{{ t('filter.plz_from') }} –
                                    {{ t('filter.plz_to') }}</label
                                >
                                <div class="flex gap-2">
                                    <Input
                                        v-model="plzFrom"
                                        :placeholder="t('common.from')"
                                        inputmode="numeric"
                                    />
                                    <Input
                                        v-model="plzTo"
                                        :placeholder="t('common.to')"
                                        inputmode="numeric"
                                    />
                                </div>
                            </div>

                            <!-- City -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{
                                    t('filter.city')
                                }}</label>
                                <Input
                                    v-model="city"
                                    :placeholder="t('filter.city')"
                                />
                            </div>

                            <!-- Gender -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{
                                    t('filter.gender')
                                }}</label>
                                <select
                                    v-model="gender"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">
                                        {{ t('filter.gender_all') }}
                                    </option>
                                    <option value="m">
                                        {{ t('filter.gender_m') }}
                                    </option>
                                    <option value="f">
                                        {{ t('filter.gender_f') }}
                                    </option>
                                    <option value="d">
                                        {{ t('filter.gender_d') }}
                                    </option>
                                </select>
                            </div>

                            <!-- Radius/Distance -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{
                                    t('filter.radius')
                                }}</label>
                                <div class="space-y-2">
                                    <Input
                                        v-model="radius"
                                        type="number"
                                        :placeholder="t('filter.radius')"
                                        min="0"
                                        step="0.1"
                                    />
                                    <Input
                                        v-model="radiusPlz"
                                        :placeholder="t('filter.radius_plz')"
                                        inputmode="numeric"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="props.radiusWarning"
                        class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
                    >
                        {{ props.radiusWarning }}
                    </div>

                    <!-- Apply Button -->
                    <div class="mt-4 flex justify-end">
                        <Button @click="applyFilters">
                            <Search class="mr-2 h-4 w-4" />
                            {{ t('filter.apply') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions -->
            <div
                v-if="selectedRows.size > 0"
                class="flex items-center gap-2 rounded-lg bg-blue-50 p-4 dark:bg-blue-950"
            >
                <span class="text-sm font-medium">
                    {{ selectedRows.size }} {{ t('bulk.selected') }}
                </span>
                <div class="ml-auto flex gap-2">
                    <Button variant="outline" size="sm" @click="bulkMarkAsRead">
                        {{ t('bulk.mark_read') }}
                    </Button>
                    <Button variant="outline" size="sm" @click="exportSelected">
                        <Download class="mr-2 h-4 w-4" />
                        {{ t('bulk.export') }}
                    </Button>
                    <Button variant="destructive" size="sm" @click="bulkDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ t('bulk.delete') }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="selectedRows.clear()"
                    >
                        {{ t('bulk.clear') }}
                    </Button>
                </div>
            </div>

            <!-- Submissions Table -->
            <Card>
                <CardContent class="p-0">
                    <div
                        v-if="!submissionRows.length"
                        class="py-12 text-center"
                    >
                        <FileText class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-medium text-gray-900">
                            {{ t('table.no_results') }}
                        </h3>
                        <p class="mt-2 text-gray-500">
                            {{ t('table.no_results_desc') }}
                        </p>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead
                                    v-if="visibleColumns.has('checkbox')"
                                    class="w-12"
                                >
                                    <Checkbox
                                        :checked="allRowsSelected"
                                        @update:checked="toggleAllRows"
                                    />
                                </TableHead>
                                <TableHead
                                    v-if="visibleColumns.has('status')"
                                    class="w-12"
                                    >{{ t('table.status') }}</TableHead
                                >
                                <TableHead
                                    v-if="visibleColumns.has('id')"
                                    class="w-16"
                                    >{{ t('table.id') }}</TableHead
                                >
                                <!-- Dynamic field columns -->
                                <TableHead
                                    v-for="field in visibleFieldList"
                                    :key="`header-${field.key}`"
                                    draggable="true"
                                    class="cursor-move select-none hover:bg-muted/50"
                                    :class="
                                        field.type === 'object'
                                            ? 'hidden lg:table-cell'
                                            : ''
                                    "
                                    @dragstart="onDragStart(field.key)"
                                    @dragover.prevent
                                    @drop.prevent="onDropOn(field.key)"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <GripVertical
                                            class="h-3 w-3 shrink-0 text-muted-foreground"
                                        />
                                        {{ tField(field.key) }}
                                    </span>
                                </TableHead>
                                <TableHead
                                    v-if="visibleColumns.has('date')"
                                    class="w-32"
                                    >{{ t('table.date') }}</TableHead
                                >
                                <TableHead
                                    v-if="visibleColumns.has('actions')"
                                    class="w-48 text-right"
                                    >{{ t('table.actions') }}</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="submission in submissionRows"
                                :key="submission.id"
                                :class="[
                                    'transition-colors hover:bg-gray-50/50',
                                    !isReadByCurrentUser(submission)
                                        ? 'border-l-4 border-l-blue-500 bg-blue-50/30'
                                        : '',
                                    editingRow === submission.id
                                        ? 'bg-yellow-50/50'
                                        : '',
                                ]"
                            >
                                <!-- Checkbox -->
                                <TableCell
                                    v-if="visibleColumns.has('checkbox')"
                                >
                                    <Checkbox
                                        :checked="
                                            selectedRows.has(submission.id)
                                        "
                                        @update:checked="
                                            () => {
                                                console.log(
                                                    'Row checkbox clicked:',
                                                    submission.id,
                                                );
                                                toggleRowSelection(
                                                    submission.id,
                                                );
                                            }
                                        "
                                    />
                                </TableCell>

                                <!-- Status -->
                                <TableCell v-if="visibleColumns.has('status')">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded p-0.5 hover:bg-muted"
                                            @click="toggleRead(submission)"
                                            :title="
                                                isReadByCurrentUser(submission)
                                                    ? t('action.unread')
                                                    : t('action.read')
                                            "
                                        >
                                            <CheckCircle2
                                                v-if="
                                                    isReadByCurrentUser(
                                                        submission,
                                                    )
                                                "
                                                class="h-4 w-4 text-green-600"
                                            />
                                            <AlertCircle
                                                v-else
                                                class="h-4 w-4 text-blue-600"
                                            />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center"
                                            @click="toggleMark(submission)"
                                            :title="
                                                isMarkedByCurrentUser(
                                                    submission,
                                                )
                                                    ? t('action.unmark')
                                                    : t('action.mark')
                                            "
                                        >
                                            <Star
                                                class="h-4 w-4"
                                                :class="
                                                    isMarkedByCurrentUser(
                                                        submission,
                                                    )
                                                        ? 'fill-yellow-500 text-yellow-500'
                                                        : 'text-gray-300 hover:text-yellow-400'
                                                "
                                            />
                                        </button>
                                    </div>
                                </TableCell>

                                <!-- ID -->
                                <TableCell
                                    v-if="visibleColumns.has('id')"
                                    class="font-mono text-sm text-gray-500"
                                >
                                    <div class="flex items-center gap-1">
                                        #{{ submission.id }}
                                        <span
                                            v-if="
                                                (submission.duplicate_count ??
                                                    1) > 1
                                            "
                                            class="inline-flex items-center justify-center rounded-full bg-orange-100 px-1.5 py-0.5 text-xs leading-none font-semibold text-orange-700"
                                            :title="
                                                t('duplicate.tooltip', {
                                                    count:
                                                        submission.duplicate_count ??
                                                        1,
                                                })
                                            "
                                            >×{{
                                                submission.duplicate_count
                                            }}</span
                                        >
                                    </div>
                                </TableCell>

                                <!-- Dynamic field columns -->
                                <TableCell
                                    v-for="field in visibleFieldList"
                                    :key="`cell-${submission.id}-${field.key}`"
                                    :class="
                                        field.type === 'object'
                                            ? 'hidden lg:table-cell'
                                            : ''
                                    "
                                >
                                    <div
                                        v-if="editingRow === submission.id"
                                        class="flex items-center gap-2"
                                    >
                                        <Input
                                            v-model="editingData[field.key]"
                                            class="h-8"
                                            :placeholder="tField(field.key)"
                                        />
                                    </div>
                                    <div v-else class="text-sm">
                                        <span
                                            v-if="field.type === 'object'"
                                            class="font-mono text-xs"
                                        >
                                            {{
                                                formatFieldValue(
                                                    getFieldValue(
                                                        submission.data,
                                                        field.key,
                                                    ),
                                                    field.type,
                                                )
                                            }}
                                        </span>
                                        <span
                                            v-else-if="field.type === 'date'"
                                            class="text-gray-700"
                                        >
                                            {{
                                                formatFieldValue(
                                                    getFieldValue(
                                                        submission.data,
                                                        field.key,
                                                    ),
                                                    field.type,
                                                )
                                            }}
                                        </span>
                                        <span
                                            v-else
                                            class="max-w-xs truncate text-gray-700"
                                        >
                                            {{
                                                formatFieldValue(
                                                    getFieldValue(
                                                        submission.data,
                                                        field.key,
                                                    ),
                                                    field.type,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </TableCell>

                                <!-- Date -->
                                <TableCell
                                    v-if="visibleColumns.has('date')"
                                    class="text-sm text-gray-500"
                                >
                                    <div class="flex flex-col">
                                        <span>{{
                                            new Date(
                                                submission.created_at,
                                            ).toLocaleDateString()
                                        }}</span>
                                        <span class="text-xs">{{
                                            new Date(
                                                submission.created_at,
                                            ).toLocaleTimeString([], {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })
                                        }}</span>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell v-if="visibleColumns.has('actions')">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <!-- Edit/Save -->
                                        <template
                                            v-if="editingRow === submission.id"
                                        >
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
                                            <Link
                                                :href="`${contactShow(submission.id).url}?from=forms&webform_id=${props.webformId}`"
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>

                                            <!-- Edit -->
                                            <Button
                                                @click="
                                                    startEditing(submission)
                                                "
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
                                                    v-if="
                                                        isReadByCurrentUser(
                                                            submission,
                                                        )
                                                    "
                                                    class="h-4 w-4 text-green-600"
                                                />
                                                <AlertCircle
                                                    v-else
                                                    class="h-4 w-4 text-blue-600"
                                                />
                                            </Button>

                                            <!-- Delete -->
                                            <Button
                                                @click="
                                                    deleteSubmission(submission)
                                                "
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
            <div
                v-if="submissions.links && submissions.links.length > 3"
                class="flex items-center justify-center"
            >
                <nav class="flex items-center space-x-1">
                    <template
                        v-for="(link, index) in submissions.links"
                        :key="index"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'rounded border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50',
                                link.active
                                    ? 'border-blue-500 bg-blue-50 text-blue-600'
                                    : 'text-gray-700',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            :class="[
                                'rounded border border-gray-300 px-3 py-2 text-sm',
                                link.active
                                    ? 'border-blue-500 bg-blue-50 text-blue-600'
                                    : 'bg-gray-100 text-gray-400',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>

            <!-- Retention / Data Deletion Rule -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Trash2 class="h-4 w-4 text-red-500" />
                        {{ t('retention.title') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-sm text-muted-foreground">
                        {{ t('retention.description') }}
                    </p>
                    <div class="flex items-end gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium">{{
                                t('retention.days_label')
                            }}</label>
                            <Input
                                v-model="retentionDaysInput"
                                type="number"
                                min="1"
                                max="3650"
                                :placeholder="t('retention.days_placeholder')"
                                class="w-56"
                            />
                        </div>
                        <Button
                            @click="saveRetentionRule"
                            :disabled="savingRetention"
                            variant="outline"
                            size="sm"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            {{
                                savingRetention
                                    ? t('retention.saving')
                                    : t('retention.save')
                            }}
                        </Button>
                        <span
                            v-if="retentionSaved"
                            class="text-sm text-green-600"
                            >{{ t('retention.saved') }}</span
                        >
                        <span
                            v-if="retentionDaysInput === ''"
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('retention.forever') }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">
                            {{
                                t('retention.will_delete', {
                                    days: retentionDaysInput,
                                })
                            }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog
            :open="exportDialogOpen"
            @update:open="exportDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="space-y-2">
                    <DialogTitle>{{ t('export.title') }}</DialogTitle>
                    <DialogDescription>
                        <span v-if="exportScope === 'selected'">
                            {{
                                t('export.scope_selected', {
                                    count: selectedRows.size,
                                })
                            }}
                        </span>
                        <span v-else>
                            {{ t('export.scope_current') }}
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <div class="mt-4 grid gap-2">
                    <Button variant="outline" @click="runExport('csv')">
                        <FileText class="mr-2 h-4 w-4" />
                        {{ t('export.csv') }}
                    </Button>
                    <Button variant="outline" @click="runExport('xlsx')">
                        <FileSpreadsheet class="mr-2 h-4 w-4" />
                        {{ t('export.excel') }}
                    </Button>
                </div>
                <DialogFooter class="mt-4">
                    <DialogClose as-child>
                        <Button variant="ghost">{{
                            t('action.cancel')
                        }}</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
