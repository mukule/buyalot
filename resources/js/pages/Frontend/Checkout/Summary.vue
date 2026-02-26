<script setup lang="ts">
import MapLocationModal from '@/components/MapLocationModal.vue';
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, MapPin, Package, PlusCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

/* -------------------- TYPES -------------------- */

interface PickupPoint {
    id: number;
    name: string;
    address?: string | null;
    location?: string | null;
    latitude?: number | null;
    longitude?: number | null;
}

interface ShippingOption {
    cost?: number;
    days?: number;
}

interface RegionWithPickup {
    id: number;
    name: string;
    pickup_points: PickupPoint[];
    shipping_options?: { pickup?: ShippingOption; door?: ShippingOption };
}

interface CartItem {
    id: number;
    quantity: number;
    unit_price: number;
    total_price: number;
    product: { id: number; name: string; slug?: string; primary_image_url?: string | null };
    variant: { id: number; final_price: number };
    owner?: { type: string; name: string } | null;
}

interface Cart {
    items: CartItem[];
    totals: {
        subtotal: number;
        per_item_discount?: number;
        coupon_discount: number;
        shipping: number;
        grand_total: number;
    };
    applied_coupon?: any;
    coupon_code?: string;
    coupon_error?: string;
}

/* -------------------- PAGE PROPS -------------------- */

const page = usePage();
const cart = (page.props as any).cart as Cart;
const regionsWithPickup = (page.props as any).regions_with_pickup as RegionWithPickup[] || [];
const homeDeliveryConfig = (page.props as any).home_delivery_config as {
    fallback_price?: number;
    fallback_min_km?: number;
    extra_km_cost?: number;
    min_shipping?: number;
} | null;
const relatedProducts = (page.props as any).relatedProducts ?? [];
const googleMapsApiKey = (page.props as any).googleMapsApiKey ?? '';

/* -------------------- DELIVERY STATE -------------------- */

const deliveryType = ref<'pickup' | 'door'>('pickup');
const selectedRegionId = ref<number | null>(null);
const selectedPickupPointId = ref<number | null>(null);
const homeDeliveryLat = ref<number | null>(null);
const homeDeliveryLng = ref<number | null>(null);
const homeDeliveryRegion = ref<{ id: number; name: string } | null>(null);
const homeDeliveryCost = ref<number>(0);
const homeDeliveryDays = ref<number>(2);
const homeDeliveryError = ref<string>('');
const showMapModal = ref(false);
const isCalculatingHome = ref(false);

/* -------------------- COMPUTED -------------------- */

const selectedRegion = computed(() =>
    regionsWithPickup.find((r) => r.id === selectedRegionId.value) ?? null,
);

const availablePickupPoints = computed(() => selectedRegion.value?.pickup_points ?? []);

const selectedPickupPoint = computed(() =>
    availablePickupPoints.value.find((p) => p.id === selectedPickupPointId.value) ?? null,
);

const pickupShippingCost = computed(() => {
    if (!selectedRegion.value?.shipping_options?.pickup) return 0;
    return selectedRegion.value.shipping_options.pickup.cost ?? 0;
});

const pickupShippingDays = computed(() => {
    if (!selectedRegion.value?.shipping_options?.pickup) return 2;
    return selectedRegion.value.shipping_options.pickup.days ?? 2;
});

const effectiveShippingCost = computed(() => {
    if (deliveryType.value === 'pickup') {
        return selectedPickupPointId.value ? pickupShippingCost.value : 0;
    }
    return homeDeliveryCost.value;
});

const sub_total = computed(() => Math.round(Number(cart.totals.subtotal ?? 0)));
const perItemDiscount = computed(() => Math.round(Number(cart.totals.per_item_discount ?? 0)));
const couponDiscount = computed(() => Math.round(Number(cart.totals.coupon_discount ?? 0)));
const grandTotal = computed(() =>
    Math.round(sub_total.value - perItemDiscount.value - couponDiscount.value + effectiveShippingCost.value),
);

const canProceed = computed(() => {
    if (deliveryType.value === 'pickup') {
        return !!selectedRegionId.value && !!selectedPickupPointId.value;
    }
    return !!homeDeliveryRegion.value && homeDeliveryCost.value > 0 && homeDeliveryLat.value != null && homeDeliveryLng.value != null;
});

/* -------------------- WATCH: Reset on delivery type change -------------------- */

watch(deliveryType, (newType) => {
    if (newType === 'pickup') {
        homeDeliveryError.value = '';
        homeDeliveryRegion.value = null;
        homeDeliveryCost.value = 0;
        homeDeliveryLat.value = null;
        homeDeliveryLng.value = null;
    } else {
        selectedRegionId.value = null;
        selectedPickupPointId.value = null;
    }
});

watch(selectedRegionId, () => {
    selectedPickupPointId.value = null;
});

/* -------------------- MAP: Home delivery location -------------------- */

function openMapModal() {
    homeDeliveryError.value = '';
    showMapModal.value = true;
}

async function onMapLocationConfirm(payload: { lat: number; lng: number }) {
    homeDeliveryLat.value = payload.lat;
    homeDeliveryLng.value = payload.lng;
    showMapModal.value = false;
    await calculateHomeDeliveryCost(payload.lat, payload.lng);
}

async function calculateHomeDeliveryCost(lat: number, lng: number) {
    isCalculatingHome.value = true;
    homeDeliveryError.value = '';
    // Order total before shipping (for Nairobi free shipping when >= KSh 50,000)
    const orderTotalBeforeShipping = sub_total.value - perItemDiscount.value - couponDiscount.value;
    try {
        const axios = (window as any).axios || (await import('axios')).default;
        const { data } = await axios.post(route('shipping.calculate-home-delivery'), { lat, lng, order_total: orderTotalBeforeShipping }, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            withCredentials: true,
        });
        if (data.success) {
            homeDeliveryRegion.value = data.region;
            homeDeliveryCost.value = data.shipping_cost;
            homeDeliveryDays.value = data.days ?? 2;
        } else {
            homeDeliveryError.value = data.message || "We don't do home delivery for the selected region.";
        }
    } catch (e: any) {
        const msg = e?.response?.data?.message || e?.message || "We don't do home delivery for the selected region.";
        homeDeliveryError.value = msg;
        homeDeliveryRegion.value = null;
        homeDeliveryCost.value = 0;
    } finally {
        isCalculatingHome.value = false;
    }
}

/* -------------------- PROCEED TO PAYMENT -------------------- */

async function proceedToPayment() {
    if (!canProceed.value) return;

    let addressId: number;
    let shippingCost: number;
    let deliveryMethod: string;
    let regionName: string;
    let pickupPoint: string | null = null;
    let days: number;

    if (deliveryType.value === 'pickup') {
        const axios = (window as any).axios || (await import('axios')).default;
        const { data } = await axios.post(
            route('checkout.addresses.from-pickup'),
            { region_id: selectedRegionId.value, pickup_warehouse_id: selectedPickupPointId.value },
            { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, withCredentials: true },
        );
        if (!data.success || !data.address_id) return;
        addressId = data.address_id;
        shippingCost = pickupShippingCost.value;
        deliveryMethod = 'pickup';
        regionName = selectedRegion.value?.name ?? '';
        pickupPoint = selectedPickupPoint.value?.name ?? null;
        days = pickupShippingDays.value;
    } else {
        const axios = (window as any).axios || (await import('axios')).default;
        const { data } = await axios.post(
            route('checkout.addresses.from-map'),
            {
                latitude: homeDeliveryLat.value,
                longitude: homeDeliveryLng.value,
                region_id: homeDeliveryRegion.value!.id,
            },
            { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, withCredentials: true },
        );
        if (!data.success || !data.address_id) return;
        addressId = data.address_id;
        shippingCost = homeDeliveryCost.value;
        deliveryMethod = 'door';
        regionName = homeDeliveryRegion.value?.name ?? '';
        days = homeDeliveryDays.value;
    }

    const params = new URLSearchParams({
        address_id: String(addressId),
        shipping_cost: String(shippingCost),
        delivery_method: deliveryMethod,
        region_name: regionName,
        shipping_days: String(days),
    });
    if (pickupPoint) params.set('pickup_point', pickupPoint);

    router.visit(`${route('checkout.payment')}?${params.toString()}`, {
        method: 'get',
        preserveScroll: true,
        preserveState: true,
    });
}

/* -------------------- RELATED PRODUCTS -------------------- */

const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    relatedProducts.map((p: any) => ({
        id: p.id,
        hashid: p.hashid ?? p.id,
        name: p.name,
        product_slug: p.product_slug,
        image: p.primary_image_url || p.image_urls?.[0] || '/fallback-image.png',
        marked_price: p.marked_price ?? p.final_price ?? 0,
        final_price: p.final_price ?? p.marked_price ?? 0,
        discount_percent:
            p.discount_percent ?? (p.marked_price && p.final_price ? Math.round(((p.marked_price - p.final_price) / p.marked_price) * 100) : 0),
        has_discount: p.has_discount ?? (p.discount_percent ? true : false),
    })),
);

const goToProduct = (product: SimplifiedProduct) => {
    router.visit(route('products.show', { slug: product.product_slug }));
};

const formatPrice = (amount?: number | null) =>
    `KSh ${Math.round(amount ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex flex-col gap-4 lg:flex-row">
            <!-- LEFT -->
            <div class="flex w-full flex-col gap-4 lg:w-9/12">
                <!-- Delivery Type & Options -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <h1 class="mb-4 text-lg font-semibold text-gray-800">Delivery</h1>
                    <hr class="mb-4 border-gray-200" />

                    <!-- Step 1: Delivery Type -->
                    <div class="mb-6">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Delivery type</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex cursor-pointer items-center gap-2 rounded border p-3 transition" :class="deliveryType === 'pickup' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:bg-gray-50'">
                                <input v-model="deliveryType" type="radio" value="pickup" class="h-4 w-4 text-primary" />
                                <Package class="h-5 w-5 text-gray-600" />
                                <span class="font-medium text-gray-800">Pickup point</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded border p-3 transition" :class="deliveryType === 'door' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:bg-gray-50'">
                                <input v-model="deliveryType" type="radio" value="door" class="h-4 w-4 text-primary" />
                                <MapPin class="h-5 w-5 text-gray-600" />
                                <span class="font-medium text-gray-800">Home delivery</span>
                            </label>
                        </div>
                    </div>

                    <!-- Step 2a: Pickup – Region + Pickup Point -->
                    <div v-if="deliveryType === 'pickup'" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Select region</label>
                            <select
                                v-model="selectedRegionId"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            >
                                <option :value="null">Select region</option>
                                <option v-for="r in regionsWithPickup" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <div v-if="selectedRegionId">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pickup point</label>
                            <select
                                v-model="selectedPickupPointId"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            >
                                <option :value="null">Select pickup point</option>
                                <option v-for="p in availablePickupPoints" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="selectedPickupPoint?.address || selectedPickupPoint?.location" class="mt-1 text-xs text-gray-500">
                                {{ selectedPickupPoint?.address || selectedPickupPoint?.location }}
                            </p>
                        </div>
                    </div>

                    <!-- Step 2b: Home Delivery – Map -->
                    <div v-else class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Delivery location</label>
                            <p class="mb-2 text-xs text-gray-500">Select your delivery location on the map. We use your current location by default.</p>
<!--                            <p v-if="homeDeliveryConfig" class="mb-2 text-xs text-gray-600">-->
<!--                                Door Delivery/KM: From KSh {{ Math.round(homeDeliveryConfig.fallback_price ?? 250).toLocaleString() }} for first {{ homeDeliveryConfig.fallback_min_km ?? 10 }} km, then KSh {{ Math.round(homeDeliveryConfig.extra_km_cost ?? 20).toLocaleString() }}/km.-->
<!--                            </p>-->
                            <button
                                type="button"
                                class="rounded-md border border-primary bg-primary/10 px-3 py-2 text-sm font-medium text-primary hover:bg-primary/20 disabled:opacity-50"
                                :disabled="isCalculatingHome"
                                @click="openMapModal"
                            >
                                {{ homeDeliveryLat != null && homeDeliveryLng != null ? 'Change location' : 'Select delivery location' }}
                            </button>
                            <p v-if="homeDeliveryError" class="mt-2 text-sm text-red-600">{{ homeDeliveryError }}</p>
                            <div v-if="homeDeliveryRegion && homeDeliveryCost > 0" class="mt-2 space-y-2">
                                <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                                    {{ homeDeliveryRegion.name }} – {{ formatPrice(homeDeliveryCost) }} ({{ homeDeliveryDays }} day(s))
                                </div>
                                <div v-if="homeDeliveryLat != null && homeDeliveryLng != null" class="h-[220px] w-full overflow-hidden rounded border border-gray-200 bg-gray-100">
                                    <iframe
                                        v-if="googleMapsApiKey"
                                        :src="`https://www.google.com/maps/embed/v1/place?key=${encodeURIComponent(googleMapsApiKey)}&q=${homeDeliveryLat},${homeDeliveryLng}&zoom=15`"
                                        title="Selected delivery location - Google Maps"
                                        class="h-full w-full border-0"
                                        loading="lazy"
                                        allowfullscreen
                                        referrerpolicy="no-referrer-when-downgrade"
                                    />
                                    <a
                                        v-else
                                        :href="`https://www.google.com/maps?q=${homeDeliveryLat},${homeDeliveryLng}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex h-full items-center justify-center text-sm text-primary underline"
                                    >
                                        View selected location on Google Maps
                                    </a>
                                </div>
                            </div>
                            <p v-else-if="isCalculatingHome" class="mt-2 text-sm text-gray-500">Calculating shipping cost…</p>
                        </div>
                    </div>
                </div>

                <!-- Delivery Details (Cart Items) -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Delivery Details</h2>
                    <hr class="mb-4 border-gray-200" />
                    <div v-if="cart.items.length" class="space-y-4">
                        <div v-for="item in cart.items" :key="item.id" class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <img :src="item.product.primary_image_url || '/fallback-image.png'" class="h-16 w-16 rounded object-cover" />
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">{{ item.product.name }}</span>
                                    <span class="text-sm text-gray-500">Supplied By: {{ item.owner?.name ?? 'N/A' }}</span>
                                    <span class="text-sm text-gray-700">× {{ item.quantity }}</span>
                                    <span class="text-sm text-gray-700">Unit: {{ formatPrice(item.unit_price) }}</span>
                                </div>
                            </div>
                            <div class="font-medium text-gray-700">{{ formatPrice(item.total_price) }}</div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">No items in the cart.</p>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="w-full lg:w-3/12">
                <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                    <div>
                        <img src="/free_del.jpeg" alt="Free Delivery" class="w-full rounded-md object-contain" />
                    </div>
                    <div class="space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>{{ formatPrice(sub_total) }}</span>
                        </div>
                        <div class="flex justify-between" v-if="perItemDiscount > 0">
                            <span>Item Discounts</span>
                            <span>-{{ formatPrice(perItemDiscount) }}</span>
                        </div>
                        <div class="flex justify-between" v-if="couponDiscount > 0">
                            <span>Coupon</span>
                            <span>-{{ formatPrice(couponDiscount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Shipping</span>
                            <span>{{ formatPrice(effectiveShippingCost) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-800">
                            <span>Total</span>
                            <span>{{ formatPrice(grandTotal) }}</span>
                        </div>
                    </div>
                    <button
                        @click="proceedToPayment"
                        :disabled="!canProceed"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Proceed Checkout
                    </button>
                    <p class="mt-2 text-center text-xs text-gray-600">
                        By proceeding, you are automatically accepting the
                        <a :href="route('terms')" class="text-primary underline hover:text-primary/80"> Terms &amp; Conditions </a>
                    </p>
                </div>
            </div>
        </section>

        <!-- Related Products -->
        <section class="mx-auto mt-8 mb-8">
            <div v-if="simplifiedRelatedProducts.length">
                <ProductCarouselSection
                    title="Related Products"
                    :products="simplifiedRelatedProducts"
                    :slug="simplifiedRelatedProducts[0]?.category_slug ?? '/'"
                    @click-item="goToProduct"
                />
            </div>
        </section>

        <!-- Map Modal (Home delivery) -->
        <MapLocationModal
            v-model="showMapModal"
            :api-key="googleMapsApiKey"
            :initial-lat="homeDeliveryLat"
            :initial-lng="homeDeliveryLng"
            :use-geolocation-on-open="true"
            title="Select your delivery location"
            @confirm="onMapLocationConfirm"
        />
    </MainLayout>
</template>
