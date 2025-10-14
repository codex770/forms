<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, Shield, Calendar } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { dashboard as superadminDashboard } from '@/routes/superadmin';
import { index as usersIndex, update as usersUpdate, edit as usersEdit } from '@/routes/users';

interface Role {
    id: number;
    name: string;
}

interface UserRole {
    name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    phone?: string;
    email_verified_at?: string;
    created_at: string;
    roles: UserRole[];
}

const props = defineProps<{
    user: UserData;
    roles: Role[];
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: superadminDashboard().url },
    { title: 'User Management', href: usersIndex().url },
    { title: 'Edit User', href: usersEdit(props.user.id).url },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
    password: '',
    password_confirmation: '',
    role: props.user.roles[0]?.name || '',
});

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const submit = () => {
    form.put(usersUpdate(props.user.id).url, {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'superadmin': return 'destructive';
        case 'admin': return 'default';
        case 'user': return 'secondary';
        default: return 'outline';
    }
};
</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="usersIndex().url">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Users
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit User</h1>
                    <p class="text-muted-foreground">Update user information and settings</p>
                </div>
            </div>

            <!-- Form -->
            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                User Information
                            </CardTitle>
                            <CardDescription>
                                Update the user's basic information and credentials
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Name -->
                                <div class="space-y-2">
                                    <Label for="name">Full Name *</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Enter full name"
                                        required
                                        :class="{ 'border-red-500': form.errors.name }"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div class="space-y-2">
                                    <Label for="email">Email Address *</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="user@example.com"
                                        required
                                        :class="{ 'border-red-500': form.errors.email }"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <!-- Phone -->
                                <div class="space-y-2">
                                    <Label for="phone">Phone Number</Label>
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="+1 (555) 000-0000"
                                        :class="{ 'border-red-500': form.errors.phone }"
                                    />
                                    <InputError :message="form.errors.phone" />
                                    <p class="text-xs text-muted-foreground">Optional field</p>
                                </div>

                                <!-- Role -->
                                <div class="space-y-2">
                                    <Label for="role">User Role *</Label>
                                    <div v-if="!isMounted" class="h-10 border rounded-md px-3 py-2 text-sm text-muted-foreground">
                                        Loading roles...
                                    </div>
                                    <Select v-else v-model="form.role">
                                        <SelectTrigger :class="{ 'border-red-500': form.errors.role }">
                                            <SelectValue placeholder="Select user role" />
                                        </SelectTrigger>
                                        <SelectContent side="bottom" align="start">
                                            <SelectItem 
                                                v-for="role in roles" 
                                                :key="role.id" 
                                                :value="role.name"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <div 
                                                        class="h-2 w-2 rounded-full"
                                                        :class="{
                                                            'bg-red-500': role.name === 'superadmin',
                                                            'bg-blue-500': role.name === 'admin',
                                                            'bg-green-500': role.name === 'user'
                                                        }"
                                                    ></div>
                                                    {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.role" />
                                </div>

                                <!-- Password Update -->
                                <div class="border-t pt-6">
                                    <h3 class="mb-4 font-medium">Change Password (Optional)</h3>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="password">New Password</Label>
                                            <Input
                                                id="password"
                                                v-model="form.password"
                                                type="password"
                                                placeholder="Enter new password"
                                                :class="{ 'border-red-500': form.errors.password }"
                                            />
                                            <InputError :message="form.errors.password" />
                                            <p class="text-xs text-muted-foreground">
                                                Leave empty to keep current password
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="password_confirmation">Confirm New Password</Label>
                                            <Input
                                                id="password_confirmation"
                                                v-model="form.password_confirmation"
                                                type="password"
                                                placeholder="Confirm new password"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Buttons -->
                                <div class="flex gap-4 pt-4">
                                    <Button 
                                        type="submit" 
                                        :disabled="form.processing"
                                        class="min-w-32"
                                    >
                                        <Save class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Updating...' : 'Update User' }}
                                    </Button>

                                    <Link :href="usersIndex().url">
                                        <Button type="button" variant="outline">
                                            Cancel
                                        </Button>
                                    </Link>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-4">
                    <!-- Current User Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Current User Info</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div>
                                <Label class="text-muted-foreground">User ID</Label>
                                <p class="font-mono">{{ user.id }}</p>
                            </div>

                            <div>
                                <Label class="text-muted-foreground">Current Role</Label>
                                <div class="mt-1">
                                    <Badge 
                                        v-if="user.roles[0]"
                                        :variant="getRoleBadgeVariant(user.roles[0].name)"
                                    >
                                        {{ user.roles[0].name.toUpperCase() }}
                                    </Badge>
                                    <Badge v-else variant="outline">No Role</Badge>
                                </div>
                            </div>

                            <div>
                                <Label class="text-muted-foreground">Email Status</Label>
                                <div class="flex items-center gap-2 mt-1">
                                    <div 
                                        class="h-2 w-2 rounded-full"
                                        :class="user.email_verified_at ? 'bg-green-500' : 'bg-red-500'"
                                    ></div>
                                    <span class="text-xs">
                                        {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <Label class="text-muted-foreground">Member Since</Label>
                                <div class="flex items-center gap-2 mt-1">
                                    <Calendar class="h-3 w-3" />
                                    <span class="text-xs">
                                        {{ new Date(user.created_at).toLocaleDateString() }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Update Notes -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Update Notes</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div>
                                <h4 class="font-medium text-blue-700">📝 What you can update:</h4>
                                <ul class="mt-2 space-y-1 text-muted-foreground">
                                    <li>• Full name</li>
                                    <li>• Email address</li>
                                    <li>• Phone number</li>
                                    <li>• User role</li>
                                    <li>• Password (optional)</li>
                                </ul>
                            </div>

                            <div class="border-t pt-4">
                                <h4 class="font-medium text-orange-700">⚠️ Important:</h4>
                                <ul class="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <li>• Email changes won't affect verification status</li>
                                    <li>• Leave password empty to keep current</li>
                                    <li>• Role changes take effect immediately</li>
                                    <li>• User will remain logged in</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
