<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { PageProps as InertiaPageProps } from '@inertiajs/core';

interface PageProps extends InertiaPageProps {
    categories: Category[];
}

const page = usePage<PageProps>();
const allCategories = page.props.categories || [];

const title = 'Create Category';
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Categories', href: '/admin/categories' },
    { title, href: '' },
];

// Form
const form = useForm({
    name: '',
    active: true,
    parent_id: '' as number | '',
});

// Parent category search
const parentSearch = ref('');
const filteredParents = computed(() => {
    if (!parentSearch.value) return allCategories.slice(0, 5);
    return allCategories.filter((c) => c.name.toLowerCase().includes(parentSearch.value.toLowerCase())).slice(0, 5);
});

// Reset parent_id if not in filtered list
watch(parentSearch, () => {
    if (!filteredParents.value.find((c) => c.id === form.parent_id)) {
        form.parent_id = '';
    }
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/categories" class="text-sm text-[color:var(--primary)] hover:underline">← Back</Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <form @submit.prevent="form.post('/admin/categories')" class="mt-2 space-y-4 px-4">
                    <!-- Category Name -->
                    <div>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter category name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <!-- Parent Category -->
                    <div>
                        <label for="parent" class="mb-1 block font-semibold">Parent Category (optional)</label>
                        <input
                            id="parent"
                            type="text"
                            v-model="parentSearch"
                            placeholder="Search parent category"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <select
                            v-model="form.parent_id"
                            class="mt-1 w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="">-- Select parent --</option>
                            <option v-for="parent in filteredParents" :key="parent.id" :value="parent.id">
                                {{ parent.name }}
                            </option>
                        </select>
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
