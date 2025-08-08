<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardList,
    FileCheck,
    FileUser,
    KeyRound,
    LayoutGrid,
    ListChecks,
    Lock,
    RulerIcon,
    ShieldCheck,
    Tag, User2Icon,
    Users
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const roles = computed(() => page.props.auth.roles as string[]);

const allNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Account',
        href: '/seller/profile',
        icon: KeyRound,
    },
    {
        title: 'Sellers',
        href: '#',
        icon: Users,
        children: [
            {
                title: 'Sellers Applications',
                href: '/admin/applications',
                icon: ClipboardList,
            },
            {
                title: 'Verification Documents',
                href: '/admin/document-types',
                icon: FileCheck,
            },
        ],
    },
    {
        title: 'Inventory',
        href: '#',
        icon: ClipboardList,
        children: [
            {
                title: 'Warehouses',
                href: '/admin/warehouses',
                icon: LayoutGrid,
            },
            {
                title: 'Categories',
                href: '/admin/categories',
                icon: LayoutGrid,
            },
            {
                title: 'Brands',
                href: '/admin/brands',
                icon: Tag,
            },
            {
                title: 'Units',
                href: '/admin/unit-types',
                icon: RulerIcon,
            },
            {
                title: 'Variants',
                href: '/admin/variant-categories',
                icon: ListChecks,
            },
            {
                title: 'Products',
                href: '/admin/products',
                icon: ClipboardList,
            },
        ],
    },
    {
      title: 'Users',
      href: '/admin/users',
      icon: FileUser,
    },
    {
        title: 'Access Rights',
        href: '#',
        icon: Lock,
        children: [
            {
                title: 'Roles',
                href: '/admin/roles',
                icon: ShieldCheck,
            },
            {
                title: 'Permissions',
                href: '/admin/permissions',
                icon: KeyRound,
            },
            {
                title: 'Manage User Roles',
                href: '/admin/user-roles',
                icon: Users,
            },
        ],
    },

];

const mainNavItems = computed(() => {
    if (roles.value.includes('seller')) {
        return allNavItems.filter((item) => ['Dashboard', 'Account'].includes(item.title));
    }

    return allNavItems.filter((item) => item.title !== 'Account');
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin/dashboard">
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
