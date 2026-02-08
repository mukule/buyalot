<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, PlusCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

/* -------------------- TYPES -------------------- */

interface Address {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string;
    address: string;
    region?: string;
    pickup_point?: { id: number; name: string; region?: { name: string } };
    is_default: boolean;
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
    total_price: number;
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
const addresses = (page.props as any).customer_addresses as Address[];
const selectedShipping = (page.props as any).selected_shipping ?? null;
const relatedProducts = (page.props as any).relatedProducts ?? [];

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

/* -------------------- COMPUTED -------------------- */

const defaultAddress = computed(() => addresses.find((a) => a.is_default) || addresses[0] || null);

const sub_total = computed(() => Number(cart.totals.subtotal ?? 0));
const perItemDiscount = computed(() => Number(cart.totals.per_item_discount ?? 0));
const shippingCost = computed(() => Number(cart.totals.shipping ?? 0));
const totalWithShipping = computed(() => Number(cart.totals.grand_total ?? 0));

const addressLinkLabel = computed(() => (addresses.length > 0 ? 'Change' : 'Add Address'));

const addressLinkIcon = computed(() => (addresses.length > 0 ? Edit : PlusCircle));

const addressLinkUrl = computed(() => route('checkout.addresses.index'));

const formatPrice = (amount?: number | null) =>
    `KSh ${(amount ?? 0).toLocaleString(undefined, {
        maximumFractionDigits: 2,
    })}`;

const proceedToPayment = () => {
    router.visit(route('checkout.payment'), {
        method: 'get',
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex flex-col gap-4 lg:flex-row">
            <!-- LEFT -->
            <div class="flex w-full flex-col gap-4 lg:w-9/12">
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

                    <div v-if="defaultAddress" class="flex flex-col gap-2">
                        <span class="font-medium text-gray-700">
                            {{ defaultAddress.first_name }}
                            {{ defaultAddress.last_name }}
                        </span>

                        <div class="text-sm text-gray-600">
                            <span>
                                <template v-if="defaultAddress.phone">
                                    {{ defaultAddress.phone }}
                                </template>
                                <template v-if="(defaultAddress.pickup_warehouse?.region?.name ?? defaultAddress.pickup_point?.region?.name)">
                                    | {{ defaultAddress.pickup_warehouse?.region?.name ?? defaultAddress.pickup_point?.region?.name }}
                                </template>
                                <template v-if="(defaultAddress.pickup_warehouse?.name ?? defaultAddress.pickup_point?.name)">
                                    | {{ defaultAddress.pickup_warehouse?.name ?? defaultAddress.pickup_point?.name }}
                                </template>
                            </span>
                        </div>

                        <!-- Shipping Display -->
                        <div v-if="selectedShipping" class="mt-2 text-sm text-gray-700">
                            <span class="font-medium"> {{ selectedShipping.method === 'pickup' ? 'Pickup' : 'Door Delivery' }}: </span>

                            {{ formatPrice(selectedShipping.cost) }}

                            <template v-if="selectedShipping.days">
                                | Estimated
                                {{ selectedShipping.days }} day(s)
                            </template>

                            <template v-if="selectedShipping.region"> | Region: {{ selectedShipping.region }} </template>
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
                                <img :src="item.product.primary_image_url || '/fallback-image.png'" class="h-16 w-16 rounded object-cover" />

                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-800">
                                        {{ item.product.name }}
                                    </span>

                                    <span class="text-sm text-gray-500">
                                        Supplied By:
                                        {{ item.owner?.name ?? 'N/A' }}
                                    </span>

                                    <span class="text-sm text-gray-700"> X {{ item.quantity }} </span>

                                    <span class="text-sm text-gray-700">
                                        Unit Price:
                                        {{ formatPrice(item.unit_price) }}
                                    </span>
                                </div>
                            </div>

                            <div class="font-medium text-gray-700">
                                {{ formatPrice(item.total_price) }}
                            </div>
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
                    @click-item="goToProduct"
                    :slug="simplifiedRelatedProducts[0]?.category_slug ?? '/'"
                />
            </div>
        </section>
    </MainLayout>
</template>
