<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard as superadminDashboard } from '@/routes/superadmin';
import { index as usersIndex } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Superadmin Dashboard',
        href: superadminDashboard().url,
    },
];
</script>

<template>
    <Head title="Superadmin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Superadmin Dashboard</h1>
                    <p class="text-muted-foreground">Welcome back! You have full system access.</p>
                </div>
                <Badge variant="destructive" class="h-8 px-3">SUPERADMIN</Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Users</CardTitle>
                        <CardDescription>System-wide user count</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">3</div>
                        <p class="text-xs text-muted-foreground">All registered users</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>System Health</CardTitle>
                        <CardDescription>Overall system status</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">100%</div>
                        <p class="text-xs text-muted-foreground">All systems operational</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Admin Actions</CardTitle>
                        <CardDescription>Available operations</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">∞</div>
                        <p class="text-xs text-muted-foreground">Unlimited access</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Superadmin Privileges</CardTitle>
                        <CardDescription>What you can do</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>Manage all users and roles</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>System configuration access</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>Database management</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>Full administrative control</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>Administrative tools</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                                <Link :href="usersIndex().url" class="block">
                                <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-medium">User Management</div>
                                            <div class="text-sm text-muted-foreground">Manage system users</div>
                                        </div>
                                    </div>
                                    <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>System-wide events</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>User management system created</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>System seeded with roles</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>3 Users created</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Superadmin logged in</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

