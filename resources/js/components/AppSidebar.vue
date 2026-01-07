<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    BadgePercent,
    BookUserIcon,
    ClipboardList,
    FileCheck,
    FileText,
    KeyRound,
    Layers3Icon,
    LayoutGrid,
    ListChecks,
    Lock,
    LucideListOrdered,
    Map,
    MapPin,
    MapPinCheck,
    RulerIcon,
    ShieldCheck,
    Tag,
    Truck,
    UserCog2Icon,
    Users,
    Wallet2Icon,
    Warehouse,
    Workflow,
} from 'lucide-vue-next';
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
        title: 'Vendors',
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
                title: 'Vendors List',
                href: '/admin/sellers',
                icon: BookUserIcon,
                permissions: ['view-sellers', 'manage-sellers'],
            },
            {
                title: 'Vendors Applications',
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
            'view-discounts',
        ],
        children: [
            {
                title: 'Warehouses',
                href: '/admin/warehouses',
                icon: Warehouse,
                permissions: ['view-warehouses', 'manage-warehouse-staff'],
            },
            {
                title: 'Categories',
                href: '/admin/categories',
                icon: Layers3Icon,
                permissions: ['view-brands'],
            },
            {
                title: 'Brands',
                href: '/admin/brands',
                icon: Tag,
                permissions: ['view-brands'],
            },
            {
                title: 'Units',
                href: '/admin/unit-types',
                icon: RulerIcon,
                permissions: ['view-units'],
            },
            {
                title: 'Variants',
                href: '/admin/variant-categories',
                icon: ListChecks,
                permissions: ['view-variants'],
            },
            {
                title: 'Products',
                href: '/admin/products',
                icon: ClipboardList,
                permissions: ['view-products', 'manage-product-inventory'],
            },
            {
                title: 'Product Statuses',
                href: '/admin/product-statuses',
                icon: Workflow,
                permissions: ['view-product-statuses'],
            },
            {
                title: 'Promotion Types',
                href: '/admin/discount-types',
                icon: BadgePercent,
                permissions: ['view-discounts'],
            },
            {
                title: 'Promotions',
                href: '/admin/discounts',
                icon: BadgePercent,
                permissions: ['view-discounts'],
            },
        ],
    },
    {
        title: 'Orders',
        href: '/admin/orders',
        icon: LucideListOrdered,
        permissions: ['view-orders'],
    },
    {
        title: 'Invoices',
        href: '/admin/invoices',
        icon: FileText,
        permissions: ['view-invoices'],
    },
    {
        title: 'Payments',
        href: '/admin/payments',
        icon: Wallet2Icon,
        permissions: ['view-payments'],
    },
    {
        title: 'Customers',
        href: '/admin/customers',
        icon: BookUserIcon,
        permissions: ['view-customers'],
    },
    // {
    //     title: 'Sellers',
    //     href: '/admin/sellers',
    //     icon: BookUserIcon,
    //     permissions: ['view-sellers'],
    // },
    {
        title: 'Users',
        href: '/admin/users',
        icon: UserCog2Icon,
        permissions: ['view-users'],
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

    {
        title: 'Delivery Address',
        href: 'null',
        icon: MapPinCheck,
        permissions: ['view-regions'],
        children: [
            {
                title: 'Regions',
                href: '/admin/regions',
                icon: MapPin,
                permissions: ['view-regions'],
            },
            {
                title: 'Zones',
                href: '/admin/zones',
                icon: Map,
                permissions: ['view-regions'],
            },
            {
                title: 'Shipping Rates',
                href: '/admin/shipping-rates',
                icon: Truck,
                permissions: ['view-regions'],
            },
            // {
            //     title: 'Areas',
            //     href: '/admin/areas',
            //     icon: MapPin,
            //     permissions: ['view-regions'],
            // },
            // {
            //     title: 'Routes',
            //     href: '/admin/routes',
            //     icon: MapPin,
            //     permissions: ['view-regions'],
            // },
        ],
    },
];

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
