<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Permission, Role } from '@/types';
import { Head, router } from '@inertiajs/vue3';

// Role now includes hashid string for routing
const props = defineProps<{ role: Role & { permissions: Permission[]; hashid: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title: props.role.name, href: '' },
];

function goToEditRole() {
    router.get(route('admin.roles.edit', props.role.hashid));
}
</script>

<template>
    <Head :title="`Role: ${props.role.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="space-y-6 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">{{ props.role.name }}</h2>
                    <button
                        @click="goToEditRole"
                        class="cursor-pointer rounded bg-primary px-4 py-2 text-white transition-colors duration-200 hover:bg-secondary focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Edit Role
                    </button>
                </div>
                <hr />

                <section>
                    <h3 class="mb-3 text-lg font-medium">Permissions</h3>
                    <ul class="space-y-2">
                        <li v-if="props.role.permissions.length === 0" class="text-gray-500">No permissions assigned.</li>
                        <li v-for="permission in props.role.permissions" :key="permission.id" class="flex items-center">
                            <span class="mr-2 h-2 w-2 rounded-full bg-primary"></span>
                            <span>{{ permission.name }}</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
