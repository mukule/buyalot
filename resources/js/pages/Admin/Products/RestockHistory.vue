<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface Product {
    id: number;
    hashid: string;
    name: string;
    product_code?: string;
    primary_image_url?: string | null;
}

interface RestockHistoryItem {
    id: number;
    variant_display_name: string;
    quantity: number;
    note: string | null;
    restocked_by: string;
    restocked_at: string;
}

const props = defineProps<{
    product: Product;
    restocks: RestockHistoryItem[];
}>();

function formatDate(iso: string) {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

function backToProducts() {
    router.get(route('admin.products.index'));
}
</script>

<template>
    <Head :title="`Restock history – ${product.name}`" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: route('admin.dashboard') },
            { title: 'Products', href: route('admin.products.index') },
            { title: product.name, href: route('admin.products.show', { product: product.hashid }) },
            { title: 'Restock history', href: '' },
        ]"
    >
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <button
                        @click="backToProducts"
                        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <ArrowLeft class="h-4 w-4" /> Back to products
                    </button>
                    <a
                        :href="route('admin.products.show', { product: product.hashid })"
                        class="text-sm text-blue-600 hover:underline"
                    >
                        View product
                    </a>
                </div>

                <div class="mb-6 flex items-center gap-4">
                    <img
                        v-if="product.primary_image_url"
                        :src="product.primary_image_url"
                        :alt="product.name"
                        class="h-16 w-16 rounded object-cover"
                    />
                    <div v-else class="flex h-16 w-16 items-center justify-center rounded bg-gray-200 text-gray-400">
                        No image
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold">{{ product.name }}</h1>
                        <p v-if="product.product_code" class="text-sm text-gray-500">{{ product.product_code }}</p>
                    </div>
                </div>

                <h2 class="mb-4 text-lg font-medium">Restock history</h2>

                <div v-if="restocks.length" class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-4 py-2 text-left">Variant</th>
                                <th class="border px-4 py-2 text-left">Quantity</th>
                                <th class="border px-4 py-2 text-left">Restocked by</th>
                                <th class="border px-4 py-2 text-left">Date</th>
                                <th class="border px-4 py-2 text-left">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in restocks" :key="r.id" class="hover:bg-gray-50">
                                <td class="border px-4 py-2">{{ r.variant_display_name }}</td>
                                <td class="border px-4 py-2">+{{ r.quantity }}</td>
                                <td class="border px-4 py-2">{{ r.restocked_by }}</td>
                                <td class="border px-4 py-2">{{ formatDate(r.restocked_at) }}</td>
                                <td class="border px-4 py-2 text-gray-600">{{ r.note || '–' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="rounded border border-gray-200 bg-gray-50 p-8 text-center">
                    <p class="text-gray-600">No restock history for this product yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
