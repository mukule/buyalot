<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
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

interface OrderPayload {
    id: number;
    ulid: string;
    order_code: string;
    payment_status: string;
    payment_method?: string | null;
    total_amount: number;
    currency: string;
    customer: { id: number; first_name: string; last_name: string; email?: string | null } | null;
    order_items_summary: { product_name: string; quantity: number }[];
}

const props = defineProps<{
    warehouse: { id: number; hashid: string; name: string; code: string };
    orders: OrderPayload[];
    paymentsInitiateUrl: string;
}>();

function money(amount: number, currency: string) {
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount);
    } catch {
        return `${currency} ${amount.toFixed(2)}`;
    }
}

const selectedOrder = ref<OrderPayload | null>(null);
const customerPickedForm = useForm({
    payment_method_collected: '' as '' | 'cash' | 'mpesa',
    mpesa_receipt_number: '',
});
const stkPhone = ref('');
const stkSending = ref(false);
const stkMessage = ref('');

function openCustomerPicked(order: OrderPayload) {
    selectedOrder.value = order;
    customerPickedForm.payment_method_collected = '';
    customerPickedForm.mpesa_receipt_number = '';
    stkPhone.value = '';
    stkMessage.value = '';
}

function closeCustomerPicked() {
    selectedOrder.value = null;
    customerPickedForm.reset();
}

const isCodUnpaid = (order: OrderPayload) =>
    (order.payment_method || '').toLowerCase() === 'cash_on_delivery' && order.payment_status !== 'paid';

async function initiateStkPush() {
    if (!selectedOrder.value || !stkPhone.value.trim()) {
        stkMessage.value = 'Enter customer phone number.';
        return;
    }
    stkSending.value = true;
    stkMessage.value = '';
    try {
        const axios = (window as any).axios;
        const resp = await axios.post(
            props.paymentsInitiateUrl,
            {
                payable_type: 'order',
                payable_id: selectedOrder.value.id,
                amount: selectedOrder.value.total_amount,
                currency: selectedOrder.value.currency || 'KES',
                provider: 'mpesa',
                method: 'mobile_money',
                phone: stkPhone.value.trim(),
            },
            { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, withCredentials: true }
        );
        stkMessage.value = resp?.data?.message || 'STK push sent. Ask customer to complete payment on their phone.';
    } catch (e: any) {
        stkMessage.value = e?.response?.data?.message || 'Failed to send STK push.';
    } finally {
        stkSending.value = false;
    }
}

function submitCustomerPicked() {
    if (!selectedOrder.value) return;
    const cod = isCodUnpaid(selectedOrder.value);
    const payload: Record<string, unknown> = {};
    if (cod) {
        payload.payment_method_collected = customerPickedForm.payment_method_collected;
        payload.mpesa_receipt_number = customerPickedForm.mpesa_receipt_number || null;
    }
    customerPickedForm.transform(() => payload).post(
        route('admin.orders.customer-picked', { warehouse: props.warehouse.hashid, order: selectedOrder.value.ulid }),
        { onSuccess: () => closeCustomerPicked() }
    );
}

function goBack() {
    router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
}
</script>

<template>
    <Head :title="`Orders ready for pickup – ${warehouse.name}`" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Warehouses', href: route('admin.warehouses.index') },
            { title: warehouse.name, href: route('admin.warehouses.show', warehouse.hashid) },
            { title: 'Orders ready for pickup', href: '#' },
        ]"
    >
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Orders ready for pickup</h1>
                <Button variant="outline" @click="goBack">Back to warehouse</Button>
            </div>
            <p class="mb-4 text-sm text-muted-foreground">
                When a customer comes to collect their order, receive payment first if it is Cash on Delivery (STK push or confirm cash), then mark as delivered.
            </p>

            <div v-if="orders.length === 0" class="rounded-lg border border-dashed p-8 text-center text-muted-foreground">
                No orders ready for pickup at this warehouse.
            </div>
            <div v-else class="space-y-4">
                <div
                    v-for="order in orders"
                    :key="order.id"
                    class="rounded-lg border bg-card p-4 shadow-sm"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-medium">Order #{{ order.order_code }}</span>
                            <span v-if="order.customer" class="ml-2 text-sm text-muted-foreground">
                                {{ order.customer.first_name }} {{ order.customer.last_name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ money(order.total_amount, order.currency) }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs capitalize"
                                :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ order.payment_status }}
                            </span>
                            <Button size="sm" @click="openCustomerPicked(order)">
                                Customer picked
                            </Button>
                        </div>
                    </div>
                    <ul class="mt-2 list-inside list-disc text-sm text-muted-foreground">
                        <li v-for="(item, idx) in order.order_items_summary" :key="idx">
                            {{ item.product_name }} × {{ item.quantity }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Customer picked modal -->
        <Dialog :open="!!selectedOrder" @update:open="(v: boolean) => !v && closeCustomerPicked()">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Customer picked order – #{{ selectedOrder?.order_code }}</DialogTitle>
                    <DialogDescription>
                        If this order is Cash on Delivery, receive payment first (trigger M-Pesa STK push or confirm cash), then mark as delivered.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="selectedOrder" class="space-y-4">
                    <p class="text-sm font-medium">{{ money(selectedOrder.total_amount, selectedOrder.currency) }}</p>

                    <template v-if="isCodUnpaid(selectedOrder)">
                        <div class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                            Cash on Delivery – receive payment before closing.
                        </div>
                        <div>
                            <Label>Trigger M-Pesa STK push</Label>
                            <div class="mt-1 flex gap-2">
                                <input
                                    v-model="stkPhone"
                                    type="tel"
                                    placeholder="Customer phone (e.g. 254712345678)"
                                    class="flex-1 rounded border border-input bg-background px-3 py-2 text-sm"
                                />
                                <Button size="sm" :disabled="stkSending" @click="initiateStkPush">
                                    {{ stkSending ? 'Sending…' : 'Send STK' }}
                                </Button>
                            </div>
                            <p v-if="stkMessage" class="mt-1 text-xs" :class="stkMessage.includes('Failed') ? 'text-red-600' : 'text-green-600'">
                                {{ stkMessage }}
                            </p>
                        </div>
                        <div>
                            <Label>Or confirm payment received</Label>
                            <div class="mt-2 flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input v-model="customerPickedForm.payment_method_collected" type="radio" value="cash" class="rounded border-input" />
                                    Cash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input v-model="customerPickedForm.payment_method_collected" type="radio" value="mpesa" class="rounded border-input" />
                                    M-Pesa
                                </label>
                            </div>
                            <div v-if="customerPickedForm.payment_method_collected === 'mpesa'" class="mt-2">
                                <Label for="mpesa-receipt">M-Pesa receipt number</Label>
                                <input
                                    id="mpesa-receipt"
                                    v-model="customerPickedForm.mpesa_receipt_number"
                                    type="text"
                                    class="mt-1 w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="e.g. ABC123XY"
                                />
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-sm text-muted-foreground">Order is paid or not COD. Mark as delivered when customer has collected.</p>
                    </template>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCustomerPicked">Cancel</Button>
                    <Button
                        :disabled="(selectedOrder && isCodUnpaid(selectedOrder) && !customerPickedForm.payment_method_collected) || customerPickedForm.processing"
                        @click="submitCustomerPicked"
                    >
                        {{ customerPickedForm.processing ? 'Saving…' : 'Mark as delivered' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
