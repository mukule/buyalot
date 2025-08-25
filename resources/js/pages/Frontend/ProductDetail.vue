<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { Expand, Plus, ShoppingCart, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Props using the normalized structure
const props = defineProps<{
    product: {
        id: number;
        slug: string;
        name: string;
        description?: string;
        features?: string;
        specifications?: string;
        whats_in_the_box?: string;
        primary_image_url?: string | null;
        images: string[];
        brand?: { name: string };
        category_hierarchy?: { id: number; name: string; slug: string }[];
        variants: {
            id: number;
            regular_price: number;
            selling_price: number;
            discount?: number | null;
            stock: number;
        }[];
    };
    relatedProducts?: SimplifiedProduct[];
}>();

// Image preview
const mainImage = ref(props.product.primary_image_url || props.product.images?.[0] || '');
const showPreview = ref(false);
const openPreview = () => (showPreview.value = true);
const closePreview = () => (showPreview.value = false);
const selectImage = (img: string) => (mainImage.value = img);

// Price formatting
const formatPrice = (amount: number | null): string => `KSh ${amount?.toLocaleString() ?? 0}`;

// Compute min selling price and discount
const productPrice = computed(() => {
    if (!props.product.variants.length) return 0;
    return Math.min(...props.product.variants.map((v) => v.selling_price));
});

const productDiscount = computed(() => {
    if (!props.product.variants.length) return null;
    const minRegular = Math.min(...props.product.variants.map((v) => v.regular_price));
    const minSelling = Math.min(...props.product.variants.map((v) => v.selling_price));
    if (minRegular > 0 && minRegular > minSelling) {
        return Math.round(((minRegular - minSelling) / minRegular) * 100);
    }
    return null;
});

const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    (props.relatedProducts ?? []).map((p) => ({
        ...p,
        image: (p as any).primary_image_url || (p as any).image_urls?.[0] || '/fallback-image.png',
        onSale: p.discount ? true : false,
    })),
);
</script>

<template>
    <MainLayout>
        <section class="px-4 py-6">
            <nav class="mb-6 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li>
                        <a href="/" class="text-primary hover:underline">Home</a>
                        <span class="mx-1">/</span>
                    </li>

                    <li v-for="(cat, index) in product.category_hierarchy ?? []" :key="cat.id" class="flex items-center">
                        <a :href="`/category/${cat.slug}`" class="text-primary hover:underline">
                            {{ cat.name }}
                        </a>
                        <span v-if="index < (product.category_hierarchy?.length ?? 0) - 1" class="mx-1">/</span>
                    </li>
                    <li class="truncate font-semibold text-gray-800">/{{ product.name }}</li>
                </ol>
            </nav>

            <div class="flex flex-col gap-6 lg:flex-row">
                <div class="flex w-full flex-col gap-6 lg:w-10/12">
                    <div class="rounded-xl bg-white p-6 shadow transition hover:shadow-md">
                        <div class="flex flex-col gap-6 md:flex-row">
                            <div class="w-full md:w-1/2">
                                <div class="relative">
                                    <img :src="mainImage" class="max-h-[400px] w-full rounded-md object-contain" />
                                    <button
                                        @click="openPreview"
                                        title="Preview Image"
                                        class="bg-opacity-75 hover:bg-opacity-100 absolute top-2 right-2 flex items-center gap-1 rounded bg-white px-3 py-1 text-sm font-semibold text-gray-800 shadow"
                                    >
                                        <Expand class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="mt-4 flex gap-2 overflow-x-auto">
                                    <img
                                        v-for="(img, idx) in product.images"
                                        :key="idx"
                                        :src="img"
                                        @click="selectImage(img)"
                                        :class="[
                                            'h-16 w-16 cursor-pointer rounded border transition',
                                            img === mainImage ? 'border-primary' : 'border-gray-300',
                                        ]"
                                    />
                                </div>
                            </div>

                            <div class="w-full space-y-4 md:w-1/2">
                                <h1 class="text-2xl font-bold text-gray-800">{{ product.name }}</h1>
                                <p class="text-sm text-gray-600">Brand: {{ product.brand?.name }}</p>
                                <hr />
                                <p class="text-lg font-semibold text-primary">
                                    {{ formatPrice(productPrice) }}
                                    <span v-if="productDiscount" class="ml-2 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white">
                                        {{ productDiscount }}% OFF
                                    </span>
                                </p>
                                <div class="flex gap-[2px] text-yellow-400">
                                    <Star v-for="i in 5" :key="i" class="h-5 w-5 fill-yellow-400" />
                                </div>
                                <button
                                    class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                                >
                                    <Plus class="h-4 w-4" />
                                    <ShoppingCart class="h-4 w-4" />
                                    <span>Add to Cart</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="simplifiedRelatedProducts.length">
                        <ProductCarouselSection title="Related Products" :products="simplifiedRelatedProducts" />
                    </div>

                    <div v-if="product.description" class="rounded-xl bg-white p-4 text-sm leading-relaxed shadow">
                        <h3 class="mb-2 font-semibold text-gray-800">Product Description</h3>
                        <p v-html="product.description"></p>
                    </div>

                    <div v-if="product.features || product.specifications" class="rounded-xl bg-white p-4 text-sm leading-relaxed shadow">
                        <div class="flex flex-col gap-6 md:flex-row">
                            <div v-if="product.features" class="rounded bg-transparent p-4 md:w-1/2">
                                <h4 class="mb-2 font-semibold text-gray-700">Features</h4>
                                <div v-html="product.features"></div>
                            </div>
                            <div v-if="product.specifications" class="rounded bg-transparent p-4 md:w-1/2">
                                <h4 class="mb-2 font-semibold text-gray-700">Specifications</h4>
                                <div v-html="product.specifications"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-6 lg:w-2/12">
                    <!-- Delivery Options Card -->
                    <div class="space-y-2 rounded-xl bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Delivery Options</h2>
                        <p class="text-sm text-gray-600">Standard delivery: 2–5 days</p>
                        <p class="text-sm text-gray-600">Express delivery available</p>
                        <p class="text-sm text-gray-600">Free delivery for orders over KSh 5,000</p>
                    </div>

                    <!-- Seller Info Card -->
                    <div class="rounded-xl bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Seller Info</h2>
                        <p class="text-sm text-gray-600">Over Ridge Wood Products</p>
                        <p class="text-sm text-gray-600">Reliable seller with great reviews.</p>
                        <p class="text-sm text-gray-600">Contact: 0712 345 678</p>
                        <button class="mt-3 w-full rounded bg-primary px-3 py-2 text-sm text-white hover:bg-primary/90">View Store</button>
                    </div>

                    <!-- What's in the Box Card -->
                    <div v-if="product.whats_in_the_box" class="rounded-xl bg-white p-4 shadow">
                        <h4 class="mb-2 font-semibold text-gray-700">What's in the Box</h4>
                        <p class="text-sm leading-relaxed text-gray-600" v-html="product.whats_in_the_box"></p>
                    </div>
                </div>
            </div>

            <div v-if="showPreview" @click.self="closePreview" class="bg-opacity-70 fixed inset-0 z-50 flex items-center justify-center bg-black p-4">
                <button @click="closePreview" class="absolute top-4 right-4 rounded bg-white px-3 py-1 text-gray-800 hover:bg-gray-200">Close</button>
                <img :src="mainImage" alt="Preview Image" class="max-h-[90vh] max-w-full rounded-md shadow-lg" />
            </div>
        </section>
    </MainLayout>
</template>
