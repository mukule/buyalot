<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

interface Region {
    id: number;
    hashid: string;
    name: string;
    active: boolean | number;
    parent_id?: number | null;
}

interface PickupPoint {
    id: number;
    name: string;
    address: string;
    contact_phone?: string;
    contact_email?: string;
    active: boolean | number;
    region: { name: string };
}

interface CustomProps {
    region: Region;
    pickupPoints: PickupPoint[];
    title: string;
}

const page = usePage<InertiaPageProps & CustomProps>();
const region = page.props.region;
const pickupPoints = page.props.pickupPoints;
const title = page.props.title || `${region.name} Pickup Points`;

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Regions', href: '/admin/regions' },
    { title: region.name, href: '' },
];

function addPickupPoint() {
    router.get(route('admin.regions.pickup-points.create', { region: region.hashid }));
}

function editPickupPoint(pickupId: number) {
    // Use pickup_point as per Laravel route parameter name
    router.get(route('admin.regions.pickup-points.edit', { region: region.hashid, pickup_point: pickupId }));
}
</script>

<template>
    <Head :title="title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card rounded-lg bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold">{{ title }}</h2>
                    <div class="flex items-center space-x-4">
                        <Link href="/admin/regions" class="cursor-pointer text-sm text-[color:var(--primary)] hover:underline"> ← Back </Link>
                        <button @click="addPickupPoint" class="hover:bg-primary-dark cursor-pointer rounded bg-primary px-3 py-1 text-white">
                            + Add Pickup Point
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Region</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(pp, index) in pickupPoints" :key="pp.id" class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-primary">{{ pp.name }}</td>
                                <td class="px-4 py-2 text-sm">{{ pp.address }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <div v-if="pp.contact_phone">{{ pp.contact_phone }}</div>
                                    <div v-if="pp.contact_email">{{ pp.contact_email }}</div>
                                </td>
                                <td class="px-4 py-2 text-sm">{{ pp.region.name }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span :class="pp.active == 1 ? 'text-green-600' : 'text-red-600'">
                                        {{ pp.active == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right text-sm">
                                    <button @click.stop="editPickupPoint(pp.id)" class="mr-3 cursor-pointer text-blue-600 hover:underline">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!pickupPoints.length">
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No pickup points found for this region.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
