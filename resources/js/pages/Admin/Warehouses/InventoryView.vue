<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusIcon } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    warehouse: { id: number; hashid: string; name: string; code: string; capacity?: number };
    inventories: any[];
    pagination: any;
    filters: { search: string; per_page: number };
    warehouses: { id: number; name: string }[];
    summary?: { total_units: number; total_variants: number; capacity: number|null; remaining_capacity: number };
}>();

const inventories = ref([...props.inventories]);
const search = ref(props.filters.search);
const perPage = ref(props.filters.per_page);

const showTransferModal = ref(false);
const transferInventoryId = ref<number | null>(null);
const transferWarehouseId = ref<number | null>(null);
const transferQuantity = ref<number>(1);
const transferAvailableStock = ref<number>(0);

// Publish to Variants modal state
const showPublishModal = ref(false);
const publishInventoryId = ref<number | null>(null);
const publishAvailableStock = ref<number>(0);
const publishQuantity = ref<number>(1);
const publishAll = ref<boolean>(false);

// Add Product (Variant) Modal state
const showAddModal = ref(false);

// Cascading select state
const categoryLevels = ref<{ options: any[]; selectedId: number | null }[]>([]);
const finalSelectedCategoryId = ref<number | null>(null);
const products = ref<any[]>([]);
const variants = ref<any[]>([]);

const selectedProductId = ref<number | null>(null);
const selectedVariantId = ref<number | null>(null);

const loadingCategories = ref(false);
const loadingProducts = ref(false);
const loadingVariants = ref(false);

const addQuantity = ref<number>(1);
const regularPrice = ref(null);
const sellingPrice = ref(null);
const costPrice = ref(null);


// Edit Inventory modal state
const showEditModal = ref(false);
const editInventoryId = ref<number | null>(null);
const editStock = ref<number>(0);
const editRegularPrice = ref<number | null>(null);
const editSellingPrice = ref<number | null>(null);
const editCostPrice = ref<number | null>(null);

// Methods

function filterInventories() {
    router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {
        search: search.value,
        per_page: perPage.value,
    }, { preserveState: true, replace: true });
}

function openAddModal() {
    showAddModal.value = true;
    // reset selections
    categoryLevels.value = [];
    finalSelectedCategoryId.value = null;
    selectedProductId.value = null;
    selectedVariantId.value = null;
    addQuantity.value = 1;
    products.value = [];
    variants.value = [];
    loadRootCategories();
}

function closeAddModal() {
    showAddModal.value = false;
    categoryLevels.value = [];
    finalSelectedCategoryId.value = null;
    selectedProductId.value = null;
    selectedVariantId.value = null;
    addQuantity.value = 1;
    products.value = [];
    variants.value = [];
}

async function loadRootCategories() {
    loadingCategories.value = true;
    try {
        const url = route('admin.inventory.categories', { warehouse: props.warehouse.hashid });
        const { data } = await axios.get(url);
        categoryLevels.value = [{ options: data?.categories || [], selectedId: null }];
    } catch (e) {
        console.error('Failed to load root categories', e);
        categoryLevels.value = [{ options: [], selectedId: null }];
    } finally {
        loadingCategories.value = false;
    }
}

async function fetchProducts() {
    if (!finalSelectedCategoryId.value) { products.value = []; return; }
    loadingProducts.value = true;
    try {
        const url = route('admin.inventory.products', { warehouse: props.warehouse.hashid });
        const { data } = await axios.get(url, { params: { category_id: finalSelectedCategoryId.value } });
        products.value = data?.products || [];
    } catch (e) {
        console.error('Failed to load products', e);
        products.value = [];
    } finally {
        loadingProducts.value = false;
    }
}

async function onCategoryChange(levelIndex: number) {
    const level = categoryLevels.value[levelIndex];
    const selectedId = level?.selectedId || null;

    // Trim deeper levels
    categoryLevels.value = categoryLevels.value.slice(0, levelIndex + 1);

    // Reset dependent selections
    finalSelectedCategoryId.value = selectedId;
    selectedProductId.value = null;
    selectedVariantId.value = null;
    products.value = [];
    variants.value = [];

    if (!selectedId) return;

    // Load children for the selected category
    loadingCategories.value = true;
    try {
        const url = route('admin.inventory.categories', { warehouse: props.warehouse.hashid });
        const { data } = await axios.get(url, { params: { parent_id: selectedId } });
        const children = data?.categories || [];
        if (children.length > 0) {
            // Push next level for children
            categoryLevels.value.push({ options: children, selectedId: null });
            finalSelectedCategoryId.value = null; // not a leaf yet
        } else {
            // Leaf category selected -> load products
            finalSelectedCategoryId.value = selectedId;
            await fetchProducts();
        }
    } catch (e) {
        console.error('Failed to load child categories', e);
    } finally {
        loadingCategories.value = false;
    }
}

async function fetchVariantsByProduct() {
    if (!selectedProductId.value) { variants.value = []; return; }
    loadingVariants.value = true;
    try {
        const url = route('admin.inventory.variants-by-product', { warehouse: props.warehouse.hashid });
        const { data } = await axios.get(url, { params: { product_id: selectedProductId.value, exclude_existing: 1 } });
        variants.value = data?.variants || [];
    } catch (e) {
        console.error('Failed to load variants', e);
        variants.value = [];
    } finally {
        loadingVariants.value = false;
    }
}

function submitAddVariant() {
    if (!selectedVariantId.value || addQuantity.value <= 0) return;
    router.post(
        route('admin.inventory.add', { warehouse: props.warehouse.hashid }),
        { product_variant_id: selectedVariantId.value, quantity: addQuantity.value,
            regular_price: regularPrice.value,
            selling_price: sellingPrice.value,
            cost_price: costPrice.value, },
        {
            onSuccess: () => {
                closeAddModal();
                // Optionally, navigate to receivables list; for now just refresh inventory
                router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
            },
        }
    );
}

function adjustStock(inventoryId: number, type: 'increase' | 'decrease' | 'damaged') {
    const quantity = 1;
    router.post(route('admin.inventory.adjust', { warehouse: props.warehouse.hashid }), {
        inventory_id: inventoryId,
        type,
        quantity
    }, {
        preserveState: true,
        onSuccess: () => {
            // Refresh inventories and summary so counts reflect the new values
            router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
        }
    });
}


function openTransferModal(inv: any) {
    transferInventoryId.value = inv.id;
    transferAvailableStock.value = Number(inv.stock || 0);
    showTransferModal.value = true;
}

function closeTransferModal() {
    showTransferModal.value = false;
    transferInventoryId.value = null;
    transferWarehouseId.value = null;
    transferQuantity.value = 1;
}

function openEditModal(inv: any) {
    editInventoryId.value = inv.id;
    editStock.value = Number(inv.stock || 0);
    editRegularPrice.value = inv.regular_price ?? null;
    editSellingPrice.value = inv.selling_price ?? null;
    editCostPrice.value = inv.cost_price ?? null;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editInventoryId.value = null;
    editStock.value = 0;
    editRegularPrice.value = null;
    editSellingPrice.value = null;
    editCostPrice.value = null;
}

async function saveInventoryEdits() {
    if (!editInventoryId.value) return;
    const inv = inventories.value.find(i => i.id === editInventoryId.value);
    if (!inv) return;
    const payload = {
        inventories: [
            {
                id: editInventoryId.value,
                stock: Number(editStock.value ?? inv.stock ?? 0),
                reserved_stock: Number(inv.reserved_stock ?? 0),
                damaged_stock: Number(inv.damaged_stock ?? 0),
                regular_price: editRegularPrice.value,
                selling_price: editSellingPrice.value,
                cost_price: editCostPrice.value,
            }
        ]
    };
    await router.post(route('admin.inventory.update', { warehouse: props.warehouse.hashid }), payload, {
        preserveState: true,
        onSuccess: () => {
            // update local row
            inv.stock = payload.inventories[0].stock;
            inv.regular_price = payload.inventories[0].regular_price;
            inv.selling_price = payload.inventories[0].selling_price;
            inv.cost_price = payload.inventories[0].cost_price;
            closeEditModal();
        }
    });
}

function transferStock() {
    if (!transferInventoryId.value || !transferWarehouseId.value || transferQuantity.value <= 0) return;
    if (transferQuantity.value > transferAvailableStock.value) {
        alert(`Quantity (${transferQuantity.value}) exceeds available stock (${transferAvailableStock.value}).`);
        return;
    }
    router.post(route('admin.inventory.transfer', { warehouse: props.warehouse.hashid }), {
        inventory_id: transferInventoryId.value,
        to_warehouse_id: transferWarehouseId.value,
        quantity: transferQuantity.value,
    }, {
        preserveState: true,
        onSuccess: () => {
            closeTransferModal();
            // Refresh to reflect new stock counts and summary
            router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
        }
    });
}

function goBack() {
    router.get(route('admin.warehouses.index'));
}

function openPublishModal(inv: any) {
    publishInventoryId.value = inv.id;
    publishAvailableStock.value = Number(inv.stock || 0);
    publishQuantity.value = Math.min(1, publishAvailableStock.value);
    publishAll.value = false;
    showPublishModal.value = true;
}

function closePublishModal() {
    showPublishModal.value = false;
    publishInventoryId.value = null;
    publishAvailableStock.value = 0;
    publishQuantity.value = 1;
    publishAll.value = false;
}

function confirmPublish() {
    if (!publishInventoryId.value) return;
    const qty = Number(publishQuantity.value || 0);
    if (!publishAll.value && (qty <= 0 || qty > publishAvailableStock.value)) {
        alert(`Quantity must be between 1 and ${publishAvailableStock.value}.`);
        return;
    }
    const payload: any = {};
    if (publishAll.value) {
        payload.all = true;
    } else {
        payload.quantity = qty;
    }
    router.post(route('admin.inventory.publish', { warehouse: props.warehouse.hashid, inventory: publishInventoryId.value }), payload, {
        preserveState: true,
        onSuccess: () => {
            closePublishModal();
            router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
        }
    });
}

</script>

<template>
    <Head :title="`Inventory — ${props.warehouse.name}`" />

    <AppLayout :breadcrumbs="[
        { title: 'Dashboard', href: route('admin.dashboard') },
        { title: 'Warehouses', href: route('admin.warehouses.index') },
        { title: props.warehouse.name, href: '#' }
    ]">
        <div class="p-4">
            <div class="mb-3 flex justify-end">
                <button @click="goBack" class="inline-flex items-center gap-2 text-sm bg-gray-700 text-gray-100 px-3 py-2 rounded-md hover:bg-gray-300 hover:text-gray-900">
                    Back to Warehouses
                </button>
            </div>
            <h1 class="text-2xl font-semibold mb-2">{{ props.warehouse.name }} Inventory</h1>

            <!-- Summary Stats -->
            <div v-if="props.summary" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">Total Variants</div>
                    <div class="text-lg font-semibold">{{ props.summary.total_variants }}</div>
                </div>
                <div class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">Total Units</div>
                    <div class="text-lg font-semibold">{{ props.summary.total_units }}</div>
                </div>
                <div class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">Capacity</div>
                    <div class="text-lg font-semibold">{{ props.summary.capacity ?? '—' }}</div>
                </div>
                <div class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">Remaining</div>
                    <div class="text-lg font-semibold">{{ props.summary.remaining_capacity }}</div>
                </div>
            </div>

            <!-- Filters + Add Product Button Row -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="filterInventories"
                        placeholder="Search product or variant..."
                        class="border rounded-md px-3 py-2 w-64"
                    />

                    <select v-model.number="perPage" @change="filterInventories" class="border rounded-md px-3 py-2">
                        <option value="10">10 per page</option>
                        <option value="20">20 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>

                    <button @click="filterInventories" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/80">
                        Filter
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="openAddModal" class="inline-flex items-center gap-2 bg-primary/80 text-white px-4 py-2 rounded-md hover:bg-orange-500">
                        <PlusIcon class="h-4 w-4 p-2" /> Add Product
                    </button>
                    <a :href="route('admin.receivables.index', { warehouse: props.warehouse.hashid })" class="inline-flex items-center gap-2 bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-primary/80">
                        Receivables
                    </a>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Variant</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Reserved</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Damaged</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Regular Price</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Selling Price</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Cost Price</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="inv in inventories" :key="inv.id">
                        <td class="px-4 py-2">{{ inv.product_name }}</td>
                        <td class="px-4 py-2">{{ inv.variant_name }}</td>
                        <td class="px-4 py-2 flex items-center gap-2">
                            <span class="font-semibold">{{ inv.stock }}</span>
                            <button @click="adjustStock(inv.id, 'increase')" class="text-green-600 hover:underline text-xs">+1</button>
                            <button @click="adjustStock(inv.id, 'decrease')" class="text-red-600 hover:underline text-xs">-1</button>
                            <button @click="adjustStock(inv.id, 'damaged')" class="text-yellow-600 hover:underline text-xs">Damaged</button>
                        </td>
                        <td class="px-4 py-2">{{ inv.reserved_stock }}</td>
                        <td class="px-4 py-2">{{ inv.damaged_stock }}</td>
                        <td class="px-4 py-2">KES {{ inv.regular_price?.toLocaleString?.() ?? (inv.regular_price ?? '—') }}</td>
                        <td class="px-4 py-2">KES {{ inv.selling_price?.toLocaleString?.() ?? (inv.selling_price ?? '—') }}</td>
                        <td class="px-4 py-2">KES {{ inv.cost_price?.toLocaleString?.() ?? (inv.cost_price ?? '—') }}</td>
                        <td class="px-4 py-2 flex gap-2">
                            <button @click="openTransferModal(inv)" class="text-blue-600 hover:underline text-xs">Transfer</button>
                            <button @click="openEditModal(inv)" class="text-indigo-600 hover:underline text-xs">Edit</button>
                            <button @click="openPublishModal(inv)" class="text-green-700 hover:underline text-xs">Publish</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

<!--            <div class="mt-4 flex gap-2">-->
<!--                <button @click="addInventoryRow" class="flex items-center gap-1 text-blue-600 hover:underline">-->
<!--                    <PlusIcon class="h-4 w-4" /> Add Row-->
<!--                </button>-->

<!--                <button @click="saveInventory" class="flex items-center gap-1 bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90">-->
<!--                    <SaveIcon class="h-4 w-4" /> Save Inventory-->
<!--                </button>-->
<!--            </div>-->

            <!-- Pagination -->
            <div v-if="props.pagination.meta.total > perPage" class="mt-4 flex justify-center items-center gap-1">
                <template v-for="link in props.pagination.links" :key="link.label">
                    <button
                        v-if="link.url"
                        @click.prevent="router.get(link.url, {}, { preserveState: true, replace: true })"
                        :class="['px-3 py-1 border rounded-md', { 'bg-primary text-white': link.active, 'hover:bg-gray-100': !link.active }]"
                        v-html="link.label"
                    ></button>
                    <span v-else class="px-3 py-1 border rounded-md text-gray-400" v-html="link.label"></span>
                </template>
            </div>
        </div>


        <!-- Add Product (Variant) Modal -->
        <div v-if="showAddModal">
            <div class="fixed inset-0 bg-black/50" @click="closeAddModal"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-2xl">
                    <h2 class="text-lg font-semibold mb-4">Add Product to Warehouse</h2>

                    <!-- Category Hierarchy Selects -->
                    <div class="mb-3">
                        <label class="block text-sm mb-1">Category</label>
                        <div class="flex flex-col gap-2">
                            <div v-for="(level, i) in categoryLevels" :key="i">
                                <select
                                    v-model.number="level.selectedId"
                                    @change="onCategoryChange(i)"
                                    class="border rounded-md px-3 py-2 w-full"
                                >
                                    <option :value="null" disabled>
                                        {{ i === 0 ? 'Select parent category' : 'Select child category' }}
                                    </option>
                                    <option v-for="c in level.options" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div v-if="loadingCategories" class="text-xs text-gray-500 mt-1">Loading categories...</div>
                    </div>

                    <!-- Product Select -->
                    <div class="mb-3">
                        <label class="block text-sm mb-1">Product</label>
                        <select
                            v-model.number="selectedProductId"
                            :disabled="!finalSelectedCategoryId"
                            @change="selectedVariantId = null; variants = []; fetchVariantsByProduct();"
                            class="border rounded-md px-3 py-2 w-full"
                        >
                            <option :value="null" disabled>Select product</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <div v-if="loadingProducts" class="text-xs text-gray-500 mt-1">Loading products...</div>
                    </div>

                    <!-- Variant List -->
                    <div class="border rounded-md max-h-64 overflow-y-auto mb-3">
                        <div v-if="loadingVariants" class="p-3 text-sm text-gray-500">Loading variants...</div>
                        <template v-else>
                            <div
                                v-for="v in variants"
                                :key="v.id"
                                class="flex items-center gap-3 p-3 border-b last:border-b-0 hover:bg-gray-50"
                            >
                                <input type="radio" :value="v.id" v-model="selectedVariantId" />
                                <div class="flex-1">
                                    <div class="font-medium">{{ v.display_name || 'Variant' }}</div>
                                    <div class="text-xs text-gray-500">{{ v.variant_values || 'Default' }}<span v-if="v.sku"> • SKU: {{ v.sku }}</span></div>
                                </div>
                            </div>
                            <div v-if="!variants.length && selectedProductId" class="p-3 text-sm text-gray-500">No variants found for this product.</div>
                            <div v-if="!selectedProductId" class="p-3 text-sm text-gray-500">Select a product to view variants.</div>
                        </template>
                    </div>

<!--                    <div class="flex items-center gap-3">-->
<!--                        <label class="text-sm">Quantity</label>-->
<!--                        <input type="number" min="1" v-model.number="addQuantity" class="border rounded-md px-3 py-2 w-28" />-->
<!--                    </div>-->

                    <div class="flex flex-col gap-3 mt-3">
                        <div class="flex items-center gap-3">
                            <label class="text-sm w-32">Quantity</label>
                            <input type="number" min="1" v-model.number="addQuantity" class="border rounded-md px-3 py-2 w-40" />
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="text-sm w-32">Regular Price</label>
                            <input type="number" step="0.01" v-model.number="regularPrice" class="border rounded-md px-3 py-2 w-40" />
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="text-sm w-32">Selling Price</label>
                            <input type="number" step="0.01" v-model.number="sellingPrice" class="border rounded-md px-3 py-2 w-40" />
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="text-sm w-32">Cost Price</label>
                            <input type="number" step="0.01" v-model.number="costPrice" class="border rounded-md px-3 py-2 w-40" />
                        </div>
                    </div>


                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="closeAddModal" class="px-4 py-2 border rounded-md">Cancel</button>
                        <button @click="submitAddVariant" class="px-4 py-2 bg-primary text-white rounded-md" :disabled="!selectedVariantId || addQuantity <= 0">Add</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showTransferModal">
            <div class="fixed inset-0 bg-black/50" @click="closeTransferModal"></div>
            <div class="fixed inset-0 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Transfer Stock</h2>
                    <div class="mb-2">
                        <label class="block mb-1">Target Warehouse</label>
                        <select v-model="transferWarehouseId" class="border rounded-md px-3 py-2 w-full">
                            <option v-for="w in props.warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1">Quantity</label>
                        <input type="number" min="1" :max="transferAvailableStock" v-model.number="transferQuantity" class="border rounded-md px-3 py-2 w-full" />
                        <div class="text-xs text-gray-500 mt-1">Available: {{ transferAvailableStock }}</div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="closeTransferModal" class="px-4 py-2 border rounded-md">Cancel</button>
                        <button @click="transferStock()" class="px-4 py-2 bg-primary text-white rounded-md">Transfer</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showPublishModal">
            <div class="fixed inset-0 bg-black/50" @click="closePublishModal"></div>
            <div class="fixed inset-0 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Publish to Variants</h2>
                    <div class="mb-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="publishAll" />
                            Publish all available stock
                        </label>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1">Quantity</label>
                        <input type="number" min="1" :max="publishAvailableStock" v-model.number="publishQuantity" :disabled="publishAll" class="border rounded-md px-3 py-2 w-full" />
                        <div class="text-xs text-gray-500 mt-1">Available: {{ publishAvailableStock }}</div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="closePublishModal" class="px-4 py-2 border rounded-md">Cancel</button>
                        <button @click="confirmPublish()" class="px-4 py-2 bg-primary text-white rounded-md">Publish</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showEditModal">
            <div class="fixed inset-0 bg-black/50" @click="closeEditModal"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Edit Inventory</h2>

                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="block mb-1 text-sm">Stock</label>
                            <input type="number" min="0" v-model.number="editStock" class="border rounded-md px-3 py-2 w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 text-sm">Regular Price</label>
                            <input type="number" min="0" step="0.01" v-model.number="editRegularPrice" class="border rounded-md px-3 py-2 w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 text-sm">Selling Price</label>
                            <input type="number" min="0" step="0.01" v-model.number="editSellingPrice" class="border rounded-md px-3 py-2 w-full" />
                        </div>
                        <div>
                            <label class="block mb-1 text-sm">Cost Price</label>
                            <input type="number" min="0" step="0.01" v-model.number="editCostPrice" class="border rounded-md px-3 py-2 w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="closeEditModal" class="px-4 py-2 border rounded-md">Cancel</button>
                        <button @click="saveInventoryEdits" class="px-4 py-2 bg-primary text-white rounded-md">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
