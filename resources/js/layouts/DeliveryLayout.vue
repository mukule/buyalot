<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import AppLogo from '@/components/AppLogo.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Clock,
    CheckCircle,
    XCircle,
    PackageCheck,
    LogOut,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);
const section = computed(() => {
    const match = currentUrl.value.match(/[?&]section=(\w+)/);
    return match ? match[1] : 'dashboard';
});

function isActive(href: string) {
    if (href === '/admin/delivery' || href === '/admin/delivery?section=dashboard') {
        return section.value === 'dashboard';
    }
    return currentUrl.value.includes(href);
}

const menuItems = [
    { title: 'Dashboard', href: '/admin/delivery', icon: LayoutGrid },
    { title: 'Pending', href: '/admin/delivery?section=pending', icon: Clock },
    { title: 'Accepted', href: '/admin/delivery?section=accepted', icon: CheckCircle },
    { title: 'Rejected', href: '/admin/delivery?section=rejected', icon: XCircle },
    { title: 'Attended deliveries', href: '/admin/delivery?section=attended', icon: PackageCheck },
];
</script>

<template>
    <AppShell variant="sidebar">
        <Sidebar collapsible="icon" variant="inset" class="border-r border-sidebar-border">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link href="/admin/delivery">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in menuItems" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isActive(item.href)">
                            <Link :href="item.href">
                                <component :is="item.icon" class="h-4 w-4 shrink-0" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link :href="route('logout')" method="post" as="button">
                                <LogOut class="h-4 w-4 shrink-0" />
                                <span>Log out</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>

        <AppContent variant="sidebar" class="flex flex-col">
            <FlashMessage />
            <slot />
        </AppContent>
    </AppShell>
</template>
