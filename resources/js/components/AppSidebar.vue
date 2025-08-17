<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import { ClipboardList, FileCheck, FileUser, KeyRound, LayoutGrid, ListChecks, Lock, RulerIcon, ShieldCheck, Tag, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';
const { canAccess } = usePermissions();

// const page = usePage();
// const roles = computed(() => page.props.auth.roles as string[]);
const allNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
        permissions: ['view-dashboard'],
    },
    {
        title: 'Account',
        href: '/seller/profile',
        icon: KeyRound,
        permissions: ['view-accounts'],
    },
    {
        title: 'Sellers',
        href: '#',
        icon: Users,
        permissions: [
            'view-sellers',
            'view-seller-applications',
            'view-verification-documents',
            'reject-verification-documents',
            'view-verification-documents',
            'approve-sellers',
            'approve-seller-applications',
        ],
        children: [
            {
                title: 'Sellers Applications',
                href: '/admin/applications',
                icon: ClipboardList,
                permissions: ['view-seller-applications', 'approve-seller-applications'],
            },
            {
                title: 'Verification Documents',
                href: '/admin/document-types',
                icon: FileCheck,
                permissions: ['view-verification-documents', 'reject-verification-documents', 'view-verification-documents'],
            },
        ],
    },
    {
        title: 'Inventory',
        href: '#',
        icon: ClipboardList,
        permissions: [
            'adjust-inventory',
            'manage-inventory',
            'transfer-inventory',
            'view-inventory',
            'view-inventory-history',
            'manage-product-inventory',
            'view-warehouses',
            'manage-warehouse-staff',
            'view-categories',
            'view-brands',
            'view-units',
            'view-variants',
            'view-products',
            'manage-product-inventory',
        ],
        children: [
            {
                title: 'Warehouses',
                href: '/admin/warehouses',
                icon: LayoutGrid,
                permissions: [
                    'view-warehouses',
                    'manage-warehouse-staff',
                ],
            },
            {
                title: 'Categories',
                href: '/admin/categories',
                icon: LayoutGrid,
                permissions: [
                    'view-brands',
                ],
            },
            {
                title: 'Brands',
                href: '/admin/brands',
                icon: Tag,
                permissions: [
                    'view-brands',
                ],
            },
            {
                title: 'Units',
                href: '/admin/unit-types',
                icon: RulerIcon,
                permissions: [
                    'view-units',
                ],
            },
            {
                title: 'Variants',
                href: '/admin/variant-categories',
                icon: ListChecks,
                permissions: [
                    'view-variants',
                ],
            },
            {
                title: 'Products',
                href: '/admin/products',
                icon: ClipboardList,
                permissions: [
                    'view-products',
                    'manage-product-inventory',
                ],
            },
        ],
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: FileUser,
        permissions: [
            'view-users',
        ],
    },
    {
        title: 'Access Rights',
        href: '#',
        icon: Lock,
        permissions: ['view-roles', 'view-permissions'],
        children: [
            {
                title: 'Roles',
                href: '/admin/roles',
                icon: ShieldCheck,
                permissions: ['view-roles'],
            },
            {
                title: 'Permissions',
                href: '/admin/permissions',
                icon: KeyRound,
                permissions: ['view-permissions'],
            },
            {
                title: 'Manage User Roles',
                href: '/admin/user-roles',
                icon: Users,
                permissions: ['manage-user-roles'],
            },
        ],
    },
];

// const mainNavItems = computed(() => {
//     if (roles.value.includes('seller')) {
//         return allNavItems.filter((item) => ['Dashboard', 'Account'].includes(item.title));
//     }
//
//     return allNavItems.filter((item) => item.title !== 'Account');
// });

const filterNavItems = (items: NavItem[]): NavItem[] => {
    return items.filter((item) => {
        if (!canAccess(item)) {
            return false;
        }
        if (item.children) {
            const filteredChildren = filterNavItems(item.children);
            if (filteredChildren.length === 0) {
                return false;
            }
            item.children = filteredChildren;
        }

        return true;
    });
};

const mainNavItems = computed(() => {
    return filterNavItems([...allNavItems]);
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
