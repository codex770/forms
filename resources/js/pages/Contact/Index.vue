<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { index as contactIndex, show as contactShow, toggleRead as contactToggleRead, destroy as contactDestroy } from '@/routes/contact';
import { 
    Search, 
    Filter, 
    Eye, 
    EyeOff, 
    Trash2, 
    Mail, 
    Calendar, 
    User, 
    MessageSquare,
    Clock,
    CheckCircle2,
    AlertCircle
} from 'lucide-vue-next';
import { ref, computed, onMounted, watch } from 'vue';

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
    submissions: {
        data?: ContactSubmission[];
        links?: any[];
        meta?: any;
    };
    filters: {
        search?: string;
        category?: string;
        status?: string;
    };
    categories: Record<string, string>;
}

const props = defineProps<Props>();

const isMounted = ref(false);

const authUser = computed(() => usePage().props.auth.user);

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const selectedStatus = ref(props.filters.status || 'all');

// Check if current user has read the submission
const isReadByCurrentUser = (submission: ContactSubmission): boolean => {
    return submission.reads_with_users.some(read => read.user_id === authUser.value?.id);
};

// Get initials for avatar - handles missing name gracefully
const getInitials = (name: string | undefined): string => {
    if (!name || typeof name !== 'string') return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
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

// Get category display name
const getCategoryName = (category: string): string => {
    return props.categories[category] || category;
};

// Get message preview (only 3 words) - handles missing description gracefully
const getMessagePreview = (description: string | undefined): string => {
    if (!description || typeof description !== 'string') {
        return 'No message preview available';
    }
    const words = description.split(' ').slice(0, 3);
    return words.length >= 3 ? words.join(' ') + '...' : description;
};

// Get display name - tries name, email, or falls back to ID
const getDisplayName = (data: any): string => {
    return data?.name || data?.email || data?.full_name || data?.contact_name || 'Unknown';
};

// Get display email - tries email or returns placeholder
const getDisplayEmail = (data: any): string => {
    return data?.email || data?.email_address || 'No email provided';
};

// Get message text - tries description, message, or any text field
const getMessageText = (data: any): string => {
    return data?.description || data?.message || data?.content || data?.text || 'No message content';
};

// Apply filters
const applyFilters = () => {
    const params: any = {};
    
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedCategory.value !== 'all') params.category = selectedCategory.value;
    if (selectedStatus.value !== 'all') params.status = selectedStatus.value;

    router.get(contactIndex().url, params, {
        preserveState: true,
        replace: true
    });
};

// Reset filters
const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'all';
    selectedStatus.value = 'all';
    applyFilters();
};

// Simple debounce function
const debounce = (func: Function, delay: number) => {
    let timeoutId: NodeJS.Timeout;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(null, args), delay);
    };
};

// Debounced apply filters for search
const debouncedApplyFilters = debounce(applyFilters, 300);

// Watch for filter changes
watch([searchQuery, selectedCategory, selectedStatus], () => {
    debouncedApplyFilters();
});

// Toggle read status
const toggleRead = (submission: ContactSubmission) => {
    router.post(contactToggleRead(submission.id).url, {}, {
        preserveScroll: true
    });
};

// Delete submission
const deleteSubmission = (submission: ContactSubmission) => {
    if (confirm('Are you sure you want to permanently delete this contact submission?')) {
        router.delete(contactDestroy(submission.id).url);
    }
};

// Truncate text
const truncate = (text: string, limit: number = 100): string => {
    return text.length > limit ? text.substring(0, limit) + '...' : text;
};

onMounted(() => {
    isMounted.value = true;
});
</script>

<template>
    <Head title="Contact Messages" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Contact Messages</h1>
                            <p class="mt-2 text-gray-600">
                                Manage and respond to contact form submissions
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Search</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search messages..."
                                        class="pl-10"
                                        @keyup.enter="applyFilters"
                                    />
                                </div>
                            </div>

                            <!-- Category Filter -->
                            <div class="space-y-2" v-if="isMounted">
                                <label class="text-sm font-medium">Category</label>
                                <Select v-model="selectedCategory">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Categories" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Categories</SelectItem>
                                        <SelectItem 
                                            v-for="(label, value) in categories" 
                                            :key="value" 
                                            :value="value"
                                        >
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Status Filter -->
                            <div class="space-y-2" v-if="isMounted">
                                <label class="text-sm font-medium">Status</label>
                                <Select v-model="selectedStatus">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Messages" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Messages</SelectItem>
                                        <SelectItem value="unread">Unread</SelectItem>
                                        <SelectItem value="read">Read</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Filter Actions -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium invisible">Actions</label>
                                <div class="flex gap-2">
                                    <Button @click="applyFilters" size="sm">
                                        Apply
                                    </Button>
                                    <Button @click="resetFilters" variant="outline" size="sm">
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Results Summary -->
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Showing {{ submissions.meta?.from || 0 }} to {{ submissions.meta?.to || 0 }} 
                        of {{ submissions.meta?.total || 0 }} results
                    </p>
                </div>

                <!-- Messages Table -->
                <Card>
                    <CardContent class="p-0">
                        <div v-if="!submissions.data || submissions.data.length === 0" class="text-center py-12">
                            <MessageSquare class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No messages found</h3>
                            <p class="mt-2 text-gray-500">No contact messages match your current filters.</p>
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">Status</TableHead>
                                    <TableHead class="w-16">ID</TableHead>
                                    <TableHead>Sender</TableHead>
                                    <TableHead class="w-32">Category</TableHead>
                                    <TableHead class="hidden md:table-cell">Message Preview</TableHead>
                                    <TableHead class="w-32">Date</TableHead>
                                    <TableHead class="w-20 text-center">Read By</TableHead>
                                    <TableHead class="w-48 text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="submission in submissions.data || []" 
                                    :key="submission.id"
                                    :class="[
                                        'transition-colors hover:bg-gray-50/50',
                                        !isReadByCurrentUser(submission) ? 'bg-blue-50/30 border-l-4 border-l-blue-500' : ''
                                    ]"
                                >
                                    <!-- Status -->
                                    <TableCell>
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
                                    <TableCell class="font-mono text-sm text-gray-500">
                                        #{{ submission.id }}
                                    </TableCell>

                                    <!-- Sender -->
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-8 w-8 hidden sm:flex">
                                                <AvatarFallback class="text-xs">
                                                    {{ getInitials(getDisplayName(submission.data)) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-gray-900 truncate">
                                                    {{ getDisplayName(submission.data) }}
                                                </div>
                                                <div class="text-sm text-gray-500 truncate">
                                                    {{ getDisplayEmail(submission.data) }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Category -->
                                    <TableCell>
                                        <Badge :variant="getCategoryBadgeVariant(submission.category)" class="text-xs">
                                            {{ getCategoryName(submission.category) }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Message Preview -->
                                    <TableCell class="hidden md:table-cell">
                                        <div class="max-w-xs">
                                            <p class="text-sm text-gray-700">
                                                {{ getMessagePreview(getMessageText(submission.data)) }}
                                            </p>
                                        </div>
                                    </TableCell>

                                    <!-- Date -->
                                    <TableCell class="text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span>{{ new Date(submission.created_at).toLocaleDateString() }}</span>
                                            <span class="text-xs">{{ new Date(submission.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                        </div>
                                    </TableCell>

                                    <!-- Read By Count -->
                                    <TableCell class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Eye class="h-3 w-3 text-gray-400" />
                                            <span class="text-sm">{{ submission.reads_with_users.length }}</span>
                                        </div>
                                        <div v-if="submission.reads_with_users.length > 0" class="flex justify-center -space-x-1 mt-1">
                                            <Avatar 
                                                v-for="read in submission.reads_with_users.slice(0, 3)" 
                                                :key="read.id" 
                                                class="h-5 w-5 border border-white"
                                            >
                                                <AvatarFallback class="text-xs bg-green-100 text-green-800">
                                                    {{ getInitials(read.user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div v-if="submission.reads_with_users.length > 3" 
                                                 class="h-5 w-5 rounded-full bg-gray-200 border border-white flex items-center justify-center">
                                                <span class="text-xs text-gray-600">+{{ submission.reads_with_users.length - 3 }}</span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell>
                                        <div class="flex items-center justify-end gap-1">
                                            <!-- View Button -->
                                            <Link :href="contactShow(submission.id).url">
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>

                                            <!-- Toggle Read Button -->
                                            <Button 
                                                @click="toggleRead(submission)"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-auto px-2 py-1"
                                            >
                                                <Badge v-if="isReadByCurrentUser(submission)" 
                                                       variant="secondary" 
                                                       class="text-xs">
                                                    Read
                                                </Badge>
                                                <span v-else 
                                                      class="text-xs text-blue-600 hover:text-blue-800">
                                                    Unread
                                                </span>
                                            </Button>

                                            <!-- Delete Button -->
                                            <Button 
                                                @click="deleteSubmission(submission)"
                                                variant="ghost" 
                                                size="sm"
                                                class="h-8 w-8 p-0 text-red-600 hover:text-red-800"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="submissions.links && submissions.links.length > 3" class="mt-6">
                    <nav class="flex items-center justify-center">
                        <div class="flex items-center space-x-1">
                            <template v-for="(link, index) in submissions.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm border border-gray-300 hover:bg-gray-50',
                                        link.active ? 'bg-blue-50 border-blue-500 text-blue-600' : 'text-gray-700'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    :class="[
                                        'px-3 py-2 text-sm border border-gray-300',
                                        link.active ? 'bg-blue-50 border-blue-500 text-blue-600' : 'text-gray-400 bg-gray-100'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
