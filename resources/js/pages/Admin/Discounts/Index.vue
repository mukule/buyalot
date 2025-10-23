<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Discount {
    id: number;
    slug: string | null;
    name: string;
    code?: string | null;
    type: string;
    value?: number | null;
    is_active: boolean;
    starts_at?: string | null;
    expires_at?: string | null;
}

const page = usePage();
const discounts = (page.props as any).discounts as {
    data: Discount[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};
const filters = (page.props as any).filters as { search?: string };
const search = ref(filters?.search || '');
const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Discounts', href: route('admin.discounts.index') },
];
const onSearch = () => {
    router.get(route('admin.discounts.index'), { search: search.value }, { preserveState: true, preserveScroll: true });
};

const goCreate = () => router.visit(route('admin.discounts.create'));
const editItem = (d: Discount) => router.visit(route('admin.discounts.edit', { discount: d.id }));

const deleteItem = (d: Discount) => {
    if (!confirm('Delete this discount?')) return;
    router.delete(route('admin.discounts.destroy', { discount: d.id }));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Promotions</h1>
                    <button @click="goCreate" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">Create Promotions</button>
                </div>

                <div class="mb-4 flex gap-2">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name, code, type"
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                    />
                    <button @click="onSearch" class="rounded bg-gray-800 px-4 py-2 text-white hover:bg-black">Search</button>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Value</th>
                                <th class="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Active</th>
                                <th class="px-4 py-2 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="d in discounts.data" :key="d.id">
                                <td class="px-4 py-2">{{ d.name }}</td>
                                <td class="px-4 py-2">{{ d.code || '-' }}</td>
                                <td class="px-4 py-2">{{ d.type }}</td>
                                <td class="px-4 py-2">{{ d.value ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span :class="d.is_active ? 'text-green-700' : 'text-gray-500'">{{ d.is_active ? 'Yes' : 'No' }}</span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button @click="editItem(d)" class="mr-2 rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">Edit</button>
                                    <button @click="deleteItem(d)" class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <template v-for="(lnk, idx) in discounts.links" :key="idx">
                        <button
                            v-if="lnk.url"
                            @click="router.visit(lnk.url, { preserveState: true, preserveScroll: true })"
                            class="rounded px-3 py-1 text-sm"
                            :class="lnk.active ? 'bg-primary text-white' : 'border bg-white text-gray-700'"
                            v-html="lnk.label"
                        />
                        <span v-else class="rounded border bg-gray-100 px-3 py-1 text-sm text-gray-400" v-html="lnk.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
