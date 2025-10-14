<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as contactIndex, toggleRead as contactToggleRead, destroy as contactDestroy } from '@/routes/contact';
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
    Globe
} from 'lucide-vue-next';
import { computed } from 'vue';

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
    submission: ContactSubmission;
}

const props = defineProps<Props>();

// Check if current user has read the submission
const isReadByCurrentUser = computed((): boolean => {
    const currentUserId = (window as any).Laravel?.user?.id;
    return props.submission.reads_with_users.some(read => read.user_id === currentUserId);
});

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

// Format additional data for display
const formatAdditionalData = computed(() => {
    const { name, email, description, ...additionalData } = props.submission.data;
    return Object.entries(additionalData).filter(([key, value]) => 
        value !== null && value !== undefined && value !== ''
    );
});
</script>

<template>
    <Head :title="`Contact Message from ${submission.data.name}`" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center gap-4 mb-4">
                        <Link :href="contactIndex().url">
                            <Button variant="outline" size="sm">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Messages
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
                                Message from {{ submission.data.name }}
                            </h1>
                            <p class="mt-2 text-gray-600">
                                Submitted {{ new Date(submission.created_at).toLocaleString() }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
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
                                    <p class="text-gray-900 leading-relaxed whitespace-pre-wrap">{{ submission.data.description }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Additional Data (if any) -->
                        <Card v-if="formatAdditionalData.length > 0">
                            <CardHeader>
                                <CardTitle>Additional Information</CardTitle>
                                <CardDescription>
                                    Extra data submitted with this message
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <div 
                                        v-for="[key, value] in formatAdditionalData" 
                                        :key="key"
                                        class="flex justify-between items-start py-2 border-b border-gray-100 last:border-b-0"
                                    >
                                        <div class="font-medium text-gray-700 capitalize">
                                            {{ key.replace(/[_-]/g, ' ') }}:
                                        </div>
                                        <div class="text-gray-900 text-right max-w-xs">
                                            <span v-if="typeof value === 'object'">
                                                {{ JSON.stringify(value, null, 2) }}
                                            </span>
                                            <span v-else>{{ value }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Raw Data (for debugging) -->
                        <Card class="bg-gray-50">
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
                                            {{ getInitials(submission.data.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ submission.data.name }}
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Email -->
                                <div class="flex items-center gap-3">
                                    <Mail class="h-4 w-4 text-gray-400" />
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-500">Email</div>
                                        <a 
                                            :href="`mailto:${submission.data.email}`" 
                                            class="text-blue-600 hover:text-blue-800"
                                        >
                                            {{ submission.data.email }}
                                        </a>
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
                                    :href="`mailto:${submission.data.email}?subject=Re: Your contact message&body=Hi ${submission.data.name},%0D%0A%0D%0AThank you for your message. `"
                                    class="w-full"
                                >
                                    <Button variant="outline" size="sm" class="w-full justify-start">
                                        <Mail class="mr-2 h-4 w-4" />
                                        Reply via Email
                                    </Button>
                                </a>

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
