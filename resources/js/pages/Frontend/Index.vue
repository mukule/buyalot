<script setup lang="ts">
import CategorySidebar from '@/components/CategorySidebar.vue';
import FeaturedBrands from '@/components/FeaturedBrands.vue';
import Hero from '@/components/Hero.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';

// ✅ Shared types
import type { Brand, Category, Product, SimplifiedProduct } from '@/types';

interface PageProps extends InertiaPageProps {
    categories?: Category[];
    banners?: string[];
    brands?: Brand[];
    productsByCategory?: Record<number, Product[]>;
}

const DEFAULT_BANNERS = [
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748506085/xnghnkspb2gxrem9ccid.png',
    'https://media.takealot.com/b/2/cms/p/1292x300/smart/filters:format(jpeg):background_color(white):focal(483x0:809x300)/original_images/95f37d1908b3b66ae8ee2cf8ec208cc36e46b576.png',
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748252952/vopuvo5dm6dexmsbotrm.png',
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748869172/nncagedishwuq8urp7ps.png',
];

const page = usePage<PageProps>();

// ✅ Extract props safely
const categories = page.props.categories ?? [];
const banners = page.props.banners ?? DEFAULT_BANNERS;
const brands = page.props.brands ?? [];
const productsByCategory = page.props.productsByCategory ?? {};

// ✅ Normalize backend → SimplifiedProduct
const simplifiedProductsByCategory: Record<number, SimplifiedProduct[]> = Object.fromEntries(
    Object.entries(productsByCategory).map(([categoryId, products]) => [
        Number(categoryId),
        (products as Product[]).map((p) => ({
            id: p.id,
            slug: p.slug,
            name: p.name,
            image: p.primary_image_url || '/fallback-image.png', // ✅ FIXED
            regular_price: p.regular_price ?? null,
            selling_price: p.selling_price ?? null,
            discount: p.discount ?? null,
            rating: 3,
        })),
    ]),
);

const filteredCategories = categories.filter((category) => (simplifiedProductsByCategory[category.id]?.length ?? 0) >= 2);
</script>

<template>
    <MainLayout>
        <!-- Sidebar + Hero + Brands -->
        <section class="mt-4 mb-4 flex flex-col gap-6 bg-white lg:flex-row">
            <CategorySidebar :categories="categories" class="hidden lg:block" />
            <div class="w-full lg:w-[79.17%]">
                <Hero :banners="banners" link="/your-link-here" />
                <FeaturedBrands :brands="brands" />
            </div>
        </section>

        <!-- Product Carousels -->
        <section class="space-y-10 px-4">
            <ProductCarouselSection
                v-for="category in filteredCategories"
                :key="category.id"
                :title="category.name"
                :products="simplifiedProductsByCategory[category.id] || []"
            />
        </section>
    </MainLayout>
</template>
