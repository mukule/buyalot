<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Permission, Role } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    permission: Permission & { hashid: string; description?: string };
    roles: (Role & { hashid: string })[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Permissions', href: '/admin/permissions' },
    { title: props.permission.name, href: '' },
];

function goToEdit() {
    router.get(route('admin.permissions.edit', props.permission.hashid));
}

function goToPermissionIndex() {
    router.get(route('admin.permissions.index'));
}
</script>

<template>
    <Head :title="`Permission: ${props.permission.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">{{ props.permission.name }}</h2>
                    <div class="flex gap-2">
                        <button
                            @click="goToPermissionIndex"
                            class="rounded border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            ← Back
                        </button>
                        <button
                            @click="goToEdit"
                            class="rounded bg-primary px-4 py-2 text-white transition-colors duration-200 hover:bg-secondary"
                        >
                            Edit Permission
                        </button>
                    </div>
                </div>

                <hr class="border-gray-200" />

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Permission Name</dt>
                        <dd class="mt-1 font-mono text-sm">{{ props.permission.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Guard Name</dt>
                        <dd class="mt-1 text-sm">{{ props.permission.guard_name || 'web' }}</dd>
                    </div>
                    <div v-if="props.permission.module">
                        <dt class="text-sm font-medium text-gray-500">Module</dt>
                        <dd class="mt-1 text-sm capitalize">{{ props.permission.module }}</dd>
                    </div>
                    <div v-if="props.permission.description" class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ props.permission.description }}</dd>
                    </div>
                </dl>

                <section>
                    <h3 class="mb-4 text-xl font-medium">Roles with this permission</h3>
                    <div v-if="props.roles.length === 0" class="text-gray-500">No roles assigned.</div>
                    <ul v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3">
                        <li
                            v-for="role in props.roles"
                            :key="role.id"
                            class="rounded-lg border border-gray-200 px-4 py-2 hover:bg-gray-50"
                        >
                            <span class="font-medium">{{ role.name }}</span>
                            <span v-if="role.guard_name" class="ml-2 text-xs text-gray-500">({{ role.guard_name }})</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
