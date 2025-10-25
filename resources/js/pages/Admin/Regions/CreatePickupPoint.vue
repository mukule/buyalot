<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, AppPageProps as InertiaPageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

interface Region {
    id: number;
    hashid: string;
    name: string;
}

// Props coming from controller
const page = usePage<
    InertiaPageProps & {
        title: string;
        region: Region;
    }
>();

const region = page.props.region;
const title = page.props.title || `Add Pickup Point for ${region.name}`;
const basePath = `/admin/regions/${region.hashid}/pickup-points`;

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Regions', href: '/admin/regions' },
    { title: region.name, href: `/admin/regions/${region.hashid}` },
    { title, href: '' },
];

// Form
const form = useForm({
    name: '',
    description: '',
    address: '',
    contact_phone: '',
    contact_email: '',
    is_active: true,
});

// Submit
function submitPickupPoint() {
    form.post(basePath, {
        onSuccess: () => router.get(`/admin/regions/${region.hashid}`),
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
                    <router-link :href="`/admin/regions/${region.hashid}`" class="text-sm text-[color:var(--primary)] hover:underline">
                        ← Back
                    </router-link>
                </div>

                <hr class="border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="submitPickupPoint" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Pickup Point Name</label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter pickup point name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            v-model="form.description"
                            id="description"
                            placeholder="Optional description"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="mb-1 block text-sm font-medium text-gray-700">Address</label>
                        <input
                            v-model="form.address"
                            id="address"
                            type="text"
                            placeholder="Google Map Url"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
                    </div>

                    <!-- Contact Phone -->
                    <div>
                        <label for="contact_phone" class="mb-1 block text-sm font-medium text-gray-700">Contact Phone</label>
                        <input
                            v-model="form.contact_phone"
                            id="contact_phone"
                            type="text"
                            placeholder="Enter contact phone"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</div>
                    </div>

                    <!-- Contact Email -->
                    <div>
                        <label for="contact_email" class="mb-1 block text-sm font-medium text-gray-700">Contact Email</label>
                        <input
                            v-model="form.contact_email"
                            id="contact_email"
                            type="email"
                            placeholder="Enter contact email"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_email }}</div>
                    </div>

                    <!-- Active -->
                    <div class="flex items-center space-x-2">
                        <input
                            id="is_active"
                            type="checkbox"
                            v-model="form.is_active"
                            :true-value="true"
                            :false-value="false"
                            class="h-4 w-4 rounded border border-gray-300 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                        />
                        <label for="is_active" class="text-sm select-none"> Active </label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)]"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
