<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Category } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface CategoryWithHashid extends Category {
    hashid: string;
    parent_name?: string | null;
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

const page = usePage<AppPageProps<{ categories: PaginatedResponse<CategoryWithHashid> }>>();

const categories = computed(() => page.props.categories?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.categories || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Categories', href: route('admin.categories.index') },
];

function createCategory() {
    router.get(route('admin.categories.create'));
}

function editCategory(hashid: string) {
    if (!hashid) return console.error('editCategory called without hashid');
    router.get(route('admin.categories.edit', { category: hashid }));
}

function deleteCategory(hashid: string) {
    if (!hashid) return console.error('deleteCategory called without hashid');
    if (confirm('Are you sure you want to delete this category?')) {
        router.delete(route('admin.categories.destroy', { category: hashid }));
    }
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Categories</h1>
                    <button @click="createCategory" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Category</button>
                </div>

                <!-- Table -->
                <!-- Table -->
                <div v-if="categories.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(category, index) in categories" :key="category.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-800">{{ category.name }}</td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ category.parent_name ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editCategory(category.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteCategory(category.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No categories</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new category.</p>
                        <div class="mt-6">
                            <button
                                @click="createCategory"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
