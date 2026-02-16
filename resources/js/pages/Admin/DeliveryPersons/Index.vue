<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface DeliveryPerson {
    id: number;
    application_id: number | null;
    name: string;
    email: string;
    phone: string | null;
    transport_type: string | null;
    transport_registration_number: string | null;
    status: string;
    suspended?: boolean;
    created_at: string;
}

const page = usePage<
    AppPageProps<{
        deliveryPersons: {
            data: DeliveryPerson[];
            prev_page_url: string | null;
            next_page_url: string | null;
            current_page: number;
            last_page: number;
            total: number;
        };
        pendingCount: number;
        filters: { search?: string; status?: string };
    }>
>();

const deliveryPersons = computed(() => page.props.deliveryPersons.data);
const pendingCount = computed(() => page.props.pendingCount ?? 0);
const search = ref(page.props.filters?.search ?? '');
const statusFilter = ref(page.props.filters?.status ?? '');
const dropdownId = ref<number | null>(null);
const dropdownStyle = ref<{ top: string; left: string }>({ top: '0', left: '0' });

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Delivery Persons', href: '/admin/delivery-persons' },
];

function goToShow(applicationId: number) {
    router.get(route('admin.delivery-persons.show', applicationId));
}

function statusLabel(status: string, suspended?: boolean): string {
    if (suspended) return 'Suspended';
    const s = (status || '').toLowerCase();
    if (s === 'active') return 'Active';
    if (s === 'inactive') return 'Inactive';
    return status || 'Unknown';
}

function statusBadgeClass(status: string, suspended?: boolean): string {
    if (suspended) return 'bg-red-100 text-red-800';
    const s = (status || '').toLowerCase();
    if (s === 'active') return 'bg-green-100 text-green-800';
    if (s === 'inactive') return 'bg-gray-100 text-gray-800';
    return 'bg-gray-100 text-gray-800';
}

const currentItem = computed(() => deliveryPersons.value.find((p) => p.id === dropdownId.value) ?? null);

function goToPreviousPage() {
    if (page.props.deliveryPersons.prev_page_url) {
        router.visit(page.props.deliveryPersons.prev_page_url, { preserveState: true, replace: true });
    }
}

function goToNextPage() {
    if (page.props.deliveryPersons.next_page_url) {
        router.visit(page.props.deliveryPersons.next_page_url, { preserveState: true, replace: true });
    }
}

function applyFilters() {
    router.get(route('admin.delivery-persons.index'), { search: search.value || undefined, status: statusFilter.value || undefined }, { preserveState: true, replace: true });
}

let searchTimeout: number | undefined;
watch([search, statusFilter], () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(applyFilters, 400);
});
</script>

<template>
    <Head title="Delivery team" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold">Delivery team</h1>
                        <a
                            :href="route('admin.delivery-persons.applications.pending')"
                            class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-100"
                        >
                            Pending applications
                            <span v-if="pendingCount > 0" class="ml-1.5 rounded-full bg-amber-200 px-2 py-0.5 text-xs">{{ pendingCount }}</span>
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search name, email, phone..."
                            class="w-full max-w-xs rounded border border-gray-300 p-2 text-sm sm:w-auto"
                        />
                        <select
                            v-model="statusFilter"
                            class="rounded border border-gray-300 p-2 text-sm"
                        >
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Email</th>
                                <th class="px-4 py-3 text-left">Phone</th>
                                <th class="px-4 py-3 text-left">Transport</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Joined</th>
                                <th class="px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(person, index) in deliveryPersons" :key="person.id">
                                <td class="px-4 py-3">{{ (page.props.deliveryPersons.current_page - 1) * 15 + index + 1 }}</td>
                                <td
                                    v-if="person.application_id"
                                    class="cursor-pointer px-4 py-3 font-medium text-primary hover:underline"
                                    @click="goToShow(person.application_id!)"
                                >
                                    {{ person.name }}
                                </td>
                                <td v-else class="px-4 py-3 font-medium">{{ person.name }}</td>
                                <td class="px-4 py-3">{{ person.email }}</td>
                                <td class="px-4 py-3">{{ person.phone ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    {{ (person.transport_type || '').replace(/_/g, ' ') }}
                                    <span v-if="person.transport_registration_number" class="text-muted-foreground">({{ person.transport_registration_number }})</span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded px-2 py-0.5 text-xs font-medium', statusBadgeClass(person.status, person.suspended)]">
                                        {{ statusLabel(person.status, person.suspended) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ person.created_at ? new Date(person.created_at).toLocaleDateString() : '-' }}</td>
                                    <td class="px-4 py-3">
                                        <button
                                            v-if="person.application_id"
                                            type="button"
                                            class="inline-flex items-center rounded-md px-2 py-1 text-sm text-gray-700 hover:bg-gray-50"
                                            @click="(e) => { const el = (e.currentTarget as HTMLElement); const r = el.getBoundingClientRect(); dropdownStyle = { top: `${r.bottom + 4}px`, left: `${r.left}px` }; dropdownId = person.id; (e as MouseEvent).stopPropagation(); }"
                                        >
                                            Actions
                                            <svg class="ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.58l3.71-4.35a.75.75 0 111.14.98l-4.25 5a.75.75 0 01-1.14 0l-4.25-5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="deliveryPersons.length === 0" class="py-8 text-center text-muted-foreground">
                    No delivery team members found. <a v-if="pendingCount > 0" :href="route('admin.delivery-persons.applications.pending')" class="font-medium underline">Review pending applications</a> to add them.
                </div>

                <div class="mt-4 flex justify-end gap-4">
                    <button
                        type="button"
                        :disabled="!page.props.deliveryPersons.prev_page_url"
                        class="rounded border px-4 py-2 hover:bg-primary/10 disabled:opacity-50"
                        @click="goToPreviousPage"
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        :disabled="!page.props.deliveryPersons.next_page_url"
                        class="rounded border px-4 py-2 hover:bg-primary/10 disabled:opacity-50"
                        @click="goToNextPage"
                    >
                        Next
                    </button>
                </div>
            </div>

            <Teleport to="body">
                <div
                    v-if="dropdownId !== null"
                    class="fixed z-[9999] w-40 rounded-md border bg-white shadow-lg"
                    :style="{ top: dropdownStyle.top, left: dropdownStyle.left }"
                    @click.outside="dropdownId = null"
                >
                    <ul class="divide-y divide-gray-200 py-1 text-sm">
                        <template v-if="currentItem && currentItem.application_id">
                            <li>
                                <button
                                    type="button"
                                    class="w-full px-4 py-2 text-left hover:bg-gray-50"
                                    @click="goToShow(currentItem.application_id); dropdownId = null"
                                >
                                    View details
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>

<style scoped>
table,
thead,
tbody,
tr,
th,
td {
    border: none !important;
}
tbody tr:hover {
    background-color: #f9fafb;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
