<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, User } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { dashboard as superadminDashboard } from '@/routes/superadmin';
import { index as usersIndex, store as usersStore, create as usersCreate } from '@/routes/users';

interface Role {
    id: number;
    name: string;
}

const props = defineProps<{
    roles: Role[];
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: superadminDashboard().url },
    { title: 'User Management', href: usersIndex().url },
    { title: 'Create User', href: usersCreate().url },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const submit = () => {
    form.post(usersStore().url, {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Create User" />

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
                    <h1 class="text-3xl font-bold tracking-tight">Create New User</h1>
                    <p class="text-muted-foreground">Add a new user to the system</p>
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
                                Enter the user's basic information and credentials
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

                                <!-- Password -->
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="password">Password *</Label>
                                        <Input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            placeholder="Enter secure password"
                                            required
                                            :class="{ 'border-red-500': form.errors.password }"
                                        />
                                        <InputError :message="form.errors.password" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="password_confirmation">Confirm Password *</Label>
                                        <Input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            placeholder="Confirm password"
                                            required
                                        />
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
                                        {{ form.processing ? 'Creating...' : 'Create User' }}
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
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Create User</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div>
                                <h4 class="font-medium text-green-700">✓ What happens next:</h4>
                                <ul class="mt-2 space-y-1 text-muted-foreground">
                                    <li>• User account will be created</li>
                                    <li>• Email will be automatically verified</li>
                                    <li>• Selected role will be assigned</li>
                                    <li>• User can login immediately</li>
                                </ul>
                            </div>

                            <div class="border-t pt-4">
                                <h4 class="font-medium text-blue-700">📋 Role Permissions:</h4>
                                <div class="mt-2 space-y-2 text-xs">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-red-500"></div>
                                        <span class="font-medium">Superadmin:</span>
                                        <span class="text-muted-foreground">Full system access</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                        <span class="font-medium">Admin:</span>
                                        <span class="text-muted-foreground">User management</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                        <span class="font-medium">User:</span>
                                        <span class="text-muted-foreground">Limited access</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <h4 class="font-medium text-orange-700">⚠️ Security Notes:</h4>
                                <ul class="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <li>• Use strong passwords</li>
                                    <li>• Unique email addresses only</li>
                                    <li>• Phone number is optional</li>
                                    <li>• Users can update their profile later</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
