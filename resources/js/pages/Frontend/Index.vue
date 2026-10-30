<script setup lang="ts">
import CategorySidebar from '@/components/CategorySidebar.vue';
import CountdownTimer from '@/components/CountdownTimer.vue';
import DealsShortcuts from '@/components/DealsShortcuts.vue';
import FeaturedBrands from '@/components/FeaturedBrands.vue';
import Hero from '@/components/Hero.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import SeoHead from '@/components/SeoHead.vue';
import { campaignHref, toSimplifiedProduct, type CampaignSummary } from '@/lib/products';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
import type { Brand, Category, SimplifiedProduct } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────
interface Promotion {
    image_url:     string;
    category_slug: string | null;
}

interface PageProps extends InertiaPageProps {
    categories?:         Category[];
    brands?:             Brand[];
    promotions?:         Promotion[];
    productsByCategory?: Record<number, any[]>;
    campaignSections?:   CampaignSummary[];
}

// ─── Fallback ─────────────────────────────────────────────────────────────────
const DEFAULT_PROMOTIONS: Promotion[] = [
    { image_url: '/default.jpg', category_slug: null },
];

// ─── Page props ───────────────────────────────────────────────────────────────
const page = usePage<PageProps>();

const categories         = page.props.categories         ?? [];
const brands             = page.props.brands             ?? [];
const productsByCategory = page.props.productsByCategory ?? {};
const campaignSections   = page.props.campaignSections   ?? [];

const promotions = (page.props.promotions?.length ?? 0) > 0
    ? page.props.promotions!
    : DEFAULT_PROMOTIONS;

// ─── Products ─────────────────────────────────────────────────────────────────
const simplifiedProductsByCategory: Record<number, SimplifiedProduct[]> = Object.fromEntries(
    Object.entries(productsByCategory).map(([categoryId, products]) => [
        Number(categoryId),
        (products as any[]).map(toSimplifiedProduct),
    ]),
);

const filteredCategories = categories.filter(
    (category) => (simplifiedProductsByCategory[category.id]?.length ?? 0) >= 2
);
</script>

<template>
    <MainLayout>
        <SeoHead :seo="($page.props as any).seo" />
        <!-- Row 1: Sidebar + Banner -->
        <section class="mt-4 mb-4 flex flex-col gap-4 lg:flex-row lg:items-stretch lg:h-[450px]">

            <!-- Categories Sidebar — hidden on mobile -->
            <div class="w-full lg:w-2/12">
                <CategorySidebar :categories="categories" class="hidden lg:block h-full" />
            </div>

            <!-- Hero Banner — full width on mobile, 10/12 on desktop -->
            <div class="w-full lg:w-10/12 h-[220px] lg:h-full">
                <div class="relative h-full overflow-hidden rounded-lg shadow">
                    <Hero
                        :banners="promotions.map(p => p.image_url)"
                        :links="promotions.map(p => p.category_slug ? `/${p.category_slug}` : null)"
                        class="h-full cursor-pointer"
                        animation="fade"
                        lazy
                    />
                </div>
            </div>

        </section>

        <!-- Deals shortcuts: Today's Deals / Offers / Seasonal -->
        <section class="mb-4">
            <DealsShortcuts :campaigns="campaignSections" />
        </section>

        <!-- Live campaigns (Today's Deals first) -->
        <section v-if="campaignSections.length" class="mb-4 space-y-6">
            <div
                v-for="campaign in campaignSections"
                :key="campaign.slug"
                class="overflow-hidden rounded-lg"
                :style="campaign.theme_color ? { borderTop: `4px solid ${campaign.theme_color}` } : undefined"
            >
                <ProductCarouselSection
                    :title="campaign.title"
                    :slug="campaign.slug"
                    :href="campaign.type === 'daily_deal' ? '/deals/today' : campaignHref(campaign)"
                    :products="(campaign.products ?? []).map(toSimplifiedProduct)"
                >
                    <template #header-extra>
                        <span v-if="campaign.subtitle" class="text-sm text-gray-500">{{ campaign.subtitle }}</span>
                        <CountdownTimer
                            v-if="campaign.ends_at"
                            :ends-at="campaign.ends_at"
                            :label="campaign.type === 'daily_deal' ? 'Ends in' : 'Offer ends in'"
                            class="text-red-600"
                        />
                    </template>
                </ProductCarouselSection>
            </div>
        </section>

        <!-- Featured Brands -->
        <section class="mb-4">
            <div class="rounded-lg bg-white p-4 shadow">
                <FeaturedBrands :brands="brands" />
            </div>
        </section>

        <!-- Product Carousels -->
        <section class="mb-4 space-y-10">
            <ProductCarouselSection
                v-for="category in filteredCategories"
                :key="category.id"
                :title="category.name"
                :slug="category.slug"
                :products="simplifiedProductsByCategory[category.id] || []"
            />
        </section>

    </MainLayout>
</template>

<style scoped>
.hero-slide-enter-active,
.hero-slide-leave-active {
    transition: opacity 0.5s ease;
}
.hero-slide-enter-from,
.hero-slide-leave-to {
    opacity: 0;
}
.hero-slide-enter-to,
.hero-slide-leave-from {
    opacity: 1;
}
</style>