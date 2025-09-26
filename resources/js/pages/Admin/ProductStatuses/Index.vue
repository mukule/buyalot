<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, ProductStatus } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface ProductStatusWithHashid extends ProductStatus {
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

const page = usePage<AppPageProps<{ productStatuses: PaginatedResponse<ProductStatusWithHashid> }>>();

const statuses = computed(() => {
    const data = page.props.productStatuses?.data || [];
    data.forEach((s) => {
        if (!s.hashid) console.warn('Missing hashid for status:', s);
    });
    return data;
});

const pagination = computed(() => {
    const { links, meta } = page.props.productStatuses || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Product Statuses', href: route('admin.product-statuses.index') },
];

function createStatus() {
    router.get(route('admin.product-statuses.create'));
}

function showStatus(hashid: string) {
    if (!hashid) return;
    router.get(route('admin.product-statuses.show', { product_status: hashid }));
}

function editStatus(hashid: string) {
    if (!hashid) return;
    router.get(route('admin.product-statuses.edit', { product_status: hashid }));
}
</script>

<template>
    <Head title="Product Statuses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Product Statuses</h1>
                    <button @click="createStatus" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Status</button>
                </div>

                <!-- Table -->
                <div v-if="statuses.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(status, index) in statuses" :key="status.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td
                                    @click="showStatus(status.hashid)"
                                    class="cursor-pointer px-4 py-4 text-sm font-medium text-primary hover:underline"
                                >
                                    {{ status.name }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500">{{ status.label || 'N/A' }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span
                                        v-if="status.color_class"
                                        :class="['inline-block h-6 w-6 rounded-full', status.color_class]"
                                        title="Color"
                                    ></span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editStatus(status.hashid)" class="text-blue-600 hover:underline">Edit</button>
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
                                Showing <span class="font-medium">{{ pagination.meta?.from }}</span> to
                                <span class="font-medium">{{ pagination.meta?.to }}</span> of
                                <span class="font-medium">{{ pagination.meta?.total }}</span> results
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
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No product statuses</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new product status.</p>
                        <div class="mt-6">
                            <button
                                @click="createStatus"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
