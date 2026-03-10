<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SellerRow {
    id: number;
    first_name?: string | null;
    last_name?: string | null;
    company_legal_name?: string | null;
    business_type?: string | null;
    primary_product_category?: string | null;
    owner_email?: string | null;
    owner_phone?: string | null;
    is_active?: boolean;
    status?: number;
    created_at?: string;
}

const page = usePage();
const sellers = (page.props as any).sellers as {
    data: SellerRow[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const filters = (page.props as any).filters as { search?: string };
const search = ref(filters?.search || '');

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Vendors', href: route('admin.sellers.index') },
];

const onSearch = () => {
    router.get(route('admin.sellers.index'), { search: search.value }, { preserveState: true, preserveScroll: true });
};

const viewItem = (s: SellerRow) => router.visit(route('admin.sellers.show', s.id));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Vendors</h1>
                </div>

                <div class="mb-4 flex gap-2">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name, email, phone, company, products"
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                    />
                    <button type="button" @click="onSearch" class="rounded bg-gray-800 px-4 py-2 text-white hover:bg-black">Search</button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-lg bg-white shadow">
                    <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Company Name</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Name</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Business Type</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Primary Products</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Email</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Phone</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase">Status</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="s in sellers.data || []" :key="s.id" class="odd:bg-gray-50">
                                <td class="px-4 py-2 break-words">
                                    {{ s.company_legal_name || '-' }}
                                </td>

                                <td class="px-4 py-2 break-words">
                                    {{ (s.first_name || '') + (s.last_name ? ' ' + s.last_name : '') }}
                                </td>

                                <td class="px-4 py-2 break-words">
                                    {{ s.business_type || '-' }}
                                </td>

                                <td class="px-4 py-2 break-words">
                                    {{ s.primary_product_category || '-' }}
                                </td>

                                <td class="px-4 py-2 break-words">
                                    {{ s.owner_email || '-' }}
                                </td>

                                <td class="px-4 py-2 break-words">
                                    {{ s.owner_phone || '-' }}
                                </td>

                                <td class="px-4 py-2">
                                    <span
                                        :class="
                                            s.is_active
                                                ? 'rounded bg-green-100 px-2 py-1 text-green-700'
                                                : 'rounded bg-gray-100 px-2 py-1 text-gray-500'
                                        "
                                    >
                                        {{ s.is_active ? 'Approved' : 'Pending' }}
                                    </span>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <button type="button" @click="viewItem(s)" class="rounded px-3 py-1 text-green-700 hover:bg-green-100">
                                        View
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <template v-for="(lnk, idx) in sellers.links || []" :key="idx">
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
