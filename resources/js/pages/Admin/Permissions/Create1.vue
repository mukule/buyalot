<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Permission } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    permission?: Permission & { hashid: string };
    modules: string[];
}

const page = usePage<AppPageProps<Props>>();
const props = computed(() => page.props);

const isEditing = computed(() => !!props.value.permission);

const form = useForm({
    name: props.value.permission?.name || '',
    module: props.value.permission?.module || '',
    description: props.value.permission?.description || '',
    guard_name: props.value.permission?.guard_name || 'web',
});

const breadcrumbs = computed(() => [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Access Rights', href: '#' },
    { title: 'Permissions', href: '/admin/permissions' },
    {
        title: isEditing.value ? 'Edit Permission' : 'Create Permission',
        href: '#',
    },
]);

const commonPermissionTemplates = [
    { name: 'view-{module}', description: 'View {module} records' },
    { name: 'create-{module}', description: 'Create new {module}' },
    { name: 'edit-{module}', description: 'Edit existing {module}' },
    { name: 'delete-{module}', description: 'Delete {module} records' },
    { name: 'manage-{module}', description: 'Full management of {module}' },
];

function submit() {
    if (isEditing.value) {
        form.put(route('admin.permissions.update', props.value.permission!.hashid), {
            onSuccess: () => {
                // Handle success
            },
        });
    } else {
        form.post(route('admin.permissions.store'), {
            onSuccess: () => {
                // Handle success
            },
        });
    }
}

function useTemplate(template: { name: string; description: string }) {
    const module = form.module || 'resource';
    form.name = template.name.replace('{module}', module);
    form.description = template.description.replace('{module}', module);
}

function generatePermissionName() {
    if (form.module) {
        const baseName = form.name.replace(/^(view|create|edit|delete|manage)-.*$/, '');
        if (baseName && !baseName.includes(form.module)) {
            form.name = `${baseName}-${form.module}`;
        }
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Permission' : 'Create Permission'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card rounded-lg bg-white p-6 shadow-sm">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold">
                        {{ isEditing ? 'Edit Permission' : 'Create New Permission' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ isEditing ? 'Update permission details and settings' : 'Define a new permission for the system' }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Permission Templates (only for create) -->
                    <div v-if="!isEditing" class="rounded-lg bg-blue-50 p-4">
                        <h3 class="mb-3 text-sm font-medium text-blue-900">Quick Templates</h3>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="template in commonPermissionTemplates"
                                :key="template.name"
                                type="button"
                                @click="useTemplate(template)"
                                class="rounded border border-blue-200 p-2 text-left transition-colors hover:border-blue-300 hover:bg-blue-100"
                            >
                                <div class="text-sm font-medium text-blue-900">{{ template.name }}</div>
                                <div class="text-xs text-blue-700">{{ template.description }}</div>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Permission Name -->
                            <div>
                                <label for="name" class="mb-1 block text-sm font-medium text-gray-700"> Permission Name * </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g., view-users, create-products"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Use kebab-case (e.g., view-users, manage-inventory). This will be used in code.
                                </p>
                            </div>

                            <!-- Module -->
                            <div>
                                <label for="module" class="mb-1 block text-sm font-medium text-gray-700"> Module </label>
                                <div class="flex gap-2">
                                    <select
                                        id="module"
                                        v-model="form.module"
                                        @change="generatePermissionName"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.module }"
                                    >
                                        <option value="">Select or enter module</option>
                                        <option v-for="module in modules" :key="module" :value="module">
                                            {{ module.charAt(0).toUpperCase() + module.slice(1) }}
                                        </option>
                                    </select>
                                </div>
                                <p v-if="form.errors.module" class="mt-1 text-sm text-red-600">{{ form.errors.module }}</p>
                                <p class="mt-1 text-xs text-gray-500">Group related permissions together (e.g., users, products, orders).</p>
                            </div>

                            <!-- Custom Module Input -->
                            <div v-if="!modules.includes(form.module) || form.module === ''">
                                <label for="custom-module" class="mb-1 block text-sm font-medium text-gray-700"> Custom Module Name </label>
                                <input
                                    id="custom-module"
                                    v-model="form.module"
                                    type="text"
                                    placeholder="Enter new module name"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">Enter a new module name if it doesn't exist in the list.</p>
                            </div>

                            <!-- Guard Name -->
                            <div>
                                <label for="guard_name" class="mb-1 block text-sm font-medium text-gray-700"> Guard Name </label>
                                <select
                                    id="guard_name"
                                    v-model="form.guard_name"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.guard_name }"
                                >
                                    <option value="web">Web</option>
                                    <option value="api">API</option>
                                </select>
                                <p v-if="form.errors.guard_name" class="mt-1 text-sm text-red-600">{{ form.errors.guard_name }}</p>
                                <p class="mt-1 text-xs text-gray-500">Choose 'web' for web interface permissions, 'api' for API-only permissions.</p>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Description -->
                            <div>
                                <label for="description" class="mb-1 block text-sm font-medium text-gray-700"> Description </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Describe what this permission allows users to do..."
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.description }"
                                ></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                                <p class="mt-1 text-xs text-gray-500">Provide a clear description of what this permission grants access to.</p>
                            </div>

                            <!-- Permission Preview -->
                            <div class="rounded-lg bg-gray-50 p-4">
                                <h3 class="mb-3 text-sm font-medium text-gray-900">Permission Preview</h3>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Name:</span>
                                        <span class="text-sm font-medium">{{ form.name || 'Not set' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Module:</span>
                                        <span class="text-sm font-medium capitalize">{{ form.module || 'None' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Guard:</span>
                                        <span class="text-sm font-medium">{{ form.guard_name }}</span>
                                    </div>
                                    <div v-if="form.description" class="border-t border-gray-200 pt-2">
                                        <span class="text-sm text-gray-600">Description:</span>
                                        <p class="mt-1 text-sm text-gray-900">{{ form.description }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Best Practices -->
                            <div class="rounded-lg bg-green-50 p-4">
                                <h3 class="mb-2 text-sm font-medium text-green-900">Best Practices</h3>
                                <ul class="space-y-1 text-xs text-green-800">
                                    <li>• Use descriptive, action-based names (view, create, edit, delete)</li>
                                    <li>• Keep permissions granular for better control</li>
                                    <li>• Group related permissions in the same module</li>
                                    <li>• Write clear descriptions for easy understanding</li>
                                    <li>• Follow consistent naming conventions</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                        <!--                        <button-->
                        <!--                            type="button"-->
                        <!--                            @click="$inertia.visit(route('admin.permissions.index'))"-->
                        <!--                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"-->
                        <!--                        >-->
                        <!--                            Cancel-->
                        <!--                        </button>-->

                        <div class="flex items-center gap-3">
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="form.reset()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
                            >
                                Reset Form
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                :class="[
                                    'rounded-lg px-4 py-2 text-sm font-medium text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none',
                                    form.processing ? 'cursor-not-allowed bg-gray-400' : 'hover:bg-primary-dark bg-primary',
                                ]"
                            >
                                {{
                                    form.processing
                                        ? isEditing
                                            ? 'Updating...'
                                            : 'Creating...'
                                        : isEditing
                                          ? 'Update Permission'
                                          : 'Create Permission'
                                }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
