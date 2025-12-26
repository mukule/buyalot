<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

/* =============================
   TYPES
============================= */
interface SummaryItem {
    id: number;
    quantity: number;
    unit_price: number;
    line_subtotal: number;
    line_discount: number;
    product: { id: number; name: string; primary_image_url?: string | null };
    variant: { id: number; sku?: string; regular_price?: number; selling_price?: number };
}

interface Totals {
    subtotal: number;
    per_item_discount: number;
    coupon_discount: number;
    discount_total: number;
    shipping: number;
    tax: number;
    grand_total: number;
}

const page = usePage();
const props = page.props as any;

const cart = props.cart as {
    items: SummaryItem[];
    counts: { unique_items: number; total_qty: number };
    totals: Totals;
    applied_coupon?: { type: string; code: string; name: string; amount: number } | null;
    coupon_code?: string | null;
};

const defaultPhone = (props.default_phone as string) || '';
const relatedProducts = props.relatedProducts ?? [];

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
        has_discount: p.has_discount ?? false,
    })),
);

const goToProduct = (product: SimplifiedProduct) => {
    router.visit(route('products.show', { slug: product.product_slug }));
};

type Address = {
    id: number;
    label?: string | null;
    address_line_1?: string | null;
    city?: string | null;
    is_default?: boolean;
};
const addresses = ref<Address[]>(Array.isArray(props.customer_addresses) ? props.customer_addresses : []);
const selectedAddressId = ref<number | null>((props.shipping_address_id as number | null) ?? null);

const phone = ref(defaultPhone);
const initiating = ref(false);
const polling = ref(false);
const progress = ref(0);
const status = ref<'idle' | 'initiating' | 'polling' | 'success' | 'failed'>('idle');
const message = ref('');

const canPay = computed(() => !!phone.value && phone.value.trim().length >= 9 && !!selectedAddressId.value && !initiating.value && !polling.value);

const paymentDisabledMessage = computed(() => {
    if (!phone.value || phone.value.trim().length < 9) return 'Enter a valid phone number';
    if (!selectedAddressId.value) return 'Select a shipping address';
    return '';
});

const formatPrice = (amount: number) => `KSh ${amount.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;

onMounted(() => {
    status.value = 'idle';
    message.value = '';
});

function goBack() {
    router.visit(route('checkout.summary'), { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex max-w-7xl flex-col overflow-x-hidden">
            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- LEFT -->
                <div class="w-full lg:w-8/12">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h1 class="text-lg font-semibold text-gray-800">Payment</h1>

                        <div v-if="status === 'idle'" class="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                            After you click Pay, you will receive an M-Pesa STK Push on your phone.
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number (M-Pesa)</label>
                            <input
                                v-model="phone"
                                type="tel"
                                placeholder="07xxxxxxxx or 2547xxxxxxxx"
                                class="w-full rounded border px-3 py-2 focus:border-primary focus:outline-none"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex flex-col">
                                <button
                                    class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                    :class="{ 'cursor-pointer': canPay }"
                                    :disabled="!canPay"
                                >
                                    Complete Order
                                </button>
                                <span v-if="!canPay" class="mt-1 text-xs text-red-500">{{ paymentDisabledMessage }}</span>
                            </div>

                            <button @click="goBack" class="rounded border px-4 py-2 text-gray-700 hover:bg-gray-50">Back</button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="w-full lg:w-4/12">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Order Summary</h2>

                        <div class="space-y-2 text-sm text-gray-700">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>{{ formatPrice(cart.totals.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between" v-if="cart.totals.per_item_discount > 0">
                                <span>Item Discounts</span>
                                <span>-{{ formatPrice(cart.totals.per_item_discount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Shipping</span>
                                <span>{{ formatPrice(cart.totals.shipping) }}</span>
                            </div>
                        </div>

                        <hr />

                        <div class="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{{ formatPrice(cart.totals.grand_total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto mt-10 mb-8 max-w-7xl">
            <div v-if="simplifiedRelatedProducts.length">
                <ProductCarouselSection title="You might also like" :products="simplifiedRelatedProducts" @click-item="goToProduct" />
            </div>
        </section>
    </MainLayout>
</template>
