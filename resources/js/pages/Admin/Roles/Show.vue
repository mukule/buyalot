<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Permission, Role } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{ role: Role & { permissions: Permission[]; hashid: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title: props.role.name, href: '' },
];

const groupedPermissions = computed(() => {
    if (!props.role.permissions) {
        return {};
    }
    return props.role.permissions.reduce((groups, permission) => {
        const groupName = permission.module || 'general';
        if (!groups[groupName]) {
            groups[groupName] = [];
        }
        groups[groupName].push(permission);
        return groups;
    }, {} as Record<string, Permission[]>);
});

function formatGroupName(name: string): string {
    return name
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function goToEditRole() {
    router.get(route('admin.roles.edit', props.role.hashid));
}
</script>

<template>
    <Head :title="`Role: ${props.role.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">{{ props.role.name }}</h2>
                    <button
                        @click="goToEditRole"
                        class="cursor-pointer rounded bg-primary px-4 py-2 text-white transition-colors duration-200 hover:bg-secondary focus:outline-none"
                    >
                        Edit Role
                    </button>
                </div>
                <hr />

                <section>
                    <h3 class="mb-4 text-xl font-medium">Assigned Permissions</h3>

                    <!-- Display a message if no permissions are assigned -->
                    <div v-if="props.role.permissions.length === 0" class="text-gray-500">
                        No permissions assigned.
                    </div>
                    <div v-else class="space-y-5">
                        <div v-for="(permissionsInGroup, groupName) in groupedPermissions" :key="groupName">
                            <h4 class="mb-2 font-semibold text-gray-800">
                                {{ formatGroupName(groupName) }}
                            </h4>
                            <ul class="grid grid-cols-1 gap-x-4 gap-y-2 pl-2 sm:grid-cols-2 md:grid-cols-3">
                                <li v-for="permission in permissionsInGroup" :key="permission.id" class="flex items-center">
                                    <svg class="mr-2 h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-sm text-gray-600">{{ permission.name }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
