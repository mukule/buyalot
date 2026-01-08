<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, ChevronsUpDown } from 'lucide-vue-next'; // Added ArrowLeftRight
import { computed } from 'vue';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const { isMobile, state } = useSidebar(); // Ensure these are initialized

const user = computed(() => page.props.auth.user as User);
const activeRole = computed(() => page.props.auth.active_role);

const switchTarget = computed(() => {
    if (!user.value?.secondary_role) return null;
    return activeRole.value === user.value.user_type ? user.value.secondary_role : user.value.user_type;
});
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem v-if="user?.secondary_role" class="mb-2 px-2">
            <Link
                :href="route('role.switch')"
                method="post"
                as="button"
                class="flex w-full items-center gap-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-indigo-700 transition-all hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300"
            >
                <ArrowLeftRight class="size-4 shrink-0" />
                <div class="flex flex-col items-start text-[10px] leading-tight">
                    <span class="font-bold uppercase opacity-70">Switch Account</span>
                    <span class="text-sm font-semibold capitalize">{{ switchTarget }} Portal</span>
                </div>
            </Link>
        </SidebarMenuItem>

        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg" class="data-[state=open]:bg-primary data-[state=open]:text-white">
                        <UserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
