<!-- File: SellerApplications.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, SellerApplication } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage<
    AppPageProps<{
        applications: {
            data: (SellerApplication & { hashid: string })[];
            prev_page_url: string | null;
            next_page_url: string | null;
            current_page: number;
            last_page: number;
            total: number;
        };
        filters: {
            search?: string;
        };
    }>
>();

const applications = computed(() => page.props.applications.data);
const search = ref(page.props.filters?.search ?? '');
const dropdownAppId = ref<number | null>(null);
const dropdownCoords = ref<{ x: number; y: number }>({ x: 0, y: 0 });

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Seller Applications', href: '/admin/applications' },
];

function goToApplicationShow(hashid: string) {
    router.get(route('admin.applications.show', hashid));
}

function goToSellerVerification(hashid: string) {
    router.get(route('admin.seller-verification.show', hashid));
}

function statusLabel(status: number | undefined): string {
    switch (status) {
        case 0:
            return 'Pending';
        case 1:
            return 'Approved';
        case 2:
            return 'Rejected';
        default:
            return 'Unknown';
    }
}

function goToPreviousPage() {
    if (page.props.applications.prev_page_url) {
        router.visit(page.props.applications.prev_page_url, { preserveState: true, replace: true });
    }
}
function goToNextPage() {
    if (page.props.applications.next_page_url) {
        router.visit(page.props.applications.next_page_url, { preserveState: true, replace: true });
    }
}

function toggleDropdown(id: number, el: HTMLElement) {
    const rect = el.getBoundingClientRect();
    dropdownCoords.value = {
        x: rect.left,
        y: rect.bottom + window.scrollY,
    };
    dropdownAppId.value = dropdownAppId.value === id ? null : id;
}

function closeDropdown() {
    dropdownAppId.value = null;
}

function approveApplication(hashid: string) {
    if (confirm('Are you sure you want to approve this application?')) {
        router.put(route('admin.applications.approve', hashid), {});
    }
}

const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectErrors = ref<string | null>(null);
const rejectTargetHashid = ref<string | null>(null);
const submittingReject = ref(false);

function openRejectModal(hashid: string) {
    rejectTargetHashid.value = hashid;
    rejectReason.value = '';
    rejectErrors.value = null;
    showRejectModal.value = true;
}

function closeRejectModal() {
    showRejectModal.value = false;
    rejectTargetHashid.value = null;
    rejectReason.value = '';
    rejectErrors.value = null;
}

function submitReject() {
    if (!rejectTargetHashid.value) return;
    submittingReject.value = true;
    router.put(
        route('admin.applications.reject', rejectTargetHashid.value),
        { reason: rejectReason.value },
        {
            onSuccess: () => closeRejectModal(),
            onError: (errors) => {
                rejectErrors.value = errors.reason?.[0] || 'Please provide a valid reason.';
            },
            onFinish: () => {
                submittingReject.value = false;
            },
        },
    );
}

let searchTimeout: number | undefined;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.applications.index'), { search: val || undefined }, { preserveState: true, replace: true });
    }, 500);
});
</script>

<template>
    <Head title="Seller Applications" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl font-semibold">Seller Applications</h1>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search business type, company name, or product category"
                        class="w-full max-w-xs rounded border border-gray-300 p-2 text-sm sm:w-auto"
                    />
                </div>
                <hr />
                <div class="overflow-visible overflow-x-auto rounded-xl">
                    <table class="min-w-full table-auto text-left text-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Company Name</th>
                                <th class="px-4 py-3">Business Type</th>
                                <th class="px-4 py-3">Primary Products</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(app, index) in applications" :key="app.id">
                                <td class="px-4 py-3">{{ index + 1 }}</td>
                                <td
                                    class="cursor-pointer px-4 py-3 font-medium text-primary hover:underline"
                                    @click="goToApplicationShow(app.hashid)"
                                >
                                    {{ app.first_name }} {{ app.last_name }}
                                </td>
                                <td class="px-4 py-3">{{ app.company_legal_name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ app.business_type ?? '-' }}</td>
                                <td class="px-4 py-3">{{ app.primary_product_category ?? '-' }}</td>
                                <td class="px-4 py-3">{{ statusLabel(app.status ?? 0) }}</td>
                                <td class="px-4 py-3">
                                    <button
                                        class="inline-flex justify-center rounded-md px-2 py-1 text-sm text-gray-700 hover:bg-gray-50"
                                        @click="toggleDropdown(app.id, $event.currentTarget as HTMLElement)"
                                    >
                                        Actions
                                        <svg class="ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.58l3.71-4.35a.75.75 0 111.14.98l-4.25 5a.75.75 0 01-1.14 0l-4.25-5a.75.75 0 01.02-1.06z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end space-x-4">
                    <button
                        @click="goToPreviousPage"
                        :disabled="!page.props.applications.prev_page_url"
                        class="rounded border px-4 py-2 hover:bg-primary/10 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <button
                        @click="goToNextPage"
                        :disabled="!page.props.applications.next_page_url"
                        class="rounded border px-4 py-2 hover:bg-primary/10 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>

            <!-- Dropdown rendered via Teleport to prevent table overflow -->
            <Teleport to="body">
                <div
                    v-if="dropdownAppId !== null"
                    class="ring-opacity-5 absolute z-[9999] w-36 rounded-md border bg-white"
                    :style="{ left: `${dropdownCoords.x}px`, top: `${dropdownCoords.y}px` }"
                    @click.outside="closeDropdown"
                >
                    <ul class="divide-y divide-gray-200 text-sm">
                        <li>
                            <button
                                class="w-full px-4 py-2 text-left text-green-700 hover:bg-green-100"
                                @click="
                                    approveApplication(applications.find((a) => a.id === dropdownAppId)?.hashid ?? '');
                                    closeDropdown();
                                "
                            >
                                Approve
                            </button>
                        </li>
                        <li>
                            <button
                                class="w-full px-4 py-2 text-left text-red-700 hover:bg-red-100"
                                @click="
                                    openRejectModal(applications.find((a) => a.id === dropdownAppId)?.hashid ?? '');
                                    closeDropdown();
                                "
                            >
                                Reject
                            </button>
                        </li>
                        <li>
                            <button
                                @click="
                                    goToSellerVerification(applications.find((a) => a.id === dropdownAppId)?.hashid ?? '');
                                    closeDropdown();
                                "
                                class="w-full px-4 py-2 text-left text-blue-700 hover:bg-blue-100"
                            >
                                Verify
                            </button>
                        </li>
                    </ul>
                </div>
            </Teleport>

            <!-- Reject Modal -->
            <transition name="fade">
                <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-green-600/40">
                    <div class="w-full max-w-md rounded bg-white p-6 shadow-lg">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Reject Application</h3>
                        <textarea
                            v-model="rejectReason"
                            rows="4"
                            maxlength="500"
                            placeholder="Enter rejection reason (max 500 characters)"
                            class="w-full rounded border border-gray-300 p-2 text-sm"
                        ></textarea>
                        <p v-if="rejectErrors" class="mt-2 text-sm text-red-600">{{ rejectErrors }}</p>
                        <div class="mt-4 flex justify-end space-x-2">
                            <button @click="closeRejectModal" class="rounded border border-gray-300 bg-gray-100 px-4 py-2 text-sm">Cancel</button>
                            <button
                                @click="submitReject"
                                class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                                :disabled="submittingReject"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
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
