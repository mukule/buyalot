<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Expand, Info, Minus, Plus, ShoppingCart, Star } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

// --- Props ---
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
        owner?: {
            name?: string | null;
            company_legal_name?: string | null;
            type?: string | null;
        };
        warranty?: {
            id: number;
            duration: string;
            description: string;
        } | null;
        variants: {
            id: number;
            marked_price: number;
            final_price: number;
            discount_percent?: number | null;
            has_discount?: boolean;
            stock: number;
        }[];
    };
    relatedProducts?: SimplifiedProduct[];
}>();

// --- IMAGE PREVIEW ---
const mainImage = ref(props.product.primary_image_url || props.product.images?.[0] || '');
const showPreview = ref(false);
const openPreview = () => (showPreview.value = true);
const closePreview = () => (showPreview.value = false);
const selectImage = (img: string) => (mainImage.value = img);

// --- VARIANT HANDLING ---
const params = new URLSearchParams(window.location.search);
const variantId = params.get('v');
const selectedVariant = ref<any>(null);

onMounted(() => {
    if (variantId) {
        const found = props.product.variants.find((v) => v.id == Number(variantId));
        selectedVariant.value = found ?? props.product.variants[0];
    } else {
        selectedVariant.value = props.product.variants[0];
    }
});

// --- PRICE / DISCOUNT ---
const formatPrice = (amount: number | null): string => `KSh ${amount?.toLocaleString() ?? 0}`;

const displayPrice = computed(() => {
    if (!selectedVariant.value) return null;
    return {
        marked_price: selectedVariant.value.marked_price,
        final_price: selectedVariant.value.final_price,
        discount_percent: selectedVariant.value.discount_percent,
        has_discount: selectedVariant.value.has_discount,
    };
});

// --- OWNER INFO ---
const ownerInfo = computed(() => {
    const owner = props.product.owner;
    return {
        type: owner?.type ?? 'Unknown',
        displayName: owner?.company_legal_name ?? owner?.name ?? 'Unknown Seller',
    };
});

// --- WARRANTY INFO ---
const warrantyInfo = computed(() => {
    const warranty = props.product.warranty;
    return warranty
        ? {
              id: warranty.id,
              duration: warranty.duration,
              description: warranty.description,
          }
        : null;
});

// --- RELATED PRODUCTS ---
const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    (props.relatedProducts ?? []).map((p) => ({
        ...p,
        image: (p as any).primary_image_url || (p as any).image_urls?.[0] || '/fallback-image.png',
        onSale: (p as any).has_discount ?? false,
    })),
);

// --- CART HANDLING ---
const page = usePage();
const auth = computed<any>(() => page.props.auth ?? {});
const cartItems = computed(() => auth.value.cartItems ?? []);

const currentCartItem = computed(() => {
    if (!selectedVariant.value) return null;
    return cartItems.value.find((i: any) => i.product_variant_id === selectedVariant.value.id);
});

const addToCart = () => {
    if (!selectedVariant.value) return;
    router.post(route('cart.store'), { product_variant_id: selectedVariant.value.id, quantity: 1 }, { preserveScroll: true });
};

const increaseQty = () => {
    if (!selectedVariant.value || !currentCartItem.value) return;
    router.post(
        route('cart.store'),
        { product_variant_id: selectedVariant.value.id, quantity: currentCartItem.value.quantity + 1 },
        { preserveScroll: true },
    );
};

const decreaseQty = () => {
    if (!selectedVariant.value || !currentCartItem.value) return;
    const newQty = currentCartItem.value.quantity - 1;
    router.post(route('cart.store'), { product_variant_id: selectedVariant.value.id, quantity: newQty }, { preserveScroll: true });
};

// --- REGION / PICKUP POINTS / SHIPPING OPTIONS ---
interface PickupPoint {
    id: number;
    name: string;
}

interface ShippingOptions {
    pickup: { cost: number; days: number };
    door: { cost: number; days: number };
    express: { cost: number; hours: number };
}

interface Region {
    id: number;
    name: string;
    pickup_points: PickupPoint[];
    shipping_options?: ShippingOptions;
}

const regions = ref<Region[]>((page.props.regions as Region[]) ?? []);

const selectedRegionId = ref<number | ''>('');
const selectedPickupId = ref<number | ''>('');
const filteredPickupPoints = ref<PickupPoint[]>([]);
const selectedShippingOptions = ref<ShippingOptions | null>(null);

const updatePickupPoints = () => {
    const region = regions.value.find((r) => r.id === selectedRegionId.value);
    filteredPickupPoints.value = region?.pickup_points ?? [];
    selectedPickupId.value = '';
    selectedShippingOptions.value = region?.shipping_options ?? null;
};

watch(selectedRegionId, updatePickupPoints);
</script>

<template>
    <MainLayout>
        <!-- Main Product Section -->
        <section class="mx-auto mt-4 mb-4 flex flex-col overflow-x-hidden">
            <!-- Breadcrumb -->
            <nav class="p-4 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li>
                        <a href="/" class="text-primary hover:underline">Home</a>
                        <span class="mx-1">/</span>
                    </li>
                    <li v-for="(cat, index) in product.category_hierarchy ?? []" :key="cat.id" class="flex items-center">
                        <a :href="`/${cat.slug}`" class="text-primary hover:underline">
                            {{ cat.name }}
                        </a>
                        <span v-if="index < (product.category_hierarchy?.length ?? 0) - 1" class="mx-1">/</span>
                    </li>
                    <li class="truncate font-semibold text-gray-800">/{{ product.name }}</li>
                </ol>
            </nav>

            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- MAIN CONTENT -->
                <div class="flex w-full flex-col gap-4 lg:w-10/12">
                    <div class="rounded-xl bg-white p-4 shadow transition hover:shadow-md">
                        <div class="flex flex-col gap-4 md:flex-row">
                            <!-- LEFT: IMAGES -->
                            <div class="w-full md:w-1/2">
                                <div class="relative">
                                    <img :src="mainImage" class="max-h-[400px] w-full rounded-md object-contain" />
                                    <button
                                        @click="openPreview"
                                        class="bg-opacity-75 hover:bg-opacity-100 absolute top-2 right-2 flex items-center gap-1 rounded bg-white px-3 py-1 text-sm font-semibold text-gray-800 shadow"
                                    >
                                        <Expand class="h-4 w-4" />
                                    </button>
                                </div>

                                <!-- Image Thumbnails -->
                                <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto">
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

                            <!-- RIGHT: DETAILS -->
                            <div class="w-full space-y-4 md:w-1/2">
                                <h1 class="text-2xl font-bold text-gray-800">{{ product.name }}</h1>
                                <p class="text-sm text-gray-600">Brand: {{ product.brand?.name }}</p>
                                <hr />

                                <!-- Price + Discount -->
                                <div v-if="displayPrice" class="space-y-1">
                                    <p class="text-lg font-bold text-primary">
                                        {{ formatPrice(displayPrice.final_price) }}
                                        <span
                                            v-if="displayPrice.has_discount && displayPrice.discount_percent"
                                            class="ml-2 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white"
                                        >
                                            {{ Math.round(displayPrice.discount_percent) }} % OFF
                                        </span>
                                    </p>
                                    <p
                                        v-if="displayPrice.has_discount && displayPrice.marked_price > displayPrice.final_price"
                                        class="text-sm text-gray-500 line-through"
                                    >
                                        {{ formatPrice(displayPrice.marked_price) }}
                                    </p>
                                </div>

                                <!-- Stock -->
                                <p v-if="selectedVariant" class="text-sm text-gray-600">Available in stock: {{ selectedVariant.stock }}</p>

                                <!-- Ratings -->
                                <div class="flex gap-[2px] text-yellow-400">
                                    <Star v-for="i in 5" :key="i" class="h-5 w-5 fill-yellow-400" />
                                </div>

                                <!-- CART CONTROLS -->
                                <div class="mt-4">
                                    <template v-if="currentCartItem">
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="decreaseQty"
                                                class="flex items-center justify-center rounded bg-gray-200 px-3 py-2 hover:bg-gray-300"
                                            >
                                                <Minus class="h-4 w-4" />
                                            </button>
                                            <span class="min-w-[40px] text-center font-semibold">
                                                {{ currentCartItem.quantity }}
                                            </span>
                                            <button
                                                @click="increaseQty"
                                                class="flex items-center justify-center rounded bg-gray-200 px-3 py-2 hover:bg-gray-300"
                                            >
                                                <Plus class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <button
                                            @click="addToCart"
                                            class="flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <ShoppingCart class="h-4 w-4" />
                                            <span>Add to Cart</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related -->
                    <div v-if="simplifiedRelatedProducts.length">
                        <ProductCarouselSection
                            title="Related Products"
                            :products="simplifiedRelatedProducts"
                            :slug="simplifiedRelatedProducts[0]?.category_slug ?? 'all-products'"
                        />
                    </div>

                    <!-- Description -->
                    <div v-if="product.description" class="rounded-xl bg-white p-4 text-sm leading-relaxed shadow">
                        <h3 class="mb-2 font-semibold text-gray-800">Product Description</h3>
                        <p v-html="product.description"></p>
                    </div>

                    <!-- Features + Specs -->
                    <div v-if="product.features || product.specifications" class="rounded-xl bg-white p-4 text-sm leading-relaxed shadow">
                        <div class="flex flex-col gap-4 md:flex-row">
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

                <!-- SIDEBAR -->
                <div class="flex w-full flex-col gap-4 lg:w-2/12">
                    <!-- Buyalot Swift -->
                    <div class="flex flex-col items-center rounded-xl bg-white p-4 shadow">
                        <img src="/images/buyalotswift.png" alt="Buyalot Swift" class="h-10 w-auto object-contain" />

                        <p class="text-[11px] text-gray-500">
                            Fast.Smooth.Reliable
                            <a href="/buyalot-swift" class="mb-3 text-xs font-medium text-primary hover:underline">more →</a>
                        </p>

                        <hr class="my-2 w-full border-gray-200" />

                        <div class="w-full space-y-2 text-sm">
                            <label for="region" class="block font-semibold text-gray-700">Select Region</label>
                            <select
                                id="region"
                                v-model="selectedRegionId"
                                @change="updatePickupPoints"
                                class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary focus:ring-primary"
                            >
                                <option disabled value="">Choose region...</option>
                                <option v-for="region in regions" :key="region.id" :value="region.id">
                                    {{ region.name }}
                                </option>
                            </select>

                            <label for="pickup" class="block font-semibold text-gray-700">Pickup Point</label>
                            <select
                                id="pickup"
                                v-model="selectedPickupId"
                                :disabled="filteredPickupPoints.length === 0"
                                class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary focus:ring-primary"
                            >
                                <option disabled value="">Choose pickup point...</option>
                                <option v-for="pickup in filteredPickupPoints" :key="pickup.id" :value="pickup.id">
                                    {{ pickup.name }}
                                </option>
                            </select>

                            <div v-if="selectedShippingOptions" class="mt-2 space-y-4 text-sm">
                                <div class="rounded border border-gray-200 p-3">
                                    <p class="font-semibold text-gray-700">Pickup Point</p>
                                    <ul class="mt-1 list-none space-y-1 pl-0 text-[11px] text-gray-500">
                                        <li>
                                            Delivery Cost KSh
                                            {{ selectedShippingOptions.pickup.cost.toLocaleString() }}
                                        </li>
                                        <li>
                                            Your order will be available for pickup in
                                            {{ selectedShippingOptions.pickup.days }} day(s).
                                        </li>
                                    </ul>
                                    <p class="font-semibold text-gray-700">Door Delivery</p>
                                    <ul class="mt-1 list-none space-y-1 pl-0 text-[11px] text-gray-500">
                                        <li>
                                            Delivery Cost KSh
                                            {{ selectedShippingOptions.door.cost.toLocaleString() }}
                                        </li>
                                        <li>
                                            Your order will be delivered in
                                            {{ selectedShippingOptions.door.days }} day(s).
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seller Info -->
                    <div class="rounded-xl bg-white p-4 shadow">
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                            <Info class="text-secondary-500 h-5 w-5" />
                            Seller Info
                        </h2>
                        <p class="mt-2 text-sm font-medium text-gray-700">
                            {{ ownerInfo.displayName }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Reliable seller with great reviews.</p>
                    </div>

                    <!-- Warranty Info -->
                    <div v-if="warrantyInfo" class="rounded-xl bg-white p-4 shadow">
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                            <Info class="text-secondary-500 h-5 w-5" />
                            Warranty Info
                        </h2>
                        <p class="mt-2 text-sm font-medium text-gray-700">{{ warrantyInfo.duration }} Months</p>
                        <p class="mt-1 text-xs text-gray-500">{{ warrantyInfo.description }}</p>
                    </div>

                    <!-- What's in the Box -->
                    <div v-if="product.whats_in_the_box" class="rounded-xl bg-white p-4 shadow">
                        <h4 class="mb-2 font-semibold text-gray-700">What's in the Box</h4>
                        <p class="text-sm leading-relaxed text-gray-600" v-html="product.whats_in_the_box"></p>
                    </div>
                </div>
            </div>

            <!-- Image Preview Modal -->
            <div v-if="showPreview" @click.self="closePreview" class="bg-opacity-70 fixed inset-0 z-50 flex items-center justify-center bg-black p-4">
                <button @click="closePreview" class="absolute top-4 right-4 rounded bg-white px-3 py-1 text-gray-800 hover:bg-gray-200">Close</button>
                <img :src="mainImage" alt="Preview Image" class="max-h-[90vh] max-w-full rounded-md shadow-lg" />
            </div>
        </section>
    </MainLayout>
</template>
