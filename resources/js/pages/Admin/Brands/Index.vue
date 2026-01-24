<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Brand } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, PlusIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// Brand type with extra fields
interface BrandWithExtras extends Brand {
    hashid: string;
    logo_url?: string;
    active: boolean;
}

// Pagination types
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}

// Page props type — must extend Record<string, unknown> for TS
interface BrandPageProps extends Record<string, unknown> {
    brands?: PaginatedResponse<BrandWithExtras>;
    filters?: {
        search?: string;
        status?: string | number | null;
    };
}

// Use Inertia page props
const page = usePage<AppPageProps<BrandPageProps>>();

// Brands array (safe fallback)
const brands = computed(() => page.props.brands?.data || []);

// Safe pagination object
const pagination = computed(() => ({
    links: page.props.brands?.links || [],
    meta: page.props.brands?.meta || {
        current_page: 1,
        from: 0,
        last_page: 1,
        path: '',
        per_page: 10,
        to: 0,
        total: 0,
    },
}));

// Filters
const search = ref(page.props.filters?.search || '');
const status = ref(page.props.filters?.status ?? null);

// Watchers for reactive search & status
watch([search, status], () => {
    router.get(route('admin.brands.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
});

// Breadcrumbs
const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Brands', href: route('admin.brands.index') },
];

// CRUD actions
function createBrand() {
    router.get(route('admin.brands.create'));
}

function editBrand(hashid: string) {
    if (!hashid) return console.error('editBrand called without hashid');
    router.get(route('admin.brands.edit', { brand: hashid }));
}

function deleteBrand(hashid: string) {
    if (!hashid) return console.error('deleteBrand called without hashid');
    if (confirm('Are you sure you want to delete this brand?')) {
        router.delete(route('admin.brands.destroy', { brand: hashid }));
    }
}

// Status badge classes
const statusClasses = (active: boolean) => ({
    'text-green-600': active,
    'text-red-600': !active,
});

// Pagination navigation
function goToPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Brands" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header + Filters -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Brands</h1>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search brands..."
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                        />
                        <select
                            v-model="status"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring focus:ring-primary/30"
                        >
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <button @click="createBrand" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Brand</button>
                    </div>
                </div>

                <!-- Brand Table -->
                <div v-if="brands.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="(brand, index) in brands" :key="brand.hashid" class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    {{ index + 1 + (pagination.meta.current_page - 1) * pagination.meta.per_page }}
                                </td>
                                <td class="flex items-center space-x-3 px-4 py-4 text-sm font-medium text-primary">
                                    <img v-if="brand.logo_url" :src="brand.logo_url" alt="Logo" class="h-10 w-10 rounded bg-white object-contain" />
                                    <span>{{ brand.name }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span :class="statusClasses(brand.active)">
                                        {{ brand.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <button @click.stop="editBrand(brand.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>
                                    <button @click.stop="deleteBrand(brand.hashid)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav class="mt-4 flex justify-end space-x-2">
                        <button
                            @click="goToPage(pagination.links[0]?.url)"
                            :disabled="!pagination.links[0]?.url"
                            class="rounded border px-3 py-1 text-sm hover:bg-gray-200 disabled:opacity-50"
                        >
                            <ChevronLeft class="inline h-4 w-4" />
                        </button>

                        <button
                            v-for="link in pagination.links.slice(1, -1)"
                            :key="link.label"
                            @click="goToPage(link.url)"
                            :class="['rounded border px-3 py-1 text-sm hover:bg-gray-200', link.active ? 'bg-primary text-white' : '']"
                            v-html="link.label"
                        ></button>

                        <button
                            @click="goToPage(pagination.links[pagination.links.length - 1]?.url)"
                            :disabled="!pagination.links[pagination.links.length - 1]?.url"
                            class="rounded border px-3 py-1 text-sm hover:bg-gray-200 disabled:opacity-50"
                        >
                            <ChevronRight class="inline h-4 w-4" />
                        </button>
                    </nav>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No brands</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new brand.</p>
                        <div class="mt-6">
                            <button
                                @click="createBrand"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Brand
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
