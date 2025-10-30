<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, AppPageProps as InertiaPageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<
    InertiaPageProps & {
        title: string;
    }
>();

const title = page.props.title || 'Create Zone';
const basePath = '/admin/zones';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Zones', href: basePath },
    { title, href: '' },
];

// Zone form data
const form = useForm({
    name: '',
    tier: 1,
    is_default_origin: false,
});

function submitZone() {
    form.post(basePath, {
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
                    <router-link href="/admin/zones" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </router-link>
                </div>

                <hr class="border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="submitZone" class="space-y-4">
                    <!-- Zone Name -->
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700"> Zone Name </label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter zone name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Tier -->
                    <div>
                        <label for="tier" class="mb-1 block text-sm font-medium text-gray-700"> Tier Level </label>
                        <input
                            v-model="form.tier"
                            id="tier"
                            type="number"
                            min="1"
                            placeholder="e.g. 1"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <p class="mt-1 text-xs text-gray-500">Tier 1 = Default zone, higher numbers = farther zones.</p>
                        <div v-if="form.errors.tier" class="mt-1 text-sm text-red-600">
                            {{ form.errors.tier }}
                        </div>
                    </div>

                    <!-- Default Origin Checkbox -->
                    <div class="flex items-center">
                        <input
                            v-model="form.is_default_origin"
                            id="default_origin"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                        />
                        <label for="default_origin" class="ml-2 text-sm text-gray-700"> Set as Default Origin Zone </label>
                    </div>

                    <div v-if="form.errors.is_default_origin" class="mt-1 text-sm text-red-600">
                        {{ form.errors.is_default_origin }}
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)]"
                    >
                        {{ form.processing ? 'Submitting...' : 'Create Zone' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
