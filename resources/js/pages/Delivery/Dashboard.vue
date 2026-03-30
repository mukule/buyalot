<script setup lang="ts">
import DeliveryLayout from '@/layouts/DeliveryLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onBeforeUnmount, watch } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Clock, CheckCircle, XCircle, PackageCheck, LayoutGrid, Navigation } from 'lucide-vue-next';

interface OrderItemPayload {
    id: number;
    quantity: number;
    quantity_returned: number;
    product_name: string;
}

interface DeliveryOrder {
    id: number;
    ulid: string;
    order_code: string;
    status: string;
    payment_status: string;
    payment_method?: string | null;
    delivery_type?: string | null;
    pickup_warehouse_id?: number | null;
    pickup_warehouse?: {
        id: number;
        name: string;
        address?: string | null;
        location?: string | null;
        latitude?: number | null;
        longitude?: number | null;
    } | null;
    pickup_receivables_status?: 'pending' | 'received' | 'rejected' | null;
    dispatching_warehouse?: { id: number; name: string; address?: string | null; location?: string | null } | null;
    cod_reconciliation?: { id: number; status: 'pending_confirmation' | 'confirmed'; reconciled_at?: string | null; confirmed_at?: string | null } | null;
    delivery_assignment_status: string;
    delivery_rejection_reason?: string | null;
    allocated_for_pickup_at?: string | null;
    picked_at?: string | null;
    total_amount: number;
    currency: string;
    created_at: string | null;
    customer?: { first_name: string; last_name: string } | null;
    shipping_address?: {
        address_line_1: string;
        address_line_2?: string | null;
        city: string;
        state_province?: string | null;
        postal_code?: string | null;
        country: string;
        phone?: string | null;
        latitude?: number | null;
        longitude?: number | null;
    } | null;
    delivery_note_summary: string;
    order_items: OrderItemPayload[];
}

interface Stats {
    pending_count: number;
    accepted_count: number;
    rejected_count: number;
    attended_count: number;
    total_count: number;
}

const props = defineProps<{
    section: string;
    stats: Stats;
    pending: DeliveryOrder[];
    accepted: DeliveryOrder[];
    rejected: DeliveryOrder[];
    attended: DeliveryOrder[];
}>();

function money(amount: number, currency: string) {
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount);
    } catch {
        return `${currency} ${amount.toFixed(2)}`;
    }
}

/** Build Google Maps URL for directions to delivery location */
function getDirectionsUrl(order: DeliveryOrder): string | null {
    // Home delivery: use shipping address
    if (order.delivery_type === 'customer_address' && order.shipping_address) {
        const addr = order.shipping_address;
        if (addr.latitude != null && addr.longitude != null) {
            return `https://www.google.com/maps/dir/?api=1&destination=${addr.latitude},${addr.longitude}`;
        }
        const parts = [addr.address_line_1, addr.address_line_2, addr.city, addr.state_province, addr.postal_code, addr.country].filter(Boolean);
        if (parts.length > 0) {
            return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(parts.join(', '))}`;
        }
    }
    // Pickup point: use warehouse location
    if (order.pickup_warehouse) {
        const wh = order.pickup_warehouse;
        if (wh.latitude != null && wh.longitude != null) {
            return `https://www.google.com/maps/dir/?api=1&destination=${wh.latitude},${wh.longitude}`;
        }
        const loc = wh.address || wh.location || wh.name;
        if (loc) {
            return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(loc)}`;
        }
    }
    return null;
}

function openDirections(order: DeliveryOrder) {
    const url = getDirectionsUrl(order);
    if (url) window.open(url, '_blank', 'noopener,noreferrer');
}

function acceptOrder(order: DeliveryOrder) {
    router.post(route('delivery.orders.accept', order.ulid));
}

const rejectOrderUlid = ref<string | null>(null);
const rejectReason = useForm({ reason: '' });

function openReject(order: DeliveryOrder) {
    rejectOrderUlid.value = order.ulid;
    rejectReason.reset();
}

function closeReject() {
    rejectOrderUlid.value = null;
    rejectReason.reset();
}

function submitReject() {
    if (!rejectOrderUlid.value) return;
    rejectReason.post(route('delivery.orders.reject', rejectOrderUlid.value), {
        onSuccess: () => closeReject(),
    });
}

const statCards = [
    { key: 'pending_count', label: 'Pending requests', icon: Clock, class: 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400' },
    { key: 'accepted_count', label: 'Accepted requests', icon: CheckCircle, class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
    { key: 'rejected_count', label: 'Rejected requests', icon: XCircle, class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
    { key: 'attended_count', label: 'Attended deliveries', icon: PackageCheck, class: 'bg-muted text-muted-foreground' },
];

const PICKUP_STATUS_LABELS: Record<string, string> = {
    pending: 'Waiting to be received at pick up point',
    rejected: 'Rejected at pick up point',
    received: 'Received at pick up point',
};

const RETURN_REASONS: Record<string, string> = {
    breakages: 'Breakages',
    expiry: 'Expiry',
    wrong_items: 'Wrong items',
    spoiled: 'Spoiled items',
    wrong_quantities: 'Unmatched quantities',
    order_cancellation: 'Order cancellation',
    pickup_point_closed: 'Pick up point closed',
    other: 'Other',
};

const returnOrder = ref<DeliveryOrder | null>(null);
const returnForm = useForm({
    reason: '',
    reason_notes: '',
    is_full_return: true,
    items: [] as { order_item_id: number; quantity_returned: number }[],
});

const markDeliveredOrder = ref<DeliveryOrder | null>(null);

function openRaiseReturn(order: DeliveryOrder) {
    returnOrder.value = order;
    returnForm.reason = '';
    returnForm.reason_notes = '';
    returnForm.is_full_return = true;
    returnForm.items = order.order_items.map((item) => ({
        order_item_id: item.id,
        quantity_returned: Math.max(0, item.quantity - item.quantity_returned),
    }));
}

function closeRaiseReturn() {
    returnOrder.value = null;
}

function submitRaiseReturn() {
    if (!returnOrder.value) return;
    const payload: Record<string, unknown> = {
        reason: returnForm.reason,
        reason_notes: returnForm.reason_notes || null,
        is_full_return: returnForm.is_full_return,
    };
    if (!returnForm.is_full_return) {
        payload.items = returnForm.items.filter((i) => i.quantity_returned > 0).map((i) => ({
            order_item_id: i.order_item_id,
            quantity_returned: i.quantity_returned,
        }));
    }
    returnForm.transform(() => payload).post(route('delivery.orders.raise-return', returnOrder.value!.ulid), {
        onSuccess: () => closeRaiseReturn(),
    });
}

const confirmPickOrder = ref<DeliveryOrder | null>(null);
function openConfirmPick(order: DeliveryOrder) {
    confirmPickOrder.value = order;
}
function closeConfirmPick() {
    confirmPickOrder.value = null;
}
function confirmPickedForDelivery() {
    if (!confirmPickOrder.value) return;
    router.post(route('delivery.orders.confirm-picked', confirmPickOrder.value.ulid), {}, {
        onSuccess: () => closeConfirmPick(),
    });
}

/** Normalize payment method for comparison (handles "CASH ON DELIVERY" vs "cash_on_delivery") */
function isPaymentMethodCod(method: string | null | undefined): boolean {
    const m = (method || '').toLowerCase().replace(/\s+/g, '_');
    return m === 'cash_on_delivery';
}

/** COD order that is not yet paid */
function isCodUnpaid(order: DeliveryOrder): boolean {
    return isPaymentMethodCod(order.payment_method) && order.payment_status !== 'paid';
}

function isOrderDelivered(status: string | null | undefined): boolean {
    return (status || '').toLowerCase() === 'delivered';
}

function orderStatusAllowsDelivery(status: string | null | undefined): boolean {
    const s = (status || '').toLowerCase();
    return s === 'out_for_delivery' || s === 'shipped';
}

/** Can show Mark as delivered: for COD must be paid first; never for already delivered */
function canMarkDelivered(order: DeliveryOrder): boolean {
    if (isOrderDelivered(order.status)) return false;
    if (order.delivery_type !== 'customer_address' || !order.picked_at) return false;
    if (!orderStatusAllowsDelivery(order.status)) return false;
    if (isCodUnpaid(order)) return false;
    return true;
}

/** Can show Collect payment: only for COD unpaid, never for delivered */
function canShowCollectPayment(order: DeliveryOrder): boolean {
    if (isOrderDelivered(order.status)) return false;
    return order.delivery_type === 'customer_address' && !!order.picked_at && orderStatusAllowsDelivery(order.status) && isCodUnpaid(order);
}

/** Delivery point has coordinates for proximity check */
function hasDeliveryCoordinates(order: DeliveryOrder): boolean {
    const addr = order.shipping_address;
    return !!(addr && addr.latitude != null && addr.longitude != null);
}

/** Haversine distance in km between two lat/lng points */
function haversineDistanceKm(
    lat1: number,
    lng1: number,
    lat2: number,
    lng2: number
): number {
    const R = 6371; // Earth radius km
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLng = ((lng2 - lng1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLng / 2) *
            Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

const PROXIMITY_METERS = 500;
const deliveryPersonLocation = ref<{ lat: number; lng: number } | null>(null);
const locationError = ref<string | null>(null);
const locationWatchId = ref<number | null>(null);

function startLocationWatch() {
    if (!navigator.geolocation || locationWatchId.value != null) return;
    locationError.value = null;
    locationWatchId.value = navigator.geolocation.watchPosition(
        (pos) => {
            deliveryPersonLocation.value = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
            };
            locationError.value = null;
        },
        (err) => {
            locationError.value =
                err.code === 1
                    ? 'Allow location access to collect payment'
                    : 'Location unavailable';
            deliveryPersonLocation.value = null;
        },
        { enableHighAccuracy: true, maximumAge: 30000, timeout: 10000 }
    );
}

function stopLocationWatch() {
    if (locationWatchId.value != null && navigator.geolocation) {
        navigator.geolocation.clearWatch(locationWatchId.value);
        locationWatchId.value = null;
    }
    deliveryPersonLocation.value = null;
    locationError.value = null;
}

function distanceToDeliveryMeters(order: DeliveryOrder): number | null {
    const addr = order.shipping_address;
    if (!addr || addr.latitude == null || addr.longitude == null) return null;
    const loc = deliveryPersonLocation.value;
    if (!loc) return null;
    return haversineDistanceKm(loc.lat, loc.lng, addr.latitude, addr.longitude) * 1000;
}

/** Collect payment button is active only when within 500m of delivery point */
/** Can show Reconcile cash: delivered COD cash order, not yet reconciled, has warehouse to reconcile to */
function canShowReconcileCash(order: DeliveryOrder): boolean {
    if (order.status !== 'delivered') return false;
    if (!isPaymentMethodCod(order.payment_method)) return false;
    if (order.payment_status !== 'paid') return false;
    if (order.cod_reconciliation) return false; // already reconciled
    return !!(order.dispatching_warehouse || order.pickup_warehouse);
}

function reconcileCash(order: DeliveryOrder) {
    router.post(route('delivery.orders.reconcile-cash', order.ulid), {}, {
        onSuccess: () => router.reload(),
    });
}

function canCollectPaymentActive(order: DeliveryOrder): boolean {
    if (!canShowCollectPayment(order)) return false;
    if (!hasDeliveryCoordinates(order)) return false;
    const distM = distanceToDeliveryMeters(order);
    if (distM == null) return false;
    return distM <= PROXIMITY_METERS;
}

function collectPaymentDisabledReason(order: DeliveryOrder): string {
    if (!canShowCollectPayment(order)) return '';
    if (!hasDeliveryCoordinates(order)) return 'Delivery address has no coordinates – cannot verify proximity';
    if (locationError.value) return locationError.value;
    const distM = distanceToDeliveryMeters(order);
    if (distM == null) return 'Allow location access to collect payment';
    if (distM > PROXIMITY_METERS) return `You are still ~${Math.round(distM)}m away from the delivery point.`;
    return '';
}

const collectPaymentOrder = ref<DeliveryOrder | null>(null);
const collectPhone = ref('');
const collectMpesaStatus = ref<'idle' | 'sending' | 'polling' | 'success' | 'failed'>('idle');
const collectMpesaMessage = ref('');

function openCollectPayment(order: DeliveryOrder) {
    collectPaymentOrder.value = order;
    collectPhone.value = order.shipping_address?.phone?.replace(/\D/g, '').slice(-9) ? `254${order.shipping_address!.phone!.replace(/\D/g, '').slice(-9)}` : '';
    collectMpesaStatus.value = 'idle';
    collectMpesaMessage.value = '';
}

function closeCollectPayment() {
    collectPaymentOrder.value = null;
    collectPhone.value = '';
    collectMpesaStatus.value = 'idle';
    collectMpesaMessage.value = '';
}

function confirmCashReceived() {
    if (!collectPaymentOrder.value) return;
    router.post(route('delivery.orders.confirm-cash-payment', collectPaymentOrder.value.ulid), {}, {
        onSuccess: () => {
            closeCollectPayment();
            router.reload();
        },
    });
}

async function initiateMpesaStk() {
    if (!collectPaymentOrder.value) return;
    const phone = collectPhone.value.trim().replace(/\D/g, '');
    if (phone.length < 9) {
        collectMpesaMessage.value = 'Enter a valid phone number (e.g. 0712345678 or 254712345678)';
        return;
    }
    const normalized = phone.startsWith('254') ? phone : `254${phone.slice(-9)}`;
    collectMpesaStatus.value = 'sending';
    collectMpesaMessage.value = '';
    try {
        const axios = (window as any).axios || (await import('axios')).default;
        const { data } = await axios.post(
            route('delivery.orders.initiate-mpesa', collectPaymentOrder.value.ulid),
            { phone: normalized },
            { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, withCredentials: true },
        );
        if (data.success && data.checkout_request_id) {
            collectMpesaStatus.value = 'polling';
            collectMpesaMessage.value = 'M-Pesa prompt sent. Ask customer to enter PIN on their phone.';
            await pollMpesaStatus(data.checkout_request_id);
        } else {
            collectMpesaStatus.value = 'failed';
            collectMpesaMessage.value = data.message || 'Failed to send M-Pesa prompt.';
        }
    } catch (e: any) {
        collectMpesaStatus.value = 'failed';
        collectMpesaMessage.value = e?.response?.data?.message || e?.message || 'Failed to send M-Pesa prompt.';
    }
}

async function pollMpesaStatus(checkoutRequestId: string): Promise<'success' | 'failed'> {
    const maxSeconds = 45;
    const intervalMs = 4000;
    let elapsed = 0;
    const axios = (window as any).axios || (await import('axios')).default;
    while (elapsed < maxSeconds) {
        await new Promise((r) => setTimeout(r, intervalMs));
        elapsed += intervalMs / 1000;
        try {
            const { data } = await axios.get(route('payments.status', checkoutRequestId), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                withCredentials: true,
            });
            const ver = data?.verification ?? data?.log;
            const success = ver?.success ?? (ver?.status === 'COMPLETED' || ver?.status === 'SUCCESS');
            if (success === true) {
                collectMpesaStatus.value = 'success';
                collectMpesaMessage.value = 'Payment received. You can now mark as delivered.';
                closeCollectPayment();
                router.reload();
                return 'success';
            }
            if (ver && ['FAILED', 'CANCELED', 'EXPIRED'].includes(ver.status || ver.state)) {
                collectMpesaStatus.value = 'failed';
                collectMpesaMessage.value = ver.result_desc || ver.message || 'Payment failed or was cancelled.';
                return 'failed';
            }
        } catch {
            /* continue polling */
        }
    }
    collectMpesaStatus.value = 'failed';
    collectMpesaMessage.value = 'Payment timed out. Customer can try again.';
    return 'failed';
}

function openMarkDelivered(order: DeliveryOrder) {
    markDeliveredOrder.value = order;
}

function closeMarkDelivered() {
    markDeliveredOrder.value = null;
}

function submitMarkDelivered() {
    if (!markDeliveredOrder.value) return;
    router.post(route('delivery.orders.mark-delivered', markDeliveredOrder.value!.ulid), {}, {
        onSuccess: () => {
            closeMarkDelivered();
            router.reload();
        },
    });
}

/** Start location watch when on accepted section with COD orders; stop when leaving */
watch(
    () => props.section,
    (section) => {
        if (section === 'accepted') {
            const hasCodUnpaid = props.accepted.some(canShowCollectPayment);
            if (hasCodUnpaid) startLocationWatch();
        } else {
            stopLocationWatch();
        }
    },
    { immediate: true }
);
onBeforeUnmount(() => stopLocationWatch());
</script>

<template>
    <Head title="Delivery dashboard" />
    <DeliveryLayout>
        <div class="flex-1 p-6">
            <!-- Dashboard: statistics only -->
            <template v-if="!props.section || props.section === 'dashboard'">
                <h1 class="mb-6 text-2xl font-bold">Dashboard</h1>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="card in statCards"
                        :key="card.key"
                        class="flex items-center gap-4 rounded-lg border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                            :class="card.class"
                        >
                            <component :is="card.icon" class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ props.stats[card.key as keyof Stats] }}</p>
                            <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 rounded-lg border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <LayoutGrid class="h-5 w-5 text-muted-foreground" />
                        <span class="font-semibold">Total requests</span>
                    </div>
                    <p class="mt-2 text-3xl font-bold">{{ props.stats.total_count }}</p>
                </div>
            </template>

            <!-- Pending list -->
            <template v-else-if="props.section === 'pending'">
                <h1 class="mb-6 text-2xl font-bold">Pending acceptance</h1>
                <p class="mb-4 text-sm text-muted-foreground">Accept or reject these assignments.</p>
                <div v-if="pending.length === 0" class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                    No pending assignments.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in pending"
                        :key="order.id"
                        class="rounded-lg border bg-card p-4 shadow-sm"
                    >
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">Order #{{ order.order_code }}</span>
                            <span class="text-sm text-muted-foreground">{{ order.created_at }}</span>
                        </div>
                        <pre class="mb-3 whitespace-pre-wrap rounded bg-muted p-2 text-xs">{{ order.delivery_note_summary }}</pre>
                        <p v-if="order.shipping_address" class="mb-2 text-sm">
                            {{ order.shipping_address.address_line_1 }}, {{ order.shipping_address.city }}
                            <span v-if="order.shipping_address.phone"> · {{ order.shipping_address.phone }}</span>
                        </p>
                        <p v-else-if="order.pickup_warehouse" class="mb-2 text-sm">
                            {{ order.pickup_warehouse.name }}
                            <span v-if="order.pickup_warehouse.address"> · {{ order.pickup_warehouse.address }}</span>
                        </p>
                        <p class="mb-3 text-sm font-medium">{{ money(order.total_amount, order.currency) }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Button size="sm" @click="acceptOrder(order)">Accept</Button>
                            <Button size="sm" variant="destructive" @click="openReject(order)">Reject</Button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Accepted list -->
            <template v-else-if="props.section === 'accepted'">
                <h1 class="mb-6 text-2xl font-bold">Accepted</h1>
                <p class="mb-4 text-sm text-muted-foreground">
                    When admin allocates an order for pickup, confirm items and quantity match the delivery note, then mark as picked for delivery.
                </p>
                <div v-if="accepted.length === 0" class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                    No accepted deliveries.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in accepted"
                        :key="order.id"
                        class="rounded-lg border bg-card p-4 shadow-sm"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">Order #{{ order.order_code }}</span>
                            <span class="text-sm capitalize text-muted-foreground">{{ order.status }}</span>
                        </div>
                        <div v-if="!order.allocated_for_pickup_at" class="mt-2 space-y-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                <Clock class="h-4 w-4 shrink-0" />
                                Waiting delivery allocation
                            </span>
                            <p class="text-xs text-muted-foreground">Admin will allocate this order for pickup; then you can confirm items and mark as picked for delivery.</p>
                        </div>
                        <pre class="mt-2 whitespace-pre-wrap rounded bg-muted p-2 text-xs">{{ order.delivery_note_summary }}</pre>
                        <p v-if="order.shipping_address" class="mt-2 text-sm">
                            {{ order.shipping_address.address_line_1 }}, {{ order.shipping_address.city }}
                            <span v-if="order.shipping_address.phone"> · {{ order.shipping_address.phone }}</span>
                        </p>
                        <p v-else-if="order.pickup_warehouse" class="mt-2 text-sm">
                            {{ order.pickup_warehouse.name }}
                            <span v-if="order.pickup_warehouse.address"> · {{ order.pickup_warehouse.address }}</span>
                        </p>
                        <p class="mt-1 text-sm font-medium">{{ money(order.total_amount, order.currency) }}</p>
                        <div v-if="order.allocated_for_pickup_at" class="mt-3 flex flex-wrap items-center gap-2">
                            <Button
                                v-if="getDirectionsUrl(order)"
                                size="sm"
                                variant="outline"
                                @click="openDirections(order)"
                            >
                                <Navigation class="mr-1.5 h-4 w-4" />
                                View on map / Get directions
                            </Button>
                            <p v-if="order.picked_at" class="text-sm text-green-600">
                                Out for delivery (picked at {{ order.picked_at }})
                            </p>
                            <Button
                                v-if="!order.picked_at"
                                size="sm"
                                @click="openConfirmPick(order)"
                                class="text-white"
                            >
                                Confirm items & mark as picked for delivery
                            </Button>
                            <span v-if="canShowCollectPayment(order)" class="inline-flex flex-col items-start gap-0.5">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    :disabled="!canCollectPaymentActive(order)"
                                    :title="collectPaymentDisabledReason(order)"
                                    @click="openCollectPayment(order)"
                                >
                                    Collect payment
                                </Button>
                                <span
                                    v-if="!canCollectPaymentActive(order) && collectPaymentDisabledReason(order)"
                                    class="text-xs text-amber-600 dark:text-amber-400"
                                >
                                    {{ collectPaymentDisabledReason(order) }}
                                </span>
                            </span>
                            <Button
                                v-else-if="canMarkDelivered(order)"
                                size="sm"
                                variant="outline"
                                @click="openMarkDelivered(order)"
                            >
                                Mark as delivered
                            </Button>
                        </div>
                        <template v-if="order.delivery_type === 'pickup_point'">
                            <div v-if="order.allocated_for_pickup_at || order.pickup_receivables_status" class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium"
                                    :class="(order.pickup_receivables_status || 'pending') === 'rejected'
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'
                                        : (order.pickup_receivables_status || 'pending') === 'received'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'"
                                >
                                    {{ PICKUP_STATUS_LABELS[order.pickup_receivables_status || 'pending'] || (order.pickup_receivables_status || 'pending') }}
                                </span>
                                <Button
                                    v-if="order.status === 'shipped' || order.status === 'out_for_delivery' || order.pickup_receivables_status === 'rejected'"
                                    size="sm"
                                    variant="outline"
                                    @click="openRaiseReturn(order)"
                                >
                                    Raise return
                                </Button>
                            </div>
                            <div v-else-if="(order.status === 'shipped' || order.status === 'out_for_delivery') && order.status !== 'returned' && order.status !== 'partially_returned'" class="mt-3">
                                <Button size="sm" variant="outline" @click="openRaiseReturn(order)">
                                    Raise return
                                </Button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Rejected list -->
            <template v-else-if="props.section === 'rejected'">
                <h1 class="mb-6 text-2xl font-bold">Rejected</h1>
                <div v-if="rejected.length === 0" class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                    No rejected assignments.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in rejected"
                        :key="order.id"
                        class="rounded-lg border bg-card p-4 shadow-sm"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">Order #{{ order.order_code }}</span>
                            <span class="text-sm text-muted-foreground">{{ order.created_at }}</span>
                        </div>
                        <p v-if="order.delivery_rejection_reason" class="mt-1 text-sm text-red-600">
                            Reason: {{ order.delivery_rejection_reason }}
                        </p>
                    </div>
                </div>
            </template>

            <!-- Attended (history) list -->
            <template v-else-if="props.section === 'attended'">
                <h1 class="mb-6 text-2xl font-bold">Attended deliveries</h1>
                <p class="mb-4 text-sm text-muted-foreground">
                    History of previous assigned orders (delivered or cancelled).
                    For COD cash orders, hand over the cash to the pickup warehouse and mark reconciled.
                </p>
                <div v-if="attended.length === 0" class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                    No attended deliveries yet.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in attended"
                        :key="order.id"
                        class="rounded-lg border bg-muted/30 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">Order #{{ order.order_code }}</span>
                            <span class="text-sm capitalize">{{ order.status }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">{{ order.created_at }}</p>
                        <p v-if="order.total_amount" class="mt-1 text-sm font-medium">{{ money(order.total_amount, order.currency) }}</p>
                        <div v-if="canShowReconcileCash(order)" class="mt-3">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="reconcileCash(order)"
                            >
                                Reconcile cash to {{ (order.dispatching_warehouse || order.pickup_warehouse)?.name ?? 'warehouse' }}
                            </Button>
                            <p class="mt-1 text-xs text-muted-foreground">Hand over {{ money(order.total_amount, order.currency) }} to the warehouse where you picked up the order.</p>
                        </div>
                        <div v-else-if="order.cod_reconciliation" class="mt-2">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="order.cod_reconciliation.status === 'confirmed'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'"
                            >
                                {{ order.cod_reconciliation.status === 'confirmed' ? 'Cash reconciled & confirmed' : 'Reconciled – awaiting warehouse confirmation' }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Collect payment (COD) dialog -->
        <Dialog :open="!!collectPaymentOrder" @update:open="(v: boolean) => !v && closeCollectPayment()">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Collect payment – Order #{{ collectPaymentOrder?.order_code }}</DialogTitle>
                    <DialogDescription>
                        Confirm payment received for this COD order ({{ money(collectPaymentOrder?.total_amount ?? 0, collectPaymentOrder?.currency ?? 'KES') }}).
                        Only after payment is confirmed can you mark as delivered.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="collectPaymentOrder" class="space-y-4">
                    <div class="flex flex-col gap-3">
                        <div class="rounded border border-green-200 bg-green-50 p-3">
                            <p class="mb-2 text-sm font-medium text-green-800">Customer paid cash?</p>
                            <Button size="sm" @click="confirmCashReceived" :disabled="collectMpesaStatus === 'sending' || collectMpesaStatus === 'polling'">
                                Confirm cash received
                            </Button>
                        </div>
                        <div class="rounded border border-blue-200 bg-blue-50 p-3">
                            <p class="mb-2 text-sm font-medium text-blue-800">Customer paying via M-Pesa?</p>
                            <p class="mb-2 text-xs text-blue-700">Enter customer's phone to send M-Pesa prompt to their phone.</p>
                            <div class="flex gap-2">
                                <input
                                    v-model="collectPhone"
                                    type="tel"
                                    placeholder="0712345678 or 254712345678"
                                    class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <Button
                                    size="sm"
                                    @click="initiateMpesaStk"
                                    :disabled="!collectPhone.trim() || collectMpesaStatus === 'sending' || collectMpesaStatus === 'polling'"
                                >
                                    {{ collectMpesaStatus === 'sending' ? 'Sending…' : collectMpesaStatus === 'polling' ? 'Waiting…' : 'Send M-Pesa prompt' }}
                                </Button>
                            </div>
                            <p v-if="collectMpesaMessage" class="mt-2 text-sm" :class="collectMpesaStatus === 'failed' ? 'text-red-600' : 'text-blue-700'">
                                {{ collectMpesaMessage }}
                            </p>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCollectPayment">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Mark delivered dialog -->
        <Dialog :open="!!markDeliveredOrder" @update:open="(v: boolean) => !v && closeMarkDelivered()">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Mark order as delivered – Order #{{ markDeliveredOrder?.order_code }}</DialogTitle>
                    <DialogDescription>
                        Confirm that the customer has received the order.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="markDeliveredOrder" class="space-y-4">
                    <div>
                        <p class="mb-1 text-sm font-medium">Summary</p>
                        <pre class="whitespace-pre-wrap rounded bg-muted p-2 text-xs">{{ markDeliveredOrder.delivery_note_summary }}</pre>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeMarkDelivered">Cancel</Button>
                    <Button @click="submitMarkDelivered">
                        Confirm delivered
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Confirm items & mark as picked dialog -->
        <Dialog :open="!!confirmPickOrder" @update:open="(v: boolean) => !v && closeConfirmPick()">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Confirm items – Order #{{ confirmPickOrder?.order_code }}</DialogTitle>
                    <DialogDescription>
                        Verify that each item and quantity match the delivery note. If everything is correct, mark as picked for delivery.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="confirmPickOrder" class="space-y-4">
                    <div>
                        <p class="mb-1 text-sm font-medium">Delivery note</p>
                        <pre class="whitespace-pre-wrap rounded bg-muted p-2 text-xs">{{ confirmPickOrder.delivery_note_summary }}</pre>
                    </div>
                    <div>
                        <p class="mb-1 text-sm font-medium">Items to deliver</p>
                        <ul class="list-inside list-disc rounded bg-muted/50 p-2 text-sm">
                            <li v-for="(item, idx) in confirmPickOrder.order_items" :key="idx">
                                {{ item.product_name }} × {{ item.quantity }}
                            </li>
                        </ul>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeConfirmPick">Cancel</Button>
                    <Button @click="confirmPickedForDelivery" class="text-white">Items match – mark as picked for delivery</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Raise return dialog -->
        <Dialog :open="!!returnOrder" @update:open="(v: boolean) => !v && closeRaiseReturn()">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Raise return – Order #{{ returnOrder?.order_code }}</DialogTitle>
                    <DialogDescription>
                        Record a full or partial return with reason. Dispatch will be notified to receive items when they arrive.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="returnOrder" class="space-y-4">
                    <div>
                        <Label for="return-reason">Reason</Label>
                        <select
                            id="return-reason"
                            v-model="returnForm.reason"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Select reason</option>
                            <option v-for="(label, key) in RETURN_REASONS" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <Label for="return-notes">Notes (optional)</Label>
                        <textarea
                            id="return-notes"
                            v-model="returnForm.reason_notes"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            rows="2"
                        />
                    </div>
                    <div>
                        <span class="text-sm font-medium">Return type</span>
                        <div class="mt-2 flex gap-4">
                            <label class="flex items-center gap-2">
                                <input v-model="returnForm.is_full_return" type="radio" :value="true" class="rounded border-input" />
                                Full return
                            </label>
                            <label class="flex items-center gap-2">
                                <input v-model="returnForm.is_full_return" type="radio" :value="false" class="rounded border-input" />
                                Partial return
                            </label>
                        </div>
                    </div>
                    <div v-if="!returnForm.is_full_return" class="space-y-2">
                        <Label>Quantities to return per item</Label>
                        <div v-for="(item, idx) in returnOrder.order_items" :key="item.id" class="flex items-center justify-between gap-2 rounded border p-2 text-sm">
                            <span>{{ item.product_name }} (max {{ item.quantity - item.quantity_returned }})</span>
                            <input
                                v-model.number="returnForm.items[idx].quantity_returned"
                                type="number"
                                :min="0"
                                :max="item.quantity - item.quantity_returned"
                                class="w-20 rounded border border-input bg-background px-2 py-1 text-sm"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeRaiseReturn">Cancel</Button>
                    <Button :disabled="!returnForm.reason || returnForm.processing" @click="submitRaiseReturn">
                        Submit return
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reject reason dialog -->
        <Dialog :open="!!rejectOrderUlid" @update:open="(v: boolean) => !v && closeReject()">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject delivery</DialogTitle>
                    <DialogDescription>Please provide a reason. Admin may assign another delivery person.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitReject" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="reason">Reason (required)</Label>
                        <textarea
                            id="reason"
                            v-model="rejectReason.reason"
                            required
                            rows="3"
                            placeholder="e.g. Out of area, vehicle unavailable..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                        <p v-if="rejectReason.errors.reason" class="text-sm text-destructive">{{ rejectReason.errors.reason }}</p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeReject">Cancel</Button>
                        <Button type="submit" variant="destructive" :disabled="rejectReason.processing">Reject</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </DeliveryLayout>
</template>
