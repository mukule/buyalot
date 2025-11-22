<script setup lang="ts">
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
const defaultPickupPointId = (page.props as any).selected_pickup_point_id || '';
const defaultIsDefault = Boolean((page.props as any).is_default);

// --- Form ---
interface AddressForm {
    first_name: string;
    last_name: string;
    phone: string;
    address_line_1: string;
    region_id: string | number;
    pickup_point_id: string | number;
    is_default: boolean;
}

const form = useForm<AddressForm>({
    first_name: defaultFirst,
    last_name: defaultLast,
    phone: defaultPhone,
    address_line_1: defaultAddressLine,
    region_id: defaultRegionId,
    pickup_point_id: '', // set after nextTick to ensure select options exist
    is_default: defaultIsDefault,
});

// --- Computed: available pickup points based on selected region ---
const availablePickupPoints = computed(() => {
    const selectedRegion = regions.find((r) => r.id == form.region_id);
    return selectedRegion?.pickup_points || [];
});

// --- Prefill pickup_point_id after nextTick ---
if (defaultPickupPointId) {
    nextTick(() => {
        // Ensure the pickup point exists in the computed list
        if (availablePickupPoints.value.find((p) => p.id == defaultPickupPointId)) {
            form.pickup_point_id = defaultPickupPointId;
        }
    });
}

// --- Shipping Fee ---
const shippingFee = ref(0);
const selectedShippingOption = computed(() => {
    const selectedRegion = regions.find((r) => r.id == form.region_id);
    return selectedRegion?.shipping_options?.pickup || null;
});

watch(
    () => form.region_id,
    () => {
        shippingFee.value = selectedShippingOption.value?.cost ?? 0;
        // Reset pickup point if region changes
        form.pickup_point_id = '';
    },
    { immediate: true },
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

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pickup Point</label>
                            <select
                                v-model="form.pickup_point_id"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                            >
                                <option value="">Select pickup point</option>
                                <option v-for="p in availablePickupPoints" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="form.errors.pickup_point_id" class="mt-1 text-xs text-red-600">{{ form.errors.pickup_point_id }}</p>
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
    </MainLayout>
</template>
