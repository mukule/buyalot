<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Role, User } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type UserWithRoles = User & { roles: Role[] };

const page = usePage<
    AppPageProps<{
        users: (User & {
            hashid: string;
            phone?: string;
            roles: (Role & { hashid: string })[];
        })[];
        roles: string[];
        filters?: {
            search?: string;
            role?: string;
            status?: string;
        };
    }>
>();

const users = computed(() => page.props.users);
const availableRoles = computed(() => page.props.roles);
const filters = computed(() => page.props.filters || {});

const searchForm = useForm({
    search: filters.value.search || '',
    role: filters.value.role || '',
    status: filters.value.status || '',
});

const selectedUsers = ref<string[]>([]);
const showBulkActions = computed(() => selectedUsers.value.length > 0);
const showRoleModal = ref(false);
const showCreateModal = ref(false);

const roleForm = useForm({
    roles: [] as string[],
});

const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    gender: '',
    password: '',
    password_confirmation: '',
    status: true,
    roles: [] as string[],
});

const showViewModal = ref(false);
const showEditModal = ref(false);
const selectedUser = ref<UserWithRoles | null>(null);

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    gender: '',
    status: true,
    errors: {},
    processing: false,
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Users', href: '/admin/users' },
];

function search() {
    router.get(
        route('admin.user-roles.index'),
        {
            search: searchForm.search,
            role: searchForm.role,
            status: searchForm.status,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function clearFilters() {
    searchForm.search = '';
    searchForm.role = '';
    searchForm.status = '';
    search();
}

function toggleSelectAll() {
    if (selectedUsers.value.length === users.value.length) {
        selectedUsers.value = [];
    } else {
        selectedUsers.value = users.value.map((u) => u.id.toString());
    }
}

function deleteUser(userId: string) {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route('admin.users.destroy', userId));
    }
}

function getRoleColor(roleName: string): string {
    const colors: Record<string, string> = {
        'super-admin': 'bg-red-100 text-orange-800',
        admin: 'bg-purple-100 text-purple-800',
        manager: 'bg-blue-100 text-blue-800',
        staff: 'bg-green-100 text-green-800',
        seller: 'bg-yellow-100 text-yellow-800',
        user: 'bg-gray-100 text-gray-800',
    };
    return colors[roleName] || 'bg-gray-100 text-gray-800';
}
function closeModals() {
    showViewModal.value = false;
    showEditModal.value = false;
    showRoleModal.value = false;
    showCreateModal.value = false;
    selectedUser.value = null;
    editForm.reset();
    roleForm.reset();
    createForm.reset();
}

function openCreateModal() {
    createForm.reset();
    createForm.status = true;
    createForm.roles = ['user'];
    showCreateModal.value = true;
}

function openViewModal(user: UserWithRoles) {
    selectedUser.value = user;
    showViewModal.value = true;
}

function openEditModal(user: UserWithRoles) {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone = user.phone ?? '';
    editForm.gender = user.gender ?? '';
    editForm.status = !!user.status;
    showEditModal.value = true;
}

function openRoleModal(user: UserWithRoles) {
    selectedUser.value = user;
    roleForm.roles = user.roles.map((role) => role.name);
    showRoleModal.value = true;
}

// --- FORM SUBMISSION FUNCTIONS ---
function createUser() {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
    });
}
function updateUser() {
    if (!selectedUser.value) return;

    editForm.put(route('admin.user-roles.update', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
    });
}

function updateUserRoles() {
    if (!selectedUser.value) return;

    roleForm.put(route('admin.user.roles.update', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModals();
        },
    });
}
// Watch for changes in search form
watch(
    [() => searchForm.search, () => searchForm.role, () => searchForm.status],
    () => {
        search();
    },
    { debounce: 300 },
);
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Users</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage user accounts and role assignments</p>
                    </div>
                    <!--                    <button-->
                    <!--                        @click="router.get(route('admin.users.create'))"-->
                    <!--                        class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white"-->
                    <!--                    >-->
                    <!--                        + New User-->
                    <!--                    </button>-->
                    <button @click="openCreateModal" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New User</button>
                </div>

                <!-- Filters and Search -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <!-- Search -->
                        <div class="relative">
                            <input
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search users..."
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

                        <!-- Role Filter -->
                        <select v-model="searchForm.role" class="rounded-lg border border-gray-300 px-4 py-2 focus:border-primary focus:outline-none">
                            <option value="">All Roles</option>
                            <option v-for="role in availableRoles" :key="role" :value="role">
                                {{ role.charAt(0).toUpperCase() + role.slice(1).replace('-', ' ') }}
                            </option>
                        </select>

                        <select
                            v-model="searchForm.status"
                            class="rounded-lg border border-gray-300 px-4 py-2 focus:border-primary focus:outline-none"
                        >
                            <option value="">All Status</option>
                            <option :value="true">Active</option>
                            <option :value="false">Suspended</option>
                        </select>

                        <button
                            v-if="searchForm.search || searchForm.role || searchForm.status"
                            @click="clearFilters"
                            class="text-sm text-gray-600 hover:text-gray-800"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="showBulkActions" class="flex items-center gap-4 rounded-lg bg-blue-50 p-4">
                    <span class="text-sm text-blue-800">{{ selectedUsers.length }} users selected</span>
                    <button class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700">Bulk Edit Roles</button>
                    <button class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">Bulk Delete</button>
                    <button @click="selectedUsers = []" class="text-sm text-blue-600 hover:text-blue-800">Clear Selection</button>
                </div>

                <!-- Users Table -->
                <div class="overflow-x-auto rounded-xl border border-primary">
                    <table class="min-w-full table-auto border-collapse text-left text-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3">
                                    <input
                                        @change="toggleSelectAll"
                                        :checked="selectedUsers.length === users.length && users.length > 0"
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                    />
                                </th>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Roles</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in users" :key="user.id" class="border-t border-primary/30 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input
                                        v-model="selectedUsers"
                                        :value="user.id.toString()"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                </td>
                                <td class="px-4 py-3">{{ index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                            <span class="text-sm font-medium text-primary">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ user.name }}</div>
                                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                                    ></path>
                                                </svg>
                                                {{ user.phone }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ user.email || '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            :class="['rounded-full px-2 py-1 text-xs font-medium', getRoleColor(role.name)]"
                                        >
                                            {{ role.name.replace('-', ' ') }}
                                        </span>
                                        <span v-if="user.roles.length === 0" class="text-xs text-gray-400">No roles</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="user.status == true" class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                        Active
                                    </span>
                                    <span v-else class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-500"> Suspended </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <button @click="openViewModal(user)" class="text-sm text-blue-600 hover:underline">View</button>
                                        <button @click="openEditModal(user)" class="text-sm text-yellow-600 hover:underline">Edit</button>
                                        <button @click="openRoleModal(user)" class="text-sm text-purple-600 hover:underline">Roles</button>
                                        <button @click="deleteUser(user.id.toString())" class="text-sm text-red-600 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="users.length === 0" class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                        ></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            filters.search || filters.role || filters.status
                                ? 'Try adjusting your search criteria.'
                                : 'Get started by creating a new user.'
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="closeModals"></div>

                <div
                    class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-middle shadow-xl transition-all"
                >
                    <form @submit.prevent="createUser">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="mb-4 text-xl leading-6 font-semibold text-gray-900">Create New User</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="create-name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                                    <input
                                        v-model="createForm.name"
                                        type="text"
                                        id="create-name"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="createForm.errors.name" class="mt-1 text-xs text-red-500">{{ createForm.errors.name }}</div>
                                </div>

                                <div>
                                    <label for="create-email" class="block text-sm font-medium text-gray-700">Email *</label>
                                    <input
                                        v-model="createForm.email"
                                        type="email"
                                        id="create-email"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="createForm.errors.email" class="mt-1 text-xs text-red-500">{{ createForm.errors.email }}</div>
                                </div>

                                <div>
                                    <label for="create-phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input
                                        v-model="createForm.phone"
                                        type="tel"
                                        id="create-phone"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="createForm.errors.phone" class="mt-1 text-xs text-red-500">{{ createForm.errors.phone }}</div>
                                </div>

                                <div>
                                    <label for="create-gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select
                                        v-model="createForm.gender"
                                        id="create-gender"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div v-if="createForm.errors.gender" class="mt-1 text-xs text-red-500">{{ createForm.errors.gender }}</div>
                                </div>

                                <div>
                                    <label for="create-password" class="block text-sm font-medium text-gray-700">Password *</label>
                                    <input
                                        v-model="createForm.password"
                                        type="password"
                                        id="create-password"
                                        required
                                        minlength="8"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="createForm.errors.password" class="mt-1 text-xs text-red-500">{{ createForm.errors.password }}</div>
                                </div>

                                <div>
                                    <label for="create-password-confirmation" class="block text-sm font-medium text-gray-700"
                                        >Confirm Password *</label
                                    >
                                    <input
                                        v-model="createForm.password_confirmation"
                                        type="password"
                                        id="create-password-confirmation"
                                        required
                                        minlength="8"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="createForm.errors.password_confirmation" class="mt-1 text-xs text-red-500">
                                        {{ createForm.errors.password_confirmation }}
                                    </div>
                                </div>

                                <div>
                                    <label for="create-status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select
                                        v-model="createForm.status"
                                        id="create-status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option :value="true">Active</option>
                                        <option :value="false">Suspended</option>
                                    </select>
                                    <div v-if="createForm.errors.status" class="mt-1 text-xs text-red-500">{{ createForm.errors.status }}</div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Assign Roles</label>
                                    <div class="space-y-2">
                                        <div v-for="role in availableRoles" :key="role" class="flex items-center">
                                            <input
                                                v-model="createForm.roles"
                                                :value="role"
                                                :id="`create-role-${role}`"
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            />
                                            <label :for="`create-role-${role}`" class="ml-3 text-sm font-medium text-gray-700 capitalize">
                                                {{ role.replace('-', ' ') }}
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="createForm.errors.roles" class="mt-1 text-xs text-red-500">{{ createForm.errors.roles }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                :class="[
                                    'inline-flex w-full justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm focus:ring-2 focus:ring-offset-2 focus:outline-none sm:ml-3 sm:w-auto',
                                    createForm.processing ? 'cursor-not-allowed bg-gray-400' : 'hover:bg-primary-dark bg-primary focus:ring-primary',
                                ]"
                            >
                                {{ createForm.processing ? 'Creating...' : 'Create User' }}
                            </button>
                            <button
                                type="button"
                                @click="closeModals"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:mt-0 sm:w-auto"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
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
                    class="relative z-10 inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <form @submit.prevent="updateUserRoles">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                    <h3 class="mb-4 text-lg leading-6 font-medium text-gray-900">Manage Roles for {{ selectedUser?.name }}</h3>

                                    <div class="space-y-3">
                                        <div v-for="role in availableRoles" :key="role" class="flex items-center">
                                            <input
                                                v-model="roleForm.roles"
                                                :value="role"
                                                :id="`role-${role}`"
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            />
                                            <label :for="`role-${role}`" class="ml-3 text-sm font-medium text-gray-700 capitalize">
                                                {{ role.replace('-', ' ') }}
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

        <div v-if="showViewModal && selectedUser" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 text-center">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="closeModals"></div>

                <div
                    class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all"
                >
                    <div class="flex items-start justify-between">
                        <h3 class="text-xl leading-6 font-semibold text-gray-900">User Details</h3>
                        <button @click="closeModals" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <div class="mt-4 border-t border-gray-200">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ selectedUser.name }}</dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ selectedUser.phone }}</dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ selectedUser.email }}</dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Gender</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ selectedUser.gender }}</dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            selectedUser.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ selectedUser.status ? 'true' : 'false' }}
                                    </span>
                                </dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Roles</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in selectedUser.roles"
                                            :key="role.id"
                                            :class="['rounded-full px-2 py-1 text-xs font-medium', getRoleColor(role.name)]"
                                        >
                                            {{ role.name.replace('-', ' ') }}
                                        </span>
                                        <span v-if="!selectedUser.roles.length" class="text-xs text-gray-500">No roles assigned</span>
                                    </div>
                                </dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Joined On</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ new Date(selectedUser.created_at).toLocaleDateString() }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div class="mt-5 sm:mt-6">
                        <button
                            type="button"
                            @click="closeModals"
                            class="hover:bg-primary-dark inline-flex w-full justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showEditModal && selectedUser" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="closeModals"></div>

                <div
                    class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-middle shadow-xl transition-all"
                >
                    <form @submit.prevent="updateUser">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-xl leading-6 font-semibold text-gray-900">Edit User</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input
                                        v-model="editForm.name"
                                        type="text"
                                        id="name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="editForm.errors.name" class="mt-1 text-xs text-red-500">{{ editForm.errors.name }}</div>
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input
                                        v-model="editForm.email"
                                        type="email"
                                        id="email"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="editForm.errors.email" class="mt-1 text-xs text-red-500">{{ editForm.errors.email }}</div>
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input
                                        v-model="editForm.phone"
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <div v-if="editForm.errors.phone" class="mt-1 text-xs text-red-500">{{ editForm.errors.phone }}</div>
                                </div>
                                <div class="mb-3">
                                    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select
                                        v-model="editForm.gender"
                                        id="gender"
                                        name="gender"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div v-if="editForm.errors.gender" class="mt-1 text-xs text-red-500">{{ editForm.errors.gender }}</div>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select
                                        v-model="editForm.status"
                                        id="status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option :value="true">Active</option>
                                        <option :value="false">Suspended</option>
                                    </select>
                                    <div v-if="editForm.errors.status" class="mt-1 text-xs text-red-500">{{ editForm.errors.status }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                :disabled="editForm.processing"
                                type="submit"
                                class="hover:bg-primary-dark inline-flex w-full justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50 sm:ml-3 sm:w-auto"
                            >
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                            <button
                                type="button"
                                @click="closeModals"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:mt-0 sm:w-auto"
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
