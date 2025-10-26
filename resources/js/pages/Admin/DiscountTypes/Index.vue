<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { Pencil, Trash } from 'lucide-vue-next'; // icons
import { reactive, ref, watch } from 'vue';

interface DiscountType {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    is_active: boolean | number;
}

// ----- Breadcrumbs -----
interface BreadcrumbItem {
    title: string;
    href: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Discounts', href: '/admin/discount-types' },
    { title: 'Types', href: '' },
];

// ----- State -----
const discountTypes = ref<{
    data: DiscountType[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}>({ data: [], current_page: 1, last_page: 1, links: [] });

const search = ref('');

// ----- Fetch -----
const fetchDiscountTypes = (params = {}) => {
    router.get(
        route('admin.discount-types.index'),
        { search: search.value, ...params },
        {
            preserveState: true,
            onSuccess: (page) => {
                discountTypes.value = (page.props as any).discountTypes;
            },
        },
    );
};

// initial load
fetchDiscountTypes();

// watch search
watch(search, () => {
    fetchDiscountTypes({ page: 1 });
});

// ----- Modals -----
const addModalOpen = ref(false);
const editModalOpen = ref(false);

const selectedDiscountType = reactive<Partial<DiscountType>>({
    id: undefined,
    name: '',
    code: '',
    description: '',
    is_active: true,
});

const openAddModal = () => {
    Object.assign(selectedDiscountType, {
        id: undefined,
        name: '',
        code: '',
        description: '',
        is_active: true,
    });
    addModalOpen.value = true;
};

const openEditModal = (discountType: DiscountType) => {
    Object.assign(selectedDiscountType, {
        id: discountType.id,
        name: discountType.name,
        code: discountType.code,
        description: discountType.description,
        is_active: !!discountType.is_active, // ensure boolean
    });
    editModalOpen.value = true;
};

// ----- Add -----
const addDiscountType = () => {
    router.post(
        route('admin.discount-types.store'),
        {
            ...selectedDiscountType,
            is_active: selectedDiscountType.is_active ? 1 : 0,
        },
        {
            onSuccess: () => {
                addModalOpen.value = false;
                fetchDiscountTypes({ page: discountTypes.value.current_page });
            },
        },
    );
};

// ----- Update -----
const updateDiscountType = () => {
    if (!selectedDiscountType.id) return;
    router.put(
        route('admin.discount-types.update', { discount_type: selectedDiscountType.id }),
        {
            name: selectedDiscountType.name,
            code: selectedDiscountType.code,
            description: selectedDiscountType.description,
            is_active: selectedDiscountType.is_active ? 1 : 0,
        },
        {
            onSuccess: () => {
                editModalOpen.value = false;
                fetchDiscountTypes({ page: discountTypes.value.current_page });
            },
        },
    );
};

// ----- Delete -----
const deleteItem = (item: DiscountType) => {
    if (!confirm('Are you sure you want to delete this discount type?')) return;
    router.delete(route('admin.discount-types.destroy', { discount_type: item.id }), {
        onSuccess: () => fetchDiscountTypes({ page: discountTypes.value.current_page }),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Discount Types</h1>
                    <button @click="openAddModal" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">Add Discount Type</button>
                </div>

                <div class="mb-4 flex gap-2">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or code"
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                    />
                    <button @click="fetchDiscountTypes({ page: 1 })" class="rounded bg-gray-800 px-4 py-2 text-white hover:bg-black">Search</button>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="d in discountTypes.data" :key="d.id">
                                <td class="px-4 py-2">{{ d.name }}</td>
                                <td class="px-4 py-2">{{ d.code }}</td>
                                <td class="px-4 py-2">{{ d.description || '-' }}</td>
                                <td class="px-4 py-2">
                                    <span :class="d.is_active ? 'text-green-600' : 'text-gray-500'">
                                        {{ d.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="flex justify-end gap-2 px-4 py-2 text-right">
                                    <button
                                        @click="openEditModal(d)"
                                        class="rounded border border-blue-500 p-2 text-blue-500 hover:bg-blue-50"
                                        title="Edit"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteItem(d)"
                                        class="rounded border border-red-500 p-2 text-red-500 hover:bg-red-50"
                                        title="Delete"
                                    >
                                        <Trash class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
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
        <div v-if="addModalOpen || editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded bg-white p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold">
                    {{ addModalOpen ? 'Add Discount Type' : 'Edit Discount Type' }}
                </h2>

                <div class="mb-4 grid gap-4">
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

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="selectedDiscountType.is_active"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        @click="
                            addModalOpen = false;
                            editModalOpen = false;
                        "
                        class="rounded border px-4 py-2"
                    >
                        Cancel
                    </button>
                    <button @click="addModalOpen ? addDiscountType() : updateDiscountType()" class="rounded bg-primary px-4 py-2 text-white">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
