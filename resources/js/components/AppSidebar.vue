<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard as superadminDashboard } from '@/routes/superadmin';
import { dashboard as adminDashboard } from '@/routes/admin';
import { dashboard as userDashboard } from '@/routes/user';
import { index as usersIndex } from '@/routes/users';
import { index as contactIndex } from '@/routes/contact';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Users, MessageSquare } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const userHasRole = (roleName: string): boolean => {
    return user.value.roles?.some(role => role.name === roleName) ?? false;
};

const getDashboardUrl = () => {
    if (userHasRole('superadmin')) {
        return superadminDashboard().url;
    } else if (userHasRole('admin')) {
        return adminDashboard().url;
    } else if (userHasRole('user')) {
        return userDashboard().url;
    }
    // Fallback to user dashboard if no role is found
    return userDashboard().url;
};

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: getDashboardUrl(),
            icon: LayoutGrid,
        },
    ];

    // Add User Management for superadmins
    if (userHasRole('superadmin')) {
        items.push({
            title: 'User Management',
            href: usersIndex().url,
            icon: Users,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="getDashboardUrl()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
