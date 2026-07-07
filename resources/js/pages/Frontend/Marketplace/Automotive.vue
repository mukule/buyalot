<script setup lang="ts">
import MarketplaceListing from '@/components/marketplace/MarketplaceListing.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    vertical: { key: string; label: string; tagline: string; type: string; icon: string };
    filterSchema: any[];
    quickTags?: { label: string; params: Record<string, any> }[];
    sorts: { key: string; label: string }[];
    filterOptions: Record<string, (string | number)[]>;
    activeFilters: Record<string, any>;
    products: any;
    title?: string;
}>();
</script>

<template>
    <Head :title="props.title ?? 'Cars & Motors'" />
    <MainLayout>
        <MarketplaceListing
            :vertical="vertical"
            :filter-schema="filterSchema"
            :quick-tags="quickTags"
            :sorts="sorts"
            :filter-options="filterOptions"
            :active-filters="activeFilters"
            :products="products"
        />
    </MainLayout>
</template>
