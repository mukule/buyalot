<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Customer } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage<
    AppPageProps<{
        customers: (Customer & {
            hashid: string;
            phone?: string;
        })[];
        filters?: {
            search?: string;
            status?: string;
        };
    }>
>();
const customers = computed(() => page.props.customers.data || []);
const filters = computed(() => page.props.filters || {});

const searchForm = useForm({
    search: filters.value.search || '',
    status: filters.value.status || '',
});

const selectedCustomers = ref<string[]>([]);
const showBulkActions = computed(() => selectedCustomers.value.length > 0);

const showViewModal = ref(false);
const showEditModal = ref(false);
const showCreateModal = ref(false);
const selectedCustomer = ref<Customer | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    status: true,
});

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    status: true,
    errors: {},
    processing: false,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Customers', href: '/admin/customers' },
];

function search() {
    router.get(
        route('admin.customers.index'),
        {
            search: searchForm.search,
            status: searchForm.status,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function clearFilters() {
    searchForm.search = '';
    searchForm.status = '';
    search();
}

function toggleSelectAll() {
    if (selectedCustomers.value.length === customers.value.length) {
        selectedCustomers.value = [];
    } else {
        selectedCustomers.value = customers.value.map((c) => c.id.toString());
    }
}

function deleteCustomer(customerId: string) {
    if (confirm('Are you sure you want to delete this customer?')) {
        router.delete(route('admin.customers.destroy', customerId));
    }
}

function closeModals() {
    showViewModal.value = false;
    showEditModal.value = false;
    showCreateModal.value = false;
    selectedCustomer.value = null;
    editForm.reset();
    createForm.reset();
}

function openCreateModal() {
    createForm.reset();
    createForm.status = true;
    showCreateModal.value = true;
}

function openViewModal(customer: Customer) {
    selectedCustomer.value = customer;
    showViewModal.value = true;
}

function openEditModal(customer: Customer) {
    selectedCustomer.value = customer;
    editForm.name = customer.name;
    editForm.email = customer.email;
    editForm.phone = customer.phone ?? '';
    editForm.status = !!customer.status;
    showEditModal.value = true;
}

// --- FORM SUBMISSION FUNCTIONS ---
function createCustomer() {
    createForm.post(route('admin.customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
    });
}
function updateCustomer() {
    if (!selectedCustomer.value) return;

    editForm.put(route('admin.customers.update', selectedCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
    });
}

// Watch for changes in search form
watch(
    [() => searchForm.search, () => searchForm.status],
    () => {
        search();
    },
    { debounce: 300 },
);
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Customers</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage customer accounts</p>
                    </div>
                    <button @click="openCreateModal" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Customer</button>
                </div>

                <!-- Filters and Search -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <!-- Search -->
                        <div class="relative">
                            <input
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search customers..."
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 pr-10 focus:border-primary focus:outline-none md:w-64"
                            />
                        </div>

                        <select
                            v-model="searchForm.status"
                            class="rounded-lg border border-gray-300 px-4 py-2 focus:border-primary focus:outline-none"
                        >
                            <option value="">All Status</option>
                            <option :value="true">Active</option>
                            <option :value="false">Suspended</option>
                        </select>

                        <button
                            v-if="searchForm.search || searchForm.status"
                            @click="clearFilters"
                            class="text-sm text-gray-600 hover:text-gray-800"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="showBulkActions" class="flex items-center gap-4 rounded-lg bg-blue-50 p-4">
                    <span class="text-sm text-blue-800">{{ selectedCustomers.length }} customers selected</span>
                    <button class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">Bulk Delete</button>
                    <button @click="selectedCustomers = []" class="text-sm text-blue-600 hover:text-blue-800">Clear Selection</button>
                </div>

                <!-- Customers Table -->
                <div class="overflow-x-auto rounded-xl border border-primary">
                    <table class="min-w-full table-auto border-collapse text-left text-sm">
                        <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-4 py-3">
                                <input
                                    @change="toggleSelectAll"
                                    :checked="selectedCustomers.length === customers.length && customers.length > 0"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                            </th>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Orders</th> <!-- 👈 added -->
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(customer, index) in customers" :key="customer.id" class="border-t border-primary/30 hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <input
                                    v-model="selectedCustomers"
                                    :value="customer.id.toString()"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary focus:ring-primary"
                                />
                            </td>
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                      <span class="text-sm font-medium text-primary">
                                        {{ (customer.name || '').charAt(0).toUpperCase() }}
                                      </span>
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ customer.name }}</div>
                                        <div class="text-xs text-gray-500">{{ customer.phone }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">{{ customer.email || '-' }}</td>
                            <td class="px-4 py-3">{{ customer.orders_count }}</td> <!-- 👈 show orders count -->
                            <td class="px-4 py-3">
                                <span v-if="customer.status == true" class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                    Active
                                </span>
                                                    <span v-else class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-500">
                                    Suspended
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button @click="openViewModal(customer)" class="text-sm text-blue-600 hover:underline">View</button>
                                    <button @click="openEditModal(customer)" class="text-sm text-yellow-600 hover:underline">Edit</button>
                                    <button @click="deleteCustomer(customer.id.toString())" class="text-sm text-red-600 hover:underline">Delete</button>
                                </div>
                            </td>
                        </tr>
                        </tbody>

                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="customers.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                        ></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No customers found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            filters.search || filters.status
                                ? 'Try adjusting your search criteria.'
                                : 'Get started by creating a new customer.'
                        }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
