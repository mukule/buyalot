<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Category } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

// Child category interface
interface ChildCategory {
    id: number;
    name: string;
    slug: string;
    hashid: string;
    active: boolean;
}

// Category with children & optional breadcrumb
interface CategoryWithHashid extends Category {
    hashid: string;
    children?: ChildCategory[];
    parent?: CategoryWithHashid | null;
    breadcrumb?: { name: string; hashid: string }[];
}

// Page props
const page = usePage<AppPageProps<{ category: CategoryWithHashid; children: ChildCategory[] }>>();
const category = page.props.category;
const children = computed(() => page.props.children || []);

// Breadcrumbs using the BreadcrumbItem interface
const breadcrumbs = computed(() => {
    const crumbs = [{ title: 'Dashboard', href: route('admin.dashboard') }];
    if (category.breadcrumb && category.breadcrumb.length) {
        category.breadcrumb.forEach((c: { name: string; hashid: string }) => {
            crumbs.push({ title: c.name, href: route('admin.categories.show', { category: c.hashid }) });
        });
    }
    // Add current category
    crumbs.push({ title: category.name, href: '' });
    return crumbs;
});

// Actions
function createChild(categoryHashid: string) {
    router.get(route('admin.categories.create', { parent: categoryHashid }));
}

function editChild(childHashid: string) {
    router.get(route('admin.categories.edit', { category: childHashid }));
}

function deleteChild(childHashid: string) {
    if (confirm('Are you sure you want to delete this category?')) {
        router.delete(route('admin.categories.destroy', { category: childHashid }));
    }
}

function showCategory(categoryHashid: string) {
    router.get(route('admin.categories.show', { category: categoryHashid }));
}
</script>

<template>
    <Head :title="`Children of ${category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">{{ category.name }}</h1>
                        <p v-if="category.parent" class="text-sm text-gray-500">Parent: {{ category.parent.name }}</p>
                        <p v-else class="text-sm text-gray-500">Top-level category</p>
                    </div>
                    <button @click="createChild(category.hashid)" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">
                        + New Child Category
                    </button>
                </div>

                <!-- Children Table -->
                <div v-if="children.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(child, index) in children" :key="child.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 align-top text-sm text-gray-500">{{ index + 1 }}</td>
                                <td
                                    @click="showCategory(child.hashid)"
                                    class="cursor-pointer px-4 py-4 align-top text-sm font-medium text-primary hover:underline"
                                >
                                    {{ child.name }}
                                </td>
                                <td class="px-4 py-4 text-right align-top text-sm">
                                    <button @click.stop="editChild(child.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteChild(child.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No categories here</h3>
                        <p class="mt-1 text-sm text-gray-500">Start by creating a new category for this.</p>
                        <div class="mt-6">
                            <button
                                @click="createChild(category.hashid)"
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
