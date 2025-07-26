<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, DocumentType, SellerApplication, SellerDocument } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<
    AppPageProps<{
        application: SellerApplication & { hashid: string };
        documentTypes: DocumentType[];
        sellerDocuments: Record<number, SellerDocument | undefined>;
    }>
>();

const app = computed(() => page.props.application);
const documentTypes = computed(() => page.props.documentTypes);
const sellerDocuments = computed(() => page.props.sellerDocuments);

const VERIFICATION_TAB_ID = -1;

const tabs = computed(() => [
    ...documentTypes.value.map((dt) => ({
        id: dt.id,
        name: dt.name,
    })),
    { id: VERIFICATION_TAB_ID, name: 'Verification' },
]);

const selectedTab = ref<number>(tabs.value.length > 0 ? tabs.value[0].id : 0);
const selectedDocType = computed(() => documentTypes.value.find((dt) => dt.id === selectedTab.value));
const selectedDocument = computed(() => sellerDocuments.value[selectedTab.value]);

const reviewStatus = ref<string>(''); // Admin decision: approve or reject
const rejectionReason = ref<string>(''); // Rejection reason text

const verified = ref<boolean>(app.value.verified ?? false);
const previousVerified = ref(verified.value);

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Seller Verification', href: '#' },
];

function openFullPdf(url: string | undefined) {
    if (url) {
        window.open(url, '_blank', 'noopener');
    }
}

function submitReview() {
    if (!reviewStatus.value) {
        alert('Please select a decision (Approve or Reject)');
        return;
    }
    if (reviewStatus.value === 'rejected' && rejectionReason.value.trim() === '') {
        alert('Please provide a rejection reason when rejecting');
        return;
    }

    const docId = selectedDocument.value?.id;
    if (!docId) {
        alert('No document selected.');
        return;
    }

    router.put(`/admin/seller-documents/${docId}/review`, {
        status: reviewStatus.value,
        rejection_reason: rejectionReason.value,
    });
}

function onToggleVerification() {
    if (verified.value && !previousVerified.value) {
        const confirmed = confirm('Verifying this seller means their products will be available for buyers. Are you sure you want to proceed?');
        if (confirmed) {
            submitVerification();
            previousVerified.value = true;
        } else {
            verified.value = false;
        }
    } else if (!verified.value && previousVerified.value) {
        const confirmed = confirm(
            'Revoking this seller verification means their products will no longer be visible to buyers. This may be due to violations of platform policies or other issues. Are you sure you want to proceed?',
        );
        if (confirmed) {
            submitVerification();
            previousVerified.value = false;
        } else {
            verified.value = true;
        }
    }
}

function submitVerification() {
    const url = `/admin/seller-applications/${app.value.hashid}/verify`;
    const payload = { verified: verified.value ? 1 : 0 };

    console.log('Submitting verification:', { url, payload });

    router.put(url, payload);
}
</script>

<template>
    <Head :title="`${app.first_name} ${app.last_name} - Seller Verification`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 p-1">
            <!-- Tabs -->
            <div class="mb-6 flex gap-2 overflow-x-auto border-b">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="selectedTab = tab.id"
                    class="border-b-2 px-4 py-2 text-sm font-medium whitespace-nowrap"
                    :class="{
                        'border-secondary text-primary': selectedTab === tab.id,
                        'border-transparent text-gray-600 hover:text-secondary': selectedTab !== tab.id,
                    }"
                >
                    {{ tab.name }}
                </button>
            </div>

            <!-- Document Review Tab -->
            <section v-if="selectedTab !== VERIFICATION_TAB_ID && selectedDocType" class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">{{ selectedDocType.name }}</h2>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <!-- PDF Preview -->
                    <div class="lg:col-span-8">
                        <div v-if="selectedDocument">
                            <p>
                                <strong>Status:</strong>
                                <span
                                    :class="{
                                        'text-yellow-600': selectedDocument?.status === 'pending',
                                        'text-green-600': selectedDocument?.status === 'approved',
                                        'text-red-600': selectedDocument?.status === 'rejected',
                                    }"
                                >
                                    {{ selectedDocument?.status }}
                                </span>
                            </p>

                            <p v-if="selectedDocument?.rejection_reason" class="text-red-600 italic">
                                Reason: {{ selectedDocument?.rejection_reason }}
                            </p>

                            <p class="mt-4"><strong>Uploaded File Preview:</strong></p>

                            <div
                                class="relative cursor-pointer overflow-hidden rounded border border-gray-300 bg-white"
                                style="height: 400px"
                                @click="openFullPdf(selectedDocument?.file_path)"
                                title="Click to view full document"
                            >
                                <iframe
                                    :src="selectedDocument?.file_path + '#toolbar=0&navpanes=0&scrollbar=0'"
                                    class="h-full w-full"
                                    frameborder="0"
                                    style="border: none"
                                ></iframe>
                            </div>

                            <button
                                class="hover:bg-primary-dark mt-2 rounded bg-primary px-4 py-2 text-white"
                                @click="openFullPdf(selectedDocument?.file_path)"
                            >
                                Open in full Mode
                            </button>
                        </div>

                        <div v-else class="text-gray-500 italic">No document submitted for this type.</div>
                    </div>

                    <!-- Admin Review Form -->
                    <div class="lg:col-span-4" v-if="selectedDocument">
                        <form @submit.prevent="submitReview" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Decision:</label>
                                <div class="mt-1 flex items-center gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="approved" class="form-radio" v-model="reviewStatus" />
                                        <span class="ml-2 text-green-700">Approve</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="rejected" class="form-radio" v-model="reviewStatus" />
                                        <span class="ml-2 text-red-700">Reject</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="reviewStatus === 'rejected'">
                                <label for="rejection_reason" class="block text-sm font-medium text-gray-700"> Rejection Reason </label>
                                <textarea
                                    id="rejection_reason"
                                    v-model="rejectionReason"
                                    rows="3"
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Reason for rejection"
                                    required
                                ></textarea>
                            </div>

                            <button type="submit" class="hover:bg-primary-dark w-full rounded bg-primary px-4 py-2 text-white">Submit Review</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Final Verification Tab -->
            <section v-if="selectedTab === VERIFICATION_TAB_ID" class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Final Verification</h2>
                <div class="flex items-center gap-4">
                    <label class="relative inline-flex cursor-pointer items-center select-none">
                        <!-- Hidden checkbox -->
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            v-model="verified"
                            @change="onToggleVerification"
                            aria-checked="false"
                            aria-label="Toggle verification status"
                        />

                        <!-- Background track -->
                        <div
                            class="h-8 w-14 rounded-full bg-gray-300 transition-colors duration-300 peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary"
                        ></div>

                        <!-- Slider knob -->
                        <div
                            class="absolute top-1 left-1 h-6 w-6 rounded-full bg-white shadow-md transition-transform duration-300 peer-checked:translate-x-6"
                        ></div>
                    </label>

                    <!-- Dynamic label -->
                    <span class="font-medium text-gray-700 select-none">
                        {{ verified ? 'Cancel Verification' : 'Verify' }}
                    </span>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
