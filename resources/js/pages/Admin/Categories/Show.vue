<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Category } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Subcategory {
    id: number;
    name: string;
    slug: string;
    hashid: string;
    active: boolean;
}

interface CategoryWithHashid extends Category {
    hashid: string;
    subcategories?: Subcategory[];
}

// Expect category prop with subcategories paginated or full list
const page = usePage<AppPageProps<{ category: CategoryWithHashid }>>();
const category = page.props.category;

const subcategories = computed(() => category.subcategories || []);

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Categories', href: route('admin.categories.index') },
    { title: category.name, href: '' },
];

function createSubcategory(categoryHashid: string) {
    if (!categoryHashid) return console.error('createSubcategory called without categoryHashid');
    router.get(route('admin.categories.subcategories.create', { category: categoryHashid }));
}

function editSubcategory(subcategoryHashid: string) {
    if (!subcategoryHashid) return console.error('editSubcategory called without subcategoryHashid');
    router.get(route('admin.categories.subcategories.edit', { category: category.hashid, subcategory: subcategoryHashid }));
}

function deleteSubcategory(subcategoryHashid: string) {
    if (!subcategoryHashid) return console.error('deleteSubcategory called without subcategoryHashid');
    if (confirm('Are you sure you want to delete this subcategory?')) {
        router.delete(route('admin.categories.subcategories.destroy', { category: category.hashid, subcategory: subcategoryHashid }));
    }
}
</script>

<template>
    <Head :title="`Subcategories for ${category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">{{ category.name }}</h1>
                    <button @click="createSubcategory(category.hashid)" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">
                        + New Subcategory
                    </button>
                </div>

                <!-- Table -->
                <div v-if="subcategories.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(sub, index) in subcategories" :key="sub.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 align-top text-sm text-gray-500">{{ index + 1 }}</td>
                                <td
                                    @click="editSubcategory(sub.hashid)"
                                    class="cursor-pointer px-4 py-4 align-top text-sm font-medium text-primary hover:underline"
                                >
                                    {{ sub.name }}
                                </td>
                                <td class="px-4 py-4 text-right align-top text-sm">
                                    <button @click.stop="editSubcategory(sub.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteSubcategory(sub.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No subcategories</h3>
                        <p class="mt-1 text-sm text-gray-500">Start by creating a new subcategory.</p>
                        <div class="mt-6">
                            <button
                                @click="createSubcategory(category.hashid)"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Subcategory
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
