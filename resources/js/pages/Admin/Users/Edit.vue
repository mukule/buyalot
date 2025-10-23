<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Role, User } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<
    AppPageProps<{
        user: User & {
            status: string;
            roles: Role[];
        };
        roles: Role[];
    }>
>();

const user = computed(() => page.props.user);
const availableRoles = computed(() => page.props.roles);

const showPasswordSection = ref(false);

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    status: user.value.status,
    roles: user.value.roles.map((role) => role.name),
    password: '',
    password_confirmation: '',
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Users', href: '/admin/users' },
    { title: user.value.name, href: route('admin.users.show', user.value.id) },
    { title: 'Edit', href: '#' },
];

function submit() {
    const data = {
        name: form.name,
        email: form.email,
        status: form.status,
        roles: form.roles,
    };

    // Only include password if it's being changed
    if (form.password) {
        data.password = form.password;
        data.password_confirmation = form.password_confirmation;
    }

    form.put(route('admin.users.update', user.value.id), {
        data: data,
        onSuccess: () => {
            form.password = '';
            form.password_confirmation = '';
            showPasswordSection.value = false;
        },
    });
}

function getRoleColor(roleName: string): string {
    const colors: Record<string, string> = {
        'super-admin': 'bg-red-100 text-red-800 border-red-200',
        admin: 'bg-purple-100 text-purple-800 border-purple-200',
        manager: 'bg-blue-100 text-blue-800 border-blue-200',
        staff: 'bg-green-100 text-green-800 border-green-200',
        seller: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        user: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[roleName] || 'bg-gray-100 text-gray-800 border-gray-200';
}
function getRoleDescription(roleName) {
    const descriptions = {
        'super-admin': 'Full system access with all permissions',
        admin: 'Administrative access to most system features',
        manager: 'Management access to assigned modules',
        staff: 'Basic operational access',
        seller: 'Seller-specific permissions for marketplace',
        user: 'Standard user permissions',
    };
    return descriptions[roleName] || 'Custom role with specific permissions';
}

function togglePasswordSection() {
    showPasswordSection.value = !showPasswordSection.value;
    if (!showPasswordSection.value) {
        form.password = '';
        form.password_confirmation = '';
    }
}
</script>

<template>
    <Head :title="`Edit User: ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card rounded-lg bg-white p-6 shadow-sm">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold">Edit User</h1>
                    <p class="mt-1 text-sm text-gray-600">Update user information, roles, and settings</p>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <!-- User Information Section -->
                    <div class="border-b border-gray-200 pb-8">
                        <h2 class="mb-4 text-lg font-medium text-gray-900">Basic Information</h2>
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <!-- Full Name -->
                            <div>
                                <label for="name" class="mb-1 block text-sm font-medium text-gray-700"> Full Name * </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="mb-1 block text-sm font-medium text-gray-700"> Email Address * </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="mb-1 block text-sm font-medium text-gray-700"> Account Status * </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.status }"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Active users can login, inactive users cannot login, suspended users are temporarily blocked
                                </p>
                            </div>

                            <!-- User ID (Read-only) -->
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">User ID</label>
                                <input
                                    type="text"
                                    :value="`#${user.id}`"
                                    readonly
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="border-b border-gray-200 pb-8">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">Password</h2>
                            <button type="button" @click="togglePasswordSection" class="hover:text-primary-dark text-sm text-primary">
                                {{ showPasswordSection ? 'Cancel Password Change' : 'Change Password' }}
                            </button>
                        </div>

                        <div v-if="showPasswordSection" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <!-- New Password -->
                            <div>
                                <label for="password" class="mb-1 block text-sm font-medium text-gray-700"> New Password </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                                <p class="mt-1 text-xs text-gray-500">Minimum 8 characters required</p>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700"> Confirm New Password </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">Re-enter the new password</p>
                            </div>
                        </div>

                        <div v-else class="text-sm text-gray-600">Click "Change Password" to update the user's password</div>
                    </div>

                    <!-- Roles Section -->
                    <div class="border-b border-gray-200 pb-8">
                        <h2 class="mb-4 text-lg font-medium text-gray-900">Role Assignment</h2>
                        <p class="mb-4 text-sm text-gray-600">
                            Select the roles to assign to this user. Users inherit permissions from their assigned roles.
                        </p>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="role in availableRoles"
                                :key="role.id"
                                :class="[
                                    'relative cursor-pointer rounded-lg border-2 p-4 transition-all',
                                    form.roles.includes(role.name) ? getRoleColor(role.name) : 'border-gray-200 hover:border-gray-300',
                                ]"
                            >
                                <label :for="`role-${role.id}`" class="cursor-pointer">
                                    <div class="flex items-start space-x-3">
                                        <input
                                            v-model="form.roles"
                                            :value="role.name"
                                            :id="`role-${role.id}`"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium capitalize">
                                                {{ role.name.replace('-', ' ') }}
                                            </h3>
                                            <p class="mt-1 text-xs text-gray-600">
                                                {{ getRoleDescription(role.name) }}
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <p v-if="form.errors.roles" class="mt-2 text-sm text-red-600">{{ form.errors.roles }}</p>
                    </div>

                    <!-- Current Roles Preview -->
                    <div v-if="form.roles.length > 0" class="rounded-lg bg-blue-50 p-4">
                        <h3 class="mb-2 text-sm font-medium text-blue-900">Selected Roles Preview</h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="roleName in form.roles"
                                :key="roleName"
                                :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', getRoleColor(roleName)]"
                            >
                                {{ roleName.replace('-', ' ') }}
                            </span>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6">
                        <button
                            type="button"
                            @click="$inertia.visit(route('admin.users.show', user.id))"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                        >
                            Cancel
                        </button>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="form.reset()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                            >
                                Reset Changes
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                :class="[
                                    'rounded-lg px-6 py-2 text-sm font-medium text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none',
                                    form.processing ? 'cursor-not-allowed bg-gray-400' : 'hover:bg-primary-dark bg-primary',
                                ]"
                            >
                                {{ form.processing ? 'Updating User...' : 'Update User' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
