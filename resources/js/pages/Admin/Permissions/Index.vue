<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Permission } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage<
    AppPageProps<{
        permissions: (Permission & { hashid: string })[];
        grouped_permissions: Record<string, (Permission & { hashid: string })[]>;
        modules: string[];
        filters: {
            module?: string;
            search?: string;
        };
    }>
>();

const permissions = computed(() => page.props.permissions);
const groupedPermissions = computed(() => page.props.grouped_permissions);
const modules = computed(() => page.props.modules);
const filters = computed(() => page.props.filters);

const searchForm = useForm({
    search: filters.value.search || '',
    module: filters.value.module || '',
});

const selectedPermissions = ref<string[]>([]);
const showBulkActions = computed(() => selectedPermissions.value.length > 0);
const showGrouped = ref(true);
const expandedModules = ref<Record<string, boolean>>({});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Access Rights', href: '#' },
    { title: 'Permissions', href: '/admin/permissions' },
];

// Initialize expanded modules
Object.keys(groupedPermissions.value).forEach((module) => {
    expandedModules.value[module] = true;
});

function goToCreatePage() {
    router.get(route('admin.permissions.create'));
}

function goToPermissionShow(hashid: string) {
    router.get(route('admin.permissions.show', hashid));
}

function goToPermissionEdit(hashid: string) {
    router.get(route('admin.permissions.edit', hashid));
}

function deletePermission(hashid: string) {
    if (confirm('Are you sure you want to delete this permission?')) {
        router.delete(route('admin.permissions.destroy', hashid));
    }
}

function bulkDeletePermissions() {
    if (confirm(`Are you sure you want to delete ${selectedPermissions.value.length} permissions?`)) {
        router.post('/admin/permissions/bulk-delete', {
            permissions: selectedPermissions.value,
        });
        selectedPermissions.value = [];
    }
}

function toggleSelectAll() {
    if (selectedPermissions.value.length === permissions.value.length) {
        selectedPermissions.value = [];
    } else {
        selectedPermissions.value = permissions.value.map((p) => p.id.toString());
    }
}

function search() {
    router.get(
        route('admin.permissions.index'),
        {
            search: searchForm.search,
            module: searchForm.module,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function clearFilters() {
    searchForm.search = '';
    searchForm.module = '';
    search();
}

function toggleModule(module: string) {
    expandedModules.value[module] = !expandedModules.value[module];
}

// Watch for changes in search form
watch(
    [() => searchForm.search, () => searchForm.module],
    () => {
        search();
    },
    { debounce: 300 },
);
</script>

<template>
    <Head title="Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Permissions</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage system permissions and access controls</p>
                    </div>
                    <button @click="goToCreatePage" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Permission</button>
                </div>

                <!-- Filters and Search -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <!-- Search -->
                        <div class="relative">
                            <input
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search permissions..."
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 pr-10 focus:border-primary focus:outline-none md:w-64"
                            />
                            <svg class="absolute top-2.5 right-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                        </div>

                        <!-- Module Filter -->
                        <select
                            v-model="searchForm.module"
                            class="rounded-lg border border-gray-300 px-4 py-2 focus:border-primary focus:outline-none"
                        >
                            <option value="">All Modules</option>
                            <option v-for="module in modules" :key="module" :value="module">
                                {{ module.charAt(0).toUpperCase() + module.slice(1) }}
                            </option>
                        </select>

                        <!-- Clear Filters -->
                        <button v-if="searchForm.search || searchForm.module" @click="clearFilters" class="text-sm text-gray-600 hover:text-gray-800">
                            Clear Filters
                        </button>
                    </div>

                    <!-- View Toggle -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">View:</span>
                        <button
                            @click="showGrouped = true"
                            :class="['rounded px-3 py-1 text-sm', showGrouped ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700']"
                        >
                            Grouped
                        </button>
                        <button
                            @click="showGrouped = false"
                            :class="['rounded px-3 py-1 text-sm', !showGrouped ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700']"
                        >
                            List
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="showBulkActions" class="flex items-center gap-4 rounded-lg bg-blue-50 p-4">
                    <span class="text-sm text-blue-800">{{ selectedPermissions.length }} permissions selected</span>
                    <button @click="bulkDeletePermissions" class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">
                        Delete Selected
                    </button>
                    <button @click="selectedPermissions = []" class="text-sm text-blue-600 hover:text-blue-800">Clear Selection</button>
                </div>

                <!-- Grouped View -->
                <div v-if="showGrouped" class="space-y-6">
                    <div v-for="(modulePermissions, module) in groupedPermissions" :key="module" class="rounded-lg border border-gray-200">
                        <!-- Module Header -->
                        <div
                            @click="toggleModule(module)"
                            class="flex cursor-pointer items-center justify-between bg-gray-50 px-4 py-3 hover:bg-gray-100"
                        >
                            <div class="flex items-center gap-3">
                                <svg
                                    :class="['h-5 w-5 transition-transform', expandedModules[module] ? 'rotate-90' : '']"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <h3 class="font-semibold capitalize">{{ module || 'Uncategorized' }}</h3>
                                <span class="rounded-full bg-primary px-2 py-1 text-xs text-white">
                                    {{ modulePermissions.length }}
                                </span>
                            </div>
                        </div>

                        <!-- Module Permissions -->
                        <div v-if="expandedModules[module]" class="divide-y divide-gray-100">
                            <div
                                v-for="permission in modulePermissions"
                                :key="permission.id"
                                class="flex items-center justify-between p-4 hover:bg-gray-50"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="selectedPermissions"
                                        :value="permission.id.toString()"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <div>
                                        <h4 class="font-medium">{{ permission.name }}</h4>
                                        <p v-if="permission.description" class="text-sm text-gray-600">
                                            {{ permission.description }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="goToPermissionShow(permission.hashid)" class="text-sm text-blue-600 hover:underline">View</button>
                                    <button @click="goToPermissionEdit(permission.hashid)" class="text-sm text-primary hover:underline">Edit</button>
                                    <button @click="deletePermission(permission.hashid)" class="text-sm text-red-600 hover:underline">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else class="overflow-x-auto rounded-xl border border-primary">
                    <table class="min-w-full table-auto border-collapse text-left text-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3">
                                    <input
                                        @change="toggleSelectAll"
                                        :checked="selectedPermissions.length === permissions.length && permissions.length > 0"
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                    />
                                </th>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Permission</th>
                                <th class="px-4 py-3">Module</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(permission, index) in permissions" :key="permission.id" class="border-t border-primary/30 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input
                                        v-model="selectedPermissions"
                                        :value="permission.id.toString()"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                </td>
                                <td class="px-4 py-3">{{ index + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ permission.name }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="permission.module" class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize">
                                        {{ permission.module }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="max-w-xs truncate px-4 py-3">
                                    <span v-if="permission.description" :title="permission.description">
                                        {{ permission.description }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button @click="goToPermissionShow(permission.hashid)" class="text-sm text-blue-600 hover:underline">
                                            View
                                        </button>
                                        <button @click="goToPermissionEdit(permission.hashid)" class="text-sm text-primary hover:underline">
                                            Edit
                                        </button>
                                        <button @click="deletePermission(permission.hashid)" class="text-sm text-red-600 hover:underline">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="permissions.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        ></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No permissions found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ filters.search || filters.module ? 'Try adjusting your search criteria.' : 'Get started by creating a new permission.' }}
                    </p>
                    <div class="mt-6">
                        <button
                            v-if="!filters.search && !filters.module"
                            @click="goToCreatePage"
                            class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm"
                        >
                            + New Permission
                        </button>
                        <button
                            v-else
                            @click="clearFilters"
                            class="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
