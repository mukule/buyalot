<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type User } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const { isMobile, state } = useSidebar();

const user = computed(() => page.props.auth.user as User);
const activeRole = computed(() => page.props.auth.active_role as string | undefined);
const switchableRoles = computed(() => (page.props.auth.switchable_roles as string[]) ?? []);

const otherRoles = computed(() => {
    const current = activeRole.value === 'vendor' ? 'seller' : activeRole.value;
    return switchableRoles.value.filter((r) => (r === 'vendor' ? 'seller' : r) !== current);
});

const hasSwitchableAccount = computed(() => otherRoles.value.length > 0);

const switchTarget = computed(() => {
    if (otherRoles.value.length === 1) return otherRoles.value[0];
    return null;
});

function switchToRole(role: string) {
    router.post(route('role.switch'), { role });
}

const roleLabel = (role: string) => {
    const r = role === 'vendor' ? 'seller' : role;
    return `${r.charAt(0).toUpperCase() + r.slice(1)} Portal`;
};
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem v-if="hasSwitchableAccount && otherRoles.length === 1" class="mb-2 px-2">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-indigo-700 transition-all hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300"
                @click="switchToRole(switchTarget!)"
            >
                <ArrowLeftRight class="size-4 shrink-0" />
                <div class="flex flex-col items-start text-[10px] leading-tight">
                    <span class="font-bold uppercase opacity-70">Switch Account</span>
                    <span class="text-sm font-semibold capitalize">{{ roleLabel(switchTarget!) }}</span>
                </div>
            </button>
        </SidebarMenuItem>

        <SidebarMenuItem v-else-if="hasSwitchableAccount && otherRoles.length >= 2" class="mb-2 px-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-indigo-700 transition-all hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300"
                    >
                        <ArrowLeftRight class="size-4 shrink-0" />
                        <div class="flex flex-col items-start text-[10px] leading-tight">
                            <span class="font-bold uppercase opacity-70">Switch Account</span>
                            <span class="text-sm font-semibold">Customer / Seller / Distributor</span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4 shrink-0" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="min-w-48"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuItem
                        v-for="role in otherRoles"
                        :key="role"
                        @click="switchToRole(role)"
                    >
                        {{ roleLabel(role) }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
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
