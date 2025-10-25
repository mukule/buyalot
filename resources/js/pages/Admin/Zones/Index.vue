<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Zone {
    id: number;
    name: string;
    tier: number;
    is_default_origin: boolean;
    created_at: string;
    updated_at: string;
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

const page = usePage<AppPageProps<{ zones: PaginatedResponse<Zone>; title: string; basePath: string }>>();
const zones = computed(() => page.props.zones?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.zones || {};
    return { links, meta };
});

const title = computed(() => page.props.title || 'Zones');
const basePath = computed(() => page.props.basePath || '/admin/zones');

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: title.value, href: basePath.value },
];

function createZone() {
    router.get(`${basePath.value}/create`);
}

function viewZone(id: number) {
    if (!id) return console.error('viewZone called without id');
    router.get(`${basePath.value}/${id}`);
}

function editZone(id: number) {
    if (!id) return console.error('editZone called without id');
    router.get(`${basePath.value}/${id}/edit`);
}

function deleteZone(id: number) {
    if (!id) return console.error('deleteZone called without id');
    if (confirm('Are you sure you want to delete this zone?')) {
        router.delete(`${basePath.value}/${id}`);
    }
}
</script>

<template>
    <Head :title="title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">{{ title }}</h1>
                    <button @click="createZone" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Zone</button>
                </div>

                <!-- Zones Table -->
                <div v-if="zones.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default Origin</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(zone, index) in zones" :key="zone.id" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>

                                <!-- Name -->
                                <td class="px-4 py-4 text-sm font-medium text-primary">
                                    <button @click.stop="viewZone(zone.id)" class="hover:underline">
                                        {{ zone.name }}
                                    </button>
                                </td>

                                <!-- Tier -->
                                <td class="px-4 py-4 text-sm text-gray-600">{{ zone.tier }}</td>

                                <!-- Default Origin -->
                                <td class="px-4 py-4 text-sm">
                                    <span :class="zone.is_default_origin ? 'font-semibold text-green-600' : 'text-gray-500'">
                                        {{ zone.is_default_origin ? 'Yes' : 'No' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="flex justify-end gap-3 px-4 py-4 text-right text-sm">
                                    <button @click.stop="editZone(zone.id)" class="text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteZone(zone.id)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No zones found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new zone.</p>
                        <div class="mt-6">
                            <button
                                @click="createZone"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Zone
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
