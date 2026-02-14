<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface DeliveryApplicationShow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    id_number: string;
    kra_pin: string;
    address: string;
    transport_type: string;
    transport_registration_number: string | null;
    transport_details: Record<string, unknown> | null;
    status: string;
    rejection_reason: string | null;
    reviewed_at: string | null;
    created_at: string;
    reviewed_by: { id: number; name: string } | null;
    id_copy_url: string | null;
    kra_copy_url: string | null;
    suspended_at: string | null;
    suspension_reason: string | null;
}

const page = usePage<
    AppPageProps<{
        application: DeliveryApplicationShow;
    }>
>();

const app = computed(() => page.props.application);

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Delivery Persons', href: '/admin/delivery-persons' },
    { title: app.value.name, href: '#' },
];

function statusLabel(status: string): string {
    const s = (status || '').toLowerCase();
    if (s === 'pending') return 'Pending';
    if (s === 'approved') return 'Approved';
    if (s === 'rejected') return 'Rejected';
    return status || 'Unknown';
}

function statusBadgeClass(status: string): string {
    const s = (status || '').toLowerCase();
    if (s === 'pending') return 'bg-amber-100 text-amber-800';
    if (s === 'approved') return 'bg-green-100 text-green-800';
    if (s === 'rejected') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
}

const showApproveModal = ref(false);
const submittingApprove = ref(false);

function openApproveModal() {
    showApproveModal.value = true;
}

function closeApproveModal() {
    showApproveModal.value = false;
}

function submitApprove() {
    submittingApprove.value = true;
    router.put(route('admin.delivery-persons.approve', app.value.id), {}, {
        onSuccess: () => closeApproveModal(),
        onFinish: () => { submittingApprove.value = false; },
    });
}

const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectErrors = ref<string | null>(null);
const submittingReject = ref(false);

function openRejectModal() {
    rejectReason.value = '';
    rejectErrors.value = null;
    showRejectModal.value = true;
}

function closeRejectModal() {
    showRejectModal.value = false;
    rejectReason.value = '';
    rejectErrors.value = null;
}

function submitReject() {
    submittingReject.value = true;
    router.put(route('admin.delivery-persons.reject', app.value.id), { reason: rejectReason.value }, {
        onSuccess: () => closeRejectModal(),
        onError: (errors) => {
            rejectErrors.value = (errors as Record<string, string[]>).reason?.[0] || 'Please provide a valid reason.';
        },
        onFinish: () => { submittingReject.value = false; },
    });
}

const transportDetailsNotes = computed(() => {
    const d = app.value.transport_details;
    if (!d || typeof d !== 'object') return null;
    return (d as { notes?: string }).notes ?? null;
});

const isSuspended = computed(() => !!app.value.suspended_at);

const showSuspendModal = ref(false);
const suspendReason = ref('');
const suspendNotify = ref(true);
const suspendErrors = ref<string | null>(null);
const submittingSuspend = ref(false);

function openSuspendModal() {
    suspendReason.value = '';
    suspendNotify.value = true;
    suspendErrors.value = null;
    showSuspendModal.value = true;
}

function closeSuspendModal() {
    showSuspendModal.value = false;
    suspendReason.value = '';
    suspendErrors.value = null;
}

function submitSuspend() {
    submittingSuspend.value = true;
    router.put(route('admin.delivery-persons.suspend', app.value.id), {
        reason: suspendReason.value,
        notify: suspendNotify.value,
    }, {
        onSuccess: () => closeSuspendModal(),
        onError: (errors) => {
            suspendErrors.value = (errors as Record<string, string[]>).reason?.[0] || 'Please provide a reason.';
        },
        onFinish: () => { submittingSuspend.value = false; },
    });
}

const showUnsuspendModal = ref(false);
const submittingUnsuspend = ref(false);

function openUnsuspendModal() {
    showUnsuspendModal.value = true;
}

function closeUnsuspendModal() {
    showUnsuspendModal.value = false;
}

function submitUnsuspend() {
    submittingUnsuspend.value = true;
    router.put(route('admin.delivery-persons.unsuspend', app.value.id), {}, {
        onSuccess: () => closeUnsuspendModal(),
        onFinish: () => { submittingUnsuspend.value = false; },
    });
}
</script>

<template>
    <Head :title="app.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold">Delivery Person Details</h1>
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="['rounded px-3 py-1 text-sm font-medium', statusBadgeClass(app.status)]">
                            {{ statusLabel(app.status) }}
                        </span>
                        <span v-if="isSuspended" class="rounded px-3 py-1 text-sm font-medium bg-red-100 text-red-800">Suspended</span>
                    </div>
                </div>
                <hr />

                <div v-if="isSuspended" class="mb-4 rounded border border-red-200 bg-red-50 p-4">
                    <h2 class="mb-2 text-lg font-semibold text-red-800">Account suspended</h2>
                    <p v-if="app.suspended_at" class="text-sm text-red-700">Suspended on {{ new Date(app.suspended_at).toLocaleString() }}.</p>
                    <p v-if="app.suspension_reason" class="mt-2 text-sm text-red-800">{{ app.suspension_reason }}</p>
                </div>

                <h2 class="mb-2 text-lg font-semibold text-gray-700">Contact</h2>
                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    <p><strong>Name:</strong> {{ app.name }}</p>
                    <p><strong>Email:</strong> {{ app.email }}</p>
                    <p><strong>Phone:</strong> {{ app.phone ?? '-' }}</p>
                </div>

                <hr class="my-6" />

                <h2 class="mb-2 text-lg font-semibold text-gray-700">KYC</h2>
                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    <p><strong>ID number:</strong> {{ app.id_number }}</p>
                    <p><strong>KRA PIN:</strong> {{ app.kra_pin }}</p>
                    <p class="sm:col-span-2">
                        <strong>Copy of ID:</strong>
                        <a v-if="app.id_copy_url" :href="app.id_copy_url" target="_blank" rel="noopener" class="ml-2 text-primary underline">View / Download</a>
                        <span v-else class="ml-2 text-muted-foreground">—</span>
                    </p>
                    <p class="sm:col-span-2">
                        <strong>KRA copy:</strong>
                        <a v-if="app.kra_copy_url" :href="app.kra_copy_url" target="_blank" rel="noopener" class="ml-2 text-primary underline">View / Download</a>
                        <span v-else class="ml-2 text-muted-foreground">—</span>
                    </p>
                </div>

                <hr class="my-6" />

                <h2 class="mb-2 text-lg font-semibold text-gray-700">Address</h2>
                <p class="whitespace-pre-wrap text-sm">{{ app.address }}</p>

                <hr class="my-6" />

                <h2 class="mb-2 text-lg font-semibold text-gray-700">Transport</h2>
                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    <p><strong>Type:</strong> {{ app.transport_type.replace(/_/g, ' ') }}</p>
                    <p><strong>Registration number:</strong> {{ app.transport_registration_number ?? '-' }}</p>
                    <p v-if="transportDetailsNotes" class="sm:col-span-2"><strong>Other details:</strong> {{ transportDetailsNotes }}</p>
                </div>

                <hr class="my-6" />

                <h2 class="mb-2 text-lg font-semibold text-gray-700">Application status</h2>
                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    <p><strong>Applied at:</strong> {{ app.created_at ? new Date(app.created_at).toLocaleString() : '-' }}</p>
                    <p v-if="app.reviewed_at"><strong>Reviewed at:</strong> {{ new Date(app.reviewed_at).toLocaleString() }}</p>
                    <p v-if="app.reviewed_by"><strong>Reviewed by:</strong> {{ app.reviewed_by.name }}</p>
                    <p v-if="app.rejection_reason" class="sm:col-span-2">
                        <strong>Rejection reason:</strong>
                        <span class="mt-1 block rounded bg-red-50 p-2 text-red-800">{{ app.rejection_reason }}</span>
                    </p>
                </div>

                <hr class="my-6" />

                <div v-if="app.status === 'pending'" class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                        @click="openApproveModal"
                    >
                        Approve
                    </button>
                    <button
                        type="button"
                        class="rounded border border-red-600 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                        @click="openRejectModal"
                    >
                        Reject
                    </button>
                </div>
                <div v-else-if="app.status === 'approved' && !isSuspended" class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded border border-amber-600 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50"
                        @click="openSuspendModal"
                    >
                        Suspend account
                    </button>
                </div>
                <div v-else-if="isSuspended" class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                        @click="openUnsuspendModal"
                    >
                        Unsuspend account
                    </button>
                </div>
            </div>
        </div>

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
            <div v-if="showSuspendModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Suspend delivery person</h3>
                    <textarea
                        v-model="suspendReason"
                        rows="4"
                        maxlength="1000"
                        placeholder="Enter reason for suspension (required)"
                        class="w-full rounded border border-gray-300 p-2 text-sm"
                    />
                    <p v-if="suspendErrors" class="mt-2 text-sm text-red-600">{{ suspendErrors }}</p>
                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                        <input v-model="suspendNotify" type="checkbox" class="rounded border-gray-300" />
                        Notify delivery person by email about suspension
                    </label>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded border border-gray-300 bg-gray-100 px-4 py-2 text-sm" @click="closeSuspendModal">Cancel</button>
                        <button
                            type="button"
                            class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700 disabled:opacity-50"
                            :disabled="submittingSuspend"
                            @click="submitSuspend"
                        >
                            Suspend
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
        <Transition name="fade">
            <div v-if="showUnsuspendModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Unsuspend delivery person</h3>
                    <p class="mb-4 text-sm text-gray-600">Restore this account? They will be able to log in and receive delivery assignments again.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="rounded border border-gray-300 bg-gray-100 px-4 py-2 text-sm" @click="closeUnsuspendModal">Cancel</button>
                        <button
                            type="button"
                            class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50"
                            :disabled="submittingUnsuspend"
                            @click="submitUnsuspend"
                        >
                            Unsuspend
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
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
