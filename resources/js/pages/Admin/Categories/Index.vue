<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Category } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon, RotateCcwIcon } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

interface CategoryWithHashid extends Category {
    hashid: string;
    id: number;
    parent_name?: string | null;
    deleted_at?: string | null;
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

const page = usePage<AppPageProps<{ categories: PaginatedResponse<CategoryWithHashid>; filters: any }>>();

const categories = computed(() => page.props.categories?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.categories || {};
    return { links: links || [], meta: meta || {} };
});

const filters = reactive({
    name: page.props.filters.name || '',
    active: page.props.filters.active ?? '',
    with_deleted: page.props.filters.with_deleted ?? false,
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Categories', href: route('admin.categories.index') },
];

function createCategory() {
    router.get(route('admin.categories.create'));
}

function editCategory(hashid: string) {
    router.get(route('admin.categories.edit', { category: hashid }));
}

function deleteCategory(hashid: string) {
    if (confirm('This will delete the category and ALL its children. Continue?')) {
        router.delete(route('admin.categories.destroy', { category: hashid }));
    }
}

function restoreCategory(id: number) {
    if (confirm('Restore this category and all its Related Subcategories?')) {
        router.post(route('admin.categories.restore', { id }));
    }
}

function forceDeleteCategory(hashid: string) {
    if (confirm('This will permanently delete the category and ALL its subcategories. This cannot be undone. Continue?')) {
        router.delete(route('admin.categories.force-destroy', { category: hashid }));
    }
}

function applyFilters() {
    router.get(route('admin.categories.index'), filters, { preserveState: true });
}

function goToPage(url: string | null) {
    if (!url) return;
    router.get(url);
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header + Filters -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Categories</h1>
                    <button @click="createCategory" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Category</button>
                </div>

                <!-- Filters -->
                <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
                    <input
                        type="text"
                        v-model="filters.name"
                        @input="applyFilters"
                        placeholder="Search by name"
                        class="w-full rounded border px-3 py-2 md:w-64"
                    />

                    <select v-model="filters.active" @change="applyFilters" class="rounded border px-3 py-2">
                        <option value="">All</option>
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </select>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" v-model="filters.with_deleted" @change="applyFilters" />
                        Include Deleted
                    </label>
                </div>

                <!-- Table -->
                <div v-if="categories.length" class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="(category, index) in categories"
                                :key="category.hashid"
                                :class="['hover:bg-gray-50', category.deleted_at ? 'bg-gray-100 opacity-50' : '']"
                            >
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    {{ index + 1 + ((pagination.meta.current_page || 1) - 1) * (pagination.meta.per_page || categories.length || 1) }}
                                </td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-800">
                                    {{ category.name }}
                                    <span v-if="category.deleted_at" class="ml-2 rounded bg-red-100 px-2 py-0.5 text-xs text-red-600"> Deleted </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ category.parent_name ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <template v-if="!category.deleted_at">
                                        <button @click="editCategory(category.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                        <button @click="deleteCategory(category.hashid)" class="mr-3 text-orange-600 hover:underline">Del</button>
                                        <button @click="forceDeleteCategory(category.hashid)" class="text-red-600 hover:underline">
                                            Del Permanently
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            @click="restoreCategory(category.id)"
                                            class="inline-flex items-center gap-1 text-green-600 hover:underline"
                                        >
                                            <RotateCcwIcon class="h-4 w-4" />
                                            Restore
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-gray-600">Page {{ pagination.meta.current_page }} of {{ pagination.meta.last_page }}</div>

                        <div class="flex space-x-1">
                            <button
                                v-for="link in pagination.links"
                                :key="link.label"
                                :disabled="!link.url"
                                @click="goToPage(link.url)"
                                class="rounded border border-gray-300 px-3 py-1 hover:bg-gray-100 disabled:opacity-50"
                                :class="{ 'bg-gray-200 font-semibold': link.active }"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center">
                    <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No categories</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new category.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
