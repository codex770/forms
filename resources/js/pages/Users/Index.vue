<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { index as usersIndex, create as usersCreate, show as usersShow, edit as usersEdit, destroy as usersDestroy, restore as usersRestore, forceDelete as usersForceDelete } from '@/routes/users';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, Eye, Edit, Trash2, RotateCcw, UserCheck, UserMinus } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';
import { dashboard as superadminDashboard } from '@/routes/superadmin';

interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    email_verified_at?: string;
    deleted_at?: string;
    created_at: string;
    roles: Array<{ name: string }>;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Role {
    id: number;
    name: string;
}

interface Filters {
    search: string;
    status: string;
    role: string;
}

const props = defineProps<{
    users: PaginatedUsers;
    roles: Role[];
    filters: Filters;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: superadminDashboard().url },
    { title: 'User Management', href: usersIndex().url },
];

// Filter state
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'active');
const roleFilter = ref(props.filters.role || 'all');
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

// Watch for filter changes and update URL
watch([search, status, roleFilter], () => {
    router.get(usersIndex().url, {
        search: search.value || undefined,
        status: status.value !== 'active' ? status.value : undefined,
        role: roleFilter.value === 'all' ? undefined : roleFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
};

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'superadmin': return 'destructive';
        case 'admin': return 'default';
        case 'user': return 'secondary';
        default: return 'outline';
    }
};

const deleteUser = (userId: number) => {
    if (confirm('Deactivate this user?\n\nThe user will be temporarily disabled but can be restored later.')) {
        router.delete(usersDestroy(userId).url);
    }
};

const restoreUser = (userId: number) => {
    if (confirm('Are you sure you want to restore this user?')) {
        router.post(usersRestore(userId).url);
    }
};

const forceDeleteUser = (userId: number) => {
    if (confirm('⚠️ PERMANENTLY DELETE USER?\n\nThis will completely remove the user from the system.\nThis action CANNOT be undone!\n\nAre you absolutely sure?')) {
        router.delete(usersForceDelete(userId).url);
    }
};
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
                    <p class="text-muted-foreground">Manage system users, roles, and permissions</p>
                </div>
                <Link :href="usersCreate().url">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Add User
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filters</CardTitle>
                    <CardDescription>Search and filter users</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Search -->
                        <div class="space-y-2">
                            <Label>Search</Label>
                            <div class="relative">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search by name, email, or phone..."
                                    class="pl-8"
                                />
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label>Status</Label>
                            <div v-if="!isMounted" class="h-10 border rounded-md px-3 py-2 text-sm text-muted-foreground">
                                Loading...
                            </div>
                            <Select v-else v-model="status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent side="bottom" align="start">
                                    <SelectItem value="active">Active Users</SelectItem>
                                    <SelectItem value="deleted">Deactivated Users</SelectItem>
                                    <SelectItem value="all">All Users</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Role -->
                        <div class="space-y-2">
                            <Label>Role</Label>
                            <div v-if="!isMounted" class="h-10 border rounded-md px-3 py-2 text-sm text-muted-foreground">
                                Loading...
                            </div>
                            <Select v-else v-model="roleFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All roles" />
                                </SelectTrigger>
                                <SelectContent side="bottom" align="start">
                                    <SelectItem value="all">All Roles</SelectItem>
                                    <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                        {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>
                                Users ({{ users.total }})
                            </CardTitle>
                            <CardDescription>
                                Showing {{ users.from }}-{{ users.to }} of {{ users.total }} users
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">User</th>
                                    <th class="px-4 py-3 text-left font-medium">Contact</th>
                                    <th class="px-4 py-3 text-left font-medium">Role</th>
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
                                    <th class="px-4 py-3 text-left font-medium">Created</th>
                                    <th class="px-4 py-3 text-center font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/50">
                                    <!-- User Info -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10">
                                                <AvatarFallback class="bg-primary/10 text-primary">
                                                    {{ getInitials(user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium">{{ user.name }}</div>
                                                <div class="text-sm text-muted-foreground">ID: {{ user.id }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Contact -->
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="font-medium">{{ user.email }}</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ user.phone || 'No phone' }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role -->
                                    <td class="px-4 py-3">
                                        <Badge 
                                            v-if="user.roles[0]"
                                            :variant="getRoleBadgeVariant(user.roles[0].name)"
                                        >
                                            {{ user.roles[0].name.toUpperCase() }}
                                        </Badge>
                                        <Badge v-else variant="outline">No Role</Badge>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <Badge :variant="user.deleted_at ? 'destructive' : 'default'">
                                                {{ user.deleted_at ? 'Deactivated' : 'Active' }}
                                            </Badge>
                                            <div v-if="user.email_verified_at" class="flex items-center gap-1 text-xs text-green-600">
                                                <UserCheck class="h-3 w-3" />
                                                Verified
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Created -->
                                    <td class="px-4 py-3">
                                        <div class="text-sm">
                                            {{ new Date(user.created_at).toLocaleDateString() }}
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3">
                                        <TooltipProvider>
                                            <div class="flex items-center justify-center gap-1">
                                                <!-- View Button -->
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Link :href="usersShow(user.id).url">
                                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                                <Eye class="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>View Details</p>
                                                    </TooltipContent>
                                                </Tooltip>

                                                <!-- Edit Button (only if not deleted) -->
                                                <Tooltip v-if="!user.deleted_at">
                                                    <TooltipTrigger as-child>
                                                        <Link :href="usersEdit(user.id).url">
                                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                                <Edit class="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Edit User</p>
                                                    </TooltipContent>
                                                </Tooltip>

                                                <!-- Restore Button (only if deleted) -->
                                                <Tooltip v-if="user.deleted_at">
                                                    <TooltipTrigger as-child>
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            class="h-8 w-8 p-0 text-green-600 hover:text-green-700" 
                                                            @click="restoreUser(user.id)"
                                                        >
                                                            <RotateCcw class="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Restore User</p>
                                                    </TooltipContent>
                                                </Tooltip>

                                                <!-- Deactivate Button (only if not deleted) -->
                                                <Tooltip v-if="!user.deleted_at">
                                                    <TooltipTrigger as-child>
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            class="h-8 w-8 p-0 text-orange-600 hover:text-orange-700" 
                                                            @click="deleteUser(user.id)"
                                                        >
                                                            <UserMinus class="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Deactivate User</p>
                                                    </TooltipContent>
                                                </Tooltip>

                                                <!-- Permanently Delete Button (for active users too) -->
                                                <Tooltip v-if="!user.deleted_at">
                                                    <TooltipTrigger as-child>
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            class="h-8 w-8 p-0 text-red-600 hover:text-red-700" 
                                                            @click="forceDeleteUser(user.id)"
                                                        >
                                                            <Trash2 class="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Permanently Delete</p>
                                                    </TooltipContent>
                                                </Tooltip>

                                                <!-- Force Delete Button (only if deleted) -->
                                                <Tooltip v-if="user.deleted_at">
                                                    <TooltipTrigger as-child>
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            class="h-8 w-8 p-0 text-red-600 hover:text-red-700" 
                                                            @click="forceDeleteUser(user.id)"
                                                        >
                                                            <Trash2 class="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Permanently Delete</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </div>
                                        </TooltipProvider>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between border-t px-4 py-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                        </div>
                        
                        <div class="flex gap-2">
                            <!-- Previous Page -->
                            <Link 
                                v-if="users.current_page > 1"
                                :href="usersIndex({ 
                                    query: {
                                        ...filters, 
                                        page: users.current_page - 1 
                                    }
                                }).url"
                                preserve-state
                            >
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>

                            <!-- Page Numbers -->
                            <template v-for="page in Math.min(users.last_page, 5)" :key="page">
                                <Link 
                                    :href="usersIndex({ 
                                        query: {
                                            ...filters, 
                                            page 
                                        }
                                    }).url"
                                    preserve-state
                                >
                                    <Button 
                                        :variant="users.current_page === page ? 'default' : 'outline'" 
                                        size="sm"
                                    >
                                        {{ page }}
                                    </Button>
                                </Link>
                            </template>

                            <!-- Next Page -->
                            <Link 
                                v-if="users.current_page < users.last_page"
                                :href="usersIndex({ 
                                    query: {
                                        ...filters, 
                                        page: users.current_page + 1 
                                    }
                                }).url"
                                preserve-state
                            >
                                <Button variant="outline" size="sm">Next</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
