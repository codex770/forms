<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard as userDashboard } from '@/routes/user';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileText, ArrowRight, Radio } from 'lucide-vue-next';

interface WebForm {
    webform_id: string;
    name: string;
    count: number;
}

interface FormType {
    type: string;
    forms: WebForm[];
    totalCount: number;
}

interface Station {
    station: string;
    stationName: string;
    types: FormType[];
    totalCount: number;
}

interface Props {
    stations: Station[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Form Center',
        href: userDashboard().url,
    },
];

const getBadgeVariant = (station: string): string => {
    const variants: Record<string, string> = {
        'bigfm': 'default',
        'rpr1': 'secondary',
        'regenbogen': 'outline',
        'rockfm': 'destructive',
        'bigkarriere': 'default'
    };
    return variants[station] || 'default';
};

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
    return `/forms/${webformId}`;
};
</script>

<template>
    <Head title="Form Center Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Form Center</h1>
                    <p class="text-muted-foreground mt-1">
                        Overview of all available forms and their submissions
                    </p>
                </div>
                <Badge variant="secondary" class="h-8 px-3">USER</Badge>
            </div>

            <!-- Stations & Forms Overview -->
            <div v-if="props.stations.length === 0" class="text-center py-12">
                <FileText class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">No Forms Yet</h3>
                <p class="text-muted-foreground mt-2">
                    Forms will appear here once submissions are received from webhooks.
                </p>
            </div>

            <!-- 2-Column Grid of Station Cards -->
            <div v-else class="grid gap-6 md:grid-cols-2">
                <!-- Station Card (Outer) -->
                <Card v-for="station in props.stations" :key="station.station" class="overflow-hidden flex flex-col border-2">
                    <!-- Station Header with Unique Color -->
                    <CardHeader :class="getStationHeaderClass(station.station) + ' pb-3'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Radio class="h-5 w-5 flex-shrink-0" />
                                <div>
                                    <CardTitle class="text-lg font-bold">{{ station.stationName }}</CardTitle>
                                    <div class="text-xs mt-0.5 opacity-90">
                                        {{ station.types?.reduce((sum, type) => sum + type.forms.length, 0) || 0 }} forms • {{ station.totalCount }} entries
                                    </div>
                                </div>
                            </div>
                            <Badge :class="getStationBadgeClass(station.station) + ' text-xs px-2 py-0.5 font-semibold'">
                                {{ station.station.toUpperCase() }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <!-- Forms by Type -->
                    <CardContent class="p-0 flex-1">
                        <!-- Empty State -->
                        <div v-if="!station.types || station.types.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                            <FileText class="h-10 w-10 text-muted-foreground/50 mb-2" />
                            <p class="text-sm text-muted-foreground">No forms yet</p>
                            <p class="text-xs text-muted-foreground/70 mt-1">
                                Forms will appear when webhooks are received
                            </p>
                        </div>

                        <!-- Types and Forms -->
                        <div v-else-if="station.types && station.types.length > 0" class="overflow-auto max-h-96">
                            <div v-for="formType in station.types" :key="formType.type" class="border-b last:border-b-0">
                                <!-- Type Header -->
                                <div class="bg-muted/30 px-3 py-2 sticky top-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold text-muted-foreground">{{ formType.type }}</span>
                                        <span class="text-xs text-muted-foreground">{{ formType.totalCount }} entries</span>
                                    </div>
                                </div>
                                
                                <!-- Forms in this type -->
                            <table class="w-full text-sm">
                                    <thead class="bg-muted/20">
                                        <tr>
                                            <th class="text-left font-medium px-3 py-1.5 text-xs">Form Name</th>
                                            <th class="text-right font-medium px-3 py-1.5 text-xs w-20">Entries</th>
                                        <th class="w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr 
                                            v-for="form in formType.forms" 
                                        :key="form.webform_id"
                                        class="hover:bg-muted/50 transition-colors cursor-pointer group"
                                        @click="$inertia.visit(getFormUrl(form.webform_id))"
                                    >
                                        <td class="px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <FileText class="h-3.5 w-3.5 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
                                                <div class="min-w-0">
                                                    <div class="font-medium text-xs truncate group-hover:text-primary transition-colors">
                                                        {{ form.name }}
                                                    </div>
                                                    <div class="text-[10px] text-muted-foreground truncate">
                                                        {{ form.webform_id }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <span class="font-semibold text-sm">{{ form.count }}</span>
                                        </td>
                                        <td class="px-2 py-2">
                                            <ArrowRight class="h-3.5 w-3.5 text-muted-foreground group-hover:text-primary group-hover:translate-x-0.5 transition-all" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Summary Stats -->
            <div v-if="props.stations.length > 0" class="grid gap-4 md:grid-cols-3 mt-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Stations</CardTitle>
                        <CardDescription>Active radio stations</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ props.stations.length }}</div>
                        <p class="text-xs text-muted-foreground">Radio stations</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Total Forms</CardTitle>
                        <CardDescription>Unique webforms</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">
                            {{ props.stations.reduce((sum, station) => sum + station.types.reduce((typeSum, type) => typeSum + type.forms.length, 0), 0) }}
                        </div>
                        <p class="text-xs text-muted-foreground">Active forms</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Total Submissions</CardTitle>
                        <CardDescription>All entries combined</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">
                            {{ props.stations.reduce((sum, station) => sum + station.totalCount, 0) }}
                        </div>
                        <p class="text-xs text-muted-foreground">Total entries</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

