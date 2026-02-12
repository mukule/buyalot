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
    created_at: string;
}

interface PendingApplication {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    transport_type: string;
    transport_registration_number: string | null;
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
        pendingApplications: PendingApplication[];
        filters: { search?: string; status?: string };
    }>
>();

const deliveryPersons = computed(() => page.props.deliveryPersons.data);
const pendingApplications = computed(() => page.props.pendingApplications ?? []);
const search = ref(page.props.filters?.search ?? '');
const statusFilter = ref(page.props.filters?.status ?? '');
const dropdownId = ref<number | null>(null);
const dropdownIsPending = ref(false);
const dropdownStyle = ref<{ top: string; left: string }>({ top: '0', left: '0' });

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Delivery Persons', href: '/admin/delivery-persons' },
];

function goToShow(applicationId: number) {
    router.get(route('admin.delivery-persons.show', applicationId));
}

function statusLabel(status: string): string {
    const s = (status || '').toLowerCase();
    if (s === 'active') return 'Active';
    if (s === 'inactive') return 'Inactive';
    return status || 'Unknown';
}

function statusBadgeClass(status: string): string {
    const s = (status || '').toLowerCase();
    if (s === 'active') return 'bg-green-100 text-green-800';
    if (s === 'inactive') return 'bg-gray-100 text-gray-800';
    return 'bg-gray-100 text-gray-800';
}

const currentItem = computed(() => {
    if (dropdownIsPending.value) {
        return pendingApplications.value.find((a) => a.id === dropdownId.value) ?? null;
    }
    return deliveryPersons.value.find((a) => a.id === dropdownId.value) ?? null;
});

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

function approveApplication(applicationId: number) {
    if (confirm('Approve this delivery person? They will be able to log in to the delivery portal.')) {
        router.put(route('admin.delivery-persons.approve', applicationId), {});
    }
}

const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectErrors = ref<string | null>(null);
const rejectTargetId = ref<number | null>(null);
const submittingReject = ref(false);

function openRejectModal(applicationId: number) {
    rejectTargetId.value = applicationId;
    rejectReason.value = '';
    rejectErrors.value = null;
    showRejectModal.value = true;
}

function closeRejectModal() {
    showRejectModal.value = false;
    rejectTargetId.value = null;
    rejectReason.value = '';
    rejectErrors.value = null;
}

function submitReject() {
    if (rejectTargetId.value == null) return;
    submittingReject.value = true;
    router.put(
        route('admin.delivery-persons.reject', rejectTargetId.value),
        { reason: rejectReason.value },
        {
            onSuccess: () => closeRejectModal(),
            onError: (errors) => {
                rejectErrors.value = (errors as Record<string, string[]>).reason?.[0] || 'Please provide a valid reason.';
            },
            onFinish: () => {
                submittingReject.value = false;
            },
        }
    );
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
    <Head title="Delivery Persons" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl font-semibold">Delivery Persons</h1>
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

                <!-- Pending applications -->
                <div v-if="pendingApplications.length > 0" class="rounded border border-amber-200 bg-amber-50/50 p-4">
                    <h2 class="mb-3 text-lg font-medium text-amber-800">Pending applications ({{ pendingApplications.length }})</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto divide-y divide-amber-200 text-sm">
                            <thead class="bg-amber-100">
                                <tr>
                                    <th class="px-4 py-2 text-left">Name</th>
                                    <th class="px-4 py-2 text-left">Email</th>
                                    <th class="px-4 py-2 text-left">Phone</th>
                                    <th class="px-4 py-2 text-left">Transport</th>
                                    <th class="px-4 py-2 text-left">Applied</th>
                                    <th class="px-4 py-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="app in pendingApplications" :key="app.id">
                                    <td
                                        class="cursor-pointer px-4 py-2 font-medium text-primary hover:underline"
                                        @click="goToShow(app.id)"
                                    >
                                        {{ app.name }}
                                    </td>
                                    <td class="px-4 py-2">{{ app.email }}</td>
                                    <td class="px-4 py-2">{{ app.phone ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        {{ (app.transport_type || '').replace(/_/g, ' ') }}
                                        <span v-if="app.transport_registration_number" class="text-muted-foreground">({{ app.transport_registration_number }})</span>
                                    </td>
                                    <td class="px-4 py-2">{{ app.created_at ? new Date(app.created_at).toLocaleDateString() : '-' }}</td>
                                    <td class="px-4 py-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md px-2 py-1 text-sm text-gray-700 hover:bg-gray-50"
                                            @click="(e) => { const el = (e.currentTarget as HTMLElement); const r = el.getBoundingClientRect(); dropdownStyle = { top: `${r.bottom + 4}px`, left: `${r.left}px` }; dropdownId = app.id; dropdownIsPending = true; (e as MouseEvent).stopPropagation(); }"
                                        >
                                            Actions
                                            <svg class="ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.58l3.71-4.35a.75.75 0 111.14.98l-4.25 5a.75.75 0 01-1.14 0l-4.25-5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr />

                <h2 class="text-lg font-medium">Delivery team</h2>
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
                                    <span :class="['rounded px-2 py-0.5 text-xs font-medium', statusBadgeClass(person.status)]">
                                        {{ statusLabel(person.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ person.created_at ? new Date(person.created_at).toLocaleDateString() : '-' }}</td>
                                <td class="px-4 py-3">
                                    <button
                                        v-if="person.application_id"
                                        type="button"
                                        class="inline-flex items-center rounded-md px-2 py-1 text-sm text-gray-700 hover:bg-gray-50"
                                        @click="(e) => { const el = (e.currentTarget as HTMLElement); const r = el.getBoundingClientRect(); dropdownStyle = { top: `${r.bottom + 4}px`, left: `${r.left}px` }; dropdownId = person.id; dropdownIsPending = false; (e as MouseEvent).stopPropagation(); }"
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

                <div v-if="deliveryPersons.length === 0 && pendingApplications.length === 0" class="py-8 text-center text-muted-foreground">
                    No delivery persons found.
                </div>
                <div v-else-if="deliveryPersons.length === 0" class="py-4 text-center text-sm text-muted-foreground">
                    No users with delivery role yet. Approve pending applications to add them.
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
                        <template v-if="currentItem">
                            <li>
                                <button
                                    type="button"
                                    class="w-full px-4 py-2 text-left hover:bg-gray-50"
                                    @click="goToShow(dropdownIsPending ? (currentItem as PendingApplication).id : (currentItem as DeliveryPerson).application_id!); dropdownId = null"
                                >
                                    View details
                                </button>
                            </li>
                            <li v-if="dropdownIsPending">
                                <button
                                    type="button"
                                    class="w-full px-4 py-2 text-left text-green-700 hover:bg-green-50"
                                    @click="approveApplication((currentItem as PendingApplication).id); dropdownId = null"
                                >
                                    Approve
                                </button>
                            </li>
                            <li v-if="dropdownIsPending">
                                <button
                                    type="button"
                                    class="w-full px-4 py-2 text-left text-red-700 hover:bg-red-50"
                                    @click="openRejectModal((currentItem as PendingApplication).id); dropdownId = null"
                                >
                                    Reject
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </Teleport>

            <Transition name="fade">
                <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Reject Application</h3>
                        <p class="mb-2 text-sm text-gray-600">The applicant will receive an email with this reason.</p>
                        <textarea
                            v-model="rejectReason"
                            rows="4"
                            maxlength="1000"
                            placeholder="Enter rejection reason (required)"
                            class="w-full rounded border border-gray-300 p-2 text-sm"
                        />
                        <p v-if="rejectErrors" class="mt-2 text-sm text-red-600">{{ rejectErrors }}</p>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" class="rounded border border-gray-300 bg-gray-100 px-4 py-2 text-sm" @click="closeRejectModal">Cancel</button>
                            <button
                                type="button"
                                class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                                :disabled="submittingReject"
                                @click="submitReject"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
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
