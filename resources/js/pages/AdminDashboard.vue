<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: adminDashboard().url,
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
                    <p class="text-muted-foreground">Manage users and content.</p>
                </div>
                <Badge variant="default" class="h-8 px-3">ADMIN</Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Users</CardTitle>
                        <CardDescription>Users you manage</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">3</div>
                        <p class="text-xs text-muted-foreground">Active users</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Content</CardTitle>
                        <CardDescription>Managed content items</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">Total items</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pending Tasks</CardTitle>
                        <CardDescription>Requires attention</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">Tasks pending</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Admin Privileges</CardTitle>
                        <CardDescription>Your permissions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>Manage regular users</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>Edit and delete content</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-600">✓</span>
                                <span>Moderate submissions</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-red-600">✗</span>
                                <span>System configuration (limited)</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>Common admin tasks</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>View all users</span>
                                <span class="text-xs text-blue-600 cursor-pointer">Go →</span>
                            </div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>Content management</span>
                                <span class="text-xs text-blue-600 cursor-pointer">Go →</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Reports</span>
                                <span class="text-xs text-blue-600 cursor-pointer">Go →</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

