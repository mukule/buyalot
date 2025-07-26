<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem, UnitType } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<AppPageProps<{ unitType: UnitType }>>();
const unitType = page.props.unitType;

const title = `Create Unit for ${unitType.name}`;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Unit Types', href: '/admin/unit-types' },
    { title: unitType.name, href: `/admin/unit-types/${unitType.hashid}` },
    { title, href: '' },
];

const form = useForm({
    name: '',
    symbol: '',
    active: true,
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link :href="`/admin/unit-types/${unitType.hashid}`" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="form.post(route('admin.unit-types.units.store', { unit_type: unitType.hashid }))" class="mt-2 space-y-4 px-4">
                    <!-- Unit Name -->
                    <div>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter unit name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Unit Symbol -->
                    <div>
                        <input
                            v-model="form.symbol"
                            id="symbol"
                            type="text"
                            required
                            placeholder="Enter unit symbol"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.symbol" class="mt-1 text-sm text-red-600">
                            {{ form.errors.symbol }}
                        </div>
                    </div>

                    <!-- Active Checkbox -->
                    <div class="flex items-center space-x-2">
                        <input
                            id="active"
                            type="checkbox"
                            v-model="form.active"
                            class="h-4 w-4 rounded border border-gray-300 text-primary focus:ring-primary"
                        />
                        <label for="active" class="select-none">Active</label>
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
