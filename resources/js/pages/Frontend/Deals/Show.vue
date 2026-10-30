<script setup lang="ts">
import CountdownTimer from '@/components/CountdownTimer.vue';
import Pagination from '@/components/Pagination.vue';
import ProductCard from '@/components/ProductCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { toSimplifiedProduct, type CampaignSummary } from '@/lib/products';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, Tag } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    seo?: any;
    campaign: CampaignSummary;
    products: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
}>();

const items = computed(() => props.products.data.map(toSimplifiedProduct));
const accent = computed(() => props.campaign.theme_color || (props.campaign.type === 'daily_deal' ? '#dc2626' : null));
</script>

<template>
    <MainLayout>
        <SeoHead :seo="seo" />

        <div class="mt-4 mb-2">
            <Link href="/deals" class="inline-flex items-center text-sm text-gray-500 hover:text-primary">
                <ChevronLeft class="h-4 w-4" /> All deals
            </Link>
        </div>

        <section class="mb-4 overflow-hidden rounded-lg bg-white shadow">
            <img v-if="campaign.banner_url" :src="campaign.banner_url" :alt="campaign.title" class="h-40 w-full object-cover md:h-64" />
            <div
                class="flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between"
                :style="accent ? { borderLeft: `6px solid ${accent}` } : undefined"
            >
                <div>
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ campaign.type_label }}</span>
                    <h1 class="text-2xl font-bold text-gray-800 md:text-3xl">{{ campaign.title }}</h1>
                    <p v-if="campaign.subtitle" class="mt-1 text-sm text-gray-600">{{ campaign.subtitle }}</p>
                    <p v-if="campaign.description" class="mt-2 max-w-3xl text-sm whitespace-pre-line text-gray-600">{{ campaign.description }}</p>
                </div>
                <CountdownTimer
                    v-if="campaign.ends_at"
                    :ends-at="campaign.ends_at"
                    size="md"
                    class="shrink-0 text-gray-800"
                    :label="campaign.type === 'daily_deal' ? 'Deals end in' : 'Ends in'"
                />
            </div>
        </section>

        <div v-if="items.length" class="mb-6">
            <p class="mb-3 text-sm text-gray-500">{{ products.total }} product{{ products.total === 1 ? '' : 's' }}</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                <ProductCard v-for="product in items" :key="product.id" :product="product" grid />
            </div>
            <Pagination :links="products.links" class="mt-6 justify-center" />
        </div>

        <div v-else class="mb-6 rounded-lg bg-white p-10 text-center shadow-sm">
            <Tag class="mx-auto h-10 w-10 text-gray-300" />
            <h2 class="mt-3 text-lg font-semibold text-gray-800">No products in this deal yet</h2>
            <Link href="/deals" class="mt-4 inline-block rounded-md bg-primary px-4 py-2 text-sm text-white">Browse other deals</Link>
        </div>
    </MainLayout>
</template>
