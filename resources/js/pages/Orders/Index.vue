<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { AppPageProps, Order } from '@/types'
import { Head, router, useForm, usePage, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

// Props from Inertia page
const page = usePage<AppPageProps<{
    orders: Order[]
    filters?: {
        order_code?: string
        status?: string
        payment_status?: string
        fulfillment_status?: string
    }
    statusOptions: string[]
    paymentStatusOptions: string[]
    fulfillmentStatusOptions: string[]
}>>()

// Orders and filters
const orders = computed(() => page.props.orders || [])
const filters = computed(() => page.props.filters || {})

// Form for filters
const searchForm = useForm({
    order_code: filters.value.order_code || '',
    status: filters.value.status || '',
    payment_status: filters.value.payment_status || '',
    fulfillment_status: filters.value.fulfillment_status || '',
})

// Selected orders for bulk actions (if needed)
const selectedOrders = ref<string[]>([])
const showBulkActions = computed(() => selectedOrders.value.length > 0)

// Modal states
const showViewModal = ref(false)
const selectedOrder = ref<Order | null>(null)

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Orders', href: '/admin/orders' },
]

// Filter search
function search() {
    router.get(
        route('orders.index'),
        {
            order_code: searchForm.order_code,
            status: searchForm.status,
            payment_status: searchForm.payment_status,
            fulfillment_status: searchForm.fulfillment_status,
        },
        { preserveState: true, replace: true }
    )
}

// Clear filters
function clearFilters() {
    searchForm.order_code = ''
    searchForm.status = ''
    searchForm.payment_status = ''
    searchForm.fulfillment_status = ''
    search()
}

// Toggle select all orders
function toggleSelectAll() {
    if (selectedOrders.value.length === orders.value.length) {
        selectedOrders.value = []
    } else {
        selectedOrders.value = orders.value.map((o) => o.id.toString())
    }
}

// Open view modal
function openViewModal(order: Order) {
    selectedOrder.value = order
    showViewModal.value = true
}

// Watch filters and auto-search
watch(
    [
        () => searchForm.order_code,
        () => searchForm.status,
        () => searchForm.payment_status,
        () => searchForm.fulfillment_status,
    ],
    () => {
        search()
    },
    { debounce: 300 }
)
</script>

<template>
    <Head title="Orders" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold">Orders</h1>
                </div>

                <!-- Filters -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <input
                            v-model="searchForm.order_code"
                            type="text"
                            placeholder="Search order code..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 md:w-64"
                        />
                        <select v-model="searchForm.status" class="rounded-lg border border-gray-300 px-4 py-2">
                            <option value="">All Status</option>
                            <option v-for="s in page.props.statusOptions" :key="s" :value="s">{{ s }}</option>
                        </select>
                        <select v-model="searchForm.payment_status" class="rounded-lg border border-gray-300 px-4 py-2">
                            <option value="">All Payment</option>
                            <option v-for="p in page.props.paymentStatusOptions" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <select v-model="searchForm.fulfillment_status" class="rounded-lg border border-gray-300 px-4 py-2">
                            <option value="">All Fulfillment</option>
                            <option v-for="f in page.props.fulfillmentStatusOptions" :key="f" :value="f">{{ f }}</option>
                        </select>

                        <button
                            v-if="searchForm.order_code || searchForm.status || searchForm.payment_status || searchForm.fulfillment_status"
                            @click="clearFilters"
                            class="text-sm text-gray-600 hover:text-gray-800"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="showBulkActions" class="flex items-center gap-4 rounded-lg bg-blue-50 p-4">
                    <span class="text-sm text-blue-800">{{ selectedOrders.length }} orders selected</span>
                    <button class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">Bulk Delete</button>
                    <button @click="selectedOrders = []" class="text-sm text-blue-600 hover:text-blue-800">Clear Selection</button>
                </div>

                <!-- Orders Table -->
                <div v-if="orders.length > 0" class="overflow-x-auto rounded-xl border border-primary">
                    <table class="min-w-full table-auto border-collapse text-left text-sm">
                        <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-4 py-3">
                                <input
                                    @change="toggleSelectAll"
                                    :checked="selectedOrders.length === orders.length && orders.length > 0"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                            </th>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Order Code</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(order, index) in orders" :key="order.id" class="border-t border-primary/30 hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <input v-model="selectedOrders" :value="order.id.toString()" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary" />
                            </td>
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3">{{ order.order_code }}</td>
                            <td class="px-4 py-3">{{ order.customer?.first_name || '-' }} {{ order.customer?.last_name || '' }}</td>
                            <td class="px-4 py-3 capitalize">{{ order.status || '-' }}</td>
                            <td class="px-4 py-3 capitalize">{{ order.payment_status || '-' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ order.total_amount != null ? Number(order.total_amount).toFixed(2) : '0.00' }}</td>
                            <td class="px-4 py-3">{{ order.created_at ? new Date(order.created_at).toLocaleDateString() : '-' }}</td>
                            <td class="px-4 py-3">
                                <button @click="openViewModal(order)" class="text-sm text-blue-600 hover:underline">View</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18v18H3V3z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No orders found</h3>
                    <p class="mt-1 text-sm text-gray-500">Adjust your filters or create a new order to get started.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
