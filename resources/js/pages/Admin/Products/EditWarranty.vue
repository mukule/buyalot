<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

// Extend Inertia PageProps with product and warranty info
interface PageProps extends InertiaPageProps {
    product: {
        id: number;
        hashid: string;
        slug: string;
        name: string;
    };
    warranty: {
        id: number;
        hashid: string;
        duration: number;
        description?: string;
        active: boolean;
    };
}

const page = usePage<PageProps>();
const product = page.props.product;
const warranty = page.props.warranty;

const title = 'Edit Warranty';
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Products', href: '/admin/products' },
    { title: product.name, href: `/admin/products/${product.slug}` },
    { title, href: '' },
];

// Form setup with existing warranty data
const form = useForm({
    product_id: product.id,
    duration: warranty.duration,
    description: warranty.description ?? '',
    active: warranty.active,
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link :href="`/admin/products`" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <!-- Form -->
                <form
                    @submit.prevent="form.put(route('admin.products.warranties.update', { product: product.hashid, warranty: warranty.hashid }))"
                    class="mt-2 space-y-4 px-4"
                >
                    <!-- Product Name (readonly) -->
                    <div>
                        <label for="product" class="mb-1 block font-semibold">Product</label>
                        <input
                            id="product"
                            type="text"
                            :value="product.name"
                            readonly
                            class="w-full cursor-not-allowed rounded border border-[color:var(--border)] bg-gray-100 px-3 py-2 focus:outline-none"
                        />
                    </div>

                    <!-- Duration -->
                    <div>
                        <label for="duration" class="mb-1 block font-semibold">Duration (months)</label>
                        <input
                            id="duration"
                            type="number"
                            min="1"
                            v-model="form.duration"
                            required
                            placeholder="Enter warranty duration"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.duration" class="mt-1 text-sm text-red-600">
                            {{ form.errors.duration }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="mb-1 block font-semibold">Description (optional)</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Enter details about the warranty"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                            {{ form.errors.description }}
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

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)]"
                    >
                        {{ form.processing ? 'Updating...' : 'Update' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
