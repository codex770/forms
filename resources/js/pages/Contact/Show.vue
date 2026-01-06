<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { index as contactIndex, toggleRead as contactToggleRead, destroy as contactDestroy } from '@/routes/contact';
import { computed as vueComputed } from 'vue';
import { 
    ArrowLeft, 
    Eye, 
    EyeOff, 
    Trash2, 
    Mail, 
    Calendar, 
    User, 
    MessageSquare,
    Clock,
    CheckCircle2,
    AlertCircle,
    MapPin,
    Globe,
    Settings,
    Columns
} from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';
import { computed, ref, onMounted } from 'vue';
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

interface FieldInfo {
    key: string;
    type: string;
    label: string;
}

interface Props {
    submission: ContactSubmission;
    availableFields?: FieldInfo[]; // Form-specific fields
    typeFields?: FieldInfo[];
    stationFields?: FieldInfo[];
    newFields?: string[]; // New field keys for notification
    smartDefaults?: string[]; // Smart defaults for this type
}

const props = defineProps<Props>();

// New fields from backend
const newFields = computed(() => props.newFields || []);

const authUser = computed(() => usePage().props.auth.user);

// Get query parameters to determine where to go back
const urlParams = new URLSearchParams(window.location.search);
const fromPage = urlParams.get('from');
const webformId = urlParams.get('webform_id');

// Determine back URL
const backUrl = computed(() => {
    if (fromPage === 'forms' && webformId) {
        return `/forms/${webformId}`;
    }
    return contactIndex().url; // Fallback to old contact messages page
});

const backButtonText = computed(() => {
    if (fromPage === 'forms') {
        return 'Back to Form';
    }
    return 'Back to Messages';
});

// Check if current user has read the submission
const isReadByCurrentUser = computed((): boolean => {
    return props.submission.reads_with_users.some(read => read.user_id === authUser.value?.id);
});

// Get initials for avatar - handles missing name gracefully
const getInitials = (name: string | undefined): string => {
    if (!name || typeof name !== 'string') return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
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

// Get display email - tries email fields
const getDisplayEmail = (data: any): string | null => {
    return data?.email || data?.email_address || null;
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
        || 'No message content available';
};

// Get category badge variant
const getCategoryBadgeVariant = (category: string) => {
    const variants: Record<string, string> = {
        'bigfm': 'default',
        'rpr1': 'secondary',
        'regenbogen': 'outline',
        'rockfm': 'destructive',
        'bigkarriere': 'default'
    };
    return variants[category] || 'default';
};

// Get category name
const getCategoryName = (category: string): string => {
    const names: Record<string, string> = {
        'bigfm': 'BigFM',
        'rpr1': 'RPR1',
        'regenbogen': 'Regenbogen',
        'rockfm': 'RockFM',
        'bigkarriere': 'BigKarriere'
    };
    return names[category] || category;
};

// Toggle read status
const toggleRead = () => {
    router.post(contactToggleRead(props.submission.id).url, {}, {
        preserveScroll: true
    });
};

// Delete submission
const deleteSubmission = () => {
    if (confirm('Are you sure you want to permanently delete this contact submission?')) {
        router.delete(contactDestroy(props.submission.id).url);
    }
};

// Format additional data for display (excludes common fields we show separately)
const formatAdditionalData = computed(() => {
    const excludedFields = ['name', 'email', 'description', 'message', 'content', 'text', 'full_name', 'contact_name', 'email_address', 'category'];
    return Object.entries(props.submission.data)
        .filter(([key, value]) => 
            !excludedFields.includes(key.toLowerCase()) &&
            value !== null && value !== undefined && value !== ''
        );
});

// Build category path for preferences
// DEFAULT: Type level (station:type) - most efficient
const categoryPath = computed(() => {
    const submission = props.submission;
    // Default to TYPE level for preference storage (efficient)
    if (submission.submission_form && submission.station) {
        return `${submission.station}:${submission.submission_form}`; // TYPE level
    }
    return submission.webform_id || null;
});

// Field preferences composable for detail view
// Pass smart defaults from backend for optimal first-time experience
const {
    visibleFields,
    loadPreferences,
    savePreferences,
    getFieldValue,
    getFieldLabel,
    toggleField,
    setAllFields,
} = useFieldPreferences('detail', categoryPath.value, props.smartDefaults || []);

// Available fields from backend - PRIMARY: Form-specific fields only
const allAvailableFields = computed(() => {
    // PRIORITY: Use form-specific fields (availableFields) as primary source
    if (props.availableFields && props.availableFields.length > 0) {
        return props.availableFields;
    }
    
    // FALLBACK: If no form-specific fields, use type-level
    if (props.typeFields && props.typeFields.length > 0) {
        return props.typeFields;
    }
    
    // FALLBACK: Use station-level fields
    return props.stationFields || [];
});

// Check if a field is new
const isNewField = (fieldKey: string): boolean => {
    return props.newFields?.includes(fieldKey) || false;
};

// Clear new fields notification
const clearNewFields = async () => {
    if (!props.submission.webform_id) return;
    
    try {
        await fetch(`/forms/${props.submission.webform_id}/clear-new-fields`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
    } catch (error) {
        console.error('Error clearing new fields:', error);
    }
};

const groupedFields = computed(() => groupFieldsByCategory(allAvailableFields.value));

// Get visible data fields based on preferences
const visibleDataFields = computed(() => {
    if (visibleFields.value.size === 0) {
        // Default: show essential fields
        return Object.entries(props.submission.data).filter(([key]) => 
            ['fname', 'lname', 'email', 'message_long'].includes(key)
        );
    }
    
    return Object.entries(props.submission.data).filter(([key]) => 
        visibleFields.value.has(key)
    );
});

// Get all data fields (for display purposes)
const allDataFields = computed(() => {
    return Object.entries(props.submission.data);
});

// Layout settings
const showLayoutSettings = ref(false);
const showAdvancedView = ref(false);

// Save layout preferences
const saveLayoutPreferences = async () => {
    await savePreferences(Array.from(visibleFields.value), {
        asDefault: false,
    });
    showLayoutSettings.value = false;
};

onMounted(() => {
    loadPreferences();
});
</script>

<template>
    <Head :title="`Contact Message #${submission.id}`" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center gap-4 mb-4">
                        <Link :href="backUrl">
                            <Button variant="outline" size="sm">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                {{ backButtonText }}
                            </Button>
                        </Link>
                    </div>

                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <!-- Read/Unread Indicator -->
                                <div v-if="isReadByCurrentUser" class="flex items-center text-green-600">
                                    <CheckCircle2 class="h-5 w-5" />
                                    <span class="text-sm ml-1 font-medium">Read</span>
                                </div>
                                <div v-else class="flex items-center text-blue-600">
                                    <AlertCircle class="h-5 w-5" />
                                    <span class="text-sm ml-1 font-medium">Unread</span>
                                </div>

                                <!-- Category Badge -->
                                <Badge :variant="getCategoryBadgeVariant(submission.category)" class="text-sm">
                                    {{ getCategoryName(submission.category) }}
                                </Badge>
                            </div>

                            <h1 class="text-3xl font-bold text-gray-900">
                                Message from {{ getDisplayName(submission.data) }}
                            </h1>
                            <p class="mt-2 text-gray-600">
                                Submitted {{ new Date(submission.created_at).toLocaleString() }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <!-- Layout Settings Button -->
                            <div class="relative">
                                <Button 
                                    @click="showLayoutSettings = !showLayoutSettings"
                                    variant="outline"
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    Layout Settings
                                </Button>
                                
                                <!-- Layout Settings Dropdown -->
                                <div v-if="showLayoutSettings" class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 border rounded-lg shadow-xl z-50 max-h-[500px] overflow-y-auto">
                                    <div class="p-3 border-b">
                                        <div class="text-sm font-semibold mb-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <Columns class="h-4 w-4" />
                                                Show/Hide Fields
                                                <Badge v-if="newFields && newFields.length > 0" variant="secondary" class="ml-2 text-xs">
                                                    {{ newFields.length }} new
                                                </Badge>
                                            </div>
                                            <div class="flex gap-1">
                                                <Button variant="ghost" size="sm" @click="setAllFields(allAvailableFields.map(f => f.key), true)" class="h-6 text-xs">
                                                    All
                                                </Button>
                                                <Button variant="ghost" size="sm" @click="setAllFields(allAvailableFields.map(f => f.key), false)" class="h-6 text-xs">
                                                    None
                                                </Button>
                                            </div>
                                        </div>
                                        <div v-if="newFields && newFields.length > 0" class="mb-3 p-2 bg-blue-50 dark:bg-blue-950 rounded text-xs">
                                            <div class="flex items-center justify-between">
                                                <span class="text-blue-700 dark:text-blue-300">
                                                    New fields detected! Check them below.
                                                </span>
                                                <Button variant="ghost" size="sm" @click="clearNewFields" class="h-5 text-xs">
                                                    Dismiss
                                                </Button>
                                            </div>
                                        </div>
                                        <div class="space-y-3 max-h-60 overflow-y-auto">
                                            <div v-for="(fields, category) in groupedFields" :key="category" class="space-y-1">
                                                <div class="text-xs font-medium text-muted-foreground uppercase">{{ category }}</div>
                                                <div class="space-y-1 pl-2">
                                                    <label 
                                                        v-for="field in fields" 
                                                        :key="field.key"
                                                        class="flex items-center gap-2 p-1 hover:bg-muted rounded cursor-pointer"
                                                    >
                                                        <Checkbox 
                                                            :checked="visibleFields.has(field.key)"
                                                            @update:checked="() => toggleField(field.key)"
                                                        />
                                                        <span class="text-sm">{{ field.label }}</span>
                                                        <Badge 
                                                            v-if="isNewField(field.key)" 
                                                            variant="secondary" 
                                                            class="ml-auto text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                                                        >
                                                            New
                                                        </Badge>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-xs text-muted-foreground">
                                            Saving at: <strong>Type level</strong> (applies to all {{ submission.submission_form || 'forms' }} forms)
                                        </div>
                                    </div>
                                    <div class="p-3 bg-muted/30">
                                        <Button 
                                            variant="default" 
                                            size="sm" 
                                            class="w-full"
                                            @click="saveLayoutPreferences"
                                        >
                                            Save Layout
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle Read Button -->
                            <Button 
                                @click="toggleRead"
                                variant="outline"
                                :class="isReadByCurrentUser ? 'text-gray-600' : 'text-blue-600'"
                            >
                                <EyeOff v-if="isReadByCurrentUser" class="mr-2 h-4 w-4" />
                                <Eye v-else class="mr-2 h-4 w-4" />
                                {{ isReadByCurrentUser ? 'Mark Unread' : 'Mark Read' }}
                            </Button>

                            <!-- Delete Button -->
                            <Button 
                                @click="deleteSubmission"
                                variant="destructive"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Message Content -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <MessageSquare class="h-5 w-5" />
                                    Message
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="prose prose-gray max-w-none">
                                    <p class="text-gray-900 leading-relaxed whitespace-pre-wrap">{{ getMessageText(submission.data) }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Form Data (Customizable) -->
                        <Card v-if="visibleDataFields.length > 0 || showAdvancedView">
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Form Data</CardTitle>
                                        <CardDescription>
                                            {{ visibleFields.size > 0 ? 'Selected fields' : 'Essential fields' }}
                                        </CardDescription>
                                    </div>
                                    <Button 
                                        variant="ghost" 
                                        size="sm"
                                        @click="showAdvancedView = !showAdvancedView"
                                    >
                                        {{ showAdvancedView ? 'Hide' : 'Show' }} All Fields
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <div 
                                        v-for="[key, value] in (showAdvancedView ? allDataFields : visibleDataFields)" 
                                        :key="key"
                                        class="flex justify-between items-start py-2 border-b border-gray-100 last:border-b-0"
                                    >
                                        <div class="font-medium text-gray-700">
                                            {{ getFieldLabel(key) }}:
                                        </div>
                                        <div class="text-gray-900 text-right max-w-xs break-words">
                                            <span v-if="typeof value === 'object' && value !== null">
                                                <pre class="text-xs bg-gray-50 p-2 rounded">{{ JSON.stringify(value, null, 2) }}</pre>
                                            </span>
                                            <span v-else-if="value === null || value === undefined" class="text-gray-400 italic">
                                                (empty)
                                            </span>
                                            <span v-else class="break-all">{{ formatFieldValue(value) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Raw Data (for debugging) - Hidden by default -->
                        <Card v-if="showAdvancedView" class="bg-gray-50">
                            <CardHeader>
                                <CardTitle class="text-sm">Raw Data (JSON)</CardTitle>
                                <CardDescription>
                                    Complete data payload as received
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <pre class="text-xs bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">{{ JSON.stringify(submission.data, null, 2) }}</pre>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Contact Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Contact Information</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Avatar and Name -->
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-12 w-12">
                                        <AvatarFallback class="text-lg">
                                            {{ getInitials(getDisplayName(submission.data)) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ getDisplayName(submission.data) }}
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Email -->
                                <div v-if="getDisplayEmail(submission.data)" class="flex items-center gap-3">
                                    <Mail class="h-4 w-4 text-gray-400" />
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-500">Email</div>
                                        <a 
                                            :href="`mailto:${getDisplayEmail(submission.data)}`" 
                                            class="text-blue-600 hover:text-blue-800"
                                        >
                                            {{ getDisplayEmail(submission.data) }}
                                        </a>
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-3 text-gray-400">
                                    <Mail class="h-4 w-4" />
                                    <div class="flex-1">
                                        <div class="text-sm">No email provided</div>
                                    </div>
                                </div>

                                <!-- IP Address -->
                                <div class="flex items-center gap-3">
                                    <Globe class="h-4 w-4 text-gray-400" />
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-500">IP Address</div>
                                        <div class="font-mono text-sm text-gray-900">
                                            {{ submission.ip_address }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Submission Time -->
                                <div class="flex items-center gap-3">
                                    <Calendar class="h-4 w-4 text-gray-400" />
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-500">Submitted</div>
                                        <div class="text-sm text-gray-900">
                                            {{ new Date(submission.created_at).toLocaleString() }}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Read Status -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Eye class="h-5 w-5" />
                                    Read Status
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="submission.reads_with_users.length === 0" class="text-center py-4">
                                    <AlertCircle class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                                    <p class="text-sm text-gray-500">Not read by anyone yet</p>
                                </div>

                                <div v-else class="space-y-3">
                                    <div class="text-sm text-gray-500 mb-3">
                                        Read by {{ submission.reads_with_users.length }} 
                                        {{ submission.reads_with_users.length === 1 ? 'user' : 'users' }}:
                                    </div>

                                    <div 
                                        v-for="read in submission.reads_with_users" 
                                        :key="read.id"
                                        class="flex items-center gap-3 py-2"
                                    >
                                        <Avatar class="h-8 w-8">
                                            <AvatarFallback>
                                                {{ getInitials(read.user.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">
                                                {{ read.user.name }}
                                            </div>
                                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                                <Clock class="h-3 w-3" />
                                                {{ new Date(read.read_at).toLocaleString() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Quick Actions -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Quick Actions</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <!-- Reply via Email -->
                                <a 
                                    v-if="getDisplayEmail(submission.data)"
                                    :href="`mailto:${getDisplayEmail(submission.data)}?subject=Re: Your contact message&body=Hi ${getDisplayName(submission.data)},%0D%0A%0D%0AThank you for your message. `"
                                    class="w-full"
                                >
                                    <Button variant="outline" size="sm" class="w-full justify-start">
                                        <Mail class="mr-2 h-4 w-4" />
                                        Reply via Email
                                    </Button>
                                </a>
                                <Button 
                                    v-else
                                    variant="outline" 
                                    size="sm" 
                                    class="w-full justify-start"
                                    disabled
                                >
                                    <Mail class="mr-2 h-4 w-4" />
                                    No Email Available
                                </Button>

                                <!-- Toggle Read Status -->
                                <Button 
                                    @click="toggleRead"
                                    variant="outline" 
                                    size="sm"
                                    class="w-full justify-start"
                                    :class="isReadByCurrentUser ? 'text-gray-600' : 'text-blue-600'"
                                >
                                    <EyeOff v-if="isReadByCurrentUser" class="mr-2 h-4 w-4" />
                                    <Eye v-else class="mr-2 h-4 w-4" />
                                    {{ isReadByCurrentUser ? 'Mark Unread' : 'Mark Read' }}
                                </Button>

                                <!-- Delete -->
                                <Button 
                                    @click="deleteSubmission"
                                    variant="destructive" 
                                    size="sm"
                                    class="w-full justify-start"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete Message
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
