<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const page = usePage<
    AppPageProps<{
        users: { id: number; name: string; roles: string[] }[];
        roles: string[];
    }>
>();

const users = computed(() => page.props.users);
const availableRoles = computed(() => page.props.roles);

// Track roles per user as a Set
const forms = reactive<{ [key: number]: Set<string> }>({});

// Initialize forms with current user roles
users.value.forEach((user) => {
    forms[user.id] = new Set(user.roles);
});

const updateRoles = async (userId: number) => {
    const rolesArray = Array.from(forms[userId]);

    try {
        await useForm({ roles: rolesArray }).put(`/admin/user-roles/${userId}`, {
            preserveScroll: true,
        });
    } catch (error) {
        console.error('Error updating roles:', error);
    }
};

const toggleRole = (userId: number, role: string) => {
    if (forms[userId].has(role)) {
        forms[userId].delete(role);
    } else {
        forms[userId].add(role);
    }
    updateRoles(userId);
};
</script>

<template>
    <Head title="Manage User Roles" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Manage User Roles', href: '#' },
        ]"
    >
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold">Manage User Roles</h1>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full table-auto text-left text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Roles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in users" :key="user.id" class="border-t align-top">
                                <td class="px-4 py-3">{{ index + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                                <td class="flex flex-wrap gap-6 px-4 py-3">
                                    <label v-for="role in availableRoles" :key="role" class="flex cursor-pointer items-center space-x-3 select-none">
                                        <input
                                            type="checkbox"
                                            :checked="forms[user.id].has(role)"
                                            @change="toggleRole(user.id, role)"
                                            class="peer sr-only"
                                        />

                                        <div
                                            class="relative h-6 w-11 rounded-full bg-gray-200 transition-colors duration-200 peer-checked:bg-blue-600 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800"
                                        >
                                            <span
                                                class="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition-transform duration-200 peer-checked:translate-x-5"
                                            ></span>
                                        </div>
                                        <span class="text-gray-700 capitalize">{{ role }}</span>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Add any additional styles if needed */
</style>
