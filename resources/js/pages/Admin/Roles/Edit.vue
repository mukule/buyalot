<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Permission, type Role } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    role: Role & { permissions: Permission[]; hashid: string };
    permissions: Permission[];
}>();

const title = 'Edit Role';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title, href: '' },
];

// Initialize form with role data
const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions.map((p) => p.id),
});

const searchQuery = ref('');

// Group by `permission.module`
const groupedPermissions = computed(() => {
    const filtered = props.permissions.filter((permission) =>
        permission.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );

    // Group by the 'module' property from the database
    return filtered.reduce(
        (groups, permission) => {
            const groupName = permission.module || 'general';
            if (!groups[groupName]) {
                groups[groupName] = [];
            }
            groups[groupName].push(permission);
            return groups;
        },
        {} as Record<string, Permission[]>,
    );
});

// Check if all permissions in a group are selected
function isGroupFullySelected(groupPermissions: Permission[]): boolean {
    return groupPermissions.every(permission => form.permissions.includes(permission.id));
}

// Check if some permissions in a group are selected (for indeterminate state)
function isGroupPartiallySelected(groupPermissions: Permission[]): boolean {
    const selectedCount = groupPermissions.filter(permission =>
        form.permissions.includes(permission.id)
    ).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
}

// Toggle all permissions in a group
function toggleGroupPermissions(groupPermissions: Permission[]): void {
    const groupPermissionIds = groupPermissions.map(p => p.id);

    if (isGroupFullySelected(groupPermissions)) {
        // Uncheck all permissions in this group
        form.permissions = form.permissions.filter(id => !groupPermissionIds.includes(id));
    } else {
        // Check all permissions in this group
        const newPermissions = [...form.permissions];
        groupPermissionIds.forEach(id => {
            if (!newPermissions.includes(id)) {
                newPermissions.push(id);
            }
        });
        form.permissions = newPermissions;
    }
}

function formatGroupName(name: string): string {
    return name
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function submit() {
    form.put(`/admin/roles/${props.role.hashid}`);
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/roles" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="submit" class="mt-2 space-y-4 px-4">
                    <!-- Role Name -->
                    <div>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter role name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Permissions Checkbox List -->
                    <div class="space-y-3">
                        <label class="mb-2 block font-semibold">Permissions</label>

                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search permissions..."
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[color:var(--primary)]"
                        />

                        <div class="max-h-96 space-y-4 overflow-y-auto rounded border border-[color:var(--border)] p-4">
                            <div
                                v-for="(permissionsInGroup, groupName) in groupedPermissions"
                                :key="groupName"
                                class="space-y-3"
                            >
                                <!-- Group Header with Select All Checkbox -->
                                <div class="flex items-center space-x-3 border-b border-gray-200 pb-2">
                                    <label class="inline-flex cursor-pointer items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            :checked="isGroupFullySelected(permissionsInGroup)"
                                            :indeterminate="isGroupPartiallySelected(permissionsInGroup)"
                                            @change="toggleGroupPermissions(permissionsInGroup)"
                                            class="rounded border-gray-300 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                        />
                                        <h5 class="font-semibold text-gray-800">
                                            {{ formatGroupName(groupName) }}
                                        </h5>
                                    </label>
                                    <span class="text-xs text-gray-500">
                                        ({{ permissionsInGroup.filter(p => form.permissions.includes(p.id)).length }}/{{ permissionsInGroup.length }})
                                    </span>
                                </div>

                                <!-- Individual Permissions -->
                                <div class="ml-6 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 md:grid-cols-3">
                                    <label
                                        v-for="permission in permissionsInGroup"
                                        :key="permission.id"
                                        class="inline-flex cursor-pointer items-center space-x-2"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="permission.id"
                                            v-model="form.permissions"
                                            class="rounded border-gray-300 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                        />
                                        <span class="text-sm text-gray-700">{{ permission.name }}</span>
                                    </label>
                                </div>
                            </div>
                            <div v-if="Object.keys(groupedPermissions).length === 0" class="text-center text-gray-500">
                                No permissions found.
                            </div>
                        </div>
                        <div v-if="form.errors.permissions" class="mt-1 text-sm text-red-600">
                            {{ form.errors.permissions }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)]"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Role' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
