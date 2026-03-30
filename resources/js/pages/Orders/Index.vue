<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { AppPageProps, Order } from '@/types'
import { Head, router, useForm, usePage, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

// Props from Inertia page
const page = usePage<AppPageProps<{
    orders: { data: Order[]; links: any[] }
    filters?: {
        section?: string
        search?: string
        status?: string
        payment_status?: string
        fulfillment_status?: string
        date_from?: string
        date_to?: string
    }
    statusOptions: string[]
    paymentStatusOptions: string[]
    fulfillmentStatusOptions: string[]
}>>()

// Orders and filters
const orders = computed(() => page.props.orders?.data || [])
const filters = computed(() => page.props.filters || {})
const currentSection = computed(() => filters.value.section || 'in_progress')

// Form for filters
const searchForm = useForm({
    section: filters.value.section || 'in_progress',
    search: filters.value.search || '',
    status: filters.value.status || '',
    payment_status: filters.value.payment_status || '',
    fulfillment_status: filters.value.fulfillment_status || '',
    date_from: filters.value.date_from || '',
    date_to: filters.value.date_to || '',
})

// Selected orders for bulk actions (if needed)
const selectedOrders = ref<string[]>([])
const showBulkActions = computed(() => selectedOrders.value.length > 0)

// Breadcrumbs
const breadcrumbs = computed(() => [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Orders', href: '/admin/orders?section=in_progress' },
    { title: currentSection.value === 'delivered' ? 'Delivered' : 'In progress', href: '#' },
])

const hasActiveFilters = computed(() =>
    !!(searchForm.search || searchForm.status || searchForm.payment_status || searchForm.fulfillment_status || searchForm.date_from || searchForm.date_to)
)

// Filter search
function search() {
    const params: Record<string, string> = { section: searchForm.section }
    if (searchForm.search) params.search = searchForm.search
    if (searchForm.status) params.status = searchForm.status
    if (searchForm.payment_status) params.payment_status = searchForm.payment_status
    if (searchForm.fulfillment_status) params.fulfillment_status = searchForm.fulfillment_status
    if (searchForm.date_from) params.date_from = searchForm.date_from
    if (searchForm.date_to) params.date_to = searchForm.date_to

    router.get(route('admin.orders.index'), params, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(search, 350)

function setSection(section: 'in_progress' | 'delivered') {
    searchForm.section = section
    search()
}

// Clear filters
function clearFilters() {
    searchForm.search = ''
    searchForm.status = ''
    searchForm.payment_status = ''
    searchForm.fulfillment_status = ''
    searchForm.date_from = ''
    searchForm.date_to = ''
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

// Debounce the text search; fire immediately for dropdowns/dates
watch(() => searchForm.search, debouncedSearch)
watch(
    [() => searchForm.status, () => searchForm.payment_status, () => searchForm.fulfillment_status, () => searchForm.date_from, () => searchForm.date_to],
    search,
)
</script>

<template>
    <Head title="Orders" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header and Section Tabs -->
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <h1 class="text-2xl font-semibold">Orders</h1>
                        <div class="flex rounded-lg border border-gray-200 p-1">
                            <button
                                type="button"
                                @click="setSection('in_progress')"
                                class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                                :class="currentSection === 'in_progress'
                                    ? 'bg-primary text-white'
                                    : 'text-gray-600 hover:bg-gray-100'"
                            >
                                In progress
                            </button>
                            <button
                                type="button"
                                @click="setSection('delivered')"
                                class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                                :class="currentSection === 'delivered'
                                    ? 'bg-primary text-white'
                                    : 'text-gray-600 hover:bg-gray-100'"
                            >
                                Delivered
                            </button>
                        </div>
                    </div>

                <!-- Filters -->
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:flex-wrap">
                        <input
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Search by order code, customer name or email..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 md:w-80"
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
                    </div>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 whitespace-nowrap">From</label>
                            <input v-model="searchForm.date_from" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 whitespace-nowrap">To</label>
                            <input v-model="searchForm.date_to" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                        </div>
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="text-sm text-gray-600 hover:text-gray-800 underline"
                        >
                            Clear filters
                        </button>
                    </div>
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
                            <th class="px-4 py-3">Delivery Person Assigned</th>
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
                            <td class="px-4 py-3">
                                <span v-if="order.assigned_rider">{{ order.assigned_rider.name }}</span>
                                <span v-else class="text-gray-500">—</span>
                                <span v-if="order.assigned_rider && order.delivery_assignment_status" class="ml-1 text-xs text-gray-500">({{ order.delivery_assignment_status }})</span>
                            </td>
                            <td class="px-4 py-3">{{ order.created_at ? new Date(order.created_at).toLocaleDateString() : '-' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('admin.orders.show', order.ulid ?? order.id)" class="text-sm text-blue-600 hover:underline">View</Link>
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
