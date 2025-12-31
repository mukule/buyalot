<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

interface SummaryItem {
    id: number;
    quantity: number;
    unit_price: number;
    total_price: number;
    line_discount: number;
    product: { id: number; name: string; primary_image_url?: string | null };
    variant: { id: number; sku?: string; unit_price?: number };
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
    cart_id?: number;
    items: SummaryItem[];
    counts: { unique_items: number; total_qty: number };
    totals: Totals;
    applied_coupon?: { type: string; code: string; name: string; amount: number } | null;
    coupon_code?: string | null;
};

const defaultPhone = (props.default_phone as string) || '';

// Addresses
type Address = {
    id: number;
    type?: string | null;
    name?: string | null;
    label?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    company?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    country?: string | null;
    phone?: string | null;
    is_default?: boolean;
    coordinates?: { lat?: number; lng?: number; accuracy?: number } | null;
    delivery_instructions?: string | null;
};

const addresses = ref<Address[]>(Array.isArray(props.customer_addresses) ? props.customer_addresses : []);
const selectedAddressId = ref<number | null>((props.shipping_address_id as number | null) ?? null);

const showAddressForm = ref(false);

const selected_shipping = ref<{
    method: string;
    cost: number;
    days: number;
    region: string;
    pickup_point?: string;
} | null>(props.selected_shipping ?? null);

const formatPrice = (amount?: number | null) => {
    if (amount == null || isNaN(amount)) return 'KSh 0.00';
    return `KSh ${amount.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
};

// Phone handling
const phone = ref<string>(defaultPhone);

// UI State
const initiating = ref(false);
const polling = ref(false);
const progress = ref(0);
const status = ref<'idle' | 'initiating' | 'polling' | 'success' | 'failed'>('idle');
const message = ref<string>('');
const insufficientItems = ref<Array<{ product_variant_id: number; requested: number; available: number; product_name: string }>>([]);

// Payment refs
const paymentId = ref<string | null>(null);
const paymentReference = ref<string | null>(null);
const currentOrder = ref<null | { id: number; ulid?: string; total_amount: number; currency?: string; paymentInit: any }>(null);

const canPay = computed(() => !!phone.value && phone.value.trim().length >= 9 && !!selectedAddressId.value && !initiating.value && !polling.value);

let pollTimer: any = null;

onMounted(() => {
    status.value = 'idle';
    message.value = '';
});

// --- CART QUANTITY FUNCTIONS ---
async function increaseQty(item: SummaryItem) {
    try {
        const axios = (window as any).axios || (await import('axios')).default;
        await axios.post(route('cart.store'), {
            product_variant_id: item.variant.id,
            quantity: item.quantity + 1,
        });
        window.location.reload();
    } catch (e) {
        console.error(e);
    }
}

async function decreaseQty(item: SummaryItem) {
    if (item.quantity <= 1) return;
    try {
        const axios = (window as any).axios || (await import('axios')).default;
        await axios.post(route('cart.store'), {
            product_variant_id: item.variant.id,
            quantity: item.quantity - 1,
        });
        window.location.reload();
    } catch (e) {
        console.error(e);
    }
}

// --- PAYMENT FUNCTIONS ---
async function startPayment() {
    if (!canPay.value) return;

    try {
        status.value = 'initiating';
        initiating.value = true;

        if (currentOrder.value) {
            message.value = 'Retrying payment for your existing checkout session...';
            await pollPayment(currentOrder.value);
            return;
        }

        message.value = 'Initializing payment...';

        const createPaymentPayload = {
            cart_id: cart.cart_id,
            customer_id: (page.props as any)?.auth?.customer_id,
            billing_address_id: selectedAddressId.value,
            shipping_address_id: selectedAddressId.value,
            notes: 'Customer requested express delivery',
            coupon_code: cart.coupon_code,
            shipping_amount: cart.totals.shipping,
            payment_provider: 'mpesa',
            phone: phone.value.trim(),
        };

        const axios = (window as any).axios || (await import('axios')).default;
        const resp = await axios.post(route('orders.store'), createPaymentPayload, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            withCredentials: true,
        });

        const checkoutSession = resp.data?.checkout_session;
        const paymentInit = resp.data?.payment_init;

        if (!checkoutSession?.id || !paymentInit?.data?.checkout_request_id) {
            throw new Error('Payment initiation failed.');
        }

        currentOrder.value = {
            id: checkoutSession.id,
            ulid: checkoutSession.ref_num,
            total_amount: checkoutSession.amount,
            currency: checkoutSession.currency,
            paymentInit,
        };

        message.value = 'Payment initiated. Check your phone to complete the M-Pesa STK Push.';
        await pollPayment(currentOrder.value);
    } catch (e: any) {
        status.value = 'failed';
        initiating.value = false;
        polling.value = false;
        message.value = e?.response?.data?.message || e?.message || 'Failed to start payment.';
        console.error(e);
    }
}

async function pollPayment(order: { paymentInit: any }) {
    try {
        const checkoutRequestId = order.paymentInit.data.checkout_request_id;

        status.value = 'polling';
        initiating.value = false;
        polling.value = true;
        message.value = 'Awaiting your M-Pesa approval. Check your phone and enter your PIN.';
        startProgressBar();

        await pollVerifyUntilComplete(checkoutRequestId);
    } catch (e: any) {
        status.value = 'failed';
        initiating.value = false;
        polling.value = false;
        message.value = e?.message || 'Unexpected error while polling payment.';
        console.error(e);
    }
}

function startProgressBar() {
    progress.value = 10;
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
        if (progress.value < 95) {
            progress.value += Math.random() * 5;
        }
    }, 800);
}

async function pollVerifyUntilComplete(checkoutRequestId: string) {
    const maxSeconds = 30;
    const intervalMs = 4000;
    let elapsed = 0;
    let stopped = false;

    return new Promise<void>((resolve) => {
        const iv = setInterval(async () => {
            if (stopped) return;

            try {
                elapsed += intervalMs / 1000;
                const axios = (window as any).axios || (await import('axios')).default;
                const { data } = await axios.get(route('payments.status', checkoutRequestId), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    withCredentials: true,
                });

                const verification = data?.verification;
                if (!verification) return;

                const { success, message: msg, state } = verification;
                const isTerminal = success === true || ['SUCCESS', 'FAILED', 'CANCELED'].includes(state ?? '');

                if (isTerminal) {
                    stopped = true;
                    clearInterval(iv);
                    if (pollTimer) clearInterval(pollTimer);
                    polling.value = false;
                    status.value = success === true ? 'success' : 'failed';
                    message.value = msg || (success === true ? 'Payment completed successfully.' : 'Payment failed. Please try again.');
                    resolve();
                    return;
                }

                if (elapsed >= maxSeconds) {
                    stopped = true;
                    clearInterval(iv);
                    if (pollTimer) clearInterval(pollTimer);
                    polling.value = false;
                    status.value = 'failed';
                    message.value = 'Payment Failed, Please try again';
                    resolve();
                }
            } catch {
                stopped = true;
                clearInterval(iv);
                if (pollTimer) clearInterval(pollTimer);
                polling.value = false;
                status.value = 'failed';
                message.value = 'Could not verify payment. Please try again.';
                resolve();
            }
        }, intervalMs);
    });
}

// --- RELATED PRODUCTS ---
const relatedProducts = (props.relatedProducts ?? []) as any[];

const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    relatedProducts.map((p) => ({
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

// --- ROUTING ---
const goToProduct = (product: SimplifiedProduct) => {
    router.visit(route('products.show', { slug: product.product_slug }));
};

function goBack() {
    router.visit(route('checkout.summary'), { preserveScroll: true, preserveState: true });
}

const goToAddressPage = () => {
    router.visit(route('checkout.addresses.index'));
};
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 px-2">
            <div class="grid gap-4 lg:grid-cols-12">
                <!-- LEFT — Payment (8/12) -->
                <div class="lg:col-span-8">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h1 class="text-lg font-semibold text-gray-800">Payment</h1>

                        <!-- COMPACT CART ITEMS LIST -->
                        <div v-if="cart.items.length" class="mb-4 space-y-2">
                            <template v-for="item in cart.items" :key="item.id">
                                <div class="flex items-center justify-between gap-2 text-xs text-gray-700">
                                    <!-- Product image -->
                                    <img
                                        :src="item.product.primary_image_url || '/fallback-image.png'"
                                        class="h-10 w-10 rounded object-cover"
                                        alt="Product Image"
                                    />

                                    <!-- Product name & unit price -->
                                    <div class="flex flex-1 flex-col overflow-hidden">
                                        <span class="truncate font-medium">{{ item.product.name }}</span>
                                        <span class="text-gray-500">{{ formatPrice(item.unit_price) }}</span>
                                    </div>

                                    <!-- Quantity controls -->
                                    <div class="flex items-center gap-1">
                                        <button
                                            @click="decreaseQty(item)"
                                            class="flex h-5 w-5 items-center justify-center rounded bg-gray-200 text-gray-700 hover:bg-gray-300"
                                        >
                                            -
                                        </button>

                                        <span class="w-5 text-center">{{ item.quantity }}</span>

                                        <button
                                            @click="increaseQty(item)"
                                            class="flex h-5 w-5 items-center justify-center rounded bg-gray-200 text-gray-700 hover:bg-gray-300"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <!-- Total price -->
                                    <span class="ml-2 w-12 text-right font-medium">{{ formatPrice(item.total_price) }}</span>
                                </div>
                            </template>
                        </div>

                        <!-- Pickup Point / Shipping Address (read-only) -->
                        <div>
                            <h2 class="mb-2 text-sm font-medium text-gray-700">PickUp Point</h2>

                            <div v-if="addresses.length > 0" class="space-y-2 rounded border p-3">
                                <div class="flex items-start justify-between">
                                    <p class="text-sm text-gray-800">
                                        {{ selected_shipping ? `${selected_shipping.region} - ${selected_shipping.pickup_point ?? ''}` : '' }}
                                    </p>

                                    <button
                                        @click="goToAddressPage"
                                        class="focus:ring-opacity-50 focus:outline-non rounded border border-primary px-4 py-2 font-medium text-primary transition-colors duration-200 hover:bg-primary hover:text-white focus:ring-2 focus:ring-primary"
                                    >
                                        Change
                                    </button>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">
                                    If you order now, you will receive your order in {{ selected_shipping?.days ?? '?' }} day{{
                                        selected_shipping?.days && selected_shipping.days > 1 ? 's' : ''
                                    }}.
                                </p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number (M-Pesa)</label>
                            <input
                                v-model="phone"
                                type="tel"
                                placeholder="e.g. 07xxxxxxxx or 2547xxxxxxxx"
                                class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                            />
                            <p class="mt-1 text-xs text-gray-500">We prefilled your phone number. You can change it before paying.</p>
                        </div>

                        <!-- Status + Progress -->
                        <div v-if="status === 'initiating' || status === 'polling'" class="space-y-2">
                            <div class="h-2 w-full overflow-hidden rounded bg-gray-200">
                                <div class="h-2 bg-primary transition-all" :style="{ width: `${Math.min(100, Math.round(progress))}%` }"></div>
                            </div>
                            <div class="text-sm text-gray-700">{{ message }}</div>
                            <ul class="list-inside list-disc text-xs text-gray-600">
                                <li>Ensure your phone is on and has network coverage.</li>
                                <li>Check for the M-Pesa prompt and enter your PIN to complete.</li>
                                <li>Do not close this page while we confirm your payment.</li>
                            </ul>
                        </div>

                        <!-- Success -->
                        <div v-if="status === 'success'" class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                            {{ message }}
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="startPayment"
                                :disabled="!canPay"
                                class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Complete order
                            </button>

                            <button
                                @click="goBack"
                                class="focus:ring-opacity-50 rounded border border-primary px-4 py-2 font-medium text-primary transition-colors duration-200 hover:bg-primary hover:text-white focus:ring-2 focus:ring-primary focus:outline-none"
                            >
                                Back
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-gray-600">
                            By proceeding, you are automatically accepting the
                            <a :href="route('terms')" class="text-primary underline hover:text-primary/80"> Terms &amp; Conditions </a>
                        </p>
                    </div>
                </div>

                <!-- RIGHT — Order Summary (4/12) -->
                <div class="lg:col-span-4">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Order Summary</h2>

                        <div class="space-y-2 text-sm text-gray-700">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>{{ formatPrice(cart.totals.subtotal) }}</span>
                            </div>

                            <div class="flex justify-between" v-if="cart.totals.per_item_discount > 0">
                                <span>Item discounts</span>
                                <span>-{{ formatPrice(cart.totals.per_item_discount) }}</span>
                            </div>

                            <div class="flex justify-between" v-if="cart.totals.coupon_discount > 0">
                                <span>Coupon ({{ cart.applied_coupon?.code }})</span>
                                <span>-{{ formatPrice(cart.totals.coupon_discount) }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span>Shipping</span>
                                <span>{{ formatPrice(cart.totals.shipping) }}</span>
                            </div>

                            <div class="flex justify-between" v-if="cart.totals.tax > 0">
                                <span>Tax</span>
                                <span>{{ formatPrice(cart.totals.tax) }}</span>
                            </div>
                        </div>

                        <hr class="border-gray-200" />

                        <div class="flex justify-between text-base font-semibold text-gray-800">
                            <span>Total</span>
                            <span>{{ formatPrice(cart.totals.grand_total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto mt-8 mb-8" v-if="simplifiedRelatedProducts.length">
            <ProductCarouselSection
                title="Related Products"
                :products="simplifiedRelatedProducts"
                :slug="simplifiedRelatedProducts[0]?.category_slug ?? '/'"
                @click-item="goToProduct"
            />
        </section>
    </MainLayout>
</template>
