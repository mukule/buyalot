<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { Link } from '@inertiajs/vue3';
import { LayoutGrid } from 'lucide-vue-next';
</script>

<template>
    <AppShell variant="sidebar">
        <Sidebar collapsible="icon" variant="inset" class="border-r border-sidebar-border">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link :href="route('distributor.dashboard')">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link :href="route('distributor.dashboard')">
                                <LayoutGrid class="h-4 w-4 shrink-0" />
                                <span>Dashboard</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>

        <AppContent variant="sidebar" class="flex flex-col">
            <FlashMessage />
            <slot />
        </AppContent>
    </AppShell>
</template>
