<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, AppPageProps as InertiaPageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

interface Region {
    hashid: string;
    name: string;
    active: boolean | number;
    parent_id?: number | null;
    zone_id?: number | null;
}

interface Zone {
    id: number;
    name: string;
    tier: number;
}

const page = usePage<
    InertiaPageProps & {
        title: string;
        basePath: string;
        level: string;
        parents?: { id: number; name: string }[];
        zones?: Zone[];
        region: Region;
    }
>();

const title = page.props.title || 'Edit Region';
const basePath = page.props.basePath || '/admin/regions';
const level = page.props.level || 'region';
const parents = page.props.parents || [];
const zones = page.props.zones || [];
const region = page.props.region;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    {
        title: level === 'region' ? 'Regions' : level === 'subregion' ? 'Subregions' : level === 'area' ? 'Areas' : 'Routes',
        href: basePath,
    },
    { title, href: '' },
];

const form = useForm({
    name: region.name ?? '',
    parent_id: region.parent_id ?? null,
    zone_id: region.zone_id ?? null,
    active: Boolean(region.active),
});

function submitRegion() {
    form.put(`${basePath}/${region.hashid}`, {
        preserveScroll: true,
        onSuccess: () => router.get(basePath),
    });
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)] shadow-sm">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <router-link :href="basePath" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </router-link>
                </div>

                <hr class="border-[color:var(--border)]" />

                <form @submit.prevent="submitRegion" class="space-y-4">
                    <!-- Region Name -->
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700"> Region Name </label>
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

                    <!-- Zone Selector -->
                    <div v-if="zones.length">
                        <label for="zone" class="mb-1 block text-sm font-medium text-gray-700"> Zone </label>
                        <select
                            v-model="form.zone_id"
                            id="zone"
                            required
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option :value="null">Select Zone</option>
                            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }} (Tier: {{ z.tier }})</option>
                        </select>
                        <div v-if="form.errors.zone_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.zone_id }}
                        </div>
                    </div>

                    <!-- Parent Selector -->
                    <div v-if="parents.length">
                        <label for="parent" class="mb-1 block text-sm font-medium text-gray-700"> Parent Region </label>
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

                    <!-- Active -->
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
