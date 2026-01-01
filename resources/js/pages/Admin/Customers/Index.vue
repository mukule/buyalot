<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Customer } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage<
    AppPageProps<{
        customers: {
            data: (Customer & {
                hashid: string;
                phone?: string;
                default_address?: any;
            })[];
        };
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
const selectedCustomer = ref<(Customer & { hashid: string; default_address?: any }) | null>(null);

const editForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    status: 'active' as string,
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

function closeModals() {
    showViewModal.value = false;
    showEditModal.value = false;
    selectedCustomer.value = null;
    editForm.reset();
}

function openViewModal(customer: Customer & { hashid: string; default_address?: any }) {
    selectedCustomer.value = customer;
    showViewModal.value = true;
}

function openEditModal(customer: any) {
    selectedCustomer.value = customer;

    // Split name into first and last name if available
    if (customer.name && (!customer.first_name || !customer.last_name)) {
        const parts = customer.name.split(' ');
        editForm.first_name = parts[0] || '';
        editForm.last_name = parts.slice(1).join(' ') || '';
    } else {
        editForm.first_name = customer.first_name || '';
        editForm.last_name = customer.last_name || '';
    }

    editForm.email = customer.email;
    editForm.phone = customer.phone ?? '';
    editForm.status = customer.status;
    showEditModal.value = true;
}

function updateCustomer() {
    if (!selectedCustomer.value) return;

    if (!selectedCustomer.value.hashid) {
        console.error('Customer hashid is missing', selectedCustomer.value);
        return;
    }

    editForm.put(
        route('admin.customers.update', { customer: selectedCustomer.value.hashid }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeModals();
                // Optional: show a success message if you have a toast system
            },
        },
    );
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
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <button v-if="searchForm.search || searchForm.status" @click="clearFilters" class="text-sm text-gray-600 hover:text-gray-800">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="showBulkActions" class="flex items-center gap-4 rounded-lg bg-blue-50 p-4">
                    <span class="text-sm text-blue-800">{{ selectedCustomers.length }} customers selected</span>
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
                                <th class="px-4 py-3">Orders</th>
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
                                            <div class="text-xs text-gray-500">{{ customer.phone || customer.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">{{ customer.email || '-' }}</td>
                                <td class="px-4 py-3">{{ customer.orders_count }}</td>
                                <!-- 👈 show orders count -->
                                <td class="px-4 py-3">
                                    <span
                                        v-if="customer.status == 'active'"
                                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800"
                                    >
                                        Active
                                    </span>
                                    <span
                                        v-if="customer.status == 'suspended'"
                                        class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-orange-400"
                                    >
                                        Suspended
                                    </span>
<!--                                    <span-->
<!--                                        v-if="customer.status == 'blacklisted'"-->
<!--                                        class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-yellow-600"-->
<!--                                    >-->
<!--                                        Blacklisted-->
<!--                                    </span>-->
                                    <span
                                        v-if="customer.status == 'inactive'"
                                        class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-500"
                                    >
                                        Inactive
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <button @click="openViewModal(customer)" class="rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100">View</button>
                                        <button @click="openEditModal(customer)" class="rounded bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-600 hover:bg-yellow-100">Edit</button>
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
                        {{ filters.search || filters.status ? 'Try adjusting your search criteria.' : 'No customers are currently available.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div v-if="showViewModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                    <h3 class="text-xl font-bold text-gray-800">Customer Profile</h3>
                    <button @click="closeModals" class="rounded-full p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Account Details</h4>
                        <div class="flex flex-col gap-3">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <span class="text-lg font-bold">{{ selectedCustomer.name.charAt(0).toUpperCase() }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Full Name</p>
                                    <p class="text-base font-semibold text-gray-900">{{ selectedCustomer.name }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Email Address</p>
                                    <p class="text-base font-medium text-gray-900">{{ selectedCustomer.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Phone Number</p>
                                    <p class="text-base font-medium text-gray-900">{{ selectedCustomer.phone || 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Default Address</h4>
                        <div v-if="selectedCustomer.default_address" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-gray-800">{{ selectedCustomer.default_address.first_name }} {{ selectedCustomer.default_address.last_name }}</p>
                                <p class="text-sm text-gray-600">{{ selectedCustomer.default_address.address_line_1 }}</p>
                                <p v-if="selectedCustomer.default_address.address_line_2" class="text-sm text-gray-600">{{ selectedCustomer.default_address.address_line_2 }}</p>
                                <p class="text-sm text-gray-600">{{ selectedCustomer.default_address.city }}, {{ selectedCustomer.default_address.state_province }} {{ selectedCustomer.default_address.postal_code }}</p>
                                <p class="text-sm font-medium text-gray-500">{{ selectedCustomer.default_address.country_name }}</p>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                            <p class="text-sm text-gray-500 italic">No default address set</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500">Status:</span>
                        <span
                            :class="{
                                'bg-green-100 text-green-700': String(selectedCustomer.status) === 'active' || selectedCustomer.status === true,
                                'bg-orange-100 text-orange-700': selectedCustomer.status === 'suspended',
                                'bg-yellow-100 text-yellow-700': selectedCustomer.status === 'blacklisted',
                                'bg-red-100 text-red-700': String(selectedCustomer.status) === 'inactive' || selectedCustomer.status === false
                            }"
                            class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase"
                        >
                            {{ selectedCustomer.status }}
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeModals" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Close
                        </button>
                        <button @click="openEditModal(selectedCustomer)" class="rounded-lg bg-primary px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90">
                            Edit Account
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl transition-all">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-xl font-bold text-gray-800">Edit Customer</h3>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">First Name</label>
                            <input v-model="editForm.first_name" type="text" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary" :class="{'border-red-500': editForm.errors.first_name}" />
                            <p v-if="editForm.errors.first_name" class="mt-1 text-xs text-red-500">{{ editForm.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Last Name</label>
                            <input v-model="editForm.last_name" type="text" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary" :class="{'border-red-500': editForm.errors.last_name}" />
                            <p v-if="editForm.errors.last_name" class="mt-1 text-xs text-red-500">{{ editForm.errors.last_name }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Email Address</label>
                        <input v-model="editForm.email" type="email" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary" :class="{'border-red-500': editForm.errors.email}" />
                        <p v-if="editForm.errors.email" class="mt-1 text-xs text-red-500">{{ editForm.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Phone Number</label>
                        <input v-model="editForm.phone" type="text" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary" :class="{'border-red-500': editForm.errors.phone}" />
                        <p v-if="editForm.errors.phone" class="mt-1 text-xs text-red-500">{{ editForm.errors.phone }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Account Status</label>
                            <select v-model="editForm.status" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary" :class="{'border-red-500': editForm.errors.status}">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
<!--                                <option value="blacklisted">Blacklisted</option>-->
                                <option value="inactive">Inactive</option>
                            </select>
                            <p v-if="editForm.errors.status" class="mt-1 text-xs text-red-500">{{ editForm.errors.status }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <button @click="closeModals" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button
                        @click="updateCustomer"
                        :disabled="editForm.processing"
                        class="flex items-center gap-2 rounded-lg bg-primary px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 disabled:opacity-50"
                    >
                        <span v-if="editForm.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
