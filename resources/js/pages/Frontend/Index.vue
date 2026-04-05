<script setup lang="ts">
import CategorySidebar from '@/components/CategorySidebar.vue';
import FeaturedBrands from '@/components/FeaturedBrands.vue';
import Hero from '@/components/Hero.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
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
}

// ─── Fallback ─────────────────────────────────────────────────────────────────
const DEFAULT_PROMOTIONS: Promotion[] = [
    { image_url: 'http://127.0.0.1:8000/default.jpg', category_slug: null },
];

// ─── Page props ───────────────────────────────────────────────────────────────
const page = usePage<PageProps>();

const categories         = page.props.categories         ?? [];
const brands             = page.props.brands             ?? [];
const productsByCategory = page.props.productsByCategory ?? {};

const promotions = (page.props.promotions?.length ?? 0) > 0
    ? page.props.promotions!
    : DEFAULT_PROMOTIONS;

// ─── Products ─────────────────────────────────────────────────────────────────
const simplifiedProductsByCategory: Record<number, SimplifiedProduct[]> = Object.fromEntries(
    Object.entries(productsByCategory).map(([categoryId, products]) => [
        Number(categoryId),
        (products as any[]).map((p) => ({
            id:               p.id,
            hashid:           p.variant_hashid,
            product_slug:     p.product_slug,
            name:             p.name,
            brand:            p.brand ?? null,
            image:            p.primary_image_url || '/fallback-image.png',
            marked_price:     p.marked_price      ?? 0,
            final_price:      p.final_price       ?? 0,
            discount_percent: p.discount_percent  ?? 0,
            has_discount:     p.has_discount      ?? false,
            rating:           3,
        })),
    ]),
);

const filteredCategories = categories.filter(
    (category) => (simplifiedProductsByCategory[category.id]?.length ?? 0) >= 2
);
</script>

<template>
    <MainLayout>
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