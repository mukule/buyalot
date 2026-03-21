<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
    latitude?: number | null;
    longitude?: number | null;
    delivery_instructions?: string | null;
};

const addresses = ref<Address[]>(Array.isArray(props.customer_addresses) ? props.customer_addresses : []);
const selectedAddressId = ref<number | null>((props.shipping_address_id as number | null) ?? null);

const selected_shipping = ref<{
    method: string;
    cost: number;
    days: number;
    region: string;
    pickup_point?: string;
} | null>(props.selected_shipping ?? null);

// Delivery mode during checkout: pickup vs home delivery.
// Default from selected_shipping if provided, otherwise pickup.
const deliveryMode = ref<'pickup' | 'door'>(
    (selected_shipping.value?.method as 'pickup' | 'door' | undefined) ?? 'pickup',
);

const selectedAddress = computed(() => {
    if (!selectedAddressId.value) return null;
    return addresses.value.find((a) => a.id === selectedAddressId.value) ?? null;
});

const googleMapsApiKey = (page.props as any).googleMapsApiKey ?? '';

const deliveryMapEmbedUrl = computed(() => {
    const addr = selectedAddress.value;
    if (!addr || addr.latitude == null || addr.longitude == null) return '';
    const lat = addr.latitude;
    const lng = addr.longitude;
    if (googleMapsApiKey) {
        return `https://www.google.com/maps/embed/v1/place?key=${encodeURIComponent(googleMapsApiKey)}&q=${lat},${lng}&zoom=15`;
    }
    return `https://www.google.com/maps?q=${lat},${lng}`;
});

const hasDeliveryCoordinates = computed(() => {
    const addr = selectedAddress.value;
    return addr && addr.latitude != null && addr.longitude != null;
});

// --- Shipping: backend already sends correct amount
const effectiveShipping = computed(() => Number(cart.totals.shipping ?? 0));
const effectiveGrandTotal = computed(() => Number(cart.totals.grand_total ?? 0));

// Pay on Delivery: available when no cod_min_amount set, or when order total >= cod_min_amount
const codMinAmount = (props.cod_min_amount as number | null | undefined) ?? null;
const isCodAvailable = computed(() =>
    codMinAmount == null ? true : effectiveGrandTotal.value >= codMinAmount,
);

const formatPrice = (amount?: number | null) => {
    if (amount == null || isNaN(amount)) return 'KSh 0';
    return `KSh ${Math.round(amount).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
};

// Phone handling
const phone = ref<string>(defaultPhone);

// UI State
const initiating = ref(false);
const polling = ref(false);
const progress = ref(0);
const animatedDots = ref('.');
const status = ref<'idle' | 'initiating' | 'polling' | 'success' | 'failed'>('idle');
const message = ref<string>('');
const currentOrder = ref<null | { id: number; ulid?: string; total_amount: number; currency?: string; paymentInit: any }>(null);

const canPay = computed(() => {
    if (!selectedAddressId.value) return false;
    if (initiating.value || polling.value) return false;

    if (paymentMethod.value === 'mpesa') {
        return !!phone.value && phone.value.trim().length >= 9;
    }

    if (paymentMethod.value === 'cod') {
        return true; // no phone required for COD
    }

    return false;
});

let pollTimer: any = null;
let dotsTimer: any = null;

// ── Reservation countdown ────────────────────────────────────────────────────

const reservationExpiresAt = ref<Date | null>(
    props.reservation_expires_at ? new Date(props.reservation_expires_at as string) : null,
);
const reservationSecondsLeft = ref(0);
const reservationExpired = ref(false);
const reservationMessage = ref('');
let reservationTimer: ReturnType<typeof setInterval> | null = null;

function updateReservationCountdown() {
    if (!reservationExpiresAt.value) return;
    const diff = Math.floor((reservationExpiresAt.value.getTime() - Date.now()) / 1000);
    reservationSecondsLeft.value = Math.max(0, diff);
    if (diff <= 0) {
        reservationExpired.value = true;
        stopReservationTimer();
        triggerReReserve();
    }
}

function startReservationTimer() {
    stopReservationTimer();
    updateReservationCountdown();
    reservationTimer = setInterval(updateReservationCountdown, 1000);
}

function stopReservationTimer() {
    if (reservationTimer !== null) {
        clearInterval(reservationTimer);
        reservationTimer = null;
    }
}

async function triggerReReserve() {
    reservationMessage.value = 'Checking stock availability…';
    try {
        const axios = (window as any).axios || (await import('axios')).default;
        const { data } = await axios.post(route('checkout.re-reserve'), {}, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            withCredentials: true,
        });
        if (data.ok) {
            reservationExpiresAt.value = new Date(data.expires_at);
            reservationExpired.value = false;
            reservationMessage.value = data.refreshed
                ? 'Items are still available — your reservation has been refreshed.'
                : '';
            startReservationTimer();
        } else {
            reservationMessage.value = data.message || 'Some items are no longer available.';
            // Redirect to cart after a short delay so the customer can read the message
            setTimeout(() => router.visit(route('cart.index')), 3000);
        }
    } catch (e: any) {
        const msg = e?.response?.data?.message;
        if (msg) {
            reservationMessage.value = msg;
            setTimeout(() => router.visit(route('cart.index')), 3000);
        } else {
            reservationMessage.value = 'Could not verify stock. Please refresh the page.';
        }
    }
}

const reservationMinutes = computed(() => Math.floor(reservationSecondsLeft.value / 60));
const reservationSeconds = computed(() => String(reservationSecondsLeft.value % 60).padStart(2, '0'));
const reservationUrgent = computed(() => reservationSecondsLeft.value <= 60 && reservationSecondsLeft.value > 0);

// ─────────────────────────────────────────────────────────────────────────────

onMounted(() => {
    status.value = 'idle';
    message.value = '';
    // Ensure COD is not selected when it's not available
    if (paymentMethod.value === 'cod' && !isCodAvailable.value) {
        paymentMethod.value = 'mpesa';
    }
    if (reservationExpiresAt.value) {
        startReservationTimer();
    }
});

onUnmounted(() => {
    stopReservationTimer();
});

function startDotsAnimation() {
    animatedDots.value = '.';
    if (dotsTimer) clearInterval(dotsTimer);
    dotsTimer = setInterval(() => {
        if (animatedDots.value === '...') {
            animatedDots.value = '.';
        } else {
            animatedDots.value += '.';
        }
    }, 500);
}

function stopDotsAnimation() {
    if (dotsTimer) {
        clearInterval(dotsTimer);
        dotsTimer = null;
    }
}

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

    // Require a valid delivery option. For pickup, enforce that a pickup point exists.
    if (!selected_shipping.value) {
        status.value = 'failed';
        message.value = 'Please select a delivery option (pickup point or door delivery) before completing your order.';
        return;
    }
    if (deliveryMode.value === 'pickup' && !selected_shipping.value.pickup_point) {
        status.value = 'failed';
        message.value = 'Please select your PickUp Point in the address step before completing your order.';
        return;
    }

    if (paymentMethod.value === 'cod') {
        await placeCashOnDeliveryOrder();
        return;
    }

    try {
        status.value = 'initiating';
        initiating.value = true;
        startDotsAnimation();

        if (currentOrder.value) {
            message.value = 'Retrying payment for your existing checkout session...';
            await pollPayment(currentOrder.value);
            return;
        }

        message.value = 'Initializing payment...';

        const shippingAmount = effectiveShipping.value;

        const createPaymentPayload = {
            cart_id: cart.cart_id,
            customer_id: (page.props as any)?.auth?.customer_id,
            billing_address_id: selectedAddressId.value,
            shipping_address_id: selectedAddressId.value,
            notes: 'Customer requested express delivery',
            coupon_code: cart.coupon_code,
            shipping_amount: shippingAmount,
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
            const msg =
                paymentInit?.message ||
                resp.data?.message ||
                'Payment initiation failed. Please check your M-Pesa number and try again.';
            throw new Error(msg);
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
        stopDotsAnimation();
        const errMsg =
            e?.response?.data?.message || e?.message || 'Failed to start payment.';
        message.value = errMsg;
        console.error(e);
    }
}

async function pollPayment(order: { paymentInit: any }) {
    try {
        const checkoutRequestId = order.paymentInit.data.checkout_request_id;

        status.value = 'polling';
        initiating.value = false;
        polling.value = true;
        startDotsAnimation();
        message.value = 'Awaiting your M-Pesa approval. Check your phone and enter your PIN.';
        startProgressBar();

        const result = await pollVerifyUntilComplete(checkoutRequestId);

        if (result === 'success') {
            const customerId = (page.props as any)?.auth?.customer_id;
            setTimeout(() => {
                router.visit(route('customers.dashboard', { customer: customerId }), {
                    data: { success: message.value },
                });
            }, 2000);
        }
    } catch (e: any) {
        status.value = 'failed';
        initiating.value = false;
        polling.value = false;
        stopDotsAnimation();
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

async function pollVerifyUntilComplete(checkoutRequestId: string): Promise<'success' | 'failed'> {
    const maxSeconds = 30;
    const intervalMs = 4000;
    let elapsed = 0;
    let stopped = false;

    return new Promise<'success' | 'failed'>((resolve) => {
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
                    stopDotsAnimation();
                    polling.value = false;
                    const result: 'success' | 'failed' = success === true ? 'success' : 'failed';
                    status.value = result;
                    message.value = msg || (success === true ? 'Payment completed successfully.' : 'Payment failed. Please try again.');
                    resolve(result);
                    return;
                }

                if (elapsed >= maxSeconds) {
                    stopped = true;
                    clearInterval(iv);
                    if (pollTimer) clearInterval(pollTimer);
                    stopDotsAnimation();
                    polling.value = false;
                    status.value = 'failed';
                    message.value = 'Payment Failed, Please try again';
                    resolve('failed');
                }
            } catch {
                stopped = true;
                clearInterval(iv);
                if (pollTimer) clearInterval(pollTimer);
                stopDotsAnimation();
                polling.value = false;
                status.value = 'failed';
                message.value = 'Could not verify payment. Please try again.';
                resolve('failed');
            }
        }, intervalMs);
    });
}

async function placeCashOnDeliveryOrder() {
    try {
        status.value = 'initiating';
        initiating.value = true;
        message.value = 'Placing your order...';

        const axios = (window as any).axios || (await import('axios')).default;

        const shippingAmount = effectiveShipping.value;

        const resp = await axios.post(
            route('orders.store'),
            {
                cart_id: cart.cart_id,
                customer_id: (page.props as any)?.auth?.customer_id,
                billing_address_id: selectedAddressId.value,
                shipping_address_id: selectedAddressId.value,
                notes: 'Cash on Delivery Order',
                coupon_code: cart.coupon_code,
                shipping_amount: shippingAmount,
                payment_provider: 'cod',
            },
            {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                withCredentials: true,
            },
        );

        console.log('payment on delivery response {} ', resp);

        status.value = 'success';
        initiating.value = false;
        message.value = 'Order placed successfully. You will pay upon delivery.';

        const customerId = (page.props as any)?.auth?.customer_id;

        setTimeout(() => {
            router.visit(route('customers.dashboard', { customer: customerId }), {
                data: { success: 'Order placed successfully (Cash on Delivery).' },
            });
        }, 2000);
    } catch (e: any) {
        status.value = 'failed';
        initiating.value = false;
        message.value = e?.response?.data?.message || 'Failed to place order.';
        console.error(e);
    }
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

const paymentMethod = ref<'mpesa' | 'cod'>('mpesa');
</script>

<template>
    <MainLayout>
        <!-- ── Reservation timer banner ─────────────────────────────────── -->
        <div
            v-if="reservationExpiresAt && !reservationExpired && reservationSecondsLeft > 0"
            class="sticky top-0 z-30 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium shadow-sm"
            :class="reservationUrgent ? 'bg-red-600 text-white' : 'bg-amber-500 text-white'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Items reserved for
            <span class="tabular-nums font-bold">{{ reservationMinutes }}:{{ reservationSeconds }}</span>
            — complete payment before time runs out.
        </div>

        <!-- Re-reserve feedback (shown while checking / after refresh) -->
        <div
            v-if="reservationMessage"
            class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium"
            :class="reservationExpired && !reservationExpiresAt ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-800'"
        >
            {{ reservationMessage }}
        </div>

        <section class="mx-auto mt-4 mb-4 px-2">
            <div class="grid gap-4 lg:grid-cols-12">
                <!-- LEFT — Payment -->
                <div class="lg:col-span-9">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h1 class="text-lg font-semibold text-gray-800">Payment</h1>

                        <!-- CART ITEMS -->
                        <div v-if="cart.items.length" class="mb-4 space-y-2">
                            <template v-for="item in cart.items" :key="item.id">
                                <div class="flex items-center justify-between gap-2 text-xs text-gray-700">
                                    <img
                                        :src="item.product.primary_image_url || '/fallback-image.png'"
                                        class="h-10 w-10 rounded object-cover"
                                        alt="Product Image"
                                    />

                                    <div class="flex flex-1 flex-col overflow-hidden">
                                        <span class="truncate font-medium">
                                            {{ item.product.name }}
                                        </span>
                                        <span class="text-gray-500">
                                            {{ formatPrice(item.unit_price) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <button
                                            @click="decreaseQty(item)"
                                            class="flex h-5 w-5 items-center justify-center rounded bg-gray-200 text-gray-700 hover:bg-gray-300"
                                        >
                                            -
                                        </button>

                                        <span class="w-5 text-center">
                                            {{ item.quantity }}
                                        </span>

                                        <button
                                            @click="increaseQty(item)"
                                            class="flex h-5 w-5 items-center justify-center rounded bg-gray-200 text-gray-700 hover:bg-gray-300"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <span class="ml-2 w-12 text-right font-medium">
                                        {{ formatPrice(item.total_price) }}
                                    </span>
                                </div>
                            </template>
                        </div>

                        <!-- DELIVERY METHOD / LOCATION -->
                        <div>
                            <h2 class="mb-2 text-sm font-medium text-gray-700">Delivery details</h2>

                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs text-gray-600">
                                    {{
                                        selected_shipping
                                            ? (selected_shipping.method === 'pickup' ? 'Pickup point' : 'Home delivery') +
                                              (selected_shipping.region ? ` – ${selected_shipping.region}` : '')
                                            : 'Select an address with a delivery method'
                                    }}
                                </p>

                                <button
                                    @click="goToAddressPage"
                                    class="rounded border border-primary px-4 py-2 text-xs font-medium text-primary transition hover:bg-primary hover:text-white"
                                >
                                    Change address / method
                                </button>
                            </div>

                            <div v-if="selected_shipping" class="space-y-2 rounded border p-3">
                                <p class="text-sm text-gray-800">
                                    <span class="font-medium">
                                        {{ selected_shipping.method === 'pickup' ? 'Pickup point' : 'Home delivery' }}
                                    </span>
                                    <span v-if="selected_shipping.pickup_point">
                                        – {{ selected_shipping.pickup_point }}
                                    </span>
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    If you order now, you will receive your order in
                                    {{ selected_shipping.days ?? '?' }}
                                    day{{ selected_shipping.days && selected_shipping.days > 1 ? 's' : '' }}.
                                </p>

                                <!-- Map preview for delivery coordinates (home delivery or precise address) -->
                                <div v-if="hasDeliveryCoordinates" class="mt-2 h-[220px] w-full overflow-hidden rounded border bg-gray-100">
                                    <iframe
                                        v-if="googleMapsApiKey"
                                        :src="deliveryMapEmbedUrl"
                                        title="Delivery location - Google Maps"
                                        class="h-full w-full border-0"
                                        loading="lazy"
                                        allowfullscreen
                                        referrerpolicy="no-referrer-when-downgrade"
                                    />
                                    <a
                                        v-else
                                        :href="deliveryMapEmbedUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex h-full items-center justify-center text-sm text-primary underline"
                                    >
                                        View delivery location on Google Maps
                                    </a>
                                </div>
                                <p v-else class="mt-1 text-xs text-gray-500">
                                    To see a map here, edit your address and allow us to use your location so we can store coordinates.
                                </p>
                            </div>
                        </div>

                        <!-- PAYMENT METHOD -->
                        <div>
                            <h2 class="mb-2 text-sm font-medium text-gray-700">Payment Method</h2>

                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded border p-3 hover:bg-gray-50">
                                    <input type="radio" value="mpesa" v-model="paymentMethod" />
                                    <span class="text-sm font-medium text-gray-800"> M-Pesa </span>
                                </label>

                                <label v-if="isCodAvailable" class="flex cursor-pointer items-center gap-2 rounded border p-3 hover:bg-gray-50">
                                    <input type="radio" value="cod" v-model="paymentMethod" />
                                    <span class="text-sm font-medium text-gray-800"> Pay on Delivery </span>
                                </label>

                                <p v-else-if="codMinAmount != null" class="rounded border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    Pay on Delivery is available for orders over KSh {{ Math.round(codMinAmount).toLocaleString() }}. Your order total is {{ formatPrice(effectiveGrandTotal) }}.
                                </p>
                            </div>
                        </div>

                        <!-- PHONE (MPESA ONLY) -->
                        <div v-if="paymentMethod === 'mpesa'">
                            <label class="mb-1 block text-sm font-medium text-gray-700"> Phone Number (M-Pesa) </label>

                            <input
                                v-model="phone"
                                type="tel"
                                placeholder="e.g. 07xxxxxxxx or 2547xxxxxxxx"
                                class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                            />

                            <p class="mt-1 text-xs text-gray-500">We prefilled your phone number. You can change it before paying.</p>

                            <div v-if="status === 'polling'" class="mt-2 text-sm font-medium text-primary">
                                Awaiting M-Pesa payment {{ animatedDots }}
                            </div>
                        </div>

                        <!-- COD NOTICE -->
                        <div v-if="paymentMethod === 'cod'" class="rounded p-3 text-xs">
                            <p class="mb-2">You will pay in cash when your order is delivered.</p>

                            <div class="flex justify-start">
                                <img src="/cod.jpeg" alt="Pay on Delivery" class="h-32 w-auto object-contain" />
                            </div>
                        </div>

                        <!-- STATUS + PROGRESS -->
                        <div v-if="status === 'initiating' || status === 'polling'" class="space-y-2">
                            <div v-if="status === 'initiating' && paymentMethod != 'cod'" class="text-sm font-medium text-primary">
                                Sending Payment request {{ animatedDots }}
                            </div>

                            <div v-if="status === 'polling'" class="hidden h-2 w-full overflow-hidden rounded bg-gray-200">
                                <div
                                    class="h-2 bg-primary transition-all"
                                    :style="{
                                        width: `${Math.min(100, Math.round(progress))}%`,
                                    }"
                                ></div>
                            </div>

                            <div class="text-sm text-gray-700">
                                {{ message }}
                            </div>

                            <ul v-if="paymentMethod === 'mpesa'" class="list-inside list-disc text-xs text-gray-600">
                                <li>Ensure your phone is on and has network coverage.</li>
                                <li>Check for the M-Pesa prompt and enter your PIN.</li>
                                <li>Do not close this page while we confirm payment.</li>
                            </ul>
                        </div>

                        <!-- SUCCESS -->
                        <div v-if="status === 'success'" class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                            {{ message }}
                        </div>

                        <!-- FAILED -->
                        <div v-if="status === 'failed'" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ message }}
                        </div>

                        <!-- ACTIONS -->
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
                                class="rounded border border-primary px-4 py-2 font-medium text-primary transition hover:bg-primary hover:text-white"
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

                <!-- RIGHT — ORDER SUMMARY -->
                <div class="lg:col-span-3">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <div>
                            <img src="/free_del.jpeg" alt="Free Delivery" class="w-full rounded-md object-contain" />
                        </div>

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
                                <span>{{ selected_shipping ? (effectiveShipping > 0 ? formatPrice(effectiveShipping) : 'FREE') : '—' }}</span>
                            </div>

                            <div class="flex justify-between" v-if="cart.totals.tax > 0">
                                <span>Tax</span>
                                <span>{{ formatPrice(cart.totals.tax) }}</span>
                            </div>
                        </div>

                        <hr class="border-gray-200" />

                        <div class="flex justify-between text-base font-semibold text-gray-800">
                            <span>Total</span>
                            <span>{{ formatPrice(effectiveGrandTotal) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- RELATED PRODUCTS -->
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
