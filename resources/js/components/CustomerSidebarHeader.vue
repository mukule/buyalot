<script setup lang="ts">
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Link } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <header class="flex h-16 shrink-0 items-center gap-2 border-b px-4">
        <SidebarTrigger class="-ml-1" />
        <Separator orientation="vertical" class="mr-2 h-4" />
        <Breadcrumb v-if="breadcrumbs.length">
            <BreadcrumbList>
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                    <BreadcrumbItem :class="{ 'hidden md:block': index === 0 }">
                        <BreadcrumbLink v-if="crumb.href && index < breadcrumbs.length - 1" as-child>
                            <Link :href="crumb.href">{{ crumb.label }}</Link>
                        </BreadcrumbLink>
                        <BreadcrumbPage v-else>{{ crumb.label }}</BreadcrumbPage>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" class="hidden md:block" />
                </template>
            </BreadcrumbList>
        </Breadcrumb>
    </header>
</template>
