<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
interface Status {
    name: string;
    label: string;
    color_class: string;
    hashid: string;
}

const status = page.props.status as Status; // existing product status
const title = 'Edit Product Status';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Product Statuses', href: '/admin/product-statuses' },
    { title, href: '' },
];

const form = useForm({
    name: status.name,
    label: status.label,
    color_class: status.color_class,
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/product-statuses" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="form.put(`/admin/product-statuses/${status.hashid}`)" class="mt-2 space-y-4 px-4">
                    <!-- Status Name -->
                    <div>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter status name (e.g., pending)"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Status Label -->
                    <div>
                        <input
                            v-model="form.label"
                            id="label"
                            type="text"
                            required
                            placeholder="Enter display label (e.g., Pending Approval)"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.label" class="mt-1 text-sm text-red-600">
                            {{ form.errors.label }}
                        </div>
                    </div>

                    <!-- Color Class -->
                    <div>
                        <input
                            v-model="form.color_class"
                            id="color_class"
                            type="text"
                            placeholder="Tailwind color class (e.g., bg-green-100 text-green-600)"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.color_class" class="mt-1 text-sm text-red-600">
                            {{ form.errors.color_class }}
                        </div>
                    </div>

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
s
