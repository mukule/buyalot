<script setup lang="ts">
import MapLocationModal from '@/components/MapLocationModal.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import Car360Viewer from '@/components/marketplace/Car360Viewer.vue';
import QuoteRequestModal from '@/components/marketplace/QuoteRequestModal.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Expand, Info, Minus, Plus, Rotate3d, ShoppingCart, Star } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

// --- Types ---
interface VariantImage {
    id: number;
    url: string;
    is_primary: boolean;
    sort_order: number;
}

interface VariantValue {
    variant_category_id: number;
    value: string;
}

interface Variant {
    id: number;
    marked_price: number;
    final_price: number;
    discount_percent?: number | null;
    has_discount?: boolean;
    stock: number;
    sku?: string;
    values: VariantValue[];
    images: VariantImage[];
}

interface VariantAttribute {
    name: string;
    options: string[];
}

interface VariantMapEntry {
    id: number;
    marked_price: number;
    buying_price: number;
    stock: number;
    sku: string;
    has_discount: boolean;
    discount: number;
    discount_percent: number;
    images: { id: number; url: string; is_primary: boolean; sort_order: number }[];
}

// --- Props ---
const props = defineProps<{
    googleMapsApiKey?: string;
    product: {
        id: number;
        slug: string;
        name: string;
        description?: string;
        features?: string;
        specifications?: string;
        whats_in_the_box?: string;
        attributes?: Record<string, any>;
        marketplaces?: string[];
        primary_image_url?: string | null;
        video_url?: string | null;
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
        variants: Variant[];
        variant_attributes?: VariantAttribute[];
        variant_map?: Record<string, VariantMapEntry>;
    };
    relatedProducts?: SimplifiedProduct[];
}>();

// --- VARIANT SELECTION via variant_map ---
const selectedAttributes = ref<Record<string, string>>({});

// Initialize from the first key in variant_map
const initializeSelections = () => {
    const firstKey = Object.keys(props.product.variant_map || {})[0];
    if (!firstKey) return;
    const parts = firstKey.split('|');
    const attrs = props.product.variant_attributes || [];
    attrs.forEach((attr, i) => {
        selectedAttributes.value[attr.name] = parts[i] ?? '';
    });
};

// Build the lookup key from current selections
const currentKey = computed(() => {
    const attrs = props.product.variant_attributes || [];
    return attrs.map((attr) => selectedAttributes.value[attr.name] ?? '').join('|');
});

// Active variant from map
const activeVariant = computed<VariantMapEntry | null>(() => {
    return props.product.variant_map?.[currentKey.value] ?? null;
});

// Use dropdown when more than 5 unique values for an attribute
const DROPDOWN_THRESHOLD = 5;

onMounted(() => {
    // Try to initialize from URL ?v=variantId
    const params = new URLSearchParams(window.location.search);
    const variantId = params.get('v');

    if (variantId && props.product.variant_map) {
        // Find the key in variant_map that matches this variant id
        const matchingKey = Object.entries(props.product.variant_map).find(
            ([, entry]) => entry.id === Number(variantId),
        )?.[0];

        if (matchingKey) {
            const parts = matchingKey.split('|');
            const attrs = props.product.variant_attributes || [];
            attrs.forEach((attr, i) => {
                selectedAttributes.value[attr.name] = parts[i] ?? '';
            });
            return;
        }
    }

    // Default: initialize from first key
    initializeSelections();
});

// Select an attribute — auto-correct linked combos if needed
const selectAttribute = (attrName: string, value: string) => {
    selectedAttributes.value[attrName] = value;

    // If current combo doesn't exist in map, find nearest valid one
    if (!props.product.variant_map?.[currentKey.value]) {
        const attrs = props.product.variant_attributes || [];
        const map = props.product.variant_map || {};
        const attrIndex = attrs.findIndex((a) => a.name === attrName);

        const fallbackKey = Object.keys(map).find((key) => {
            const parts = key.split('|');
            return parts[attrIndex] === value;
        });

        if (fallbackKey) {
            const parts = fallbackKey.split('|');
            attrs.forEach((attr, i) => {
                selectedAttributes.value[attr.name] = parts[i];
            });
        }
    }
};

// An option is available if any map entry with stock > 0 contains it
const isOptionAvailable = (attrName: string, value: string): boolean => {
    const map = props.product.variant_map || {};
    const attrs = props.product.variant_attributes || [];
    const attrIndex = attrs.findIndex((a) => a.name === attrName);

    return Object.entries(map).some(([key, variant]) => {
        const parts = key.split('|');
        return parts[attrIndex] === value && variant.stock > 0;
    });
};

// --- CURRENT IMAGES ---
const currentImages = computed<string[]>(() => {
    const imgs = activeVariant.value?.images ?? [];
    if (imgs.length) {
        return imgs.slice().sort((a, b) => a.sort_order - b.sort_order).map((i) => i.url);
    }
    return props.product.images ?? [];
});

// --- IMAGE PREVIEW ---
const mainImage = ref(props.product.primary_image_url || props.product.images?.[0] || '');
const showPreview = ref(false);
const openPreview = () => (showPreview.value = true);
const closePreview = () => (showPreview.value = false);
const selectImage = (img: string) => (mainImage.value = img);

// When variant changes, update mainImage to first image of new variant
watch(activeVariant, () => {
    const imgs = currentImages.value;
    if (imgs.length) mainImage.value = imgs[0];
});

// --- PRICE ---
const formatPrice = (amount: number | string | null): string =>
    `KSh ${(Number(amount) ?? 0).toLocaleString()}`;

const displayPrice = computed(() => {
    if (!activeVariant.value) return null;
    return {
        marked_price: activeVariant.value.marked_price,
        final_price: activeVariant.value.buying_price,
        discount_percent: activeVariant.value.discount_percent,
        has_discount: activeVariant.value.has_discount,
    };
});

const currentStock = computed(() => activeVariant.value?.stock ?? 0);

// --- OWNER INFO ---
const ownerInfo = computed(() => {
    const owner = props.product.owner;
    return {
        type: owner?.type ?? 'Unknown',
        displayName: owner?.company_legal_name ?? owner?.name ?? 'Unknown Seller',
    };
});

// --- WARRANTY ---
const warrantyInfo = computed(() => props.product.warranty ?? null);

// --- VERTICAL SPEC SHEET (cars / construction attributes) ---
const ATTR_LABELS: Record<string, string> = {
    make: 'Make', model: 'Model', year: 'Year', mileage: 'Mileage', fuel: 'Fuel',
    transmission: 'Transmission', body_type: 'Body type', drive_type: 'Drive',
    steering: 'Steering', color: 'Colour', engine_cc: 'Engine', condition: 'Condition',
    location: 'Location', material: 'Material', type: 'Type', unit: 'Sold per',
};

const humanize = (k: string) => k.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const formatAttr = (key: string, val: any): string => {
    if (key === 'mileage') return `${Number(val).toLocaleString()} km`;
    if (key === 'engine_cc') return `${Number(val).toLocaleString()} cc`;
    if (key === 'steering') return `${val} hand drive`;
    return String(val);
};

const specEntries = computed(() =>
    Object.entries(props.product.attributes ?? {})
        .filter(([, v]) => v !== null && v !== '' && v !== undefined)
        .map(([k, v]) => ({ key: k, label: ATTR_LABELS[k] ?? humanize(k), value: formatAttr(k, v) })),
);

const specTitle = computed(() =>
    (props.product.marketplaces ?? []).includes('cars')
        ? 'Vehicle details'
        : (props.product.marketplaces ?? []).includes('construction')
          ? 'Product specifications'
          : 'Specifications',
);

// --- CONSTRUCTION: unit pricing, coverage estimator, bulk quote ---
const detailAttrs = computed<Record<string, any>>(() => props.product.attributes ?? {});
const isConstruction = computed(() => (props.product.marketplaces ?? []).includes('construction'));
const unitLabel = computed(() => detailAttrs.value.unit ?? 'unit');
const unitPrice = computed(() => Number(activeVariant.value?.buying_price ?? 0));
const coveragePerUnit = computed(() => Number(detailAttrs.value.coverage) || 0);
const coverageUnit = computed(() => detailAttrs.value.coverage_unit ?? 'sq metre');

const requiredArea = ref<number | null>(null);
const estimatedUnits = computed(() => {
    if (!coveragePerUnit.value || !requiredArea.value) return 0;
    return Math.ceil(requiredArea.value / coveragePerUnit.value);
});
const estimatedTotal = computed(() => estimatedUnits.value * unitPrice.value);

// --- CARS: 360° / 3D spin viewer ---
const isCar = computed(() => (props.product.marketplaces ?? []).includes('cars'));
const show360 = ref(false);
const has360 = computed(() => isCar.value && currentImages.value.length >= 2);

const showQuote = ref(false);
const quoteTarget = computed(() => ({
    id: props.product.id,
    name: props.product.name,
    vertical: isConstruction.value ? 'construction' : 'cars',
    unit: detailAttrs.value.unit ?? null,
}));

// --- RELATED PRODUCTS ---
const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    (props.relatedProducts ?? []).map((p) => ({
        ...p,
        image: (p as any).primary_image_url || (p as any).image_urls?.[0] || '/fallback-image.png',
        onSale: (p as any).has_discount ?? false,
    })),
);

// --- CART ---
const page = usePage();
const auth = computed<any>(() => page.props.auth ?? {});
const cartItems = computed(() => auth.value.cartItems ?? []);

const currentCartItem = computed(() => {
    if (!activeVariant.value) return null;
    return cartItems.value.find((i: any) => i.product_variant_id === activeVariant.value!.id);
});

const addToCart = () => {
    if (!activeVariant.value) return;
    router.post(
        route('cart.store'),
        { product_variant_id: activeVariant.value.id, quantity: 1 },
        { preserveScroll: true },
    );
};

const increaseQty = () => {
    if (!activeVariant.value || !currentCartItem.value) return;
    router.post(
        route('cart.store'),
        { product_variant_id: activeVariant.value.id, quantity: currentCartItem.value.quantity + 1 },
        { preserveScroll: true },
    );
};

const decreaseQty = () => {
    if (!activeVariant.value || !currentCartItem.value) return;
    router.post(
        route('cart.store'),
        { product_variant_id: activeVariant.value.id, quantity: currentCartItem.value.quantity - 1 },
        { preserveScroll: true },
    );
};

// --- REGION / SHIPPING ---
interface PickupPoint {
    id: number;
    name: string;
    address?: string | null;
    location?: string | null;
    latitude?: number | null;
    longitude?: number | null;
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
const homeDeliveryLat = ref<number | null>(null);
const homeDeliveryLng = ref<number | null>(null);
const filteredPickupPoints = ref<PickupPoint[]>([]);
const selectedShippingOptions = ref<ShippingOptions | null>(null);

const updatePickupPoints = () => {
    const region = regions.value.find((r) => r.id === selectedRegionId.value);
    filteredPickupPoints.value = region?.pickup_points ?? [];
    selectedPickupId.value = '';
    selectedShippingOptions.value = region?.shipping_options ?? null;
};

watch(selectedRegionId, updatePickupPoints);

const selectedPickupPoint = computed(() => {
    if (!selectedPickupId.value || !Array.isArray(filteredPickupPoints.value)) return null;
    return filteredPickupPoints.value.find((p) => p.id === selectedPickupId.value) ?? null;
});

const showMapModal = ref(false);
const mapApiKey = (props as any).googleMapsApiKey ?? (page?.props as any)?.googleMapsApiKey ?? '';

const pickupMapEmbedUrl = computed(() => {
    const p = selectedPickupPoint.value;
    if (!p || p.latitude == null || p.longitude == null) return '';
    const lat = p.latitude;
    const lng = p.longitude;
    if (mapApiKey)
        return `https://www.google.com/maps/embed/v1/place?key=${encodeURIComponent(mapApiKey)}&q=${lat},${lng}&zoom=15`;
    return `https://www.google.com/maps?q=${lat},${lng}`;
});

function closeMapModal() {
    showMapModal.value = false;
}

function openGoogleMapsExternal() {
    const p = selectedPickupPoint.value;
    if (!p) return;
    if (p.latitude != null && p.longitude != null) {
        window.open(`https://www.google.com/maps?q=${p.latitude},${p.longitude}`, '_blank');
    } else if (p.address || p.location) {
        window.open(
            `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.address || p.location || p.name)}`,
            '_blank',
        );
    }
}

const showDeliveryMapModal = ref(false);
function onDeliveryLocationConfirm(payload: { lat: number; lng: number }) {
    homeDeliveryLat.value = payload.lat;
    homeDeliveryLng.value = payload.lng;
}

// --- VIDEO ---
const videoEmbedUrl = computed<string | null>(() => {
    if (!('video_url' in props.product) || !props.product.video_url) return null;
    try {
        const url = new URL(props.product.video_url);
        const videoId = url.searchParams.get('v') ?? url.pathname.split('/').pop() ?? null;
        return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
    } catch {
        return null;
    }
});
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex flex-col overflow-x-hidden">
            <!-- Breadcrumb -->
            <nav class="p-4 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li>
                        <a href="/" class="text-primary hover:underline">Home</a>
                        <span class="mx-1">/</span>
                    </li>
                    <li v-for="(cat, index) in product.category_hierarchy ?? []" :key="cat.id" class="flex items-center">
                        <a :href="`/${cat.slug}`" class="text-primary hover:underline">{{ cat.name }}</a>
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
                                    <div class="group flex h-[400px] items-center justify-center overflow-hidden rounded-md bg-gray-50">
                                        <img
                                            :src="mainImage"
                                            class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-125"
                                        />
                                    </div>
                                    <button
                                        @click="openPreview"
                                        class="bg-opacity-75 hover:bg-opacity-100 absolute top-2 right-2 flex items-center gap-1 rounded bg-white px-3 py-1 text-sm font-semibold text-gray-800 shadow"
                                    >
                                        <Expand class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="has360"
                                        @click="show360 = true"
                                        class="absolute top-2 left-2 flex items-center gap-1 rounded bg-primary px-3 py-1 text-sm font-semibold text-white shadow hover:bg-primary/90"
                                    >
                                        <Rotate3d class="h-4 w-4" /> 360° View
                                    </button>
                                </div>

                                <!-- Thumbnails -->
                                <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto">
                                    <img
                                        v-for="(img, idx) in currentImages"
                                        :key="idx"
                                        :src="img"
                                        @click="selectImage(img)"
                                        :class="[
                                            'h-16 w-16 cursor-pointer rounded border-2 object-cover transition',
                                            img === mainImage
                                                ? 'border-primary ring-2 ring-primary/20'
                                                : 'border-gray-200 hover:border-gray-300',
                                        ]"
                                    />
                                </div>
                            </div>

                            <!-- RIGHT: DETAILS -->
                            <div class="w-full space-y-4 md:w-1/2">
                                <h1 class="text-2xl font-bold text-gray-800">{{ product.name }}</h1>
                                <p class="text-sm text-secondary">Brand: {{ product.brand?.name }}</p>
                                <hr />

                                <!-- Price -->
                                <div v-if="displayPrice" class="space-y-1">
                                    <p class="text-lg font-bold text-primary">
                                        {{ formatPrice(displayPrice.final_price) }}
                                        <span
                                            v-if="displayPrice.has_discount && displayPrice.discount_percent"
                                            class="ml-2 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white"
                                        >
                                            {{ Math.round(displayPrice.discount_percent) }}% OFF
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
                                <p v-if="currentStock === 0" class="font-semibold text-red-600">Out of Stock</p>
                                <p v-else-if="currentStock <= 5" class="text-sm font-medium text-orange-500">
                                    Only {{ currentStock }} left in stock!
                                </p>

                                <!-- Ratings -->
                                <div class="flex gap-[2px] text-yellow-400">
                                    <Star v-for="i in 5" :key="i" class="h-5 w-5 fill-yellow-400" />
                                </div>

                                <!-- VARIANT SELECTOR -->
                                <div
                                    v-if="(props.product.variant_attributes?.length ?? 0) > 0"
                                    class="space-y-4"
                                >
                                    <div
                                        v-for="attr in props.product.variant_attributes"
                                        :key="attr.name"
                                        class="space-y-2"
                                    >
                                        <p class="text-sm font-semibold text-gray-700">
                                            {{ attr.name }}:
                                            <span class="font-normal text-gray-500">{{ selectedAttributes[attr.name] }}</span>
                                        </p>

                                        <!-- Buttons for small sets -->
                                        <div v-if="attr.options.length <= DROPDOWN_THRESHOLD" class="flex flex-wrap gap-2">
                                            <button
                                                v-for="value in attr.options"
                                                :key="value"
                                                @click="selectAttribute(attr.name, value)"
                                                :disabled="!isOptionAvailable(attr.name, value)"
                                                :class="[
                                                    'rounded-md border-2 px-3 py-1.5 text-sm font-medium transition',
                                                    selectedAttributes[attr.name] === value
                                                        ? 'border-primary bg-primary text-white'
                                                        : isOptionAvailable(attr.name, value)
                                                          ? 'border-gray-300 bg-white text-gray-700 hover:border-primary hover:text-primary'
                                                          : 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 line-through',
                                                ]"
                                            >
                                                {{ value }}
                                            </button>
                                        </div>

                                        <!-- Dropdown for large sets -->
                                        <select
                                            v-else
                                            :value="selectedAttributes[attr.name]"
                                            @change="selectAttribute(attr.name, ($event.target as HTMLSelectElement).value)"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        >
                                            <option
                                                v-for="value in attr.options"
                                                :key="value"
                                                :value="value"
                                                :disabled="!isOptionAvailable(attr.name, value)"
                                            >
                                                {{ value }}{{ !isOptionAvailable(attr.name, value) ? ' (unavailable)' : '' }}
                                            </option>
                                        </select>
                                    </div>
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
                                            <span class="min-w-[40px] text-center font-semibold">{{ currentCartItem.quantity }}</span>
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
                                            :disabled="currentStock === 0"
                                            :class="[
                                                'flex w-full items-center justify-center gap-2 rounded px-4 py-2 text-white transition',
                                                currentStock === 0
                                                    ? 'cursor-not-allowed bg-gray-400'
                                                    : 'bg-primary hover:bg-primary/90',
                                            ]"
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

                    <!-- Vertical spec sheet (cars / construction) -->
                    <div v-if="specEntries.length" class="rounded-xl bg-white p-4 shadow">
                        <h3 class="mb-3 font-semibold text-gray-800">{{ specTitle }}</h3>
                        <dl class="grid grid-cols-1 gap-x-6 gap-y-0 sm:grid-cols-2">
                            <div
                                v-for="entry in specEntries"
                                :key="entry.key"
                                class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0"
                            >
                                <dt class="text-gray-500">{{ entry.label }}</dt>
                                <dd class="font-medium text-gray-800">{{ entry.value }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Construction: unit pricing, coverage estimator, bulk quote -->
                    <div v-if="isConstruction" class="rounded-xl bg-white p-4 shadow">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-lg font-bold text-primary">
                                    {{ formatPrice(unitPrice) }}
                                    <span class="text-sm font-normal text-gray-500">/ {{ unitLabel }}</span>
                                </p>
                                <p v-if="detailAttrs.min_order" class="text-xs text-gray-500">
                                    Minimum order: {{ detailAttrs.min_order }} {{ unitLabel }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90"
                                @click="showQuote = true"
                            >
                                <Info class="h-4 w-4" /> Request bulk quote
                            </button>
                        </div>

                        <!-- Coverage / quantity estimator -->
                        <div v-if="coveragePerUnit > 0" class="mt-4 rounded-lg border border-gray-200 p-3">
                            <h4 class="text-sm font-semibold text-gray-700">Quantity estimator</h4>
                            <p class="mb-2 text-xs text-gray-500">
                                Each {{ unitLabel }} covers {{ coveragePerUnit }} {{ coverageUnit }}. Enter your total area to estimate what you need.
                            </p>
                            <div class="flex flex-wrap items-end gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Area ({{ coverageUnit }})</label>
                                    <input
                                        v-model.number="requiredArea"
                                        type="number"
                                        min="0"
                                        placeholder="e.g. 120"
                                        class="w-32 rounded-md border px-3 py-2 text-sm focus:border-primary focus:ring-0"
                                    />
                                </div>
                                <div v-if="estimatedUnits > 0" class="text-sm">
                                    <p class="text-gray-600">
                                        You need approx. <span class="font-bold text-gray-900">{{ estimatedUnits }} {{ unitLabel }}(s)</span>
                                    </p>
                                    <p class="text-gray-600">
                                        Estimated total: <span class="font-bold text-primary">{{ formatPrice(estimatedTotal) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
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
                        <img src="/buyalotswift.png" alt="Buyalot Swift" class="h-10 w-auto object-contain" />
                        <p class="text-[11px] text-gray-500">
                            Fast.Smooth.Reliable
                            <a href="/buyalot-swift" class="mb-3 text-xs font-medium text-primary hover:underline">more →</a>
                        </p>
                        <hr class="my-2 w-full border-gray-200" />
                        <div class="w-full space-y-2 text-sm">
                            <div v-if="selectedShippingOptions" class="mt-2 space-y-4 text-sm">
                                <div class="rounded border border-gray-200 p-3">
                                    <p class="font-semibold text-gray-700">Pickup point</p>
                                    <ul class="mt-1 list-none space-y-1 pl-0 text-[11px] text-gray-500">
                                        <li>Delivery Cost KSh {{ selectedShippingOptions.pickup.cost.toLocaleString() }}</li>
                                        <li>Your order will be available for pickup in {{ selectedShippingOptions.pickup.days }} day(s).</li>
                                    </ul>
                                    <p class="font-semibold text-gray-700">Home delivery</p>
                                    <ul class="mt-1 list-none space-y-1 pl-0 text-[11px] text-gray-500">
                                        <li>Delivery Cost KSh {{ selectedShippingOptions.door.cost.toLocaleString() }}</li>
                                        <li>Your order will be delivered in {{ selectedShippingOptions.door.days }} day(s).</li>
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
                        <p class="mt-2 text-sm font-medium text-gray-700">{{ ownerInfo.displayName }}</p>
                        <p class="mt-1 text-xs text-gray-500">Reliable seller with great reviews.</p>
                    </div>

                    <!-- Warranty -->
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

                    <!-- Video -->
                    <div v-if="videoEmbedUrl" class="rounded-xl bg-white p-1 shadow">
                        <div class="aspect-video w-full overflow-hidden rounded-md border">
                            <iframe
                                :src="videoEmbedUrl"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                class="h-full w-full"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pickup map modal -->
            <div
                v-if="showMapModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click.self="closeMapModal"
            >
                <div class="relative max-h-[90vh] w-full max-w-2xl rounded-lg bg-white shadow-xl" @click.stop>
                    <div class="flex items-center justify-between border-b px-4 py-2">
                        <h3 class="font-semibold text-gray-800">{{ selectedPickupPoint?.name ?? 'Pickup point' }}</h3>
                        <button type="button" class="rounded p-1 text-gray-500 hover:bg-gray-100" @click="closeMapModal">×</button>
                    </div>
                    <div class="p-2">
                        <div v-if="pickupMapEmbedUrl" class="h-[400px] w-full overflow-hidden rounded border bg-gray-100">
                            <iframe
                                v-if="mapApiKey"
                                :src="pickupMapEmbedUrl"
                                title="Pickup point"
                                class="h-full w-full border-0"
                                loading="lazy"
                                allowfullscreen
                                referrerpolicy="no-referrer-when-downgrade"
                            />
                            <a
                                v-else
                                :href="pickupMapEmbedUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex h-full items-center justify-center text-sm text-primary underline"
                            >
                                View on Google Maps
                            </a>
                        </div>
                        <div class="mt-2 flex justify-center">
                            <button
                                type="button"
                                class="rounded bg-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-primary/90"
                                @click="openGoogleMapsExternal"
                            >
                                Open in Google Maps
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 360° car viewer -->
            <Car360Viewer :open="show360" :images="currentImages" :title="product.name" @close="show360 = false" />

            <!-- Bulk quote modal -->
            <QuoteRequestModal :open="showQuote" :product="quoteTarget" @close="showQuote = false" />

            <!-- Home delivery map modal -->
            <MapLocationModal
                v-model="showDeliveryMapModal"
                :api-key="(props as any).googleMapsApiKey ?? (page.props as any).googleMapsApiKey ?? ''"
                :initial-lat="homeDeliveryLat"
                :initial-lng="homeDeliveryLng"
                :use-geolocation-on-open="true"
                @confirm="onDeliveryLocationConfirm"
            />

            <!-- Image preview modal -->
            <div
                v-if="showPreview"
                @click.self="closePreview"
                class="bg-opacity-70 fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
            >
                <button @click="closePreview" class="absolute top-4 right-4 rounded bg-white px-3 py-1 text-gray-800 hover:bg-gray-200">
                    Close
                </button>
                <img :src="mainImage" alt="Preview" class="max-h-[90vh] max-w-full rounded-md shadow-lg" />
            </div>
        </section>
    </MainLayout>
</template>s