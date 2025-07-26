<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, UnitType } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Unit {
    id: number;
    name: string;
    symbol: string;
    active: boolean;
    hashid: string;
}

interface UnitTypeWithUnits extends UnitType {
    hashid: string;
    units?: Unit[];
}

const page = usePage<AppPageProps<{ unitType: UnitTypeWithUnits }>>();
const unitType = page.props.unitType;

const units = computed(() => unitType.units || []);

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Unit Types', href: route('admin.unit-types.index') },
    { title: unitType.name, href: '' },
];

function createUnit(unitTypeHashid: string) {
    if (!unitTypeHashid) return console.error('createUnit called without unitTypeHashid');
    router.get(route('admin.unit-types.units.create', { unit_type: unitTypeHashid }));
}

function editUnit(unitHashid: string) {
    if (!unitHashid) return console.error('editUnit called without unitHashid');
    router.get(route('admin.unit-types.units.edit', { unit_type: unitType.hashid, unit: unitHashid }));
}

function deleteUnit(unitHashid: string) {
    if (!unitHashid) return console.error('deleteUnit called without unitHashid');
    if (confirm('Are you sure you want to delete this unit?')) {
        router.delete(route('admin.unit-types.units.destroy', { unit_type: unitType.hashid, unit: unitHashid }));
    }
}
</script>

<template>
    <Head :title="`Units for ${unitType.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">{{ unitType.name }}</h1>
                    <button @click="createUnit(unitType.hashid)" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">
                        + New Unit
                    </button>
                </div>

                <!-- Table -->
                <div v-if="units.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(unit, index) in units" :key="unit.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-700">{{ unit.name }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ unit.symbol }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span :class="{ 'text-green-600': unit.active, 'text-red-600': !unit.active }">
                                        {{ unit.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editUnit(unit.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteUnit(unit.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No units</h3>
                        <p class="mt-1 text-sm text-gray-500">Start by creating a new unit under this unit type.</p>
                        <div class="mt-6">
                            <button
                                @click="createUnit(unitType.hashid)"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Unit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
