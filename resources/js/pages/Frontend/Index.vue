<script setup lang="ts">
import CategorySidebar from '@/components/CategorySidebar.vue';
import FeaturedBrands from '@/components/FeaturedBrands.vue';
import Hero from '@/components/Hero.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';

// ✅ Shared types
import type { Brand, Category, SimplifiedProduct } from '@/types';

interface PageProps extends InertiaPageProps {
    categories?: Category[];
    banners?: string[];
    brands?: Brand[];
    productsByCategory?: Record<number, any[]>;
}

// Default banners
const DEFAULT_BANNERS = [
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748506085/xnghnkspb2gxrem9ccid.png',
    'https://media.takealot.com/b/2/cms/p/1292x300/smart/filters:format(jpeg):background_color(white):focal(483x0:809x300)/original_images/95f37d1908b3b66ae8ee2cf8ec208cc36e46b576.png',
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748252952/vopuvo5dm6dexmsbotrm.png',
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748869172/nncagedishwuq8urp7ps.png',
];

const page = usePage<PageProps>();

const categories = page.props.categories ?? [];
const banners = page.props.banners ?? DEFAULT_BANNERS;
const brands = page.props.brands ?? [];
const productsByCategory = page.props.productsByCategory ?? {};

// Transform backend variant data to simplified frontend structure
const simplifiedProductsByCategory: Record<number, SimplifiedProduct[]> = Object.fromEntries(
    Object.entries(productsByCategory).map(([categoryId, products]) => [
        Number(categoryId),
        (products as any[]).map((p) => ({
            id: p.id,
            hashid: p.variant_hashid,
            product_slug: p.product_slug,
            name: p.name,
            brand: p.brand ?? null,
            image: p.primary_image_url || '/fallback-image.png',
            marked_price: p.marked_price ?? 0,
            final_price: p.final_price ?? 0,
            discount_percent: p.discount_percent ?? 0,
            has_discount: p.has_discount ?? false,
            rating: 3,
        })),
    ]),
);

// Only show categories with at least 2 products
const filteredCategories = categories.filter((category) => (simplifiedProductsByCategory[category.id]?.length ?? 0) >= 2);
</script>

<template>
    <MainLayout>
        <!-- Row 1: Sidebar + Banner -->
        <section class="mt-4 mb-4 flex flex-col gap-4 lg:flex-row lg:items-stretch">
            <!-- Categories Sidebar -->
            <div class="w-full lg:w-2/12">
                <div class="h-full">
                    <CategorySidebar :categories="categories" class="hidden lg:block" />
                </div>
            </div>

            <!-- Hero Banner -->
            <div class="w-full lg:w-10/12">
                <div class="relative h-full overflow-hidden rounded-lg shadow">
                    <Hero :banners="banners" link="/" class="h-full" animation="fade" lazy />
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
.h-full {
    height: 100%;
}

/* Hero banner fade animation */
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
