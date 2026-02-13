<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    parent?: Category | null;
}

interface VariantImage {
    id: number;
    url: string;
    is_primary: boolean;
    sort_order: number;
    alt_text?: string;
}

interface Variant {
    id: number;
    buying_price: number;
    marked_price: number;
    stock: number;
    sku: string;
    values: { variant_category_id: number; value: string }[];
    images: VariantImage[];
}

interface Product {
    id: number;
    hashid: string;
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
    stock?: number;
}

const props = defineProps<{ product: Product }>();

// Active tab state
const activeTab = ref<'description' | 'features' | 'specifications'>('description');

// Selected variant state
const selectedVariant = ref<Variant | null>(props.product.variants?.[0] || null);

// Current images - combines product and variant images
const currentImages = computed(() => {
    if (selectedVariant.value?.images?.length) {
        // If variant has images, show those
        return selectedVariant.value.images.map((img) => img.url);
    }
    // Otherwise show product images
    return props.product.image_urls || [];
});

// Main image display
const mainImageIndex = ref(0);
const mainImage = computed(() => currentImages.value[mainImageIndex.value] || props.product.primary_image_url || '');

// Watch for variant changes to reset image index
watch(selectedVariant, () => {
    mainImageIndex.value = 0;
});

// Image preview modal
const showPreview = ref(false);
const openPreview = () => (showPreview.value = true);
const closePreview = () => (showPreview.value = false);
const selectImage = (index: number) => (mainImageIndex.value = index);

// Navigate images
const nextImage = () => {
    if (mainImageIndex.value < currentImages.value.length - 1) {
        mainImageIndex.value++;
    }
};
const prevImage = () => {
    if (mainImageIndex.value > 0) {
        mainImageIndex.value--;
    }
};

// Price formatting
const formatPrice = (amount?: number) => (amount != null ? `KSh ${amount.toLocaleString()}` : '-');

// Calculate discount %
const calcDiscount = (regular: number, selling: number) => {
    if (!regular || !selling || selling >= regular) return null;
    return Math.round(((regular - selling) / regular) * 100);
};

// Current prices based on selected variant
const currentPrice = computed(() => selectedVariant.value?.buying_price || 0);
const markedPrice = computed(() => selectedVariant.value?.marked_price || 0);
const discount = computed(() => calcDiscount(markedPrice.value, currentPrice.value));
const currentStock = computed(() => selectedVariant.value?.stock || 0);

// Get unique variant attributes (e.g., Color, Size)
const variantAttributes = computed(() => {
    if (!props.product.variants?.length) return [];

    const attributesMap = new Map<number, { id: number; values: Set<string> }>();

    props.product.variants.forEach((variant) => {
        variant.values.forEach((val) => {
            if (!attributesMap.has(val.variant_category_id)) {
                attributesMap.set(val.variant_category_id, {
                    id: val.variant_category_id,
                    values: new Set(),
                });
            }
            attributesMap.get(val.variant_category_id)?.values.add(val.value);
        });
    });

    return Array.from(attributesMap.values()).map((attr) => ({
        id: attr.id,
        values: Array.from(attr.values),
    }));
});

// Selected attribute values
const selectedAttributes = ref<Record<number, string>>({});

// Initialize selected attributes with first variant
if (selectedVariant.value) {
    selectedVariant.value.values.forEach((val) => {
        selectedAttributes.value[val.variant_category_id] = val.value;
    });
}

// Update variant when attributes change
const selectAttribute = (categoryId: number, value: string) => {
    selectedAttributes.value[categoryId] = value;

    // Find matching variant
    const matchingVariant = props.product.variants?.find((variant) => {
        return variant.values.every((val) => selectedAttributes.value[val.variant_category_id] === val.value);
    });

    if (matchingVariant) {
        selectedVariant.value = matchingVariant;
    }
};

// Check if attribute option is available
const isAttributeAvailable = (categoryId: number, value: string) => {
    return (
        props.product.variants?.some((variant) => {
            const hasValue = variant.values.some((v) => v.variant_category_id === categoryId && v.value === value);
            const matchesOthers = variant.values.every(
                (v) => v.variant_category_id === categoryId || selectedAttributes.value[v.variant_category_id] === v.value,
            );
            return hasValue && matchesOthers && variant.stock > 0;
        }) || false
    );
};

// Button handlers
const editProduct = () => {
    router.get(route('admin.products.edit', { product: props.product.hashid }));
};

const backToProducts = () => {
    router.get('/admin/products');
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/' },
            { title: 'Products', href: '/admin/products' },
            { title: props.product.name, href: '' },
        ]"
    >
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header Actions -->
                <div class="mb-6 flex items-center justify-between">
                    <button
                        @click="backToProducts"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Products
                    </button>
                    <button
                        @click="editProduct"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary/90"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                        Edit Product
                    </button>
                </div>

                <!-- Main Product Section -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                    <!-- Left: Images -->
                    <div class="lg:col-span-7">
                        <div class="sticky top-8 rounded-xl bg-white p-6 shadow-sm">
                            <!-- Main Image -->
                            <div class="relative mb-4 overflow-hidden rounded-lg bg-gray-100">
                                <img :src="mainImage" :alt="props.product.name" class="h-[500px] w-full object-contain" />

                                <!-- Image Navigation -->
                                <button
                                    v-if="currentImages.length > 1 && mainImageIndex > 0"
                                    @click="prevImage"
                                    class="absolute top-1/2 left-4 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow-lg transition hover:bg-white"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <button
                                    v-if="currentImages.length > 1 && mainImageIndex < currentImages.length - 1"
                                    @click="nextImage"
                                    class="absolute top-1/2 right-4 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow-lg transition hover:bg-white"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                                <!-- Preview Button -->
                                <button
                                    @click="openPreview"
                                    class="absolute top-4 right-4 flex items-center gap-2 rounded-lg bg-white/90 px-3 py-2 text-sm font-medium shadow-lg transition hover:bg-white"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                                        />
                                    </svg>
                                    Zoom
                                </button>
                            </div>

                            <!-- Thumbnail Gallery -->
                            <div v-if="currentImages.length > 1" class="flex gap-2 overflow-x-auto pb-2">
                                <button
                                    v-for="(img, idx) in currentImages"
                                    :key="idx"
                                    @click="selectImage(idx)"
                                    :class="[
                                        'h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg border-2 transition',
                                        idx === mainImageIndex ? 'border-primary ring-2 ring-primary/20' : 'border-gray-200 hover:border-gray-300',
                                    ]"
                                >
                                    <img :src="img" class="h-full w-full object-cover" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Product Details -->
                    <div class="lg:col-span-5">
                        <div class="rounded-xl bg-white p-6 shadow-sm">
                            <!-- Product Name -->
                            <h1 class="mb-3 text-3xl font-bold text-gray-900">{{ props.product.name }}</h1>

                            <!-- Brand & Code -->
                            <div class="mb-4 flex flex-wrap gap-4 text-sm text-gray-600">
                                <div v-if="props.product.brand" class="flex items-center gap-2">
                                    <span class="font-medium">Brand:</span>
                                    <span class="rounded-full bg-gray-100 px-3 py-1">{{ props.product.brand.name }}</span>
                                </div>
                                <div v-if="props.product.product_code" class="flex items-center gap-2">
                                    <span class="font-medium">SKU:</span>
                                    <span class="font-mono">{{ props.product.product_code }}</span>
                                </div>
                            </div>

                            <!-- Price Section -->
                            <div class="mb-6 rounded-lg bg-gray-50 p-4">
                                <div class="flex items-baseline gap-3">
                                    <span class="text-3xl font-bold text-gray-900">{{ formatPrice(currentPrice) }}</span>
                                    <span v-if="discount" class="text-lg text-gray-500 line-through">{{ formatPrice(markedPrice) }}</span>
                                    <span v-if="discount" class="rounded-full bg-red-100 px-2 py-1 text-sm font-semibold text-red-700">
                                        -{{ discount }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Stock Status -->
                            <div class="mb-6">
                                <div
                                    :class="[
                                        'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium',
                                        currentStock > 10
                                            ? 'bg-green-100 text-green-800'
                                            : currentStock > 0
                                              ? 'bg-yellow-100 text-yellow-800'
                                              : 'bg-red-100 text-red-800',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            currentStock > 10 ? 'bg-green-600' : currentStock > 0 ? 'bg-yellow-600' : 'bg-red-600',
                                        ]"
                                    ></div>
                                    <span v-if="currentStock > 10">In Stock ({{ currentStock }} available)</span>
                                    <span v-else-if="currentStock > 0">Low Stock ({{ currentStock }} left)</span>
                                    <span v-else>Out of Stock</span>
                                </div>
                            </div>

                            <!-- Variant Selection -->
                            <div v-if="props.product.variants && props.product.variants.length > 1" class="mb-6 space-y-4">
                                <div v-for="attribute in variantAttributes" :key="attribute.id" class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700"> Select Option </label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="value in attribute.values"
                                            :key="value"
                                            @click="selectAttribute(attribute.id, value)"
                                            :disabled="!isAttributeAvailable(attribute.id, value)"
                                            :class="[
                                                'rounded-lg border-2 px-4 py-2 text-sm font-medium transition',
                                                selectedAttributes[attribute.id] === value
                                                    ? 'border-primary bg-primary text-white'
                                                    : isAttributeAvailable(attribute.id, value)
                                                      ? 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                                                      : 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 line-through',
                                            ]"
                                        >
                                            {{ value }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Variant Details -->
                            <div v-if="selectedVariant" class="mb-6 rounded-lg border border-gray-200 p-4">
                                <h3 class="mb-3 text-sm font-semibold text-gray-700">Selected Variant Details</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">SKU:</span>
                                        <span class="font-mono font-medium">{{ selectedVariant.sku }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Stock:</span>
                                        <span class="font-medium">{{ selectedVariant.stock }} units</span>
                                    </div>
                                    <div v-if="selectedVariant.values.length" class="flex justify-between">
                                        <span class="text-gray-600">Variant:</span>
                                        <span class="font-medium">
                                            {{ selectedVariant.values.map((v) => v.value).join(', ') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Owner Info (Admin) -->
                            <div v-if="props.product.owner" class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <h3 class="mb-2 text-sm font-semibold text-gray-700">Owner Information</h3>
                                <div class="space-y-1 text-sm">
                                    <p>
                                        <span class="text-gray-600">Name:</span> <span class="font-medium">{{ props.product.owner.name }}</span>
                                    </p>
                                    <p v-if="props.product.owner.roles?.length">
                                        <span class="text-gray-600">Roles:</span>
                                        <span class="font-medium">{{ props.product.owner.roles.join(', ') }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- What's in the Box -->
                        <div v-if="props.product.whats_in_the_box" class="mt-6 rounded-xl bg-white p-6 shadow-sm">
                            <h3 class="mb-3 text-lg font-semibold text-gray-900">What's in the Box</h3>
                            <div v-html="props.product.whats_in_the_box" class="prose prose-sm max-w-none text-gray-700"></div>
                        </div>
                    </div>
                </div>

                <!-- Product Information Tabs -->
                <div class="mt-8 rounded-xl bg-white shadow-sm">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-8 px-6" aria-label="Tabs">
                            <button
                                @click="activeTab = 'description'"
                                :class="[
                                    'border-b-2 px-1 py-4 text-sm font-medium transition',
                                    activeTab === 'description'
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                            >
                                Description
                            </button>
                            <button
                                v-if="props.product.features"
                                @click="activeTab = 'features'"
                                :class="[
                                    'border-b-2 px-1 py-4 text-sm font-medium transition',
                                    activeTab === 'features'
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                            >
                                Features
                            </button>
                            <button
                                v-if="props.product.specifications"
                                @click="activeTab = 'specifications'"
                                :class="[
                                    'border-b-2 px-1 py-4 text-sm font-medium transition',
                                    activeTab === 'specifications'
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                ]"
                            >
                                Specifications
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Description Tab -->
                        <div v-show="activeTab === 'description'">
                            <div v-if="props.product.description" class="prose max-w-none">
                                <div v-html="props.product.description"></div>
                            </div>
                            <div v-else class="text-gray-500">No description available.</div>
                        </div>

                        <!-- Features Tab -->
                        <div v-show="activeTab === 'features'" v-if="props.product.features">
                            <div class="prose max-w-none">
                                <div v-html="props.product.features"></div>
                            </div>
                        </div>

                        <!-- Specifications Tab -->
                        <div v-show="activeTab === 'specifications'" v-if="props.product.specifications">
                            <div class="prose max-w-none">
                                <div v-html="props.product.specifications"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showPreview"
                @click.self="closePreview"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
            >
                <button @click="closePreview" class="absolute top-4 right-4 rounded-full bg-white p-2 shadow-lg transition hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img :src="mainImage" alt="Preview" class="max-h-[90vh] max-w-full rounded-lg shadow-2xl" />
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar for thumbnail gallery */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
