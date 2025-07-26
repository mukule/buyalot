<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Brand, Product, Subcategory, Unit } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, FilterIcon, PlusIcon, SearchIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProductWithRelations extends Product {
    brand: Brand;
    unit: Unit;
    subcategory: Subcategory;
    primary_image_url: string | null;
    image_urls?: string[];
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

// Accessing page props
const page = usePage<
    AppPageProps<{
        products: PaginatedResponse<ProductWithRelations>;
    }>
>();

// Reactive state
const searchQuery = ref('');

// Computed: Search filtered products
const filteredProducts = computed(() => {
    const products = page.props.products?.data ?? [];
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) return products;

    return products.filter((p) => {
        return (
            p.name.toLowerCase().includes(query) ||
            (p.description?.toLowerCase().includes(query) ?? false) ||
            p.brand.name.toLowerCase().includes(query)
        );
    });
});

// Computed: Pagination
const pagination = computed(() => {
    const { links, meta } = page.props.products;
    return { links, meta };
});

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Products', href: route('admin.products.index') },
];

// Methods
function createProduct() {
    router.get(route('admin.products.create'));
}

function viewProduct(hashid: string) {
    router.get(route('admin.products.show', { product: hashid }));
}

function editProduct(hashid: string) {
    router.get(route('admin.products.edit', { product: hashid }));
}

function deleteProduct(hashid: string) {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(route('admin.products.destroy', { product: hashid }), {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: notify success
            },
        });
    }
}

const statusClasses = (status: number | string) => ({
    'bg-yellow-100 text-yellow-800': Number(status) === 0,
    'bg-green-100 text-green-800': Number(status) === 1,
    'bg-red-200 text-gray-800': Number(status) === 3,
});

function getInitials(name: string | null): string {
    if (!name) return '';
    const words = name.split(' ');
    return words.length >= 2 ? words[0][0].toUpperCase() + words[1][0].toUpperCase() : words[0][0].toUpperCase();
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Products</h1>
                    <button @click="createProduct" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Product</button>
                </div>

                <!-- Search and Filters -->
                <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <label for="search" class="sr-only">Search products</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <SearchIcon class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search products..."
                                class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-md border border-gray-300 py-2 pl-10 text-sm"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="focus:ring-primary-500 inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:outline-none sm:ml-4"
                    >
                        <FilterIcon class="h-4 w-4" />
                        Filters
                    </button>
                </div>

                <!-- Table -->
                <div v-if="filteredProducts.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(product, index) in filteredProducts" :key="product.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    <span
                                        class="group relative inline-flex h-8 w-8 cursor-default items-center justify-center rounded-full bg-primary font-semibold text-white select-none"
                                    >
                                        {{ getInitials(product.company_legal_name) }}

                                        <!-- Tooltip -->
                                        <div
                                            class="absolute top-full left-1/2 z-10 mt-1 -translate-x-1/2 rounded bg-primary px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition group-hover:opacity-100"
                                        >
                                            {{ product.company_legal_name }}
                                        </div>
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm font-medium text-primary">
                                    <a
                                        :href="route('admin.products.show', { product: product.hashid })"
                                        class="flex items-center space-x-3 hover:underline"
                                    >
                                        <img
                                            v-if="product.primary_image_url"
                                            :src="product.primary_image_url"
                                            alt="Product Image"
                                            class="h-10 w-10 rounded bg-white object-contain"
                                        />
                                        <span>{{ product.name.length > 30 ? product.name.slice(0, 30) + '…' : product.name }}</span>
                                    </a>
                                </td>
                                <td class="flex items-center space-x-2 px-4 py-4 text-sm text-gray-700">
                                    <img
                                        v-if="product.brand.logo_url"
                                        :src="product.brand.logo_url"
                                        :alt="product.brand.name"
                                        class="h-12 w-12 rounded-full object-contain"
                                    />
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ product.subcategory.name }}</td>

                                <!-- Price with discount applied -->
                                <td class="px-4 py-4 text-sm font-semibold text-gray-700">
                                    <span v-if="Number(product.discount) > 0">
                                        <span class="mr-2 text-gray-400 line-through">
                                            {{ Number(product.price).toFixed(2) }}
                                        </span>
                                        <span class="text-red-600">
                                            {{ (Number(product.price) * (1 - Number(product.discount) / 100)).toFixed(2) }}
                                        </span>
                                    </span>
                                    <span v-else>
                                        {{ Number(product.price).toFixed(2) }}
                                    </span>
                                </td>

                                <!-- Stock symbol instead of unit name -->
                                <td class="px-4 py-4 text-sm text-gray-700">{{ product.stock }} {{ product.unit.symbol }}</td>

                                <td class="px-4 py-4 text-sm">
                                    <span
                                        :class="statusClasses(product.status)"
                                        class="inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    >
                                        {{ product.status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right text-sm font-medium">
                                    <button @click.stop="editProduct(product.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteProduct(product.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <a
                                v-if="pagination.links[0].url"
                                :href="pagination.links[0].url"
                                class="inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <ChevronLeftIcon class="mr-1 h-5 w-5" />
                                Previous
                            </a>
                            <a
                                v-if="pagination.links.length && pagination.links[pagination.links.length - 1].url"
                                :href="pagination.links[pagination.links.length - 1].url || undefined"
                                class="ml-3 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Next
                                <ChevronRightIcon class="ml-1 h-5 w-5" />
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
                                        <span v-else v-html="link.label"></span>
                                    </a>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ searchQuery ? 'Try adjusting your search' : 'Get started by creating a new product.' }}
                    </p>
                    <div class="mt-6">
                        <button @click="createProduct" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Product</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
