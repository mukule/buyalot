<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Permission, Role } from '@/types';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<
    AppPageProps<{
        roles: (Role & {
            hashid: string;
            permissions: Permission[];
        })[];
        permissions: (Permission & { module?: string })[];
    }>
>();

const roles = computed(() => page.props.roles);
const permissions = computed(() => page.props.permissions);

const selectedRoles = ref<string[]>([]);
const showBulkActions = computed(() => selectedRoles.value.length > 0);
const showPermissionModal = ref(false);
const selectedRoleForPermissions = ref<(Role & { permissions: Permission[] }) | null>(null);

const permissionForm = useForm({
    permissions: [] as string[],
});

// Group permissions by module
const groupedPermissions = computed(() => {
    return permissions.value.reduce((groups, permission) => {
        const module = permission.module || 'uncategorized';
        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(permission);
        return groups;
    }, {} as Record<string, Permission[]>);
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Access Rights', href: '#' },
    { title: 'Roles', href: '/admin/roles' },
];

function goToCreatePage() {
    router.get(route('admin.roles.create'));
}

function goToRoleShow(hashid: string) {
    router.get(route('admin.roles.show', hashid));
}

function goToRoleEdit(hashid: string) {
    router.get(route('admin.roles.edit', hashid));
}

function deleteRole(hashid: string) {
    if (confirm('Are you sure you want to delete this role?')) {
        router.delete(route('admin.roles.destroy', hashid));
    }
}

function toggleSelectAll() {
    if (selectedRoles.value.length === roles.value.length) {
        selectedRoles.value = [];
    } else {
        selectedRoles.value = roles.value.map(r => r.id.toString());
    }
}

function openPermissionModal(role: Role & { permissions: Permission[] }) {
    selectedRoleForPermissions.value = role;
    permissionForm.permissions = role.permissions.map(permission => permission.id.toString());
    showPermissionModal.value = true;
}

function updateRolePermissions() {
    if (!selectedRoleForPermissions.value) return;

    router.post(route('admin.api.roles.assign-permissions', selectedRoleForPermissions.value.id), {
        permissions: permissionForm.permissions.map(id => parseInt(id))
    }, {
        onSuccess: () => {
            showPermissionModal.value = false;
            selectedRoleForPermissions.value = null;
            permissionForm.reset();
        },
    });
}

function toggleModulePermissions(module: string, checked: boolean) {
    const modulePermissions = groupedPermissions.value[module];
    if (checked) {
        // Add all module permissions
        modulePermissions.forEach(permission => {
            if (!permissionForm.permissions.includes(permission.id.toString())) {
                permissionForm.permissions.push(permission.id.toString());
            }
        });
    } else {
        // Remove all module permissions
        modulePermissions.forEach(permission => {
            const index = permissionForm.permissions.indexOf(permission.id.toString());
            if (index > -1) {
                permissionForm.permissions.splice(index, 1);
            }
        });
    }
}

function isModuleSelected(module: string): boolean {
    const modulePermissions = groupedPermissions.value[module];
    return modulePermissions.every(permission =>
        permissionForm.permissions.includes(permission.id.toString())
    );
}

function isModulePartiallySelected(module: string): boolean {
    const modulePermissions = groupedPermissions.value[module];
    const selectedCount = modulePermissions.filter(permission =>
        permissionForm.permissions.includes(permission.id.toString())
    ).length;
    return selectedCount > 0 && selectedCount < modulePermissions.length;
}

function getRoleColor(roleName: string): string {
    const colors: Record<string, string> = {
        'super-admin': 'bg-red-500',
        'admin': 'bg-purple-500',
        'manager': 'bg-blue-500',
        'staff': 'bg-green-500',
        'seller': 'bg-yellow-500',
        'user': 'bg-gray-500',
    };
    return colors[roleName] || 'bg-indigo-500';
}

function bulkDeleteRoles() {
    if (confirm(`Are you sure you want to delete ${selectedRoles.value.length} roles?`)) {
        // Implement bulk delete logic
        selectedRoles.value = [];
    }
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Roles</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage user roles and their permissions</p>
                    </div>
                    <button @click="goToCreatePage" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">
                        + New Role
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm">Total Roles</p>
                                <p class="text-2xl font-bold">{{ roles.length }}</p>
                            </div>
                            <svg class="h-8 w-8 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm">Active Roles</p>
                                <p class="text-2xl font-bold">{{ roles.filter(r => r.name !== 'user').length }}</p>
                            </div>
                            <svg class="h-8 w-8 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm">Total Permissions</p>
                                <p class="text-2xl font-bold">{{ permissions.length }}</p>
                            </div>
                            <svg class="h-8 w-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm">Permission Modules</p>
                                <p class="text-2xl font-bold">{{ Object.keys(groupedPermissions).length }}</p>
                            </div>
                            <svg class="h-8 w-8 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="showBulkActions" class="flex items-center gap-4 rounded-lg bg-blue-50 p-4">
                    <span class="text-sm text-blue-800">{{ selectedRoles.length }} roles selected</span>
                    <button
                        @click="bulkDeleteRoles"
                        class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                    >
                        Delete Selected
                    </button>
                    <button
                        @click="selectedRoles = []"
                        class="text-sm text-blue-600 hover:text-blue-800"
                    >
                        Clear Selection
                    </button>
                </div>

                <!-- Roles Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="role in roles"
                        :key="role.id"
                        class="relative rounded-lg border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow"
                    >
                        <!-- Selection Checkbox -->
                        <div class="absolute top-4 right-4">
                            <input
                                v-model="selectedRoles"
                                :value="role.id.toString()"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                        </div>

                        <!-- Role Header -->
                        <div class="flex items-start gap-4 mb-4">
                            <div :class="[
                                'h-12 w-12 rounded-full flex items-center justify-center text-white text-lg font-bold',
                                getRoleColor(role.name)
                            ]">
                                {{ role.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 capitalize">
                                    {{ role.name.replace('-', ' ') }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ role.permissions.length }} permissions
                                </p>
                            </div>
                        </div>

                        <!-- Permissions Preview -->
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Key Permissions:</p>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="(permission, index) in role.permissions.slice(0, 3)"
                                    :key="permission.id"
                                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800"
                                >
                                    {{ permission.name.replace('-', ' ') }}
                                </span>
                                <span
                                    v-if="role.permissions.length > 3"
                                    class="inline-flex items-center rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary"
                                >
                                    +{{ role.permissions.length - 3 }} more
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <button
                                    @click="goToRoleShow(role.hashid)"
                                    class="text-sm text-blue-600 hover:underline"
                                >
                                    View
                                </button>
                                <button
                                    @click="goToRoleEdit(role.hashid)"
                                    class="text-sm text-primary hover:underline"
                                >
                                    Edit
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="openPermissionModal(role)"
                                    class="rounded bg-primary px-3 py-1 text-xs text-white hover:bg-primary-dark"
                                >
                                    Permissions
                                </button>
                                <button
                                    v-if="role.name !== 'super-admin'"
                                    @click="deleteRole(role.hashid)"
                                    class="text-sm text-red-600 hover:underline"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="roles.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No roles found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new role.</p>
                    <div class="mt-6">
                        <button @click="goToCreatePage" class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark">
                            + New Role
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permission Assignment Modal -->
        <div v-if="showPermissionModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" @click="showPermissionModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:align-middle">
                    <form @submit.prevent="updateRolePermissions">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                        Manage Permissions for {{ selectedRoleForPermissions?.name?.replace('-', ' ') }}
                                    </h3>

                                    <div class="max-h-96 overflow-y-auto space-y-4">
                                        <div v-for="(modulePermissions, module) in groupedPermissions" :key="module" class="border rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="font-medium capitalize">{{ module.replace('-', ' ') }}</h4>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        :checked="isModuleSelected(module)"
                                                        :indeterminate="isModulePartiallySelected(module)"
                                                        @change="toggleModulePermissions(module, $event.target.checked)"
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <span class="text-sm text-gray-500">Select All</span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div v-for="permission in modulePermissions" :key="permission.id" class="flex items-center">
                                                    <input
                                                        v-model="permissionForm.permissions"
                                                        :value="permission.id.toString()"
                                                        :id="`permission-${permission.id}`"
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <label :for="`permission-${permission.id}`" class="ml-2 text-sm text-gray-700">
                                                        {{ permission.name.replace('-', ' ') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-800">
                                            <strong>{{ permissionForm.permissions.length }}</strong> permissions selected
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="submit"
                                :class="[
                                    'w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm',
                                    permissionForm.processing ? 'bg-gray-400 cursor-not-allowed' : 'bg-primary hover:bg-primary-dark focus:ring-primary'
                                ]"
                                :disabled="permissionForm.processing"
                            >
                                {{ permissionForm.processing ? 'Updating...' : 'Update Permissions' }}
                            </button>
                            <button
                                type="button"
                                @click="showPermissionModal = false"
                                class="mt-3 w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
