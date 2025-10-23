<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';

import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Region {
    hashid: string;
    name: string;
    active: boolean;
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

const page = usePage<AppPageProps<{ regions: PaginatedResponse<Region> }>>();
const regions = computed(() => page.props.regions?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.regions || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Regions', href: route('admin.regions.index') },
];

function createRegion() {
    router.get(route('admin.regions.create'));
}

function editRegion(hashid: string) {
    if (!hashid) return console.error('editRegion called without hashid');
    router.get(route('admin.regions.edit', { region: hashid }));
}

function deleteRegion(hashid: string) {
    if (!hashid) return console.error('deleteRegion called without hashid');
    if (confirm('Are you sure you want to delete this region?')) {
        router.delete(route('admin.regions.destroy', { region: hashid }));
    }
}

const statusClasses = (active: boolean) => ({
    'text-green-600': active,
    'text-red-600': !active,
});
</script>

<template>
    <Head title="Regions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Regions</h1>
                    <button @click="createRegion" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Region</button>
                </div>

                <!-- Regions Table -->
                <div v-if="regions.length" class="overflow-x-auto">
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
                            <tr v-for="(region, index) in regions" :key="region.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-primary">
                                    {{ region.name }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span :class="statusClasses(region.active)">
                                        {{ region.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editRegion(region.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteRegion(region.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No regions</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new region.</p>
                        <div class="mt-6">
                            <button
                                @click="createRegion"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Region
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
