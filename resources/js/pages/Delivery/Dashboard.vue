<script setup lang="ts">
import DeliveryLayout from '@/layouts/DeliveryLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { Clock, CheckCircle, XCircle, PackageCheck, LayoutGrid } from 'lucide-vue-next';

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
    pickup_warehouse?: { id: number; name: string; address?: string | null; location?: string | null } | null;
    pickup_receivables_status?: 'pending' | 'received' | 'rejected' | null;
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
        city: string;
        postal_code?: string;
        country: string;
        phone?: string;
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
const markDeliveredForm = useForm({
    payment_method_collected: '' as '' | 'cash' | 'mpesa',
    mpesa_receipt_number: '',
});

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

function openMarkDelivered(order: DeliveryOrder) {
    markDeliveredOrder.value = order;
    markDeliveredForm.payment_method_collected = '';
    markDeliveredForm.mpesa_receipt_number = '';
}

function closeMarkDelivered() {
    markDeliveredOrder.value = null;
    markDeliveredForm.reset();
}

function submitMarkDelivered() {
    if (!markDeliveredOrder.value) return;
    const isCod = (markDeliveredOrder.value.payment_method || '').toLowerCase() === 'cash_on_delivery'
        && markDeliveredOrder.value.payment_status !== 'paid';

    const payload: Record<string, unknown> = {};
    if (isCod) {
        payload.payment_method_collected = markDeliveredForm.payment_method_collected;
        payload.mpesa_receipt_number = markDeliveredForm.mpesa_receipt_number || null;
    }

    markDeliveredForm.transform(() => payload).post(
        route('delivery.orders.mark-delivered', markDeliveredOrder.value!.ulid),
        {
            onSuccess: () => closeMarkDelivered(),
        },
    );
}
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
                        <p class="mb-3 text-sm font-medium">{{ money(order.total_amount, order.currency) }}</p>
                        <div class="flex gap-2">
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
                        <p class="mt-1 text-sm font-medium">{{ money(order.total_amount, order.currency) }}</p>
                        <div v-if="order.allocated_for_pickup_at" class="mt-3 flex flex-wrap items-center gap-2">
                            <p v-if="order.picked_at" class="text-sm text-green-600">
                                Out for delivery (picked at {{ order.picked_at }})
                            </p>
                            <Button
                                v-else
                                size="sm"
                                @click="openConfirmPick(order)"
                            >
                                Confirm items & mark as picked for delivery
                            </Button>
                            <Button
                                v-if="order.delivery_type === 'customer_address' && order.picked_at && (order.status === 'out_for_delivery' || order.status === 'shipped')"
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
                <p class="mb-4 text-sm text-muted-foreground">History of previous assigned orders (delivered or cancelled).</p>
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
                    </div>
                </div>
            </template>
        </div>

        <!-- Mark delivered (customer picked / delivered) dialog -->
        <Dialog :open="!!markDeliveredOrder" @update:open="(v: boolean) => !v && closeMarkDelivered()">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ markDeliveredOrder?.delivery_type === 'pickup_point'
                            ? 'Confirm customer picked order'
                            : 'Mark order as delivered' }}
                        – Order #{{ markDeliveredOrder?.order_code }}
                    </DialogTitle>
                    <DialogDescription>
                        Confirm that the customer has received the order.
                        <span v-if="markDeliveredOrder && (markDeliveredOrder.payment_method || '').toLowerCase() === 'cash_on_delivery' && markDeliveredOrder.payment_status !== 'paid'">
                            This is a cash-on-delivery order – record how the customer paid.
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <div v-if="markDeliveredOrder" class="space-y-4">
                    <div>
                        <p class="mb-1 text-sm font-medium">Summary</p>
                        <pre class="whitespace-pre-wrap rounded bg-muted p-2 text-xs">{{ markDeliveredOrder.delivery_note_summary }}</pre>
                    </div>
                    <div
                        v-if="(markDeliveredOrder.payment_method || '').toLowerCase() === 'cash_on_delivery'
                            && markDeliveredOrder.payment_status !== 'paid'"
                        class="space-y-3 rounded border border-amber-200 bg-amber-50 p-3 text-sm"
                    >
                        <p class="font-medium text-amber-800">Cash on delivery payment</p>
                        <div class="space-y-2">
                            <Label>How did the customer pay?</Label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="markDeliveredForm.payment_method_collected"
                                        type="radio"
                                        value="cash"
                                        class="rounded border-input"
                                    />
                                    Cash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="markDeliveredForm.payment_method_collected"
                                        type="radio"
                                        value="mpesa"
                                        class="rounded border-input"
                                    />
                                    M-Pesa
                                </label>
                            </div>
                        </div>
                        <div v-if="markDeliveredForm.payment_method_collected === 'mpesa'">
                            <Label for="mpesa-receipt">M-Pesa receipt number</Label>
                            <input
                                id="mpesa-receipt"
                                v-model="markDeliveredForm.mpesa_receipt_number"
                                type="text"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="e.g. QFG123XYZ"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeMarkDelivered">Cancel</Button>
                    <Button
                        @click="submitMarkDelivered"
                        :disabled="(markDeliveredOrder && (markDeliveredOrder.payment_method || '').toLowerCase() === 'cash_on_delivery'
                            && markDeliveredOrder.payment_status !== 'paid'
                            && !markDeliveredForm.payment_method_collected) || markDeliveredForm.processing"
                    >
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
                    <Button @click="confirmPickedForDelivery">Items match – mark as picked for delivery</Button>
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
