<script setup lang="ts">
import MapLocationModal from '@/components/MapLocationModal.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { route } from 'ziggy-js';

// --- Props from Controller ---
const page = usePage();
const address = (page.props as any).address || null;
const isEdit = (page.props as any).is_edit || false;

interface PickupPoint {
    id: string | number;
    name: string;
    address?: string | null;
    location?: string | null;
    latitude?: number | null;
    longitude?: number | null;
}

interface ShippingOption {
    cost?: number;
}

interface Region {
    id: string | number;
    name: string;
    pickup_points?: PickupPoint[];
    shipping_options?: {
        pickup?: ShippingOption;
        door?: ShippingOption;
    };
}

const regions: Region[] = ((page.props as any).regions as Region[]) || [];
const cartSubtotal = (page.props as any).cart_subtotal ?? 0;

// --- Prefill values from controller ---
const defaultFirst = (page.props as any).first_name || '';
const defaultLast = (page.props as any).last_name || '';
const defaultPhone = (page.props as any).phone || '';
const defaultAddressLine = (page.props as any).address_line_1 || '';
const defaultRegionId = (page.props as any).selected_region_id || '';
const defaultPickupWarehouseId = (page.props as any).selected_pickup_warehouse_id || '';
const defaultIsDefault = Boolean((page.props as any).is_default);
const defaultDeliveryMode = ((page.props as any).delivery_mode as 'pickup' | 'door') || 'pickup';
const defaultLatitude = (page.props as any).latitude ?? null;
const defaultLongitude = (page.props as any).longitude ?? null;

// --- Form ---
interface AddressForm {
    first_name: string;
    last_name: string;
    phone: string;
    address_line_1: string;
    region_id: string | number;
    delivery_mode: 'pickup' | 'door';
    pickup_warehouse_id: string | number;
    latitude: number | null;
    longitude: number | null;
    is_default: boolean;
}

const form = useForm<AddressForm>({
    first_name: defaultFirst,
    last_name: defaultLast,
    phone: defaultPhone,
    address_line_1: defaultAddressLine,
    region_id: defaultRegionId,
    delivery_mode: defaultDeliveryMode,
    pickup_warehouse_id: '',
    latitude: defaultLatitude,
    longitude: defaultLongitude,
    is_default: defaultIsDefault,
});

// --- Computed: available pickup points based on selected region ---
const availablePickupPoints = computed(() => {
    const selectedRegion = regions.find((r) => r.id == form.region_id);
    return selectedRegion?.pickup_points || [];
});

// --- Selected pickup point / warehouse (for map) ---
const selectedPickupPoint = computed<PickupPoint | null>(() => {
    if (!form.pickup_warehouse_id || !availablePickupPoints.value.length) return null;
    return availablePickupPoints.value.find((p) => p.id == form.pickup_warehouse_id) ?? null;
});

// --- Map modal: use OSM embed (staticmap.openstreetmap.de is deprecated) ---
const showMapModal = ref(false);
const pickupMapEmbedUrl = computed(() => {
    const p = selectedPickupPoint.value;
    if (!p || p.latitude == null || p.longitude == null) return '';
    const lat = p.latitude;
    const lng = p.longitude;
    const delta = 0.008;
    const bbox = `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lng}`;
});
function openMapModal() {
    if (!selectedPickupPoint.value) return;
    showMapModal.value = true;
}
function closeMapModal() {
    showMapModal.value = false;
}
function openGoogleMapsExternal() {
    const p = selectedPickupPoint.value;
    if (!p) return;
    if (p.latitude != null && p.longitude != null) {
        window.open(`https://www.google.com/maps?q=${p.latitude},${p.longitude}`, '_blank');
    } else if (p.address || p.location) {
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.address || p.location || p.name)}`, '_blank');
    }
}

// --- Prefill pickup_warehouse_id after nextTick ---
if (defaultPickupWarehouseId) {
    nextTick(() => {
        if (availablePickupPoints.value.find((p) => p.id == defaultPickupWarehouseId)) {
            form.pickup_warehouse_id = defaultPickupWarehouseId;
        }
    });
}

// --- Home delivery map: modal to select location ---
const deliveryMapUrl = computed(() => {
    const lat = form.latitude;
    const lng = form.longitude;
    if (lat == null || lng == null) return '';
    const delta = 0.008;
    const bbox = `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lng}`;
});

const showDeliveryMapModal = ref(false);
function useCurrentLocation() {
    showDeliveryMapModal.value = true;
}
function onDeliveryLocationConfirm(payload: { lat: number; lng: number }) {
    form.latitude = payload.lat;
    form.longitude = payload.lng;
}

// --- Shipping Fee ---
const HOME_DELIVERY_SURCHARGE = 250;
const shippingFee = ref(0);
const pickupShippingOption = computed(() => {
    const selectedRegion = regions.find((r) => r.id == form.region_id);
    return selectedRegion?.shipping_options?.pickup || null;
});
const doorShippingOption = computed(() => {
    const selectedRegion = regions.find((r) => r.id == form.region_id);
    return selectedRegion?.shipping_options?.door || null;
});

watch(
    () => [form.region_id, form.delivery_mode],
    () => {
        if (form.delivery_mode === 'pickup') {
            shippingFee.value = pickupShippingOption.value?.cost ?? 0;
        } else {
            const baseDoor = doorShippingOption.value?.cost ?? 0;
            shippingFee.value = baseDoor + HOME_DELIVERY_SURCHARGE;
        }
        if (form.delivery_mode === 'door') {
            form.pickup_warehouse_id = '';
        }
        if (form.delivery_mode === 'pickup') {
            form.latitude = null;
            form.longitude = null;
        }
    },
    { immediate: true },
);

watch(
    () => form.delivery_mode,
    () => {
        if (form.delivery_mode === 'pickup') {
            form.pickup_warehouse_id = '';
        }
    },
);

// --- Grand Total ---
const grandTotal = computed(() => cartSubtotal + shippingFee.value);

// --- Submit ---
const submit = () => {
    if (isEdit && address?.id) {
        form.put(route('checkout.addresses.update', address.id));
    } else {
        form.post(route('checkout.addresses.store'));
    }
};

// --- Price formatter ---
const formatPrice = (amount?: number | null) => `KSh ${(amount ?? 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex max-w-7xl flex-col items-start gap-6 lg:flex-row">
            <!-- LEFT: Address Form -->
            <div class="w-full rounded-lg bg-white p-6 shadow lg:w-8/12">
                <h1 class="mb-6 text-2xl font-semibold text-gray-800">
                    {{ isEdit ? 'Edit Address' : 'Add New Address' }}
                </h1>

                <form @submit.prevent="submit" class="grid grid-cols-1 gap-5">
                    <!-- Name Fields -->
                    <fieldset class="grid grid-cols-1 gap-4 rounded-md border border-gray-200 p-4 md:grid-cols-2">
                        <legend class="px-2 text-sm font-medium text-gray-700">Customer Address</legend>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            />
                            <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            />
                            <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-600">{{ form.errors.last_name }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Address</label>
                            <input
                                v-model="form.address_line_1"
                                type="text"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            />
                            <p v-if="form.errors.address_line_1" class="mt-1 text-xs text-red-600">{{ form.errors.address_line_1 }}</p>
                        </div>
                    </fieldset>

                    <!-- Delivery Details -->
                    <fieldset class="grid grid-cols-1 gap-4 rounded-md border border-gray-200 p-4 md:grid-cols-2">
                        <legend class="px-2 text-sm font-medium text-gray-700">Delivery Details</legend>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Region</label>
                            <select
                                v-model="form.region_id"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            >
                                <option value="">Select region</option>
                                <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                            <p v-if="form.errors.region_id" class="mt-1 text-xs text-red-600">{{ form.errors.region_id }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Delivery type</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input v-model="form.delivery_mode" type="radio" value="pickup" class="h-4 w-4 text-primary" />
                                    <span>Pickup point</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input v-model="form.delivery_mode" type="radio" value="door" class="h-4 w-4 text-primary" />
                                    <span>Home delivery</span>
                                </label>
                            </div>
                        </div>

                        <!-- Pickup point (when delivery type = pickup) -->
                        <div v-if="form.delivery_mode === 'pickup'" class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pickup Point</label>
                            <select
                                v-model="form.pickup_warehouse_id"
                                :required="form.delivery_mode === 'pickup'"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            >
                                <option value="">Select pickup point</option>
                                <option v-for="p in availablePickupPoints" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="selectedPickupPoint?.address || selectedPickupPoint?.location" class="mt-1 text-xs text-gray-500">
                                {{ selectedPickupPoint?.address || selectedPickupPoint?.location }}
                            </p>
                            <button
                                v-if="selectedPickupPoint && (selectedPickupPoint.latitude != null || selectedPickupPoint.address || selectedPickupPoint.location)"
                                type="button"
                                class="mt-2 w-full rounded-md border border-primary bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary hover:bg-primary/20"
                                @click="openMapModal"
                            >
                                View on map
                            </button>
                            <p v-if="form.errors.pickup_warehouse_id" class="mt-1 text-xs text-red-600">{{ form.errors.pickup_warehouse_id }}</p>
                        </div>

                        <!-- Home delivery: map + Use current location -->
                        <div v-else class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Delivery location</label>
                            <p class="mb-2 text-xs text-gray-500">We’ll deliver to this location. Use your current location or confirm the pin on the map.</p>
                            <button
                                type="button"
                                class="mb-2 rounded-md border border-primary bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary hover:bg-primary/20"
                                @click="useCurrentLocation"
                            >
                                Use current location
                            </button>
                            <div v-if="deliveryMapUrl" class="mt-2 h-[220px] w-full overflow-hidden rounded border bg-gray-100">
                                <iframe
                                    :src="deliveryMapUrl"
                                    title="Delivery location map"
                                    class="h-full w-full border-0"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                />
                            </div>
                            <p v-else class="mt-1 text-xs text-gray-500">Click “Use current location” to set your delivery pin on the map.</p>
                        </div>
                    </fieldset>

                    <!-- Default Checkbox -->
                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_default" class="h-4 w-4 rounded border-gray-300 text-primary" />
                        <span class="text-sm font-medium text-gray-700">Set as default address</span>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex justify-end gap-3">
                        <a
                            :href="route('checkout.addresses.index')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >Cancel</a
                        >
                        <button
                            type="submit"
                            class="rounded-md bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving...' : isEdit ? 'Update Address' : 'Save Address' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT: Checkout Summary -->
            <div class="w-full lg:w-4/12">
                <div class="rounded-lg bg-white p-6 shadow">
                    <h2 class="text-lg font-semibold text-gray-800">Checkout Summary</h2>

                    <div class="mt-4 flex justify-between text-sm text-gray-700">
                        <span>Cart's Total</span>
                        <span class="font-semibold text-gray-800">{{ formatPrice(cartSubtotal) }}</span>
                    </div>

                    <div class="mt-2 flex justify-between text-sm text-gray-700">
                        <span>Shipping Fee</span>
                        <span class="font-semibold text-gray-800">{{ formatPrice(shippingFee) }}</span>
                    </div>

                    <hr class="my-4 border-gray-200" />

                    <div class="flex justify-between text-sm font-semibold text-gray-800">
                        <span>Grand Total</span>
                        <span>{{ formatPrice(grandTotal) }}</span>
                    </div>

                    <button
                        type="button"
                        disabled
                        class="mt-6 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white opacity-50"
                    >
                        Complete Order
                        <ArrowRight class="h-4 w-4" />
                    </button>

                    <p class="mt-2 text-center text-xs text-gray-500">Complete pending steps to complete order</p>
                </div>

                <p class="mt-2 text-center text-xs text-gray-500">
                    By proceeding, you agree to our
                    <a href="#" class="text-primary underline">terms and conditions</a>.
                </p>
            </div>
        </section>

        <!-- Pickup point map modal -->
        <div v-if="showMapModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="closeMapModal">
            <div class="relative max-h-[90vh] w-full max-w-2xl rounded-lg bg-white shadow-xl" @click.stop>
                <div class="flex items-center justify-between border-b px-4 py-2">
                    <h3 class="font-semibold text-gray-800">{{ selectedPickupPoint?.name ?? 'Pickup point' }}</h3>
                    <button type="button" class="rounded p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700" @click="closeMapModal" aria-label="Close">×</button>
                </div>
                <div class="p-4">
                    <div v-if="pickupMapEmbedUrl" class="h-[400px] w-full overflow-hidden rounded border bg-gray-100">
                        <iframe
                            :src="pickupMapEmbedUrl"
                            title="Pickup point map"
                            class="h-full w-full border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        />
                    </div>
                    <div v-else class="flex min-h-[200px] flex-col items-center justify-center rounded border border-gray-200 bg-gray-50 py-8 text-center">
                        <p class="text-sm text-gray-600">
                            {{ (selectedPickupPoint?.address || selectedPickupPoint?.location) ? 'No coordinates for this pickup point. You can open the address in Google Maps below.' : 'Open in Google Maps to view the pickup point location.' }}
                        </p>
                    </div>
                    <div class="mt-4 flex justify-center">
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

        <!-- Home delivery: map modal to select location (Google Maps) -->
        <MapLocationModal
            v-model="showDeliveryMapModal"
            :api-key="(page.props as any).googleMapsApiKey ?? ''"
            :initial-lat="form.latitude"
            :initial-lng="form.longitude"
            :use-geolocation-on-open="true"
            @confirm="onDeliveryLocationConfirm"
        />
    </MainLayout>
</template>
