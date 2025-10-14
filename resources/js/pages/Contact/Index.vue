<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
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
import { ref, computed, onMounted } from 'vue';

interface ContactSubmission {
    id: number;
    category: string;
    data: {
        name: string;
        email: string;
        description: string;
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

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const selectedStatus = ref(props.filters.status || 'all');

// Check if current user has read the submission
const isReadByCurrentUser = (submission: ContactSubmission): boolean => {
    const currentUserId = (window as any).Laravel?.user?.id;
    return submission.reads_with_users.some(read => read.user_id === currentUserId);
};

// Get initials for avatar
const getInitials = (name: string): string => {
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

                <!-- Messages List -->
                <div class="space-y-4">
                    <div v-if="!submissions.data || submissions.data.length === 0" class="text-center py-12">
                        <MessageSquare class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No messages found</h3>
                        <p class="mt-2 text-gray-500">No contact messages match your current filters.</p>
                    </div>

                    <Card 
                        v-for="submission in submissions.data || []" 
                        :key="submission.id"
                        :class="[
                            'cursor-pointer transition-colors hover:bg-gray-50',
                            !isReadByCurrentUser(submission) ? 'border-l-4 border-l-blue-500 bg-blue-50/30' : ''
                        ]"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-start justify-between">
                                <!-- Message Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-3">
                                        <!-- Read/Unread Indicator -->
                                        <div class="flex items-center gap-2">
                                            <div v-if="isReadByCurrentUser(submission)" 
                                                 class="flex items-center text-green-600">
                                                <CheckCircle2 class="h-4 w-4" />
                                                <span class="text-xs ml-1">Read</span>
                                            </div>
                                            <div v-else class="flex items-center text-blue-600">
                                                <AlertCircle class="h-4 w-4" />
                                                <span class="text-xs ml-1">Unread</span>
                                            </div>
                                        </div>

                                        <!-- Category Badge -->
                                        <Badge :variant="getCategoryBadgeVariant(submission.category)">
                                            {{ categories[submission.category] }}
                                        </Badge>

                                        <!-- Timestamp -->
                                        <div class="flex items-center text-gray-500 text-sm">
                                            <Calendar class="h-4 w-4 mr-1" />
                                            {{ new Date(submission.created_at).toLocaleDateString() }}
                                        </div>
                                    </div>

                                    <!-- Sender Info -->
                                    <div class="flex items-center gap-3 mb-3">
                                        <Avatar class="h-8 w-8">
                                            <AvatarFallback>
                                                {{ getInitials(submission.data.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <div class="font-medium text-gray-900">
                                                {{ submission.data.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ submission.data.email }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Preview -->
                                    <p class="text-gray-700 mb-4">
                                        {{ truncate(submission.data.description, 150) }}
                                    </p>

                                    <!-- Read By Users -->
                                    <div v-if="submission.reads_with_users.length > 0" class="flex items-center gap-2 mb-4">
                                        <Eye class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm text-gray-500">Read by:</span>
                                        <div class="flex items-center gap-1">
                                            <div 
                                                v-for="read in submission.reads_with_users.slice(0, 3)" 
                                                :key="read.id"
                                                class="flex items-center"
                                            >
                                                <Avatar class="h-6 w-6">
                                                    <AvatarFallback class="text-xs">
                                                        {{ getInitials(read.user.name) }}
                                                    </AvatarFallback>
                                                </Avatar>
                                            </div>
                                            <span v-if="submission.reads_with_users.length > 3" 
                                                  class="text-xs text-gray-500 ml-1">
                                                +{{ submission.reads_with_users.length - 3 }} more
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 ml-4">
                                    <!-- View Button -->
                                    <Link :href="contactShow(submission.id).url">
                                        <Button variant="outline" size="sm">
                                            <Eye class="h-4 w-4 mr-1" />
                                            View
                                        </Button>
                                    </Link>

                                    <!-- Toggle Read Button -->
                                    <Button 
                                        @click="toggleRead(submission)"
                                        variant="outline" 
                                        size="sm"
                                        :class="isReadByCurrentUser(submission) ? 'text-gray-600' : 'text-blue-600'"
                                    >
                                        <EyeOff v-if="isReadByCurrentUser(submission)" class="h-4 w-4 mr-1" />
                                        <Eye v-else class="h-4 w-4 mr-1" />
                                        {{ isReadByCurrentUser(submission) ? 'Unread' : 'Mark Read' }}
                                    </Button>

                                    <!-- Delete Button -->
                                    <Button 
                                        @click="deleteSubmission(submission)"
                                        variant="destructive" 
                                        size="sm"
                                    >
                                        <Trash2 class="h-4 w-4 mr-1" />
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

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
