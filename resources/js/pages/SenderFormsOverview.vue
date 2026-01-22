<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard as userDashboard } from '@/routes/user';
import userRoutes from '@/routes/user';
import { detail as formDetail } from '@/routes/forms';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import {
    ArrowLeft,
    ArrowRight,
    ChevronDown,
    ChevronUp,
    FileText,
    Radio,
    Search,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Form {
    webform_id: string;
    name: string;
    submission_form?: string;
    entry_count: number;
    created_at: string;
    updated_at: string;
}

interface PaginatedForms {
    data: Form[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    station: string;
    stationName: string;
    forms: PaginatedForms;
    totalEntryCount?: number;
    filters: {
        search?: string;
    };
    sortColumn?: string;
    sortDirection?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Form Center',
        href: userDashboard().url,
    },
    {
        title: props.stationName,
        href: userRoutes.sender.forms.overview({ station: props.station }).url,
    },
];

const searchQuery = ref(props.filters.search || '');
const sortColumn = ref(props.sortColumn || 'submission_form');
const sortDirection = ref(props.sortDirection || 'asc');
const perPage = ref(props.forms.per_page || 25);

const getStationHeaderClass = (station: string): string => {
    const classes: Record<string, string> = {
        'bigfm': 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
        'rpr1': 'bg-gradient-to-r from-purple-500 to-purple-600 text-white',
        'regenbogen': 'bg-gradient-to-r from-pink-500 to-rose-600 text-white',
        'rockfm': 'bg-gradient-to-r from-orange-500 to-red-600 text-white',
        'bigkarriere': 'bg-gradient-to-r from-green-500 to-emerald-600 text-white'
    };
    return classes[station] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

const getStationBadgeClass = (station: string): string => {
    const classes: Record<string, string> = {
        'bigfm': 'bg-blue-700 text-white border-blue-800',
        'rpr1': 'bg-purple-700 text-white border-purple-800',
        'regenbogen': 'bg-rose-700 text-white border-rose-800',
        'rockfm': 'bg-red-700 text-white border-red-800',
        'bigkarriere': 'bg-emerald-700 text-white border-emerald-800'
    };
    return classes[station] || 'bg-gray-700 text-white border-gray-800';
};

const getFormUrl = (webformId: string): string => {
    return formDetail({ webformId }).url;
};

const formatDate = (dateString: string): string => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (dateString: string): string => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Apply filters and sorting
const applyFilters = () => {
    const params: any = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (sortColumn.value) params.sort_column = sortColumn.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;
    if (perPage.value) params.per_page = perPage.value;

    router.get(userRoutes.sender.forms.overview({ station: props.station }).url, params, {
        preserveState: true,
        replace: true,
    });
};

// Toggle sort
const toggleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

// Simple debounce function
const debounce = (func: Function, delay: number) => {
    let timeoutId: ReturnType<typeof setTimeout>;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(null, args), delay);
    };
};

// Debounced apply filters for search
const debouncedApplyFilters = debounce(applyFilters, 300);

// Watch for search changes with debounce
watch(searchQuery, () => {
    debouncedApplyFilters();
});

// Watch for per page changes
watch(perPage, () => {
    applyFilters();
});

// Pagination helpers
const hasPreviousPage = computed(() => props.forms.current_page > 1);
const hasNextPage = computed(() => props.forms.current_page < props.forms.last_page);

const goToPage = (page: number) => {
    const params: any = {
        page,
    };
    if (searchQuery.value) params.search = searchQuery.value;
    if (sortColumn.value) params.sort_column = sortColumn.value;
    if (sortDirection.value) params.sort_direction = sortDirection.value;
    if (perPage.value) params.per_page = perPage.value;

    router.get(userRoutes.sender.forms.overview({ station: props.station }).url, params, {
        preserveState: true,
        replace: true,
    });
};

const getSortIcon = (column: string) => {
    if (sortColumn.value !== column) {
        return null;
    }
    return sortDirection.value === 'asc' ? ChevronUp : ChevronDown;
};

const getSortClass = (column: string) => {
    if (sortColumn.value !== column) {
        return 'cursor-pointer hover:text-primary';
    }
    return 'cursor-pointer text-primary';
};
</script>

<template>
    <Head :title="`${stationName} - Forms Overview`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Back Button -->
            <div>
                <Link :href="userDashboard().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Dashboard
                    </Button>
                </Link>
            </div>

            <!-- Station Header -->
            <Card class="overflow-hidden border-0">
                <CardHeader :class="getStationHeaderClass(station) + ' pb-3'">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Radio class="h-5 w-5 flex-shrink-0" />
                            <div>
                                <CardTitle class="text-lg font-bold">{{ stationName }}</CardTitle>
                                <div class="text-xs mt-0.5 opacity-90">
                                    {{ forms.total }} forms • {{ props.totalEntryCount || 0 }} entries
                                </div>
                            </div>
                        </div>
                        <Badge :class="getStationBadgeClass(station) + ' text-xs px-2 py-0.5 font-semibold'">
                            {{ station.toUpperCase() }}
                        </Badge>
                    </div>
                </CardHeader>
            </Card>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Search & Filter</CardTitle>
                    <CardDescription>Search forms by name or ID</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Search -->
                        <div class="space-y-2">
                            <Label>Search</Label>
                            <div class="relative">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by form name or ID..."
                                    class="pl-8"
                                />
                            </div>
                        </div>

                        <!-- Per Page -->
                        <div class="space-y-2">
                            <Label>Items per page</Label>
                            <Select v-model="perPage">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="10">10</SelectItem>
                                    <SelectItem :value="25">25</SelectItem>
                                    <SelectItem :value="50">50</SelectItem>
                                    <SelectItem :value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Forms Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>
                                Forms ({{ forms.total }})
                            </CardTitle>
                            <CardDescription>
                                Showing {{ forms.from }}-{{ forms.to }} of {{ forms.total }} forms
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead 
                                        :class="getSortClass('submission_form')"
                                        @click="toggleSort('submission_form')"
                                    >
                                        <div class="flex items-center gap-2">
                                            Form Name
                                            <component 
                                                :is="getSortIcon('submission_form')" 
                                                v-if="getSortIcon('submission_form')"
                                                class="h-4 w-4"
                                            />
                                        </div>
                                    </TableHead>
                                    <TableHead 
                                        :class="getSortClass('webform_id')"
                                        @click="toggleSort('webform_id')"
                                    >
                                        <div class="flex items-center gap-2">
                                            Form ID
                                            <component 
                                                :is="getSortIcon('webform_id')" 
                                                v-if="getSortIcon('webform_id')"
                                                class="h-4 w-4"
                                            />
                                        </div>
                                    </TableHead>
                                    <TableHead 
                                        :class="getSortClass('entry_count')"
                                        @click="toggleSort('entry_count')"
                                    >
                                        <div class="flex items-center gap-2">
                                            Entries
                                            <component 
                                                :is="getSortIcon('entry_count')" 
                                                v-if="getSortIcon('entry_count')"
                                                class="h-4 w-4"
                                            />
                                        </div>
                                    </TableHead>
                                    <TableHead 
                                        :class="getSortClass('created_at')"
                                        @click="toggleSort('created_at')"
                                    >
                                        <div class="flex items-center gap-2">
                                            Created
                                            <component 
                                                :is="getSortIcon('created_at')" 
                                                v-if="getSortIcon('created_at')"
                                                class="h-4 w-4"
                                            />
                                        </div>
                                    </TableHead>
                                    <TableHead 
                                        :class="getSortClass('updated_at')"
                                        @click="toggleSort('updated_at')"
                                    >
                                        <div class="flex items-center gap-2">
                                            Last Modified
                                            <component 
                                                :is="getSortIcon('updated_at')" 
                                                v-if="getSortIcon('updated_at')"
                                                class="h-4 w-4"
                                            />
                                        </div>
                                    </TableHead>
                                    <TableHead class="w-12"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-if="forms.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                        <FileText class="mx-auto h-12 w-12 mb-2 opacity-50" />
                                        <p>No forms found</p>
                                        <p class="text-sm mt-1">
                                            {{ searchQuery ? 'Try adjusting your search criteria' : 'Forms will appear when webhooks are received' }}
                                        </p>
                                    </TableCell>
                                </TableRow>
                                <TableRow 
                                    v-for="form in forms.data" 
                                    :key="form.webform_id"
                                    class="hover:bg-muted/50 cursor-pointer group"
                                    @click="$inertia.visit(getFormUrl(form.webform_id))"
                                >
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <FileText class="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
                                            <div>
                                                <div class="font-medium group-hover:text-primary transition-colors">
                                                    {{ form.name }}
                                                </div>
                                                <div v-if="form.submission_form && form.submission_form !== form.name" class="text-xs text-muted-foreground">
                                                    {{ form.submission_form }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <code class="text-xs bg-muted px-2 py-1 rounded">
                                            {{ form.webform_id }}
                                        </code>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {{ form.entry_count }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ formatDate(form.created_at) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ formatDateTime(form.created_at) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ formatDate(form.updated_at) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ formatDateTime(form.updated_at) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:text-primary group-hover:translate-x-0.5 transition-all" />
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="forms.last_page > 1" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Page {{ forms.current_page }} of {{ forms.last_page }}
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!hasPreviousPage"
                        @click="goToPage(forms.current_page - 1)"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Previous
                    </Button>
                    <div class="flex items-center gap-1">
                        <Button
                            v-for="page in Array.from({ length: Math.min(5, forms.last_page) }, (_, i) => {
                                const start = Math.max(1, forms.current_page - 2);
                                return start + i;
                            })"
                            :key="page"
                            variant="outline"
                            size="sm"
                            :class="page === forms.current_page ? 'bg-primary text-primary-foreground' : ''"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!hasNextPage"
                        @click="goToPage(forms.current_page + 1)"
                    >
                        Next
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
