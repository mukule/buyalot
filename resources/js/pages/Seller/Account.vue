<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, DocumentType, SellerApplication, SellerDocument } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';
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

type TabId = 'sellerInfo' | 'verificationStatus' | number;

const tabs = computed((): { id: TabId; name: string; icon?: boolean }[] => [
    { id: 'sellerInfo' as TabId, name: 'Step 1: Application Overview' },
    ...documentTypes.value.map((dt, i) => ({
        id: dt.id as TabId,
        name: `Step ${i + 2}: ${dt.name}`,
    })),
    ...(app.value.verified
        ? [
              {
                  id: 'verificationStatus' as TabId,
                  name: '', // no label
                  icon: true, // flag to show icon
              },
          ]
        : []),
]);

const selectedTab = ref<TabId>('sellerInfo');

const selectedDocType = computed(() => {
    if (typeof selectedTab.value === 'number') {
        return documentTypes.value.find((dt) => dt.id === selectedTab.value);
    }
    return null;
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Account', href: '#' },
];

function submitDocument(documentTypeId: number, event: Event) {
    event.preventDefault();
    const form = event.target as HTMLFormElement;
    const fileInput = form.querySelector('input[type="file"]') as HTMLInputElement;

    if (!fileInput?.files?.length) {
        alert('Please select a file to upload.');
        return;
    }

    const formData = new FormData();
    formData.append('document_type_id', String(documentTypeId));
    formData.append('file', fileInput.files[0]);

    router.post('/seller/documents', formData, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="`${app.first_name} ${app.last_name} - Seller Profile`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 p-1">
            <!-- Tabs -->
            <div class="mb-6 flex gap-2 overflow-x-auto border-b">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="selectedTab = tab.id"
                    class="relative flex items-center justify-center border-b-2 px-4 py-2 text-sm font-medium whitespace-nowrap"
                    :class="{
                        'border-secondary text-primary': selectedTab === tab.id,
                        'border-transparent text-gray-600 hover:text-secondary': selectedTab !== tab.id,
                    }"
                >
                    <template v-if="tab.icon">
                        <CheckCircle class="h-5 w-5 rounded-full bg-primary p-1 text-white" />
                    </template>
                    <template v-else>
                        {{ tab.name }}
                    </template>
                </button>
            </div>

            <!-- Application Overview -->
            <section v-if="selectedTab === 'sellerInfo'" class="rounded-lg bg-white p-6 shadow">
                <h2 class="text-xl font-semibold">Application Overview</h2>
                <hr class="my-4" />

                <!-- Contact Info -->
                <div class="grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">Name:</span> {{ app.first_name }} {{ app.last_name }}</div>
                    <div><span class="font-medium">Email:</span> {{ app.contact_email }}</div>
                    <div><span class="font-medium">Phone:</span> {{ app.contact_phone }}</div>
                </div>

                <!-- Identification -->
                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">ID Type:</span> {{ app.identification_type }}</div>
                    <div v-if="app.id_number"><span class="font-medium">ID Number:</span> {{ app.id_number }}</div>
                    <div v-if="app.passport_number"><span class="font-medium">Passport Number:</span> {{ app.passport_number }}</div>
                    <div v-if="app.drivers_license"><span class="font-medium">Driver's License:</span> {{ app.drivers_license }}</div>
                </div>

                <!-- Business Info -->
                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">Business Name:</span> {{ app.business_name }}</div>
                    <div><span class="font-medium">Legal Name:</span> {{ app.company_legal_name }}</div>
                    <div><span class="font-medium">Business Type:</span> {{ app.business_type }}</div>
                    <div><span class="font-medium">Product Category:</span> {{ app.primary_product_category }}</div>
                    <div class="sm:col-span-2"><span class="font-medium">Description:</span> {{ app.description }}</div>
                </div>

                <!-- Owner Info -->
                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">Owner Name:</span> {{ app.owner_first_name }} {{ app.owner_last_name }}</div>
                    <div><span class="font-medium">Email:</span> {{ app.owner_email }}</div>
                    <div><span class="font-medium">Phone:</span> {{ app.owner_phone }}</div>
                </div>

                <!-- Registration & Tax Info -->
                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">VAT Registered:</span> {{ app.vat_registered ? 'Yes' : 'No' }}</div>
                    <div><span class="font-medium">VAT Number:</span> {{ app.vat_number || 'N/A' }}</div>
                    <div><span class="font-medium">KE Reg No:</span> {{ app.ke_business_reg_number || 'N/A' }}</div>
                    <div><span class="font-medium">Non-KE Reg No:</span> {{ app.non_ke_business_reg_number || 'N/A' }}</div>
                    <div><span class="font-medium">KE ID No:</span> {{ app.ke_id_number || 'N/A' }}</div>
                    <div><span class="font-medium">Passport (SP):</span> {{ app.passport_number_sp || 'N/A' }}</div>
                    <div><span class="font-medium">Country:</span> {{ app.country }}</div>
                    <div><span class="font-medium">Nationality:</span> {{ app.nationality }}</div>
                    <div><span class="font-medium">Monthly Revenue:</span> {{ app.monthly_revenue }}</div>
                </div>

                <!-- Operations -->
                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">Owns Store:</span> {{ app.owns_physical_store ? 'Yes' : 'No' }}</div>
                    <div><span class="font-medium">Retail Store Count:</span> {{ app.retail_store_count }}</div>
                    <div><span class="font-medium">Supplier to Retailers:</span> {{ app.is_supplier_to_retailers ? 'Yes' : 'No' }}</div>
                    <div><span class="font-medium">Supplier Count:</span> {{ app.supplier_retail_count }}</div>
                    <div><span class="font-medium">Marketplaces:</span> {{ app.operates_other_marketplaces ? 'Yes' : 'No' }}</div>
                    <div><span class="font-medium">Marketplace Details:</span> {{ app.marketplace_details || 'N/A' }}</div>
                    <div><span class="font-medium">Product Count:</span> {{ app.product_count }}</div>
                    <div><span class="font-medium">Stock Handling:</span> {{ app.stock_handling }}</div>
                    <div>
                        <span class="font-medium">Website:</span>
                        <a
                            v-if="app.product_website"
                            :href="app.product_website"
                            class="text-indigo-600 hover:underline"
                            target="_blank"
                            rel="noopener"
                        >
                            {{ app.product_website }}
                        </a>
                        <span v-else>N/A</span>
                    </div>
                    <div><span class="font-medium">Origin:</span> {{ app.product_origin || 'N/A' }}</div>
                    <div><span class="font-medium">Owned Brands:</span> {{ app.owned_brands || 'N/A' }}</div>
                    <div><span class="font-medium">Licensed Brands:</span> {{ app.licensed_brands || 'N/A' }}</div>
                    <div><span class="font-medium">Branding:</span> {{ app.product_branding || 'N/A' }}</div>
                    <div><span class="font-medium">Social Media:</span> {{ app.social_media || 'N/A' }}</div>
                </div>

                <!-- Discovery -->
                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                    <div><span class="font-medium">Source:</span> {{ app.discovery_source || 'N/A' }}</div>
                    <div><span class="font-medium">Referrer Email:</span> {{ app.referrer_email || 'N/A' }}</div>
                    <div><span class="font-medium">Share with Distributors:</span> {{ app.share_with_distributors ? 'Yes' : 'No' }}</div>
                </div>

                <!-- Application Status -->
                <div class="mt-6 flex items-center space-x-4 text-sm">
                    <span
                        :class="{
                            'bg-yellow-100 text-yellow-800': app.status === 0,
                            'bg-green-100 text-green-800': app.status === 1,
                            'bg-red-100 text-red-800': app.status === 2,
                        }"
                        class="inline-block rounded-full px-3 py-1 font-semibold"
                    >
                        {{ app.status === 0 ? 'Pending' : app.status === 1 ? 'Approved' : app.status === 2 ? 'Rejected' : 'Unknown' }}
                    </span>
                </div>
            </section>

            <!-- Document Tabs -->
            <section v-else-if="selectedDocType" class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">{{ selectedDocType.name }}</h2>

                <div v-if="sellerDocuments[selectedDocType.id]">
                    <p>
                        <strong>Status:</strong>
                        <span
                            :class="{
                                'text-yellow-600': sellerDocuments[selectedDocType.id]?.status === 'pending',
                                'text-green-600': sellerDocuments[selectedDocType.id]?.status === 'approved',
                                'text-red-600': sellerDocuments[selectedDocType.id]?.status === 'rejected',
                            }"
                        >
                            {{ sellerDocuments[selectedDocType.id]?.status }}
                        </span>
                    </p>
                    <p v-if="sellerDocuments[selectedDocType.id]?.rejection_reason" class="text-red-600 italic">
                        Reason: {{ sellerDocuments[selectedDocType.id]?.rejection_reason }}
                    </p>
                    <p>
                        <strong>Uploaded File:</strong>
                        <a
                            :href="sellerDocuments[selectedDocType.id]?.file_path"
                            target="_blank"
                            rel="noopener"
                            class="text-indigo-600 hover:underline"
                        >
                            View File
                        </a>
                    </p>

                    <form @submit="submitDocument(selectedDocType.id, $event)" class="mt-4 flex items-center gap-4" enctype="multipart/form-data">
                        <input
                            :id="`file-upload-${selectedDocType.id}`"
                            type="file"
                            name="file"
                            accept="application/pdf"
                            required
                            class="h-10 w-64 cursor-pointer rounded border border-gray-300 bg-gray-200 px-2 py-1"
                        />
                        <button type="submit" class="h-10 rounded bg-primary px-4 py-2 text-white hover:bg-secondary">Submit</button>
                    </form>
                </div>

                <div v-else>
                    <p v-if="selectedDocType.description" class="mb-4 text-gray-600 italic">
                        {{ selectedDocType.description }}
                    </p>
                    <form @submit="submitDocument(selectedDocType.id, $event)" class="mt-4 flex items-center gap-4" enctype="multipart/form-data">
                        <input
                            :id="`file-upload-${selectedDocType.id}`"
                            type="file"
                            name="file"
                            accept="application/pdf"
                            required
                            class="h-10 w-64 cursor-pointer rounded border border-gray-300 bg-gray-200 px-2 py-1"
                        />
                        <button type="submit" class="h-10 rounded bg-primary px-4 py-2 text-white hover:bg-secondary">Submit</button>
                    </form>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
