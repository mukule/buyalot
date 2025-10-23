<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Permission, Role, User } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<
    AppPageProps<{
        user: User & {
            status: string;
            created_at: string;
            phone: string;
            updated_at: string;
            email_verified_at: string | null;
            roles: Role[];
            permissions: Permission[];
        };
        roles: Role[];
    }>
>();

const user = computed(() => page.props.user);
const availableRoles = computed(() => page.props.roles);

const showRoleModal = ref(false);
const showStatusModal = ref(false);

const roleForm = useForm({
    roles: user.value.roles.map((role) => role.name),
});

const statusForm = useForm({
    status: user.value.status,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Users', href: '/admin/users' },
    { title: user.value.name, href: '#' },
];

function openRoleModal() {
    roleForm.roles = user.value.roles.map((role) => role.name);
    showRoleModal.value = true;
}

function updateUserRoles() {
    roleForm.put(route('admin.users.update', user.value.id), {
        onSuccess: () => {
            showRoleModal.value = false;
        },
    });
}

function openStatusModal() {
    statusForm.status = user.value.status;
    showStatusModal.value = true;
}

function updateUserStatus() {
    router.post(
        route('admin.users.update-status', user.value.id),
        {
            status: statusForm.status,
        },
        {
            onSuccess: () => {
                showStatusModal.value = false;
            },
        },
    );
}

function deleteUser() {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        router.delete(route('admin.users.destroy', user.value.id));
    }
}

function getRoleColor(roleName: string): string {
    const colors: Record<string, string> = {
        'super-admin': 'bg-red-100 text-red-800',
        admin: 'bg-purple-100 text-purple-800',
        manager: 'bg-blue-100 text-blue-800',
        staff: 'bg-green-100 text-green-800',
        seller: 'bg-yellow-100 text-yellow-800',
        user: 'bg-gray-100 text-gray-800',
    };
    return colors[roleName] || 'bg-gray-100 text-gray-800';
}

function getStatusColor(status: string): string {
    const colors: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        suspended: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`User: ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- User Header -->
            <div class="card rounded-lg bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                            <span class="text-2xl font-bold text-primary">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">{{ user.name }}</h1>
                            <p class="text-gray-600">{{ user.email }}</p>
                            <div class="mt-2 flex items-center gap-2">
                                <span :class="['rounded-full px-3 py-1 text-sm font-medium', getStatusColor(user.status)]">
                                    {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}
                                </span>
                                <span v-if="user.email_verified_at" class="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                    Email Verified
                                </span>
                                <span v-else class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800">
                                    Email Unverified
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="openRoleModal" class="hover:bg-primary-dark rounded-lg bg-primary px-4 py-2 text-white">Manage Roles</button>
                        <button @click="openStatusModal" class="rounded-lg bg-yellow-600 px-4 py-2 text-white hover:bg-yellow-700">
                            Change Status
                        </button>
                        <button
                            @click="router.get(route('admin.users.edit', user.id))"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                        >
                            Edit User
                        </button>
                        <button
                            v-if="!user.roles.some((role) => role.name === 'super-admin')"
                            @click="deleteUser"
                            class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                        >
                            Delete User
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- User Information -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic Information -->
                    <div class="card rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Basic Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ user.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ user.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">User ID</label>
                                <p class="mt-1 text-sm text-gray-900">#{{ user.id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Account Status</label>
                                <span :class="['mt-1 inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusColor(user.status)]">
                                    {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Created At</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(user.created_at) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(user.updated_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="card rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Direct Permissions</h2>
                        <div v-if="user.permissions.length > 0" class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                            <span
                                v-for="permission in user.permissions"
                                :key="permission.id"
                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700"
                            >
                                {{ permission.name.replace('-', ' ') }}
                            </span>
                        </div>
                        <p v-else class="text-sm text-gray-500">No direct permissions assigned. User inherits permissions from roles.</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Roles -->
                    <div class="card rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Assigned Roles</h2>
                            <button @click="openRoleModal" class="hover:text-primary-dark text-sm text-primary">Edit</button>
                        </div>
                        <div v-if="user.roles.length > 0" class="space-y-2">
                            <div v-for="role in user.roles" :key="role.id" class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                                <div>
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getRoleColor(role.name)]">
                                        {{ role.name.replace('-', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">No roles assigned</p>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Quick Actions</h2>
                        <div class="space-y-2">
                            <button
                                @click="router.get(route('admin.users.edit', user.id))"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    ></path>
                                </svg>
                                Edit Profile
                            </button>
                            <button
                                @click="openRoleModal"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    ></path>
                                </svg>
                                Manage Roles
                            </button>
                            <button
                                @click="openStatusModal"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                Change Status
                            </button>
                            <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v-2l-4.257-2.257A6 6 0 0112 5h3m2 5v2a2 2 0 01-2 2h-2m-6-4h2a2 2 0 012 2v2a2 2 0 01-2 2h-2v4h.01"
                                    ></path>
                                </svg>
                                Reset Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Management Modal -->
        <div v-if="showRoleModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" @click="showRoleModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <form @submit.prevent="updateUserRoles">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                    <h3 class="mb-4 text-lg leading-6 font-medium text-gray-900">Manage Roles for {{ user.name }}</h3>

                                    <div class="space-y-3">
                                        <div v-for="role in availableRoles" :key="role.id" class="flex items-center">
                                            <input
                                                v-model="roleForm.roles"
                                                :value="role.name"
                                                :id="`role-${role.id}`"
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            />
                                            <label :for="`role-${role.id}`" class="ml-3 text-sm font-medium text-gray-700 capitalize">
                                                {{ role.name.replace('-', ' ') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="submit"
                                :class="[
                                    'w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:ring-2 focus:ring-offset-2 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm',
                                    roleForm.processing ? 'cursor-not-allowed bg-gray-400' : 'hover:bg-primary-dark bg-primary focus:ring-primary',
                                ]"
                                :disabled="roleForm.processing"
                            >
                                {{ roleForm.processing ? 'Updating...' : 'Update Roles' }}
                            </button>
                            <button
                                type="button"
                                @click="showRoleModal = false"
                                class="mt-3 w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Status Change Modal -->
        <div v-if="showStatusModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" @click="showStatusModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <form @submit.prevent="updateUserStatus">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                    <h3 class="mb-4 text-lg leading-6 font-medium text-gray-900">Change Status for {{ user.name }}</h3>

                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input
                                                v-model="statusForm.status"
                                                value="active"
                                                id="status-active"
                                                type="radio"
                                                class="h-4 w-4 border-gray-300 text-green-600 focus:ring-green-500"
                                            />
                                            <label for="status-active" class="ml-3 text-sm font-medium text-gray-700">
                                                <span class="text-green-600">Active</span> - User can access the system normally
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                v-model="statusForm.status"
                                                value="inactive"
                                                id="status-inactive"
                                                type="radio"
                                                class="h-4 w-4 border-gray-300 text-gray-600 focus:ring-gray-500"
                                            />
                                            <label for="status-inactive" class="ml-3 text-sm font-medium text-gray-700">
                                                <span class="text-gray-600">Inactive</span> - User cannot login but data is preserved
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                v-model="statusForm.status"
                                                value="suspended"
                                                id="status-suspended"
                                                type="radio"
                                                class="h-4 w-4 border-gray-300 text-red-600 focus:ring-red-500"
                                            />
                                            <label for="status-suspended" class="ml-3 text-sm font-medium text-gray-700">
                                                <span class="text-red-600">Suspended</span> - Account is temporarily blocked
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="submit"
                                :class="[
                                    'w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:ring-2 focus:ring-offset-2 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm',
                                    statusForm.processing
                                        ? 'cursor-not-allowed bg-gray-400'
                                        : 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
                                ]"
                                :disabled="statusForm.processing"
                            >
                                {{ statusForm.processing ? 'Updating...' : 'Update Status' }}
                            </button>
                            <button
                                type="button"
                                @click="showStatusModal = false"
                                class="mt-3 w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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
