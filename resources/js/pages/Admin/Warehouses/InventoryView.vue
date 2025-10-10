<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PlusIcon, SaveIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next';

const props = defineProps<{
    warehouse: { id: number; name: string; code: string; capacity?: number };
    inventories: any[];
    pagination: any;
    filters: { search: string; per_page: number };
}>();

const inventories = ref([...props.inventories]);
const search = ref(props.filters.search);
const perPage = ref(props.filters.per_page);

const showTransferModal = ref(false);
const transferInventoryId = ref<number | null>(null);
const transferWarehouseId = ref<number | null>(null);
const transferQuantity = ref<number>(1);

// Methods
function addInventoryRow() {
    inventories.value.push({
        id: 0,
        product_name: '',
        variant_name: '',
        stock: 0,
        reserved_stock: 0,
        damaged_stock: 0,
        cost_price: 0,
    });
}

function saveInventory() {
    router.post(route('admin.warehouses.inventory.update', { warehouse: props.warehouse.id }), {
        inventories: inventories.value,
    });
}

function filterInventories() {
    router.get(route('admin.warehouses.inventory', { warehouse: props.warehouse.id }), {
        search: search.value,
        per_page: perPage.value,
    }, { preserveState: true, replace: true });
}

function adjustStock(inventoryId: number, type: 'increase' | 'decrease' | 'damaged') {
    const quantity = 1;
    router.post(route('admin.warehouses.inventory.adjust', { warehouse: props.warehouse.id }), {
        inventory_id: inventoryId,
        type,
        quantity
    }, { preserveState: true });
}


function openTransferModal(inv: any) {
    transferInventoryId.value = inv.id;
    showTransferModal.value = true;
}

function closeTransferModal() {
    showTransferModal.value = false;
    transferInventoryId.value = null;
    transferWarehouseId.value = null;
    transferQuantity.value = 1;
}

function transferStock() {
    if (!transferInventoryId.value || !transferWarehouseId.value || transferQuantity.value <= 0) return;
    router.post(route('admin.warehouses.inventory.transfer', { warehouse: props.warehouse.id }), {
        inventory_id: transferInventoryId.value,
        to_warehouse_id: transferWarehouseId.value,
        quantity: transferQuantity.value,
    }, { preserveState: true, onSuccess: closeTransferModal });
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
            <h1 class="text-2xl font-semibold mb-4">{{ props.warehouse.name }} Inventory</h1>

            <!-- Filters -->
            <div class="flex gap-4 mb-4 items-center">
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

                <button @click="filterInventories" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90">
                    Filter
                </button>
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
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Cost Price</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-for="(inv, index) in inventories" :key="inv.id">
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
                        <td class="px-4 py-2">KES {{ inv.cost_price?.toLocaleString() || '—' }}</td>
                        <td class="px-4 py-2 flex gap-2">
                            <button @click="openTransferModal(inv)" class="text-blue-600 hover:underline text-xs">Transfer</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex gap-2">
                <button @click="addInventoryRow" class="flex items-center gap-1 text-blue-600 hover:underline">
                    <PlusIcon class="h-4 w-4" /> Add Row
                </button>

                <button @click="saveInventory" class="flex items-center gap-1 bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90">
                    <SaveIcon class="h-4 w-4" /> Save Inventory
                </button>
            </div>

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


        <div v-if="showTransferModal">
            <div class="fixed inset-0 bg-black/50" @click="closeTransferModal"></div>
            <div class="fixed inset-0 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Transfer Stock</h2>
                    <div class="mb-2">
                        <label class="block mb-1">Target Warehouse</label>
                        <select v-model="transferWarehouseId" class="border rounded-md px-3 py-2 w-full">
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="block mb-1">Quantity</label>
                        <input type="number" v-model.number="transferQuantity" class="border rounded-md px-3 py-2 w-full" />
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button @click="closeTransferModal" class="px-4 py-2 border rounded-md">Cancel</button>
                        <button @click="transferStock()" class="px-4 py-2 bg-primary text-white rounded-md">Transfer</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
