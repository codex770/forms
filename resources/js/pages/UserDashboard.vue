<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard as userDashboard } from '@/routes/user';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: userDashboard().url,
    },
];
</script>

<template>
    <Head title="User Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">User Dashboard</h1>
                    <p class="text-muted-foreground">Welcome to your personal workspace.</p>
                </div>
                <Badge variant="secondary" class="h-8 px-3">USER</Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>My Items</CardTitle>
                        <CardDescription>Your content</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">Items created</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Notifications</CardTitle>
                        <CardDescription>Unread messages</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">0</div>
                        <p class="text-xs text-muted-foreground">New notifications</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Activity</CardTitle>
                        <CardDescription>This month</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">1</div>
                        <p class="text-xs text-muted-foreground">Login sessions</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Getting Started</CardTitle>
                        <CardDescription>Quick links for new users</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2">
                                <span class="text-blue-600">→</span>
                                <span>Complete your profile</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-blue-600">→</span>
                                <span>Explore features</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-blue-600">→</span>
                                <span>Read documentation</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-blue-600">→</span>
                                <span>Connect with community</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Updates</CardTitle>
                        <CardDescription>What's new</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>Account created</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span>Welcome email sent</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>First login</span>
                                <span class="text-xs text-muted-foreground">Just now</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

