<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Permission } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const title = 'Create Role';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title, href: '' },
];

// Props: pass in all available permissions for checkbox assignment
const props = defineProps<{
    permissions: Permission[];
}>();

const form = useForm({
    name: '',
    permissions: [] as number[],  // selected permission IDs
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
            <div class="flex items-center justify-between">
                <h4 class="text-2xl font-bold">{{ title }}</h4>
                <Link href="/admin/roles" class="text-sm text-[color:var(--primary)] hover:underline">← Back</Link>
            </div>

            <hr class="my-1 border-[color:var(--border)]" />

            <form @submit.prevent="form.post('/admin/roles')" class="mt-2 space-y-4 px-4">
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
                <div>
                    <label class="block mb-2 font-semibold">Permissions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-72 overflow-auto rounded border border-[color:var(--border)] p-3">
                        <label
                          v-for="permission in props.permissions"
                          :key="permission.id"
                          class="inline-flex items-center space-x-2 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="permission.id"
                                v-model="form.permissions"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <span class="text-gray-800">{{ permission.name }}</span>
                        </label>
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
                    {{ form.processing ? 'Creating...' : 'Create Role' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
