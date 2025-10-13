<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

interface Region {
    hashid?: string;
    name: string;
    active: boolean;
}

// Extend default Inertia PageProps to include your regions
interface PageProps extends InertiaPageProps {
    regions?: Region[];
}

const page = usePage<PageProps>();

const page = usePage<{ title: string; level: string; basePath: string; parents?: { id: number; name: string }[] }>();
const title = page.props.title || 'Create Region';
const level = page.props.level || 'region';
const basePath = page.props.basePath || '/admin/regions';
const parents = page.props.parents || [];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: level === 'region' ? 'Regions' : level === 'subregion' ? 'Subregions' : level === 'area' ? 'Areas' : 'Routes', href: basePath },
    { title, href: '' },
];

// Form for creating a region
const form = useForm({
    name: '',
    parent_id: parents.length ? parents[0].id : null,
});

// Submit handler
function submitRegion() {
    form.post(basePath, {
        onSuccess: () => {
            router.get(basePath);
        },
    });
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <router-link href="/admin/regions" class="text-sm text-[color:var(--primary)] hover:underline">← Back</router-link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="submitRegion" class="mt-2 space-y-4 px-4">
                    <!-- Region Name -->
                    <div>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <!-- Parent Selector (for sub-levels) -->
                    <div v-if="parents.length" class="mt-2">
                        <label class="mb-1 block text-sm font-medium">Parent {{ level === 'subregion' ? 'Region' : level === 'area' ? 'Subregion' : 'Area' }}</label>
                        <select v-model="form.parent_id" class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none">
                            <option v-for="p in parents" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <div v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">{{ form.errors.parent_id }}</div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)]"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
