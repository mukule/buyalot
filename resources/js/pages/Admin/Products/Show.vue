<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3'; // <-- Inertia router
import { computed, ref } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    parent?: Category | null;
}

interface Variant {
    id: number;
    regular_price: number;
    selling_price: number;
    stock: number;
    sku: string;
    values: { variant_category_id: number; value: string }[];
}

interface Product {
    id: number;
    name: string;
    product_code?: string;
    primary_image_url?: string | null;
    image_urls?: string[];
    category_hierarchy?: Category[];
    brand?: { name: string };
    owner?: { name: string; roles: string[] };
    variants?: Variant[];
    description?: string;
    features?: string;
    specifications?: string;
    whats_in_the_box?: string;
}

const props = defineProps<{ product: Product }>();

// Image selection & preview
const mainImage = ref(props.product.primary_image_url || props.product.image_urls?.[0] || '');
const showPreview = ref(false);
const openPreview = () => (showPreview.value = true);
const closePreview = () => (showPreview.value = false);
const selectImage = (img: string) => (mainImage.value = img);

// Price formatting
const formatPrice = (amount?: number) => (amount != null ? `KSh ${amount.toLocaleString()}` : '-');

// Category breadcrumb
const categoryBreadcrumb = computed(() => {
    const items: { title: string; href: string | null }[] = [];
    if (props.product.category_hierarchy?.length) {
        props.product.category_hierarchy.forEach((cat) => {
            items.push({ title: cat.name, href: `/category/${cat.slug}` });
        });
    }
    return items;
});

// Calculate discount %
const calcDiscount = (regular: number, selling: number) => {
    if (!regular || !selling || selling >= regular) return null;
    return Math.round(((regular - selling) / regular) * 100);
};

// Button handlers using Inertia
const editProduct = () => {
    router.get(`/products/${props.product.id}/edit`);
};

const backToProducts = () => {
    router.get('/admin/products');
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/' },
            { title: 'Products', href: '/products' },
        ]"
    >
        <section class="px-4 py-6">
            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm text-gray-600">
                <ol class="flex flex-wrap items-center gap-1">
                    <li v-for="(item, idx) in categoryBreadcrumb" :key="idx" class="flex items-center">
                        <template v-if="item.href">
                            <a :href="item.href" class="text-primary hover:underline">{{ item.title }}</a>
                        </template>
                        <template v-else>
                            <span class="font-semibold text-gray-800">{{ item.title }}</span>
                        </template>
                        <span v-if="idx < categoryBreadcrumb.length - 1" class="mx-1">/</span>
                    </li>
                    <li class="flex items-center">
                        <span class="mx-1">/</span>
                        <span class="font-semibold text-gray-800">{{ props.product.name }}</span>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- Left Column -->
                <div class="flex w-full flex-col gap-6 lg:w-10/12">
                    <div class="flex justify-between">
                        <button @click="backToProducts" class="rounded bg-gray-200 px-4 py-2 text-sm font-semibold hover:bg-gray-300">Back</button>
                        <button @click="editProduct" class="rounded bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                            Edit Product
                        </button>
                    </div>

                    <div class="rounded-xl bg-white p-6 shadow">
                        <div class="flex flex-col gap-6 md:flex-row">
                            <!-- Images -->
                            <div class="w-full md:w-1/2">
                                <div class="relative">
                                    <img :src="mainImage" class="max-h-[400px] w-full rounded-md object-contain" />
                                    <button
                                        @click="openPreview"
                                        title="Preview Image"
                                        class="bg-opacity-75 hover:bg-opacity-100 absolute top-2 right-2 flex items-center gap-1 rounded bg-white px-3 py-1 text-sm font-semibold text-gray-800 shadow"
                                    >
                                        Preview
                                    </button>
                                </div>
                                <div class="mt-4 flex gap-2 overflow-x-auto">
                                    <img
                                        v-for="(img, idx) in props.product.image_urls ?? []"
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

                            <!-- Info -->
                            <div class="flex w-full flex-col gap-2 md:w-1/2">
                                <h1 class="text-2xl font-bold text-gray-800">{{ props.product.name }}</h1>
                                <p><strong>Product Code:</strong> {{ props.product.product_code ?? '-' }}</p>
                                <p><strong>Brand:</strong> {{ props.product.brand?.name ?? '-' }}</p>
                                <p><strong>Owner:</strong> {{ props.product.owner?.name ?? '-' }}</p>
                                <p v-if="props.product.owner?.roles"><strong>Owner Roles:</strong> {{ props.product.owner.roles.join(', ') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="props.product.description" class="rounded-xl bg-white p-4 shadow">
                        <h3 class="mb-2 font-semibold text-gray-800">Description</h3>
                        <p v-html="props.product.description"></p>
                    </div>

                    <!-- Features & Specifications -->
                    <div v-if="props.product.features || props.product.specifications" class="rounded-xl bg-white p-4 shadow">
                        <div class="flex flex-col gap-6 md:flex-row">
                            <div v-if="props.product.features" class="rounded border border-gray-300 p-4 md:w-1/2">
                                <h4 class="mb-2 font-semibold text-gray-700">Features</h4>
                                <div v-html="props.product.features"></div>
                            </div>
                            <div v-if="props.product.specifications" class="rounded border border-gray-300 p-4 md:w-1/2">
                                <h4 class="mb-2 font-semibold text-gray-700">Specifications</h4>
                                <div v-html="props.product.specifications"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="flex w-full flex-col gap-6 lg:w-2/12">
                    <!-- Variants -->
                    <div class="rounded-xl border bg-white p-3 shadow-sm">
                        <div v-if="props.product.variants?.length" class="space-y-2">
                            <div v-for="variant in props.product.variants" :key="variant.id">
                                <p><strong>SKU:</strong> {{ variant.sku }}</p>
                                <p><strong>Stock:</strong> {{ variant.stock }}</p>
                                <p>
                                    <strong>Price:</strong>
                                    <span>{{ formatPrice(variant.selling_price) }}</span>
                                    <span
                                        v-if="variant.regular_price && variant.selling_price < variant.regular_price"
                                        class="ml-2 text-sm text-gray-500 line-through"
                                    >
                                        {{ formatPrice(variant.regular_price) }}
                                    </span>
                                    <span v-if="calcDiscount(variant.regular_price, variant.selling_price)" class="ml-1 text-sm text-green-600">
                                        ({{ calcDiscount(variant.regular_price, variant.selling_price) }}% off)
                                    </span>
                                </p>
                                <p v-if="variant.values.length">
                                    <strong>Attributes:</strong>
                                    <span v-for="(val, idx) in variant.values" :key="val.variant_category_id">
                                        {{ val.value }}<span v-if="idx < variant.values.length - 1">, </span>
                                    </span>
                                </p>
                                <hr />
                            </div>
                        </div>
                        <div v-else class="rounded border p-4 text-gray-500">No variants available</div>
                    </div>

                    <!-- What's in the Box -->
                    <div v-if="props.product.whats_in_the_box" class="rounded-xl bg-white p-4 shadow">
                        <h4 class="mb-2 font-semibold text-gray-700">What's in the Box</h4>
                        <p v-html="props.product.whats_in_the_box"></p>
                    </div>
                </div>
            </div>

            <!-- Image Preview Modal -->
            <div v-if="showPreview" @click.self="closePreview" class="bg-opacity-70 fixed inset-0 z-50 flex items-center justify-center bg-black p-4">
                <button @click="closePreview" class="absolute top-4 right-4 rounded bg-white px-3 py-1 text-gray-800 hover:bg-gray-200">Close</button>
                <img :src="mainImage" alt="Preview Image" class="max-h-[90vh] max-w-full rounded-md shadow-lg" />
            </div>
        </section>
    </AppLayout>
</template>
