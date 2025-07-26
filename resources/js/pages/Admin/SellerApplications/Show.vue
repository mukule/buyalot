<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, SellerApplication } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<
    AppPageProps<{
        application: SellerApplication & { hashid: string };
    }>
>();

const app = computed(() => page.props.application);

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Seller Applications', href: '/admin/applications' },
    { title: `${app.value.first_name} ${app.value.last_name}`, href: '#' },
];
</script>

<template>
    <Head :title="`${app.first_name} ${app.last_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h1 class="mb-4 text-2xl font-bold">Seller Application Details</h1>
                <hr />
                <!-- Contact Info -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Contact Info</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>Name:</strong> {{ app.first_name }} {{ app.last_name }}</p>
                    <p><strong>Email:</strong> {{ app.contact_email }}</p>
                    <p><strong>Phone:</strong> {{ app.contact_phone }}</p>
                </div>

                <hr class="my-6" />

                <!-- Identification -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Identification</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>Type:</strong> {{ app.identification_type }}</p>
                    <p v-if="app.id_number"><strong>ID Number:</strong> {{ app.id_number }}</p>
                    <p v-if="app.passport_number"><strong>Passport Number:</strong> {{ app.passport_number }}</p>
                    <p v-if="app.drivers_license"><strong>Driver's License:</strong> {{ app.drivers_license }}</p>
                </div>

                <hr class="my-6" />

                <!-- Business Info -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Business Info</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>Business Name:</strong> {{ app.business_name }}</p>
                    <p><strong>Company Legal Name:</strong> {{ app.company_legal_name }}</p>
                    <p><strong>Business Type:</strong> {{ app.business_type }}</p>
                    <p><strong>Product Category:</strong> {{ app.primary_product_category }}</p>
                    <p><strong>Description:</strong> {{ app.description }}</p>
                </div>

                <hr class="my-6" />

                <!-- Owner Info -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Owner Info</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>Name:</strong> {{ app.owner_first_name }} {{ app.owner_last_name }}</p>
                    <p><strong>Email:</strong> {{ app.owner_email }}</p>
                    <p><strong>Phone:</strong> {{ app.owner_phone }}</p>
                </div>

                <hr class="my-6" />

                <!-- Tax & Registration -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Registration & Tax Info</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>VAT Registered:</strong> {{ app.vat_registered }}</p>
                    <p><strong>VAT Number:</strong> {{ app.vat_number }}</p>
                    <p><strong>KE Business Reg No:</strong> {{ app.ke_business_reg_number }}</p>
                    <p><strong>Non-KE Reg No:</strong> {{ app.non_ke_business_reg_number }}</p>
                    <p><strong>KE ID Number:</strong> {{ app.ke_id_number }}</p>
                    <p><strong>Passport (SP):</strong> {{ app.passport_number_sp }}</p>
                    <p><strong>Country:</strong> {{ app.country }}</p>
                    <p><strong>Nationality:</strong> {{ app.nationality }}</p>
                    <p><strong>Monthly Revenue:</strong> {{ app.monthly_revenue }}</p>
                </div>

                <hr class="my-6" />

                <!-- Operations -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Operations</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>Owns Physical Store:</strong> {{ app.owns_physical_store }}</p>
                    <p><strong>Retail Store Count:</strong> {{ app.retail_store_count }}</p>
                    <p><strong>Supplier to Retailers:</strong> {{ app.is_supplier_to_retailers }}</p>
                    <p><strong>Supplier Count:</strong> {{ app.supplier_retail_count }}</p>
                    <p><strong>Operates Marketplaces:</strong> {{ app.operates_other_marketplaces }}</p>
                    <p><strong>Marketplace Details:</strong> {{ app.marketplace_details }}</p>
                    <p><strong>Product Count:</strong> {{ app.product_count }}</p>
                    <p><strong>Stock Handling:</strong> {{ app.stock_handling }}</p>
                    <p><strong>Website:</strong> {{ app.product_website }}</p>
                    <p><strong>Origin:</strong> {{ app.product_origin }}</p>
                    <p><strong>Owned Brands:</strong> {{ app.owned_brands }}</p>
                    <p><strong>Licensed Brands:</strong> {{ app.licensed_brands }}</p>
                    <p><strong>Branding:</strong> {{ app.product_branding }}</p>
                    <p><strong>Social Media:</strong> {{ app.social_media }}</p>
                </div>

                <hr class="my-6" />

                <!-- Discovery -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Discovery</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p><strong>Source:</strong> {{ app.discovery_source }}</p>
                    <p><strong>Referrer Email:</strong> {{ app.referrer_email }}</p>
                    <p><strong>Share with Distributors:</strong> {{ app.share_with_distributors }}</p>
                </div>

                <hr class="my-6" />

                <!-- Status -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Application Status</h2>
                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <p>
                        <strong>Status:</strong>
                        <span v-if="app.status === 0">Pending</span>
                        <span v-else-if="app.status === 1">Approved</span>
                        <span v-else-if="app.status === 2">Rejected</span>
                        <span v-else>Unknown</span>
                    </p>
                    <p v-if="app.status_reason"><strong>Reason:</strong> {{ app.status_reason }}</p>
                </div>

                <hr class="my-6" />

                <!-- Uploaded Images -->
                <h2 class="mb-2 text-lg font-semibold text-gray-700">Sample product images</h2>
                <div v-if="app.images?.length" class="overflow-x-auto">
                    <div class="flex gap-3">
                        <div v-for="(image, idx) in app.images" :key="idx" class="h-[50px] w-[50px] flex-shrink-0 overflow-hidden rounded border">
                            <img :src="image.path" alt="Uploaded Image" class="h-full w-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
