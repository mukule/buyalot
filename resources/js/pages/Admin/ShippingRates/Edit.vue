<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, AppPageProps as InertiaPageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<
    InertiaPageProps & {
        title: string;
        rate: {
            id: number;
            hashid?: string;
            package_size: 'small' | 'medium' | 'large';
            base_price: number;
            door_price: number;
            express_price: number;
        };
    }
>();

const rate = page.props.rate;
const title = page.props.title || 'Edit Shipping Rate';
const basePath = '/admin/shipping-rates';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Shipping Rates', href: basePath },
    { title, href: '' },
];

// Shipping rate form
const form = useForm({
    package_size: rate.package_size,
    base_price: rate.base_price,
    door_price: rate.door_price,
});

function updateRate() {
    form.put(`${basePath}/${rate.id}`, {
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
                    <router-link href="/admin/shipping-rates" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </router-link>
                </div>

                <hr class="border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="updateRate" class="space-y-4">
                    <!-- Package Size -->
                    <div>
                        <label for="package_size" class="mb-1 block text-sm font-medium text-gray-700"> Package Size </label>
                        <select
                            v-model="form.package_size"
                            id="package_size"
                            required
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                        </select>
                        <div v-if="form.errors.package_size" class="mt-1 text-sm text-red-600">
                            {{ form.errors.package_size }}
                        </div>
                    </div>

                    <!-- Base Price -->
                    <div>
                        <label for="base_price" class="mb-1 block text-sm font-medium text-gray-700"> Standard/Base Price (Ksh) </label>
                        <input
                            v-model.number="form.base_price"
                            id="base_price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="e.g. 50.00"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.base_price" class="mt-1 text-sm text-red-600">
                            {{ form.errors.base_price }}
                        </div>
                    </div>

                    <!-- Door Delivery Price -->
                    <div>
                        <label for="door_price" class="mb-1 block text-sm font-medium text-gray-700"> Door Delivery Price (Ksh) </label>
                        <input
                            v-model.number="form.door_price"
                            id="door_price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="e.g. 70.00"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.door_price" class="mt-1 text-sm text-red-600">{{ form.errors.door_price }}</div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)]"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Shipping Rate' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
