<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type AppPageProps, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { User, Package, Star, Heart, Eye, Settings, CreditCard, MapPin, Bell, LogOut, LayoutDashboard } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get current user
const page = usePage<AppPageProps>();
// const user = computed(() => page.props.auth?.user);

const customerId = computed(() => page.props.auth?.customer_id);

const allNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: customerId.value ? `/customers/dashboard?customer=${customerId.value}` : '#',
        icon: LayoutDashboard,
        isActive: true,
    },
    // {
    //     title: 'My Account',
    //     icon: User,
    //     children: [
    {
        title: 'Account Overview',
        href: customerId.value ? `/customers/dashboard?customer=${customerId.value}` : '#',
        icon: User,
    },
    {
        title: 'Orders',
        href: route('orders.index'),
        icon: Package,
    },
    // {
    //     title: 'Inbox',
    //     href: '#',
    //     icon: Mail,
    // },
    {
        title: 'Pending Reviews',
        href: route('customer.reviews.pending'),
        icon: Star,
    },
    // {
    //     title: 'Vouchers',
    //     href: '#',
    //     icon: Tag,
    // },
    {
        title: 'Wishlist',
        href: route('wishlist.index'),
        icon: Heart,
    },
    // {
    //     title: 'Followed Vendors',
    //     href: '#',
    //     icon: Store,
    // },
    {
        title: 'Recently Viewed',
        href: route('customer.reviews.index'),
        icon: Eye,
    },
    //     ],
    // },
    {
        title: 'Account Management',
        icon: Settings,
        children: [
            // {
            //     title: 'Profile Settings',
            //     href: '/customer/profile',
            //     icon: Settings,
            // },
            // {
            //     title: 'Payment Settings',
            //     href: '#',
            //     icon: CreditCard,
            // },
            // {
            //     title: 'Address Book',
            //     href: customerId.value ? `/customers/${customerId.value}/addresses` : '#',
            //     icon: MapPin,
            // },
            // {
            //     title: 'Loyalty Points',
            //     href: customerId.value ? `/customers/${customerId.value}/loyalty-points` : '#',
            //     icon: Star,
            // },
            // {
            //     title: 'Newsletter Preferences',
            //     href: '#',
            //     icon: Bell,
            // },
            {
                title: 'Close Account',
                href: '/customer/account',
                icon: LogOut,
            },
        ],
        href: '',
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
