<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

interface DiscountType {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    is_active: boolean;
}

// Reactive state
const discountTypes = ref<{
    data: DiscountType[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}>({ data: [], current_page: 1, last_page: 1, links: [] });

const search = ref('');

// Fetch data helper
const fetchDiscountTypes = (params = {}) => {
    router.get(route('admin.discount-types.index'), { search: search.value, ...params }, {
        preserveState: true,
        onSuccess: page => {
            discountTypes.value = (page.props as any).discountTypes;
        }
    });
};

// Initial fetch
fetchDiscountTypes();

// Watch search for auto-update
watch(search, (val, oldVal) => {
    fetchDiscountTypes({ page: 1 });
});

// ----- ADD / EDIT MODAL -----
const addModalOpen = ref(false);
const editModalOpen = ref(false);

const selectedDiscountType = reactive<Partial<DiscountType>>({
    id: null,
    name: '',
    code: '',
    description: ''
});

const openAddModal = () => {
    selectedDiscountType.id = null;
    selectedDiscountType.name = '';
    selectedDiscountType.code = '';
    selectedDiscountType.description = '';
    addModalOpen.value = true;
};

const openEditModal = (discountType: DiscountType) => {
    selectedDiscountType.id = discountType.id;
    selectedDiscountType.name = discountType.name;
    selectedDiscountType.code = discountType.code;
    selectedDiscountType.description = discountType.description;
    editModalOpen.value = true;
};

// Submit add
const addDiscountType = () => {
    router.post(route('admin.discount-types.store'), selectedDiscountType, {
        onSuccess: () => {
            addModalOpen.value = false;
            fetchDiscountTypes({ page: discountTypes.value.current_page });
        }
    });
};

// Update
const updateDiscountType = () => {
    if (!selectedDiscountType.id) return;
    router.put(route('admin.discount-types.update', { discount_type: selectedDiscountType.id }), {
        name: selectedDiscountType.name,
        code: selectedDiscountType.code,
        description: selectedDiscountType.description,
    }, {
        onSuccess: () => {
            editModalOpen.value = false;
            fetchDiscountTypes({ page: discountTypes.value.current_page });
        }
    });
};

// Toggle status
const toggleStatus = (item: DiscountType) => {
    router.patch(route('admin.discount-types.toggle', { discount_type: item.id }), {}, {
        preserveScroll: true,
        onSuccess: () => fetchDiscountTypes({ page: discountTypes.value.current_page })
    });
};

// Delete item
const deleteItem = (item: DiscountType) => {
    if (!confirm('Are you sure you want to delete this discount type?')) return;
    router.delete(route('admin.discount-types.destroy', { discount_type: item.id }), {
        onSuccess: () => fetchDiscountTypes({ page: discountTypes.value.current_page })
    });
};
</script>


<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Discount Types</h1>
                    <button @click="openAddModal" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">
                        Add Discount Type
                    </button>
                </div>

                <div class="mb-4 flex gap-2">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or code"
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                    />
                    <button @click="onSearch" class="rounded bg-gray-800 px-4 py-2 text-white hover:bg-black">Search</button>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="d in discountTypes.data" :key="d.id">
                            <td class="px-4 py-2">{{ d.name }}</td>
                            <td class="px-4 py-2">{{ d.code }}</td>
                            <td class="px-4 py-2">
                                    <span :class="d.is_active ? 'text-green-600' : 'text-gray-500'">
                                        {{ d.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                            </td>
                            <td class="px-4 py-2">{{ d.description || '-' }}</td>
                            <td class="px-4 py-2 text-right">
                                <button @click="toggleStatus(d)" class="mr-2 rounded bg-yellow-500 px-3 py-1 text-white hover:bg-yellow-600">
                                    {{ d.is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button @click="openEditModal(d)" class="mr-2 rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">
                                    Edit
                                </button>
                                <button @click="deleteItem(d)" class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <template v-for="(lnk, idx) in discountTypes.links" :key="idx">
                        <button
                            v-if="lnk.url"
                            @click="router.visit(lnk.url, { preserveState: true, preserveScroll: true })"
                            class="rounded px-3 py-1 text-sm"
                            :class="lnk.active ? 'bg-primary text-white' : 'border bg-white text-gray-700'"
                            v-html="lnk.label"
                        />
                        <span v-else class="rounded border bg-gray-100 px-3 py-1 text-sm text-gray-400" v-html="lnk.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- ADD / EDIT MODAL -->
        <div
            v-if="addModalOpen || editModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div class="w-full max-w-md rounded bg-white p-6 shadow-lg">
                <h2 class="text-lg font-semibold mb-4">
                    {{ addModalOpen ? 'Add Discount Type' : 'Edit Discount Type' }}
                </h2>

                <div class="grid gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input v-model="selectedDiscountType.name" type="text" class="w-full rounded border px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Code</label>
                        <input v-model="selectedDiscountType.code" type="text" class="w-full rounded border px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Description</label>
                        <textarea v-model="selectedDiscountType.description" class="w-full rounded border px-3 py-2"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        @click="addModalOpen = false; editModalOpen = false"
                        class="px-4 py-2 rounded border"
                    >
                        Cancel
                    </button>
                    <button
                        @click="addModalOpen ? addDiscountType() : updateDiscountType()"
                        class="px-4 py-2 rounded bg-primary text-white"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
