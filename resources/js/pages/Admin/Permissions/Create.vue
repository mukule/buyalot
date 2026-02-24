<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<{
    modules?: string[];
}>();

const title = 'Create Permission';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Permissions', href: '/admin/permissions' },
    { title, href: '' },
];

const modules = page.props.modules ?? [];

const form = useForm({
    name: '',
    guard_name: 'web',
    module: '',
    description: '',
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/permissions" class="text-sm text-[color:var(--primary)] hover:underline">← Back</Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="form.post('/admin/permissions')" class="mt-2 space-y-4 px-4">
                    <!-- Permission Name -->
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Permission Name *</label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="e.g. view-users, create-products"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Guard Name -->
                    <div>
                        <label for="guard_name" class="mb-1 block text-sm font-medium text-gray-700">Guard Name</label>
                        <select
                            v-model="form.guard_name"
                            id="guard_name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="web">web</option>
                            <option value="api">api</option>
                        </select>
                        <div v-if="form.errors.guard_name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.guard_name }}
                        </div>
                    </div>

                    <!-- Module -->
                    <div>
                        <label for="module" class="mb-1 block text-sm font-medium text-gray-700">Module</label>
                        <input
                            v-model="form.module"
                            id="module"
                            type="text"
                            list="module-suggestions"
                            placeholder="e.g. users, products, roles"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <datalist v-if="modules.length" id="module-suggestions">
                            <option v-for="m in modules" :key="m" :value="m" />
                        </datalist>
                        <div v-if="form.errors.module" class="mt-1 text-sm text-red-600">
                            {{ form.errors.module }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            v-model="form.description"
                            id="description"
                            rows="3"
                            placeholder="Brief description of what this permission allows"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)]"
                    >
                        {{ form.processing ? 'Submitting...' : 'Create Permission' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
