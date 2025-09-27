<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import { SearchIcon, ChevronLeftIcon, ChevronRightIcon, Eye } from 'lucide-vue-next';

interface OrderItem {
    id: number;
    order_code: string;
    status: string;
    total_amount: number;
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

const page = usePage<{ orders: PaginatedResponse<OrderItem> }>();
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
    return orders.filter(
        (o) =>
            o.order_number.toLowerCase().includes(query) ||
            o.status.toLowerCase().includes(query),
    );
});

const pagination = computed(() => {
    const { links, meta } = page.props.orders;
    return { links, meta };
});

function viewOrder(id: number) {
    router.get(route('orders.show', { order: id }));
}

const statusClasses = (status: string) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-blue-100 text-blue-800': status === 'processing',
    'bg-green-100 text-green-800': status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled',
});
</script>

<template>
    <Head title="My Orders" />

    <AppLayout>
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-6">
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
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
                            <span :class="statusClasses(order.status)" class="inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize">
                                {{ order.status }}
                            </span>
                            </td>
                            <td class="px-4 py-4 text-right text-sm font-medium">
                                <button @click="viewOrder(order.id)" aria-label="View Order" class="text-blue-600 transition hover:text-blue-800">
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
    </AppLayout>
</template>
