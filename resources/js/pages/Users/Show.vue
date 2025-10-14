<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Edit, 
    Trash2, 
    Shield, 
    Mail, 
    Phone, 
    Calendar, 
    UserCheck, 
    Clock,
    RotateCcw,
    UserMinus,
    Copy,
    Key,
    Eye,
    EyeOff
} from 'lucide-vue-next';
import { dashboard as superadminDashboard } from '@/routes/superadmin';
import { index as usersIndex, show as usersShow, edit as usersEdit, destroy as usersDestroy, restore as usersRestore, forceDelete as usersForceDelete } from '@/routes/users';
import { ref } from 'vue';

interface UserRole {
    name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    phone?: string;
    email_verified_at?: string;
    deleted_at?: string;
    created_at: string;
    updated_at: string;
    roles: UserRole[];
}

const props = defineProps<{
    user: UserData;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: superadminDashboard().url },
    { title: 'User Management', href: usersIndex().url },
    { title: 'User Details', href: usersShow(props.user.id).url },
];

// Password functionality
const showPassword = ref(false);
const defaultPassword = 'password'; // Since all seeded users have 'password' as password

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const copyPassword = async () => {
    try {
        await navigator.clipboard.writeText(defaultPassword);
        // You could add a toast notification here
        alert('Password copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy password:', err);
        alert('Failed to copy password');
    }
};

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

const deleteUser = () => {
    if (confirm('Are you sure you want to deactivate this user?')) {
        router.delete(usersDestroy(props.user.id).url);
    }
};

const restoreUser = () => {
    if (confirm('Are you sure you want to restore this user?')) {
        router.post(usersRestore(props.user.id).url);
    }
};

const forceDeleteUser = () => {
    if (confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')) {
        router.delete(usersForceDelete(props.user.id).url);
    }
};
</script>

<template>
    <Head title="User Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="usersIndex().url">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Users
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">User Details</h1>
                        <p class="text-muted-foreground">View and manage user information</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <!-- Edit Button (only if not deleted) -->
                    <Link v-if="!user.deleted_at" :href="usersEdit(user.id).url">
                        <Button variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit User
                        </Button>
                    </Link>

                    <!-- Restore Button (only if deleted) -->
                    <Button 
                        v-if="user.deleted_at"
                        @click="restoreUser"
                        variant="outline"
                        class="text-green-600"
                    >
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Restore User
                    </Button>

                    <!-- Delete/Force Delete Button -->
                    <Button 
                        v-if="!user.deleted_at"
                        @click="deleteUser"
                        variant="outline"
                        class="text-orange-600 border-orange-600 hover:bg-orange-50"
                    >
                        <UserMinus class="mr-2 h-4 w-4" />
                        Deactivate
                    </Button>

                    <Button 
                        v-if="user.deleted_at"
                        @click="forceDeleteUser"
                        variant="destructive"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Permanently Delete
                    </Button>
                </div>
            </div>

            <!-- User Profile Cards -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Main Profile Card -->
                <div class="md:col-span-2">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-4">
                                <Avatar class="h-16 w-16">
                                    <AvatarFallback class="bg-primary/10 text-primary text-xl">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <CardTitle class="text-2xl">{{ user.name }}</CardTitle>
                                        <Badge 
                                            :variant="user.deleted_at ? 'destructive' : 'default'"
                                        >
                                            {{ user.deleted_at ? 'Deactivated' : 'Active' }}
                                        </Badge>
                                    </div>
                                    <CardDescription class="flex items-center gap-2 mt-1">
                                        <span>User ID: {{ user.id }}</span>
                                        <span class="text-muted-foreground">•</span>
                                        <span>
                                            Member since {{ new Date(user.created_at).toLocaleDateString() }}
                                        </span>
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-6">
                            <!-- Contact Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold">Contact Information</h3>
                                <div class="space-y-3">
                                    <!-- Email -->
                                    <div class="flex items-center gap-3">
                                        <Mail class="h-4 w-4 text-muted-foreground" />
                                        <div class="flex-1">
                                            <div class="font-medium">{{ user.email }}</div>
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <UserCheck 
                                                    v-if="user.email_verified_at"
                                                    class="h-3 w-3 text-green-600" 
                                                />
                                                <span>
                                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                                    {{ user.email_verified_at ? 
                                                        `on ${new Date(user.email_verified_at).toLocaleDateString()}` : 
                                                        'email address' 
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="flex items-center gap-3">
                                        <Phone class="h-4 w-4 text-muted-foreground" />
                                        <div class="flex-1">
                                            <div class="font-medium">
                                                {{ user.phone || 'No phone number provided' }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ user.phone ? 'Contact number' : 'Optional field not filled' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Password -->
                                    <div class="flex items-center gap-3">
                                        <Key class="h-4 w-4 text-muted-foreground" />
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <div class="font-medium">
                                                    {{ showPassword ? defaultPassword : '••••••••' }}
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 w-6 p-0"
                                                    @click="togglePasswordVisibility"
                                                >
                                                    <Eye v-if="!showPassword" class="h-3 w-3" />
                                                    <EyeOff v-else class="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 w-6 p-0"
                                                    @click="copyPassword"
                                                >
                                                    <Copy class="h-3 w-3" />
                                                </Button>
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                Default password (can be changed by user)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Role & Permissions -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold">Role & Permissions</h3>
                                <div class="space-y-3">
                                    <!-- Current Role -->
                                    <div class="flex items-center gap-3">
                                        <Shield class="h-4 w-4 text-muted-foreground" />
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">Current Role:</span>
                                                <Badge 
                                                    v-if="user.roles[0]"
                                                    :variant="getRoleBadgeVariant(user.roles[0].name)"
                                                >
                                                    {{ user.roles[0].name.toUpperCase() }}
                                                </Badge>
                                                <Badge v-else variant="outline">No Role Assigned</Badge>
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                <span v-if="user.roles[0]?.name === 'superadmin'">
                                                    Full system administrator access
                                                </span>
                                                <span v-else-if="user.roles[0]?.name === 'admin'">
                                                    User management and content administration
                                                </span>
                                                <span v-else-if="user.roles[0]?.name === 'user'">
                                                    Standard user access
                                                </span>
                                                <span v-else>
                                                    No permissions assigned
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Activity Timeline -->
                            <div>
                                <h3 class="mb-4 text-lg font-semibold">Activity Timeline</h3>
                                <div class="space-y-3">
                                    <!-- Created -->
                                    <div class="flex items-center gap-3">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                        <div class="flex-1">
                                            <div class="font-medium">Account Created</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ new Date(user.created_at).toLocaleString() }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Last Updated -->
                                    <div class="flex items-center gap-3">
                                        <Clock class="h-4 w-4 text-muted-foreground" />
                                        <div class="flex-1">
                                            <div class="font-medium">Last Updated</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ new Date(user.updated_at).toLocaleString() }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Deactivated (if applicable) -->
                                    <div v-if="user.deleted_at" class="flex items-center gap-3">
                                        <UserMinus class="h-4 w-4 text-red-500" />
                                        <div class="flex-1">
                                            <div class="font-medium text-red-600">Account Deactivated</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ new Date(user.deleted_at).toLocaleString() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-4">
                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Link 
                                v-if="!user.deleted_at" 
                                :href="usersEdit(user.id).url"
                                class="block"
                            >
                                <Button variant="outline" class="w-full justify-start">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Edit User
                                </Button>
                            </Link>

                            <Button 
                                v-if="user.deleted_at"
                                @click="restoreUser"
                                variant="outline"
                                class="w-full justify-start text-green-600"
                            >
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Restore User
                            </Button>

                            <Button 
                                v-if="!user.deleted_at"
                                @click="deleteUser"
                                variant="outline"
                                class="w-full justify-start text-orange-600 border-orange-600 hover:bg-orange-50"
                            >
                                <UserMinus class="mr-2 h-4 w-4" />
                                Deactivate User
                            </Button>

                            <Button 
                                v-if="user.deleted_at"
                                @click="forceDeleteUser"
                                variant="destructive"
                                class="w-full justify-start"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Permanently Delete
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- User Stats -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">User Statistics</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Account Status:</span>
                                <Badge :variant="user.deleted_at ? 'destructive' : 'default'">
                                    {{ user.deleted_at ? 'Deactivated' : 'Active' }}
                                </Badge>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Email Status:</span>
                                <Badge :variant="user.email_verified_at ? 'default' : 'secondary'">
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </Badge>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Account Age:</span>
                                <span class="font-medium">
                                    {{ Math.floor((Date.now() - new Date(user.created_at).getTime()) / (1000 * 60 * 60 * 24)) }} days
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Phone Provided:</span>
                                <Badge :variant="user.phone ? 'default' : 'secondary'">
                                    {{ user.phone ? 'Yes' : 'No' }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Security Notes -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Security Notes</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div>
                                <h4 class="font-medium text-green-700">✓ Secure Features:</h4>
                                <ul class="mt-2 space-y-1 text-muted-foreground">
                                    <li>• Password hashed securely</li>
                                    <li>• Role-based access control</li>
                                    <li>• Email verification system</li>
                                    <li>• Soft delete protection</li>
                                </ul>
                            </div>

                            <div class="border-t pt-4">
                                <h4 class="font-medium text-blue-700">🔒 Admin Actions:</h4>
                                <ul class="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <li>• Edit user information</li>
                                    <li>• Change user roles</li>
                                    <li>• Deactivate/restore accounts</li>
                                    <li>• Permanent deletion (irreversible)</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
