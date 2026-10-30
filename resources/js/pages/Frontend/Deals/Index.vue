<script setup lang="ts">
import CountdownTimer from '@/components/CountdownTimer.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import SeoHead from '@/components/SeoHead.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { campaignHref, toSimplifiedProduct, type CampaignSummary } from '@/lib/products';
import { Link } from '@inertiajs/vue3';
import { Tag } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    seo?: any;
    type: string | null;
    types: Record<string, string>;
    campaigns: CampaignSummary[];
}>();

const tabs = [{ key: null as string | null, label: 'All' }, ...Object.entries(props.types).map(([key, label]) => ({ key, label }))];
const tabHref = (key: string | null) => (key === 'daily_deal' ? '/deals/today' : key ? `/deals?type=${key}` : '/deals');
</script>

<template>
    <MainLayout>
        <SeoHead :seo="seo" />

        <section class="mt-4 mb-4 rounded-lg bg-gradient-to-r from-red-600 to-orange-500 p-6 text-white shadow">
            <h1 class="text-2xl font-bold md:text-3xl">{{ title }}</h1>
            <p class="mt-1 text-sm text-white/90">Limited-time prices on top products. Discounts are applied automatically at checkout.</p>
        </section>

        <nav class="scrollbar-hide mb-4 flex gap-2 overflow-x-auto">
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tabHref(tab.key)"
                class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition"
                :class="type === tab.key ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-primary hover:text-primary'"
            >
                {{ tab.label }}
            </Link>
        </nav>

        <div v-if="campaigns.length" class="mb-6 space-y-6">
            <div v-for="campaign in campaigns" :key="campaign.slug" class="overflow-hidden rounded-lg bg-white shadow-sm">
                <Link v-if="campaign.banner_url" :href="campaignHref(campaign)" class="block">
                    <img :src="campaign.banner_url" :alt="campaign.title" class="h-32 w-full object-cover md:h-48" loading="lazy" />
                </Link>
                <ProductCarouselSection
                    v-if="campaign.products?.length"
                    :title="campaign.title"
                    :slug="campaign.slug"
                    :href="campaignHref(campaign)"
                    :products="campaign.products.map(toSimplifiedProduct)"
                >
                    <template #header-extra>
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ campaign.type_label }}</span>
                        <span v-if="campaign.products_count" class="text-xs text-gray-500">{{ campaign.products_count }} products</span>
                        <CountdownTimer v-if="campaign.ends_at" :ends-at="campaign.ends_at" class="text-red-600" />
                    </template>
                </ProductCarouselSection>
            </div>
        </div>

        <div v-else class="mb-6 rounded-lg bg-white p-10 text-center shadow-sm">
            <Tag class="mx-auto h-10 w-10 text-gray-300" />
            <h2 class="mt-3 text-lg font-semibold text-gray-800">No live deals right now</h2>
            <p class="mt-1 text-sm text-gray-500">Check back soon — new offers drop regularly.</p>
            <Link href="/" class="mt-4 inline-block rounded-md bg-primary px-4 py-2 text-sm text-white">Continue shopping</Link>
        </div>
    </MainLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    scrollbar-width: none;
}
</style>
