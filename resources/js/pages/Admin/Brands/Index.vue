<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Brand } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface BrandWithExtras extends Brand {
    hashid: string;
    logo_url?: string;
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

const page = usePage<AppPageProps<{ brands: PaginatedResponse<BrandWithExtras> }>>();
const brands = computed(() => page.props.brands?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.brands || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Brands', href: route('admin.brands.index') },
];

function createBrand() {
    router.get(route('admin.brands.create'));
}

function editBrand(hashid: string) {
    if (!hashid) return console.error('editBrand called without hashid');
    router.get(route('admin.brands.edit', { brand: hashid }));
}

function deleteBrand(hashid: string) {
    if (!hashid) return console.error('deleteBrand called without hashid');
    if (confirm('Are you sure you want to delete this brand?')) {
        router.delete(route('admin.brands.destroy', { brand: hashid }));
    }
}

const statusClasses = (active: boolean) => ({
    'text-green-600': active,
    'text-red-600': !active,
});
</script>

<template>
    <Head title="Brands" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Brands</h1>
                    <button @click="createBrand" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Brand</button>
                </div>

                <!-- Brand Table -->
                <div v-if="brands.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(brand, index) in brands" :key="brand.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="flex items-center space-x-3 px-4 py-4 text-sm font-medium text-primary">
                                    <img v-if="brand.logo_url" :src="brand.logo_url" alt="Logo" class="h-10 w-10 rounded bg-white object-contain" />
                                    <span>{{ brand.name }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span :class="statusClasses(brand.active)">
                                        {{ brand.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editBrand(brand.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteBrand(brand.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No brands</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new brand.</p>
                        <div class="mt-6">
                            <button
                                @click="createBrand"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Brand
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
