<script setup lang="ts">
import AppLayout from '@/layouts/CustomerAppSidebarLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ChevronLeftIcon, ChevronRightIcon, Eye, SearchIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface OrderItem {
    id: number;
    ulid: string;
    order_code: string;
    status: string;
    payment_status?: string;
    fulfillment_status?: string;
    total_amount: number;
    currency: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}

// const page = usePage<{ orders: PaginatedResponse<OrderItem> }>();
const page = usePage<{ orders: PaginatedResponse<OrderItem>; customerPhone?: string }>();
const customerPhone = ref(page.props.customerPhone || '');

console.log(customerPhone);
const searchQuery = ref('');
const debouncedQuery = ref(searchQuery.value);

watch(
    searchQuery,
    debounce((val: string) => {
        debouncedQuery.value = val;
    }, 300),
);

const filteredOrders = computed(() => {
    const orders = page.props.orders?.data ?? [];
    const query = debouncedQuery.value.trim().toLowerCase();

    if (!query) return orders;
    return orders.filter((o) => o.order_code.toLowerCase().includes(query) || o.status.toLowerCase().includes(query));
});

const pagination = computed(() => {
    const { links, meta } = page.props.orders;
    return { links, meta };
});

function viewOrder(ulid: string) {
    router.get(route('orders.show', { order: ulid }));
}

const statusClasses = (status: string) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-indigo-100 text-indigo-800': status === 'confirmed',
    'bg-blue-100 text-blue-800': status === 'processing',
    'bg-purple-100 text-purple-800': status === 'shipped',
    'bg-green-100 text-green-800': status === 'delivered' || status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled',
});

const paymentStatusClasses = (status?: string) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-green-100 text-green-800': status === 'paid' || status === 'partially_paid',
    'bg-red-100 text-red-800': status === 'failed',
    'bg-gray-100 text-gray-800': status === 'refunded' || status === 'partially_refunded',
});

const fulfillmentStatusClasses = (status?: string) => ({
    'bg-gray-100 text-gray-800': status === 'unfulfilled',
    'bg-blue-100 text-blue-800': status === 'processing',
    'bg-amber-100 text-amber-800': status === 'partially_fulfilled',
    'bg-green-100 text-green-800': status === 'fulfilled',
    'bg-red-100 text-red-800': status === 'cancelled',
});

// Payment UI state
const payingOrderId = ref<number | null>(null);
const showPhoneModal = ref(false);
const selectedOrder = ref<OrderItem | null>(null);
const phoneInput = ref<string>('');
const phoneError = ref<string>('');

function openPayModal(order: OrderItem) {
    selectedOrder.value = order;
    phoneError.value = '';
    // Autofill order-specific phone if available, else last used, else customer phone
    const orderPhone = localStorage.getItem(`mpesa_phone_order_${order.id}`) || '';
    const lastPhone = localStorage.getItem('mpesa_phone') || '';
    phoneInput.value = orderPhone || lastPhone || (customerPhone.value?.trim() || '');
    showPhoneModal.value = true;
}

function closePayModal() {
    showPhoneModal.value = false;
    phoneError.value = '';
}

function normalizePhone(input: string): string {
    let p = (input || '').replace(/\D/g, '');
    if (p.startsWith('07') && p.length === 10) {
        p = '254' + p.substring(1);
    }
    if (p.startsWith('254') && p.length === 12) return p;
    return input.trim();
}

function validatePhone(input: string): boolean {
    const p = normalizePhone(input);
    return /^(?:2547\d{8})$/.test(p) || /^07\d{8}$/.test(input.replace(/\D/g, ''));
}

async function confirmPay() {
    if (!selectedOrder.value) return;
    if (!phoneInput.value || !validatePhone(phoneInput.value)) {
        phoneError.value = 'Enter a valid M-Pesa number (07xxxxxxxx or 2547xxxxxxxx)';
        return;
    }
    const normalized = normalizePhone(phoneInput.value);
    await initiatePayment(selectedOrder.value, normalized);
}

async function initiatePayment(order: OrderItem, phone: string) {
    try {
        payingOrderId.value = order.id;
        const axios = (window as any).axios || (await import('axios')).default;
        const payload = {
            payable_type: 'order',
            payable_id: order.id,
            amount: order.total_amount,
            currency: order.currency || 'KES',
            provider: 'mpesa',
            method: 'mobile_money',
            phone,
        };

        const resp = await axios.post(route('payments.initiate'), payload, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            withCredentials: true,
        });

        // Persist phone for next attempt (order-specific and global last used)
        localStorage.setItem(`mpesa_phone_order_${order.id}`, phone);
        localStorage.setItem('mpesa_phone', phone);

        if (resp.status >= 200 && resp.status < 300) {
            alert('Payment initiated. Please check your phone for the M-Pesa prompt and enter your PIN.');
            closePayModal();
        }
    } catch (e: any) {
        const resp = e?.response;
        if (resp?.status === 409 && resp?.data?.items?.length) {
            const items = resp.data.items as Array<{ product_variant_id: number; requested: number; available: number; product_name: string }>;
            const lines = items.map(i => `- ${i.product_name}: requested ${i.requested}, available ${i.available}`).join('\n');
            alert('Cannot proceed with payment due to insufficient stock for:\n' + lines);
        } else {
            alert(resp?.data?.message || 'Failed to initiate payment.');
        }
    } finally {
        payingOrderId.value = null;
    }
}

</script>

<template>
    <Head title="My Orders" />

    <AppLayout>
        <Link
            :href="route ? route('home') : '/'"
            class="ml-auto inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
        >
            Back to shopping
        </Link>
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">My Orders</h1>
                </div>

                <!-- Search -->
                <div class="mb-4">
                    <label for="search" class="sr-only">Search orders</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <SearchIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            id="search"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by order number or status..."
                            class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-md border border-gray-300 py-2 pl-10 text-sm"
                        />
                    </div>
                </div>

                <!-- Orders Table -->
                <div v-if="filteredOrders.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Number</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fulfillment</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(order, index) in filteredOrders" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-primary">{{ order.order_code }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ order.created_at }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-700">
                                    {{ order.currency }} {{ order.total_amount.toFixed(2) }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span
                                        :class="statusClasses(order.status)"
                                        class="inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    >
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span
                                        :class="paymentStatusClasses(order.payment_status)"
                                        class="inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    >
                                        {{ order.payment_status || '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span
                                        :class="fulfillmentStatusClasses(order.fulfillment_status)"
                                        class="inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    >
                                        {{ order.fulfillment_status || '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm font-medium flex items-center justify-end gap-3">
                                    <button
                                        v-if="order.payment_status === 'pending'"
                                        :disabled="payingOrderId === order.id"
                                        @click="openPayModal(order)"
                                        class="rounded bg-primary px-3 py-1 text-xs text-white hover:bg-primary/90 disabled:opacity-60"
                                        title="Pay for this order"
                                    >
                                        {{ payingOrderId === order.id ? 'Processing…' : 'Pay Now' }}
                                    </button>
                                    <button @click="viewOrder(order.ulid)" aria-label="View Order" class="text-blue-600 transition hover:text-blue-800">
                                        <Eye class="h-5 w-5" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <template v-for="(link, index) in pagination.links" :key="index">
                                <a
                                    href="#"
                                    @click.prevent="link.url && router.visit(link.url)"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium"
                                    :class="{
                                        'z-10 bg-primary text-white': link.active,
                                        'text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50': !link.active,
                                        'rounded-l-md': index === 0,
                                        'rounded-r-md': index === pagination.links.length - 1,
                                        'pointer-events-none opacity-50': !link.url,
                                    }"
                                >
                                    <component
                                        :is="index === 0 ? ChevronLeftIcon : index === pagination.links.length - 1 ? ChevronRightIcon : 'span'"
                                        class="h-5 w-5"
                                        v-if="index === 0 || index === pagination.links.length - 1"
                                    />
                                    <span v-else v-html="link.label"></span>
                                </a>
                            </template>
                        </nav>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No orders found</h3>
                    <p class="mt-1 text-sm text-gray-500">You have not placed any orders yet.</p>
                </div>
            </div>
        </div>

        <!-- Phone Number Modal -->
        <div v-if="showPhoneModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closePayModal"></div>
            <div class="relative z-10 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Confirm Phone Number</h3>
                <p class="mb-3 text-sm text-gray-600">We'll send the M-Pesa STK push to this number:</p>
                <div class="mb-2">
                    <label for="mpesa-phone" class="mb-1 block text-sm font-medium text-gray-700">M-Pesa Phone</label>
                    <input
                        id="mpesa-phone"
                        v-model="phoneInput"
                        type="tel"
                        inputmode="tel"
                        placeholder="07xxxxxxxx or 2547xxxxxxxx"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                    />
                    <p v-if="phoneError" class="mt-1 text-xs text-red-600">{{ phoneError }}</p>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="closePayModal" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button
                        @click="confirmPay"
                        :disabled="!phoneInput || (selectedOrder && payingOrderId === selectedOrder.id)"
                        class="inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-sm text-white hover:bg-primary/90 disabled:opacity-60"
                    >
                        <svg v-if="selectedOrder && payingOrderId === selectedOrder.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Pay Now
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
