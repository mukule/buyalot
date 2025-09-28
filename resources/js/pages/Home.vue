<script setup lang="ts">
import CategorySidebar from '@/components/CategorySidebar.vue';
import FeaturedBrands from '@/components/FeaturedBrands.vue';
import Hero from '@/components/Hero.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
console.log('Available routes from Ziggy:', route().routes);
console.log('Has login:', route().has('login'));
console.log('Has register:', route().has('register'));
console.log('Has password.request:', route().has('password.request'));
interface PageProps extends InertiaPageProps {
    categories?: string[];
    banners?: string[];
}

const DEFAULT_CATEGORIES = [
    'Electronics',
    'Apparel',
    'Home & Garden',
    'Beauty',
    'Sports',
    'Toys',
    'Books',
    'Automotive',
    'Health & Wellness',
    'Office Supplies',
    'Groceries',
    'Pet Supplies',
    'Gaming',
    'Baby & Kids',
];

const DEFAULT_BANNERS = [
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748506085/xnghnkspb2gxrem9ccid.png',
    'https://media.takealot.com/b/2/cms/p/1292x300/smart/filters:format(jpeg):background_color(white):focal(483x0:809x300)/original_images/95f37d1908b3b66ae8ee2cf8ec208cc36e46b576.png',
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748252952/vopuvo5dm6dexmsbotrm.png',
    'https://osx-tal.gumlet.io/onlinesales/image/upload/v1748869172/nncagedishwuq8urp7ps.png',
];

const BRANDS = [
    'https://media.takealot.com/b/2/cms/p/fit-in/160x70/original_images/47b3431f312335ff9daa470c2fd0fb34d4149742.png',
    'https://media.takealot.com/b/2/cms/p/fit-in/160x70/original_images/33f4e720884791b96f97c74f54ae99c66f523936.png',
    'https://media.takealot.com/b/2/cms/p/fit-in/160x70/original_images/82cae5916932e63c0720addfdba0a473c2a0a423.png',
    'https://media.takealot.com/b/2/cms/p/fit-in/160x70/original_images/0746e1b6e28d6b6370da20f07f9d592af6363d50.png',
];

const SAMPLE_PRODUCTS = [
    { id: 1, name: 'Wireless Headphones', price: 2499, image: 'https://media.takealot.com/covers_tsins/45345712/ps4slim222-zoom.jpg' },
    { id: 2, name: 'Smart Watch', price: 3999, image: 'https://media.takealot.com/covers_images/57c72b6896ff4e458d691f53ff352cc1/s-pdpxl.file' },
    { id: 3, name: 'Bluetooth Speaker', price: 1799, image: 'https://media.takealot.com/covers_images/eb7a3b56c8b94da6b1652f6a4e248541/s-zoom.file' },
    { id: 4, name: 'Power Bank', price: 1299, image: 'https://media.takealot.com/covers_images/0f93edb5d6f84196ae910320b4e57bb6/s-zoom.file' },
    { id: 5, name: 'USB-C Cable', price: 499, image: 'https://media.takealot.com/covers_images/992e4b2a8a53464ba73549978cd10bb3/s-zoom.file' },
    { id: 6, name: 'Wireless Mouse', price: 899, image: 'https://media.takealot.com/covers_tsins/51362659/51362659-1-pdpxl.jpeg' },
    { id: 7, name: 'Keyboard', price: 1599, image: 'https://media.takealot.com/covers_tsins/45345712/ps4slim222-zoom.jpg' },
    { id: 8, name: 'Monitor', price: 12999, image: 'https://media.takealot.com/covers_images/57c72b6896ff4e458d691f53ff352cc1/s-pdpxl.file' },
].map((p) => ({
    ...p,
    onSale: Math.random() < 0.5,
    rating: Math.floor(Math.random() * 3) + 3,
}));

const page = usePage<PageProps>();
const categories = page.props.categories ?? DEFAULT_CATEGORIES;
const banners = page.props.banners ?? DEFAULT_BANNERS;
</script>

<template>
    <MainLayout>
        <section class="mt-4 mb-4 flex flex-col gap-6 bg-white lg:flex-row">
            <CategorySidebar :categories="categories" class="hidden lg:block" />
            <div class="w-full lg:w-[79.17%]">
                <Hero :banners="banners" link="/your-link-here" />
                <FeaturedBrands :logos="BRANDS" />
            </div>
        </section>

        <section class="space-y-10 px-4">
            <ProductCarouselSection
                v-for="(category, index) in categories.slice(0, 4)"
                :key="category"
                :title="category"
                :products="SAMPLE_PRODUCTS"
            />
        </section>
    </MainLayout>
</template>
