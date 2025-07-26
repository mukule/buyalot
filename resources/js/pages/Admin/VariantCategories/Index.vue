<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, VariantCategory } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface VariantCategoryWithHashid extends VariantCategory {
    hashid: string;
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

const page = usePage<AppPageProps<{ variantCategories: PaginatedResponse<VariantCategoryWithHashid> }>>();

const variantCategories = computed(() => {
    const data = page.props.variantCategories?.data || [];
    data.forEach((cat) => {
        if (!cat.hashid) {
            console.warn('Missing hashid for variant category:', cat);
        }
    });
    return data;
});

const pagination = computed(() => {
    const { links, meta } = page.props.variantCategories || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Variant Categories', href: route('admin.variant-categories.index') },
];

function createVariantCategory() {
    router.get(route('admin.variant-categories.create'));
}

function showVariantCategory(hashid: string) {
    router.get(route('admin.variant-categories.show', { variant_category: hashid }));
}

function editVariantCategory(hashid: string) {
    router.get(route('admin.variant-categories.edit', { variant_category: hashid }));
}

function deleteVariantCategory(hashid: string) {
    if (confirm('Are you sure you want to delete this variant category?')) {
        router.delete(route('admin.variant-categories.destroy', { variant_category: hashid }));
    }
}
</script>

<template>
    <Head title="Variant Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Variant Categories</h1>
                    <button @click="createVariantCategory" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">
                        <PlusIcon class="mr-2 inline-block h-5 w-5" /> New Category
                    </button>
                </div>

                <!-- Table -->
                <div v-if="variantCategories.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(category, index) in variantCategories" :key="category.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td
                                    @click="showVariantCategory(category.hashid)"
                                    class="cursor-pointer px-4 py-4 text-sm font-medium text-primary hover:underline"
                                >
                                    {{ category.name }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editVariantCategory(category.hashid)" class="mr-3 text-blue-600 hover:underline">
                                        Edit
                                    </button>
                                    <button @click.stop="deleteVariantCategory(category.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ pagination.meta?.from }}</span>
                                to
                                <span class="font-medium">{{ pagination.meta?.to }}</span>
                                of
                                <span class="font-medium">{{ pagination.meta?.total }}</span>
                                results
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
                                        <span v-else>{{ link.label }}</span>
                                    </a>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No variant categories</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new variant category.</p>
                        <div class="mt-6">
                            <button
                                @click="createVariantCategory"
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
