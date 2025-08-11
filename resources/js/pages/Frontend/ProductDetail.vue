<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { Expand, Plus, ShoppingCart, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    discounted_price: number;
    has_discount: boolean;
    discount?: number;
    description?: string;
    features?: string;
    whats_in_the_box?: string;
    specifications?: string;
    primary_image_url?: string | null;
    image_urls?: string[];
    brand?: { name: string };
    subcategory?: {
        name: string;
        slug?: string;
        category?: {
            name: string;
            slug?: string;
        };
    };
}

interface SimplifiedProduct {
    id: number;
    name: string;
    slug: string;
    price: number;
    discountedPrice: number;
    hasDiscount: boolean;
    discount?: number;
    image: string;
    onSale?: boolean;
    rating?: number;
}

const props = defineProps<{
    product: Product;
    relatedProducts?: Product[];
}>();

const formatPrice = (amount: number): string => `KSh ${amount.toLocaleString()}`;

const slugify = (text: string): string =>
    text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');

const mainImage = ref(props.product.primary_image_url || props.product.image_urls?.[0] || '');
const showPreview = ref(false);

const openPreview = () => (showPreview.value = true);
const closePreview = () => (showPreview.value = false);
const selectImage = (img: string) => (mainImage.value = img);

const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    (props.relatedProducts ?? []).map((p) => ({
        id: p.id,
        name: p.name,
        slug: p.slug,
        price: p.price,
        discountedPrice: p.discounted_price ?? p.price,
        hasDiscount: p.has_discount ?? false,
        discount: p.discount ?? 0,
        image: p.primary_image_url || (p.image_urls && p.image_urls[0]) || '/fallback-image.png',
        onSale: (p.has_discount ?? false) && (p.discounted_price ?? 0) < p.price,
        rating: 3,
    })),
);
</script>

<template>
    <MainLayout>
        <section class="px-4 py-6">
            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li><a href="/" class="text-primary hover:underline">Home</a><span class="mx-1">/</span></li>
                    <li v-if="product.subcategory?.category?.name">
                        <a
                            :href="`/category/${product.subcategory.category.slug ?? slugify(product.subcategory.category.name)}`"
                            class="text-primary hover:underline"
                        >
                            {{ product.subcategory.category.name }}
                        </a>
                        <span class="mx-1">/</span>
                    </li>
                    <li v-if="product.subcategory?.name">
                        <a
                            :href="`/subcategory/${product.subcategory.slug ?? slugify(product.subcategory.name)}`"
                            class="text-primary hover:underline"
                        >
                            {{ product.subcategory.name }}
                        </a>
                        <span class="mx-1">/</span>
                    </li>
                    <li class="truncate font-semibold text-gray-800">{{ product.name }}</li>
                </ol>
            </nav>

            <div class="flex flex-col gap-6 lg:flex-row">
                <div class="flex w-full flex-col gap-6 lg:w-10/12">
                    <!-- Product details card -->
                    <div class="rounded-xl bg-white p-6 shadow">
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
                                        v-for="(img, idx) in product.image_urls ?? []"
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
                                    {{ formatPrice(product.discounted_price) }}
                                    <span v-if="product.has_discount" class="ml-2 text-sm text-gray-400 line-through">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                    <span v-if="product.discount" class="ml-2 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white">
                                        {{ product.discount }}% OFF
                                    </span>
                                </p>
                                <div class="flex gap-[2px] text-yellow-400">
                                    <Star v-for="i in 5" :key="i" :class="i <= 3 ? 'fill-yellow-400' : 'fill-gray-200'" class="h-5 w-5" />
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

                    <div v-if="simplifiedRelatedProducts.length" class="rounded-xl bg-white p-4 shadow">
                        <ProductCarouselSection title="Related Products" :products="simplifiedRelatedProducts" />
                    </div>

                    <div v-if="product.description" class="rounded-xl bg-white p-4 text-sm leading-relaxed shadow">
                        <h3 class="mb-2 font-semibold text-gray-800">Product Description</h3>
                        <p v-html="product.description"></p>
                    </div>

                    <div v-if="product.features || product.specifications" class="rounded-xl bg-white p-4 text-sm leading-relaxed shadow">
                        <div class="flex flex-col gap-6 md:flex-row">
                            <!-- Features -->
                            <div v-if="product.features" class="rounded border border-gray-300 bg-transparent p-4 md:w-1/2">
                                <h4 class="mb-2 font-semibold text-gray-700">Features</h4>
                                <div v-html="product.features"></div>
                            </div>

                            <div v-if="product.specifications" class="rounded border border-gray-300 bg-transparent p-4 md:w-1/2">
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
