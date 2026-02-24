<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
        pendingApplications: PendingApplication[];
    }>
>();

const pendingApplications = computed(() => page.props.pendingApplications ?? []);
const dropdownId = ref<number | null>(null);
const dropdownStyle = ref<{ top: string; left: string }>({ top: '0', left: '0' });
const showApproveModal = ref(false);
const approveTargetId = ref<number | null>(null);
const submittingApprove = ref(false);
const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectErrors = ref<string | null>(null);
const rejectTargetId = ref<number | null>(null);
const submittingReject = ref(false);

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Delivery Persons', href: '/admin/delivery-persons' },
    { title: 'Pending Applications', href: '/admin/delivery-persons/applications/pending' },
];

function goToShow(applicationId: number) {
    router.get(route('admin.delivery-persons.show', applicationId));
}

const currentItem = computed(() => pendingApplications.value.find((a) => a.id === dropdownId.value) ?? null);

function openApproveModal(applicationId: number) {
    approveTargetId.value = applicationId;
    showApproveModal.value = true;
}

function closeApproveModal() {
    showApproveModal.value = false;
    approveTargetId.value = null;
}

function submitApprove() {
    if (approveTargetId.value == null) return;
    submittingApprove.value = true;
    router.put(route('admin.delivery-persons.approve', approveTargetId.value), {}, {
        onSuccess: () => closeApproveModal(),
        onFinish: () => { submittingApprove.value = false; },
    });
}

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
</script>

<template>
    <Head title="Delivery Persons – Pending Applications" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl font-semibold">Pending Applications</h1>
                    <a
                        :href="route('admin.delivery-persons.index')"
                        class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50"
                    >
                        Back to Delivery team
                    </a>
                </div>

                <div v-if="pendingApplications.length === 0" class="rounded border border-amber-200 bg-amber-50/50 p-8 text-center text-amber-800">
                    No pending applications. <a :href="route('admin.delivery-persons.index')" class="font-medium underline">View delivery team</a>.
                </div>

                <div v-else class="rounded border border-amber-200 bg-amber-50/50 p-4">
                    <p class="mb-3 text-sm text-amber-800">{{ pendingApplications.length }} application(s) awaiting review.</p>
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
                                            @click="(e) => { const el = (e.currentTarget as HTMLElement); const r = el.getBoundingClientRect(); dropdownStyle = { top: `${r.bottom + 4}px`, left: `${r.left}px` }; dropdownId = app.id; (e as MouseEvent).stopPropagation(); }"
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
                                    @click="goToShow(currentItem.id); dropdownId = null"
                                >
                                    View details
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    class="w-full px-4 py-2 text-left text-green-700 hover:bg-green-50"
                                    @click="openApproveModal(currentItem.id); dropdownId = null"
                                >
                                    Approve
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    class="w-full px-4 py-2 text-left text-red-700 hover:bg-red-50"
                                    @click="openRejectModal(currentItem.id); dropdownId = null"
                                >
                                    Reject
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </Teleport>

            <Transition name="fade">
                <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Approve delivery person</h3>
                        <p class="mb-4 text-sm text-gray-600">Approve this delivery person? They will be able to log in to the delivery portal.</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="rounded border border-gray-300 bg-gray-100 px-4 py-2 text-sm" @click="closeApproveModal">Cancel</button>
                            <button
                                type="button"
                                class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50"
                                :disabled="submittingApprove"
                                @click="submitApprove"
                            >
                                Approve
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
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
    background-color: #fffbeb;
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
