<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon, TruckIcon } from 'lucide-vue-next';
import { computed } from 'vue';

// Interfaces
interface ShippingRate {
    id: number;
    zone_id?: number | null;
    zone?: { id: number; name: string; tier: number } | null;
    package_size: 'small' | 'medium' | 'large';
    base_price: number;
    door_fallback_price?: number;
    door_fallback_min_km?: number;
    door_extra_km_cost?: number;
    cod_max_amount?: number | null;
    free_shipping_min_amount?: number | null;
    created_at?: string;
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

// Page props
const page = usePage<AppPageProps<{ shippingRates: PaginatedResponse<ShippingRate> }>>();
const shippingRates = computed(() => page.props.shippingRates?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.shippingRates || {};
    return { links, meta };
});

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Shipping Rates', href: route('admin.shipping-rates.index') },
];

// Actions
function createRate() {
    router.get(route('admin.shipping-rates.create'));
}

function editRate(id: number) {
    if (!id) return;
    router.get(`/admin/shipping-rates/${id}/edit`);
}

function deleteRate(id: number) {
    if (!id) return;
    if (confirm('Are you sure you want to delete this shipping rate?')) {
        router.delete(route('admin.shipping-rates.destroy', { shipping_rate: id }));
    }
}
</script>

<template>
    <Head title="Shipping Rates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-800">Shipping Rates</h1>
                    <button
                        @click="createRate"
                        class="hover:bg-primary-dark flex cursor-pointer items-center rounded-xl bg-primary px-4 py-2 text-white transition-colors"
                    >
                        <PlusIcon class="mr-2 h-5 w-5" /> New Shipping Rates
                    </button>
                </div>

                <!-- Table -->
                <div v-if="shippingRates.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zone</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package Size</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Base Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Door Delivery/KM</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">COD Max</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Free Ship Min</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(rate, index) in shippingRates" :key="rate.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ rate.zone?.name ?? 'Default' }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-900 capitalize">{{ rate.package_size }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">Ksh. {{ rate.base_price.toFixed(2) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    <span v-if="rate.door_fallback_price != null">
                                        Fallback: Ksh. {{ rate.door_fallback_price?.toFixed(0) ?? '-' }}
                                        · First {{ rate.door_fallback_min_km ?? '-' }} km
                                        · +Ksh. {{ rate.door_extra_km_cost?.toFixed(0) ?? '-' }}/km
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ rate.cod_max_amount != null ? 'Ksh. ' + Math.round(rate.cod_max_amount).toLocaleString() : '-' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ rate.free_shipping_min_amount != null ? 'Ksh. ' + Math.round(rate.free_shipping_min_amount).toLocaleString() : '-' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    {{ rate.created_at ? new Date(rate.created_at).toLocaleDateString() : '-' }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editRate(rate.id)" class="mr-3 cursor-pointer text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteRate(rate.id)" class="cursor-pointer text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">
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
                                        class="inline-flex cursor-pointer items-center px-4 py-2 text-sm font-medium transition-colors"
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
                        <TruckIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No shipping rates</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new shipping rate.</p>
                        <div class="mt-6">
                            <button
                                @click="createRate"
                                class="hover:bg-primary-dark inline-flex cursor-pointer items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white transition-colors"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Shipping Rate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
