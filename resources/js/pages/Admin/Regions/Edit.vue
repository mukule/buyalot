<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, AppPageProps as InertiaPageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

// Define Region interface
interface Region {
    hashid: string;
    name: string;
    active: boolean | number; // can come from backend as 1 or 0
    parent_id?: number | null;
}

// Extend AppPageProps with additional props used by this page
const page = usePage<
    InertiaPageProps & {
        title: string;
        basePath: string;
        level: string;
        parents?: { id: number; name: string }[];
        region: Region;
    }
>();

// Extract props
const title = page.props.title || 'Edit Region';
const basePath = page.props.basePath || '/admin/regions';
const level = page.props.level || 'region';
const parents = page.props.parents || [];
const region = page.props.region;

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    {
        title: level === 'region' ? 'Regions' : level === 'subregion' ? 'Subregions' : level === 'area' ? 'Areas' : 'Routes',
        href: basePath,
    },
    { title, href: '' },
];

// Initialize form with region data
const form = useForm({
    name: region.name || '',
    parent_id: region.parent_id ?? null,
    active: Boolean(region.active), // ✅ ensure boolean type
});

// Submit handler
function submitRegion() {
    form.put(`${basePath}/${region.hashid}`, {
        onSuccess: () => router.get(basePath),
    });
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)] shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <router-link href="/admin/regions" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </router-link>
                </div>

                <hr class="border-[color:var(--border)]" />

                <!-- Edit Form -->
                <form @submit.prevent="submitRegion" class="space-y-4">
                    <!-- Region Name -->
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Region Name</label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter region name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Parent Region Selector -->
                    <div v-if="parents.length">
                        <label for="parent" class="mb-1 block text-sm font-medium text-gray-700">Parent Region</label>
                        <select
                            v-model="form.parent_id"
                            id="parent"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option :value="null">No Parent</option>
                            <option v-for="p in parents" :key="p.id" :value="p.id">
                                {{ p.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.parent_id }}
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center space-x-2">
                        <input
                            id="active"
                            type="checkbox"
                            v-model="form.active"
                            :true-value="true"
                            :false-value="false"
                            class="h-4 w-4 rounded border border-gray-300 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                        />
                        <label for="active" class="text-sm select-none"> Active </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)]"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Region' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
