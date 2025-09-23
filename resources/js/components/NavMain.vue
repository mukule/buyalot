<script setup lang="ts">
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';
console.log("Available routes from Ziggy:", route().routes);
console.log("Has login:", route().has('login'));
console.log("Has register:", route().has('register'));
console.log("Has password.request:", route().has('password.request'));
defineProps<{
  items: NavItem[];
}>();

const page = usePage();
const openMenus = ref<Record<string, boolean>>({});

function toggleMenu(title: string) {
  openMenus.value[title] = !openMenus.value[title];
}
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarMenu>
      <SidebarMenuItem v-for="item in items" :key="item.title">
        <!-- Parent with children -->
        <template v-if="item.children">
          <SidebarMenuButton as-child :tooltip="item.title" @click="toggleMenu(item.title)">
            <div class="flex w-full items-center justify-between">
              <div class="flex items-center gap-2">
                <component :is="item.icon" class="icon-sm" />
                <span>{{ item.title }}</span>
              </div>
              <component
                :is="openMenus[item.title] ? ChevronDown : ChevronRight"
                class="h-4 w-4 ml-auto opacity-70"
              />
            </div>
          </SidebarMenuButton>

          <transition name="fade">
            <div v-if="openMenus[item.title]" class="ml-6 mt-1 space-y-1">
              <SidebarMenuItem
                v-for="child in item.children"
                :key="child.title"
              >
                <SidebarMenuButton
                  as-child
                  :tooltip="child.title"
                  :is-active="child.href === page.url"
                >
                  <Link :href="child.href">
                    <component :is="child.icon" />
                    <span>{{ child.title }}</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </div>
          </transition>
        </template>

        <!-- Regular nav item -->
        <template v-else>
          <SidebarMenuButton
            as-child
            :tooltip="item.title"
            :is-active="item.href === page.url"
          >
            <Link :href="item.href">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </template>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-2px);
}
</style>
