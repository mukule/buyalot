<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Warehouse } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface WarehouseWithHashid extends Warehouse {
    hashid: string;
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

const page = usePage<AppPageProps<{ warehouses: PaginatedResponse<WarehouseWithHashid> }>>();

const warehouses = computed(() => {
    const data = page.props.warehouses?.data || [];
    console.log('Warehouses data:', data);
    data.forEach((w) => {
        if (!w.hashid) {
            console.warn('Missing hashid for warehouse:', w);
        }
    });
    return data;
});

const pagination = computed(() => {
    const { links, meta } = page.props.warehouses || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Warehouses', href: route('admin.warehouses.index') },
];

// Action methods
function createWarehouse() {
    router.get(route('admin.warehouses.create'));
}

function showWarehouse(hashid: string) {
    console.log('showWarehouse called with hashid:', hashid);
    if (!hashid) {
        console.error('showWarehouse called without hashid');
        return;
    }
    router.get(route('admin.warehouses.show', { warehouse: hashid }));
}

function editWarehouse(hashid: string) {
    console.log('editWarehouse called with hashid:', hashid);
    if (!hashid) {
        console.error('editWarehouse called without hashid');
        return;
    }
    router.get(route('admin.warehouses.edit', { warehouse: hashid }));
}

function deleteWarehouse(hashid: string) {
    console.log('deleteWarehouse called with hashid:', hashid);
    if (!hashid) {
        console.error('deleteWarehouse called without hashid');
        return;
    }
    if (confirm('Are you sure you want to delete this warehouse?')) {
        router.delete(route('admin.warehouses.destroy', { warehouse: hashid }));
    }
}

const statusClasses = (active: boolean) => ({
    'text-green-600': active,
    'text-red-600': !active,
});
</script>

<template>
    <Head title="Warehouses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Warehouses</h1>
                    <button @click="createWarehouse" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Warehouse</button>
                </div>

                <!-- Table -->
                <div v-if="warehouses.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(warehouse, index) in warehouses" :key="warehouse.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td
                                    @click="showWarehouse(warehouse.hashid)"
                                    class="cursor-pointer px-4 py-4 text-sm font-medium text-primary hover:underline"
                                >
                                    {{ warehouse.name }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500">{{ warehouse.location || 'N/A' }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span :class="statusClasses(warehouse.active)">
                                        {{ warehouse.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editWarehouse(warehouse.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteWarehouse(warehouse.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <a
                                v-if="pagination.links[0].url"
                                :href="pagination.links[0].url ?? undefined"
                                class="inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </a>
                            <a
                                v-if="pagination.links[pagination.links.length - 1].url"
                                :href="pagination.links[pagination.links.length - 1].url ?? undefined"
                                class="ml-3 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </a>
                        </div>

                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ pagination.meta?.from }}</span>
                                to
                                <span class="font-medium">{{ pagination.meta?.to }}</span>
                                of
                                <span class="font-medium">{{ pagination.meta?.total }}</span>
                                results
                            </p>

                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <template v-for="(link, index) in pagination.links" :key="index">
                                    <a
                                        :href="link.url ?? undefined"
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
                                        <span v-else>{{ link.label }}</span>
                                    </a>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No warehouses</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new warehouse.</p>
                        <div class="mt-6">
                            <button
                                @click="createWarehouse"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Warehouse
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
