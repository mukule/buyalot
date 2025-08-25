<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, ProductStatus } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ArrowLeft, FilterIcon, PlusIcon, SearchIcon } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

// Product type
interface ProductWithRelations {
    id: number;
    name: string;
    product_code: string;
    primary_image_url: string | null;
    stock?: number;
    hashid: string;
    status_label: string;
    status_id?: number | null;
    owner?: {
        id: number;
        name: string;
    } | null;
    warranties?: {
        id: number;
        hashid: string;
        product_hashid: string;
        duration: number;
        description?: string;
        active: boolean;
    }[];
}

// Product status type
interface ProductStatusWithHashid extends ProductStatus {
    hashid: string;
}

// Pagination interfaces
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

// Inertia page props
const page = usePage<
    AppPageProps<{
        products: PaginatedResponse<ProductWithRelations>;
        productStatuses: ProductStatusWithHashid[];
    }>
>();

// Status options
const statuses = computed(() => page.props.productStatuses ?? []);

// Compute Draft status ID for fallback
const draftStatusId = computed(() => {
    const draft = statuses.value.find((s) => s.name.toLowerCase() === 'draft');
    return draft?.id ?? null;
});

// Make a reactive copy of products so v-model works
const reactiveProducts = reactive(
    page.props.products.data.map((p) => ({
        ...p,
        status_id: p.status_id ?? draftStatusId.value,
    })),
);

// Search
const searchQuery = ref('');
const debouncedQuery = ref(searchQuery.value);

watch(
    searchQuery,
    debounce((val: string) => {
        debouncedQuery.value = val;
    }, 300),
);

const filteredProducts = computed(() => {
    const query = debouncedQuery.value.trim().toLowerCase();
    return reactiveProducts.filter((p) => {
        if (!query) return true;
        return (
            p.name.toLowerCase().includes(query) ||
            (p.product_code?.toLowerCase().includes(query) ?? false) ||
            (p.owner?.name.toLowerCase().includes(query) ?? false)
        );
    });
});

// Breadcrumbs
const breadcrumbs = reactive([
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Products', href: route('admin.products.index') },
]);

// Actions
function createProduct() {
    router.get(route('admin.products.create'));
}
function editProduct(hashid: string) {
    console.log('Editing product:', hashid);
    router.get(route('admin.products.edit', { product: hashid }));
}
function goBack() {
    router.get(route('admin.dashboard'));
}

// Truncate helper
function truncateName(name: string, length: number) {
    if (name.length <= length) return name;
    return name.slice(0, length - 3) + '...';
}

function updateStatus(productHashid: string, statusId: number) {
    router.patch(route('admin.products.updateStatus', { product: productHashid }), {
        status_id: statusId,
    });
}

// Warranty modal state
const showWarrantyModal = ref(false);
const selectedProduct = ref<ProductWithRelations | null>(null);

function openWarrantyModal(product: ProductWithRelations) {
    console.log('Opening warranty modal for product:', product);

    // Unwrap Proxy objects and attach product_hashid to each warranty
    selectedProduct.value = {
        ...product,
        warranties: product.warranties
            ? product.warranties.map((w) => ({
                  ...w,
                  product_hashid: product.hashid,
              }))
            : [],
    };
    showWarrantyModal.value = true;
}

function closeWarrantyModal() {
    showWarrantyModal.value = false;
    selectedProduct.value = null;
}

// Open Add Warranty page for a product
function addWarranty(productHashid: string) {
    console.log('Navigating to add warranty page for:', productHashid);
    router.get(route('admin.products.warranties.create', { product: productHashid }));
}

function toggleWarrantyActive(warranty: { id: number; hashid: string; active: boolean }) {
    const currentStatus = warranty.active;
    const newStatus = !currentStatus;

    // Log the warranty info before sending
    console.log('🔹 Toggling warranty:', warranty);
    console.log('🔹 Warranty hashid:', warranty.hashid);

    // Generate the route using simplified route (no product hashid)
    const url = route('admin.warranties.toggleActive', {
        warranty: warranty.hashid,
    });
    console.log('🔹 Generated route URL:', url);

    // Optimistic UI update
    warranty.active = newStatus;

    router.patch(
        url,
        {}, // no payload needed
        {
            onStart: () => {
                console.log('🚀 Sending PATCH request to:', url);
            },
            onSuccess: (page) => {
                console.log('✅ Success response:', page);

                // Make other warranties inactive in the modal
                if (newStatus && selectedProduct.value?.warranties) {
                    selectedProduct.value.warranties.forEach((w) => {
                        if (w.hashid !== warranty.hashid) w.active = false;
                    });
                }
            },
            onError: (errors) => {
                console.error('❌ Backend error:', errors);
                warranty.active = currentStatus; // revert UI if fail
            },
            onFinish: () => {
                console.log('🔚 Request finished');
            },
        },
    );
}

// Edit warranty
function editWarranty(warranty: { hashid: string; product_hashid: string }) {
    console.log('Editing warranty:', warranty);

    if (!warranty.hashid || !warranty.product_hashid) {
        console.error('Warranty hashid or product hashid is missing!');
        return;
    }

    router.get(
        route('admin.products.warranties.edit', {
            product: warranty.product_hashid,
            warranty: warranty.hashid,
        }),
    );
}
</script>

<template>
    <Head title="Products" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="relative mb-4 flex items-center justify-between">
                    <button @click="goBack" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <ArrowLeft class="h-4 w-4" /> Back
                    </button>
                    <h1 class="absolute left-1/2 -translate-x-1/2 transform text-2xl font-semibold">Products</h1>
                    <button @click="createProduct" class="hover:bg-primary-dark ml-auto rounded-xl bg-primary px-4 py-2 text-white">
                        + New Product
                    </button>
                </div>

                <!-- Search & Filter -->
                <div class="mb-4 flex items-center gap-4">
                    <div class="relative flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products..."
                            class="focus:ring-primary-500 focus:border-primary-500 block w-full rounded-md border py-2 pl-10 text-sm"
                        />
                        <SearchIcon class="absolute top-2.5 left-3 h-5 w-5 text-gray-400" />
                    </div>
                    <button class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm hover:bg-gray-50">
                        <FilterIcon class="h-4 w-4" /> Filters
                    </button>
                </div>

                <!-- Products Table -->
                <div v-if="filteredProducts.length" class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-4 py-2 text-left">Image</th>
                                <th class="border px-4 py-2 text-left">Name</th>
                                <th class="border px-4 py-2 text-left">Code</th>
                                <th class="border px-4 py-2 text-left">Stock</th>
                                <th class="border px-4 py-2 text-left">Store</th>
                                <th class="border px-4 py-2 text-left">Warranty</th>
                                <th class="border px-4 py-2 text-left">Status</th>
                                <th class="border px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50">
                                <td class="border px-4 py-2">
                                    <img
                                        v-if="product.primary_image_url"
                                        :src="product.primary_image_url"
                                        alt="Product"
                                        class="h-12 w-12 rounded object-cover"
                                    />
                                    <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-gray-200 text-gray-400">No Image</div>
                                </td>
                                <td class="border px-4 py-2">
                                    <a :href="route('admin.products.show', { product: product.hashid })" class="text-blue-600 hover:underline">
                                        {{ truncateName(product.name, 30) }}
                                    </a>
                                </td>
                                <td class="border px-4 py-2">{{ product.product_code }}</td>
                                <td class="border px-4 py-2">{{ product.stock ?? 0 }}</td>
                                <td class="border px-4 py-2">{{ product.owner?.name ?? '-' }}</td>

                                <!-- Warranty column -->
                                <td class="border px-4 py-2">
                                    <button @click="openWarrantyModal(product)" class="text-sm text-blue-600 hover:underline">
                                        {{ product.warranties?.length ? `View (${product.warranties.length})` : 'Click to Add' }}
                                    </button>
                                </td>

                                <td class="border px-4 py-2">
                                    <select
                                        v-model="product.status_id"
                                        @change="updateStatus(product.hashid, Number(product.status_id))"
                                        :class="[statuses.find((s) => s.id === product.status_id)?.color_class || 'bg-gray-100 text-gray-800']"
                                    >
                                        <option :value="product.status_id" v-if="product.status_id" hidden>
                                            {{ statuses.find((s) => s.id === product.status_id)?.label }}
                                        </option>
                                        <option
                                            v-for="status in statuses.filter((s) => s.id !== product.status_id)"
                                            :key="status.id"
                                            :value="status.id"
                                        >
                                            {{ status.name }}
                                        </option>
                                    </select>
                                </td>

                                <td class="flex gap-2 border px-4 py-2">
                                    <button @click="editProduct(product.hashid)" class="text-sm text-blue-600 hover:underline">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ searchQuery ? 'Try adjusting your search' : 'Get started by creating a new product.' }}
                    </p>
                    <button @click="createProduct" class="hover:bg-primary-dark mt-4 rounded-xl bg-primary px-4 py-2 text-white">
                        + New Product
                    </button>
                </div>
            </div>

            <!-- Warranty Modal -->
            <div
                v-if="showWarrantyModal"
                @click.self="closeWarrantyModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="mb-4 text-xl font-semibold text-gray-800">Product Warranties</h2>

                    <ul class="max-h-96 space-y-2 overflow-y-auto">
                        <template v-if="selectedProduct?.warranties?.length">
                            <li
                                v-for="warranty in selectedProduct.warranties"
                                :key="warranty.id"
                                class="flex flex-col gap-2 rounded border px-4 py-2"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-semibold">Duration:</span> {{ warranty.duration }} months
                                        <span
                                            v-if="warranty.active"
                                            class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700"
                                        >
                                            Active
                                        </span>
                                        <span v-else class="ml-2 rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">
                                            Inactive
                                        </span>
                                    </div>

                                    <div class="flex gap-2">
                                        <!-- Toggle Active -->
                                        <button
                                            @click="toggleWarrantyActive(warranty)"
                                            class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                        >
                                            {{ warranty.active ? 'Deactivate' : 'Activate' }}
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            @click="editWarranty(warranty)"
                                            class="rounded bg-yellow-500 px-2 py-1 text-xs text-white hover:bg-yellow-600"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                </div>

                                <div v-if="warranty.description" class="mt-1 text-sm text-gray-600">
                                    {{ warranty.description }}
                                </div>
                            </li>
                        </template>

                        <li v-else class="text-sm text-gray-500">No warranties available.</li>
                    </ul>

                    <button
                        v-if="selectedProduct"
                        @click="addWarranty(selectedProduct.hashid)"
                        class="mt-4 w-full rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                    >
                        {{ selectedProduct?.warranties?.length ? 'Add Another Warranty' : 'Add New Warranty' }}
                    </button>

                    <button @click="closeWarrantyModal" class="mt-2 w-full rounded border px-4 py-2 text-gray-700 hover:bg-gray-100">Close</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
