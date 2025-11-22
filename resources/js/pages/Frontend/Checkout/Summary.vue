<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, PlusCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

// --- Types ---
interface ShippingOption {
    cost: number;
    days?: number;
}

interface Address {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string;
    address: string;
    region?: string;
    pickup_point?: number | string;
    is_default: boolean;
    shipping?: {
        pickup?: ShippingOption;
        door?: ShippingOption;
    };
}

interface Owner {
    type: string;
    name: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    primary_image_url?: string | null;
}

interface Variant {
    id: number;
    final_price: number;
}

interface CartItem {
    id: number;
    quantity: number;
    unit_price: number;
    product: Product;
    variant: Variant;
    owner?: Owner | null;
}

interface Cart {
    items: CartItem[];
    totals: {
        subtotal: number;
        per_item_discount: number;
        coupon_discount: number;
        discount_total: number;
        tax: number;
        grand_total: number;
    };
    applied_coupon?: any;
    coupon_code?: string;
    coupon_error?: string;
}

const page = usePage();
const cart = (page.props as any).cart as Cart;
const addresses = (page.props as any).customer_addresses as Address[];

// --- Computed ---
const defaultAddress = computed(() => addresses.find((a) => a.is_default) || addresses[0] || null);
const subtotal = computed(() => Number(cart.totals.subtotal ?? 0));
const perItemDiscount = computed(() => Number(cart.totals.per_item_discount ?? 0));
const couponDiscount = computed(() => Number(cart.totals.coupon_discount ?? 0));
const shippingCost = computed(() => Number(defaultAddress.value?.shipping?.pickup?.cost ?? 0));
const totalWithShipping = computed(() => subtotal.value - perItemDiscount.value - couponDiscount.value + shippingCost.value);

const addressLinkLabel = computed(() => (addresses.length > 0 ? 'Change' : 'Add Address'));
const addressLinkIcon = computed(() => (addresses.length > 0 ? Edit : PlusCircle));
const addressLinkUrl = computed(() => route('checkout.addresses.index'));

const formatPrice = (amount?: number | null) => `KSh ${(amount ?? 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;

// --- Proceed to payment ---
const proceedToPayment = () => {
    if (!defaultAddress.value) return;
    router.visit(route('checkout.payment'), {
        method: 'get',
        data: { address_id: defaultAddress.value.id, coupon_code: cart.applied_coupon?.code || '' },
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex max-w-7xl flex-col gap-4 lg:flex-row">
            <!-- LEFT: Delivery Address + Delivery Details -->
            <div class="flex w-full flex-col gap-4 lg:w-8/12">
                <!-- Delivery Address -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <div class="mb-2 flex items-center justify-between">
                        <h1 class="text-lg font-semibold text-gray-800">Delivery Address</h1>
                        <a :href="addressLinkUrl" class="flex items-center gap-1 text-sm text-primary hover:text-primary/80">
                            <component :is="addressLinkIcon" class="h-4 w-4" />
                            {{ addressLinkLabel }}
                        </a>
                    </div>

                    <hr class="mb-4 border-gray-200" />

                    <div v-if="defaultAddress" class="flex flex-col gap-1">
                        <span class="font-medium text-gray-700">{{ defaultAddress.first_name }} {{ defaultAddress.last_name }}</span>
                        <div class="text-sm text-gray-600">
                            <span>
                                {{ defaultAddress.address }}
                                <template v-if="defaultAddress.phone"> | {{ defaultAddress.phone }}</template>
                                <template v-if="defaultAddress.region"> | {{ defaultAddress.region }}</template>
                                <template v-if="defaultAddress.pickup_point"> | {{ defaultAddress.pickup_point }}</template>
                            </span>
                        </div>
                    </div>

                    <p v-else class="text-sm text-gray-500">No addresses found. Please add one in your profile.</p>
                </div>

                <!-- Delivery Details -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Delivery Details</h2>
                    <hr class="mb-4 border-gray-200" />
                    <div v-if="cart.items.length" class="space-y-4">
                        <div v-for="item in cart.items" :key="item.id" class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <img
                                    :src="item.product.primary_image_url || '/fallback-image.png'"
                                    class="h-16 w-16 rounded object-cover"
                                    alt="Product Image"
                                />
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">{{ item.product.name }}</span>
                                    <span class="text-sm text-gray-500"> Supplied By: {{ item.owner?.name ?? 'N/A' }} </span>
                                    <span class="text-sm text-gray-700">X {{ item.quantity }}</span>
                                    <span class="text-sm text-gray-700">Unit Price: {{ formatPrice(item.unit_price) }}</span>
                                </div>
                            </div>
                            <div class="font-medium text-gray-700">{{ formatPrice(item.unit_price * item.quantity) }}</div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">No items in the cart.</p>
                </div>
            </div>

            <!-- RIGHT: Checkout Summary -->
            <div class="w-full lg:w-4/12">
                <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                    <h2 class="text-lg font-semibold text-gray-800">Checkout Summary</h2>

                    <div class="space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>

                        <div class="flex justify-between" v-if="perItemDiscount > 0">
                            <span>Item discounts</span>
                            <span>-{{ formatPrice(perItemDiscount) }}</span>
                        </div>

                        <div class="flex justify-between" v-if="couponDiscount > 0">
                            <span>Coupon ({{ cart.applied_coupon?.code }})</span>
                            <span>-{{ formatPrice(couponDiscount) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Shipping</span>
                            <span>{{ formatPrice(shippingCost) }}</span>
                        </div>

                        <div class="flex justify-between font-semibold text-gray-800">
                            <span>Total</span>
                            <span>{{ formatPrice(totalWithShipping) }}</span>
                        </div>
                    </div>

                    <button
                        @click="proceedToPayment"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                    >
                        Proceed to Payment
                    </button>

                    <p class="mt-2 text-center text-xs text-gray-600">
                        By proceeding, you are automatically accepting the
                        <a :href="route('terms')" class="text-primary underline hover:text-primary/80">Terms &amp; Conditions</a>
                    </p>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
