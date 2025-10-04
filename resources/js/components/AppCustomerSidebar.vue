<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    User,
    Package,
    Mail,
    Star,
    Tag,
    Heart,
    Store,
    Eye,
    Settings,
    CreditCard,
    MapPin,
    Bell,
    LogOut,
    LayoutDashboard,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get current user
const page = usePage();
const user = computed(() => page.props.auth?.user);
const customerId = computed(() => user.value?.id);

const allNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: customerId.value ? `/customers/${customerId.value}/dashboard` : '#',
        icon: LayoutDashboard,
        isActive: true,
    },
    {
        title: 'My Account',
        icon: User,
        children: [
            {
                title: 'Account Overview',
                url: customerId.value ? `/customers/${customerId.value}/dashboard` : '#',
                icon: User,
            },
            {
                title: 'Orders',
                url: '/orders/my-orders',
                icon: Package,
            },
            {
                title: 'Inbox',
                url: '#',
                icon: Mail,
            },
            {
                title: 'Pending Reviews',
                url: '#',
                icon: Star,
            },
            {
                title: 'Vouchers',
                url: '#',
                icon: Tag,
            },
            {
                title: 'Wishlist',
                url: '/wishlist',
                icon: Heart,
            },
            {
                title: 'Followed Sellers',
                url: '#',
                icon: Store,
            },
            {
                title: 'Recently Viewed',
                url: '#',
                icon: Eye,
            },
        ],
    },
    {
        title: 'Account Management',
        icon: Settings,
        children: [
            {
                title: 'Profile Settings',
                url: '/customer/profile',
                icon: Settings,
            },
            {
                title: 'Payment Settings',
                url: '#',
                icon: CreditCard,
            },
            {
                title: 'Address Book',
                url: customerId.value ? `/customers/${customerId.value}/addresses` : '#',
                icon: MapPin,
            },
            {
                title: 'Loyalty Points',
                url: customerId.value ? `/customers/${customerId.value}/loyalty-points` : '#',
                icon: Star,
            },
            {
                title: 'Newsletter Preferences',
                url: '#',
                icon: Bell,
            },
            {
                title: 'Close Account',
                url: '/customer/account',
                icon: LogOut,
            },
        ],
    },
];

const filterNavItems = (items: NavItem[]): NavItem[] => {
    return items.filter((item) => {
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
                        <Link :href="customerId ? `/customers/${customerId}/dashboard` : '/'">
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
