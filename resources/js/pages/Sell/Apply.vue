<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import allCountries from 'country-calling-code';
import { nextTick, ref } from 'vue';

const page = usePage();

// Restore current step from session or default to 1
import type { Ref } from 'vue';
const currentStep: Ref<number> = ref(Number(page.props.savedStep) || 1);
const totalSteps = 6;

const nextStep = () => {
    if (validateStep(currentStep.value)) {
        currentStep.value++;
        saveProgress();
    }
};

const prevStep = () => {
    currentStep.value--;
    saveProgress();
};

const validateStep = (step: number) => {
    // TODO: Add real validation per step
    return true;
};

// Preload saved session data
const prefilled: any = page.props.prefilled || {};

const form = useForm({
    business_type: '',
    agreed_to_privacy: false,

    // Contact Info
    first_name: '',
    last_name: '',
    contact_email: '',
    contact_phone: '',

    // Identification
    identification_type: '',
    id_number: '',
    passport_number: '',

    // Business Info
    business_name: '',

    primary_product_category: '',
    description: '',

    owner_first_name: '',
    owner_last_name: '',
    owner_email: '',
    owner_phone: '',

    vat_registered: '',
    vat_number: '',
    company_legal_name: '',
    ke_business_reg_number: '',
    non_ke_business_reg_number: '',
    ke_id_number: '',
    passport_number_sp: '',
    country: '',
    nationality: '',
    monthly_revenue: '',

    owns_physical_store: '',
    retail_store_count: 0, // number, not string
    is_supplier_to_retailers: '',
    operates_other_marketplaces: '',
    marketplace_details: '',
    supplier_retail_count: 0, // number, not string
    product_count: 1, // default minimum number
    stock_handling: '',
    product_website: '',
    product_origin: '',
    owned_brands: '',
    licensed_brands: '',
    product_branding: '',
    social_media: '',
    business_summary: '',

    product_images: [] as string[],

    discovery_source: '',
    referrer_email: '',
    share_with_distributors: '',
});

// Apply prefilled data from session
form.defaults(prefilled);

// Image previews for UI, initialize with prefilled product_images URLs if available
const imagePreviews = ref<string[]>([]);
if (prefilled.product_images && Array.isArray(prefilled.product_images)) {
    imagePreviews.value = [...prefilled.product_images];
}

const saveProgress = async () => {
    const rawData = form.data();

    // Filter out null/undefined
    const filteredData = Object.fromEntries(Object.entries(rawData).filter(([_, v]) => v !== null && v !== undefined));

    // Ensure numeric fields are stored as numbers
    ['retail_store_count', 'product_count', 'supplier_retail_count'].forEach((field) => {
        if (filteredData[field] !== undefined && filteredData[field] !== '') {
            filteredData[field] = String(Number(filteredData[field]));
        }
    });

    console.log('Saving form data:', filteredData, 'Current Step:', currentStep.value);

    try {
        await axios.post('/sell/save-progress', {
            ...filteredData,
            current_step: currentStep.value,
        });
        console.log('Progress + step saved');
    } catch (error) {
        console.error('Failed to save progress', error);
    }
};

const submit = async () => {
    try {
        console.log('Submitting final application...');

        currentStep.value = totalSteps;
        await nextTick();
        await saveProgress();

        // 🚨 Use router.post instead of axios.post
        router.post(
            '/sell/apply',
            {},
            {
                onSuccess: () => {
                    form.reset();
                    imagePreviews.value = [];
                    currentStep.value = 1;
                    console.log('Application submitted successfully');
                },
                onError: (errors) => {
                    console.error('Submission failed with validation errors', errors);
                },
            },
        );
    } catch (error) {
        console.error('Failed to submit application', error);
    }
};
// Image upload logic
const imageInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const handleFileChange = async (e: Event) => {
    const files = (e.target as HTMLInputElement).files;
    if (!files) return;

    for (const file of Array.from(files)) {
        // Upload file immediately
        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await axios.post('/sell/upload-image', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            // Push the returned image URL string (not File object)
            form.product_images.push(response.data.path);

            // Show preview from FileReader (optional, can also use URL directly)
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreviews.value.push(e.target?.result as string);
            };
            reader.readAsDataURL(file);

            // Save progress including newly added image URLs
            await saveProgress();
        } catch (error) {
            console.error('Image upload failed', error);
        }
    }
};

const handleDrop = async (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (!files) return;

    for (const file of Array.from(files)) {
        if (!file.type.startsWith('image/')) continue;

        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await axios.post('/sell/upload-image', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            form.product_images.push(response.data.path);

            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreviews.value.push(e.target?.result as string);
            };
            reader.readAsDataURL(file);

            await saveProgress();
        } catch (error) {
            console.error('Image upload failed', error);
        }
    }
};

const removeImage = (index: number) => {
    imagePreviews.value.splice(index, 1);
    form.product_images.splice(index, 1);
};

// Constants for form options, categories, etc.
const BUSINESS_TYPES = [
    { id: 'retailer', label: 'Retailer' },
    { id: 'wholesaler', label: 'Wholesaler' },
    { id: 'manufacturer', label: 'Manufacturer' },
    { id: 'distributor', label: 'Distributor' },
    { id: 'importer', label: 'Importer' }
];
const IDENTIFICATION_TYPES = [
    { id: 'id_number', label: 'National ID Number' },
    { id: 'passport', label: 'Passport' },
];

const DEFAULT_CATEGORIES = [
    'Electronics',
    'Apparel',
    'Home & Garden',
    'Beauty',
    'Sports',
    'Toys',
    'Books',
    'Automotive',
    'Health & Wellness',
    'Office Supplies',
    'Groceries',
    'Pet Supplies',
    'Gaming',
    'Baby & Kids',
];

const REVENUE_OPTIONS = [
    'Less than KSh 20,000',
    'KSh 20,000 - KSh 50,000',
    'KSh 50,000 - KSh 100,000',
    'KSh 100,000 - KSh 500,000',
    'KSh 500,000 - KSh 1 Million',
    'KSh 1 Million - KSh 2.5 Million',
    'Over KSh 2.5 Million',
];

const DISCOVERY_SOURCES = ['Google Search', 'Social Media', 'Referral', 'Online Advertisement', 'Event or Expo', 'Other'];

const COUNTRIES = allCountries.map((c) => c.country).sort();

const steps = [
    { title: 'Business Type' },
    { title: 'Business Details' },
    { title: 'Operations' },
    { title: 'Product Info' },
    { title: 'Product Images' },
    { title: 'Final Details' },
];

// Reset application data
const resetApplication = async () => {
    try {
        await axios.post('/sell/clear-progress');
        form.reset();
        imagePreviews.value = [];
        currentStep.value = 1;
        console.log('Application reset successfully');
    } catch (error) {
        console.error('Failed to reset application', error);
    }
};
</script>

<template>
    <MainLayout>
        <section class="container mx-auto px-4 py-12">
            <div class="mx-auto max-w-2xl">
                <div class="mb-8 text-center">
                    <h1 class="mb-2 text-3xl font-bold md:text-4xl">Apply to Sell on Buyalot</h1>
                    <p class="text-lg text-gray-600">4.4+ million customers are looking for new brands and unique products!</p>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="(step, index) in steps" :key="index" class="flex flex-col items-center">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full"
                                :class="{
                                    'bg-primary text-white': currentStep >= index + 1,
                                    'border-2 border-gray-300': currentStep < index + 1,
                                }"
                            >
                                {{ index + 1 }}
                            </div>
                            <!-- Hide titles on small screens, show on md and above -->
                            <span class="mt-2 hidden text-sm font-medium md:block" :class="{ 'text-primary': Number(currentStep) >= index + 1 }">
                                {{ step.title }}
                            </span>
                        </div>
                    </div>
                    <div class="relative mt-4">
                        <div class="absolute h-1 w-full bg-gray-200"></div>
                        <div
                            class="absolute h-1 bg-primary transition-all duration-300"
                            :style="`width: ${(Number(currentStep) - 1) * (100 / (steps.length - 1))}%`"
                        ></div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="rounded-xl border border-gray-100 bg-white p-8 shadow-sm">
                    <!-- Step 1: Business Type -->
                    <div v-show="currentStep === 1" class="space-y-6">
                        <div class="space-y-4">
                            <h2 class="text-center text-xl font-semibold">What type of business are you?</h2>
                            <p class="mb-4 text-gray-600">I am applying as:</p>

                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="type in BUSINESS_TYPES" :key="type.id" class="flex items-center">
                                    <input
                                        :id="`type-${type.id}`"
                                        v-model="form.business_type"
                                        type="radio"
                                        :value="type.id"
                                        name="business_type"
                                        class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                        :required="currentStep === 1"
                                    />
                                    <label :for="`type-${type.id}`" class="ml-3 text-sm font-medium text-gray-700">
                                        {{ type.label }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="space-y-4">
                            <h2 class="text-center text-xl font-semibold">How can we get in touch</h2>
                            <p class="mb-4 text-sm text-gray-500">Required so we may contact you for verification</p>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="first_name" class="mb-1 block text-sm font-medium text-gray-700">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        placeholder="First name"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 1"
                                    />
                                </div>

                                <div>
                                    <label for="last_name" class="mb-1 block text-sm font-medium text-gray-700">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        placeholder="Last name"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 1"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="contact_email" class="mb-1 block text-sm font-medium text-gray-700">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="contact_email"
                                        v-model="form.contact_email"
                                        type="email"
                                        placeholder="email"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 1"
                                    />
                                </div>

                                <div>
                                    <label for="contact_phone" class="mb-1 block text-sm font-medium text-gray-700">
                                        Phone <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="contact_phone"
                                        v-model="form.contact_phone"
                                        type="tel"
                                        placeholder="e.g., +254 700 000 000"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 1"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="currentStep === 2" class="space-y-6">
                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-600">Identification Type</h2>

                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="idType in IDENTIFICATION_TYPES" :key="idType.id" class="flex items-center">
                                    <input
                                        :id="`id-type-${idType.id}`"
                                        v-model="form.identification_type"
                                        type="radio"
                                        :value="idType.id"
                                        name="identification_type"
                                        class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                        :required="currentStep === 2"
                                    />
                                    <label :for="`id-type-${idType.id}`" class="ml-3 text-sm font-medium text-gray-700">
                                        {{ idType.label }}
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.identification_type === 'id_number'" class="mt-4">
                                <label for="id_number" class="mb-1 block text-sm font-medium text-gray-700">
                                    ID Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="id_number"
                                    v-model="form.id_number"
                                    type="text"
                                    placeholder="id number"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                    :required="form.identification_type === 'id_number' && currentStep === 2"
                                />
                            </div>

                            <div v-if="form.identification_type === 'passport'" class="mt-4">
                                <label for="passport_number" class="mb-1 block text-sm font-medium text-gray-700">
                                    Passport Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="passport_number"
                                    v-model="form.passport_number"
                                    placeholder="passport number"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                    :required="form.identification_type === 'passport' && currentStep === 2"
                                />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-600">Director's Details</h2>
                            <p class="mb-2 text-sm text-gray-500">
                                These details will be used to create your Buyalot Seller Account if your application is successful.
                            </p>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="owner_first_name" class="mb-1 block text-sm font-medium text-gray-700">
                                        Business Owner First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="owner_first_name"
                                        v-model="form.owner_first_name"
                                        type="text"
                                        placeholder="business owner first name"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 2"
                                    />
                                </div>

                                <div>
                                    <label for="owner_last_name" class="mb-1 block text-sm font-medium text-gray-700">
                                        Business Owner Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="owner_last_name"
                                        v-model="form.owner_last_name"
                                        placeholder="business owner last name"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 2"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="owner_email" class="mb-1 block text-sm font-medium text-gray-700">
                                        Business Owner Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="owner_email"
                                        v-model="form.owner_email"
                                        type="email"
                                        placeholder="business owner email"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 2"
                                    />
                                </div>

                                <div>
                                    <label for="owner_phone" class="mb-1 block text-sm font-medium text-gray-700">
                                        Business Owner Mobile Number <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="owner_phone"
                                        v-model="form.owner_phone"
                                        type="tel"
                                        placeholder="e.g., +254 700 000 000"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 2"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Registration and VAT Information -->
                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">Company Registration & VAT</h2>

                            <div>
                                <label for="company_legal_name" class="mb-1 block text-sm font-medium text-gray-700">Company Legal Name</label>
                                <input
                                    id="company_legal_name"
                                    v-model="form.company_legal_name"
                                    name="company_legal_name"
                                    placeholder="e.g., Buyalot Ltd"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="ke_business_reg_number" class="mb-1 block text-sm font-medium text-gray-700">
                                        KE Business Registration Number
                                    </label>
                                    <input
                                        id="ke_business_reg_number"
                                        v-model="form.ke_business_reg_number"
                                        type="text"
                                        placeholder="e.g., 2024/123456/07"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label for="non_ke_business_reg_number" class="mb-1 block text-sm font-medium text-gray-700">
                                        Non-KE Business Registration Number
                                    </label>
                                    <input
                                        id="non_ke_business_reg_number"
                                        v-model="form.non_ke_business_reg_number"
                                        type="text"
                                        placeholder="e.g., 123456789"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="ke_id_number" class="mb-1 block text-sm font-medium text-gray-700">KE ID Number</label>
                                    <input
                                        id="ke_id_number"
                                        v-model="form.ke_id_number"
                                        placeholder="e.g., 12345678"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label for="passport_number_sp" class="mb-1 block text-sm font-medium text-gray-700">Passport Number</label>
                                    <input
                                        id="passport_number_sp"
                                        v-model="form.passport_number_sp"
                                        placeholder="e.g., A12345678"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="country" class="mb-1 block text-sm font-medium text-gray-700">
                                        Country of Business Registration
                                    </label>
                                    <input
                                        id="country"
                                        v-model="form.country"
                                        placeholder="e.g., Kenya"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label for="nationality" class="mb-1 block text-sm font-medium text-gray-700">
                                        Nationality <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="nationality"
                                        v-model="form.nationality"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                        :required="currentStep === 2"
                                    >
                                        <option value="" disabled selected>Please select</option>
                                        <option v-for="country in COUNTRIES" :key="country" :value="country">
                                            {{ country }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Are you VAT registered?</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            value="yes"
                                            v-model="form.vat_registered"
                                            class="h-4 w-4 text-primary focus:ring-primary"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            value="no"
                                            v-model="form.vat_registered"
                                            class="h-4 w-4 text-primary focus:ring-primary"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.vat_registered === 'yes'">
                                <label for="vat_number" class="mb-1 block text-sm font-medium text-gray-700">VAT Number</label>
                                <input
                                    id="vat_number"
                                    v-model="form.vat_number"
                                    placeholder="e.g., KE1234567890"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Business Operations -->
                    <div v-show="currentStep === 3" class="space-y-6">
                        <!-- Monthly Revenue -->
                        <div class="space-y-4">
                            <h2 class="text-xl font-semibold text-gray-800">Monthly Revenue</h2>
                            <p class="mb-2 text-sm text-gray-600">Please select your approximate monthly revenue in Kenyan Shillings (KSh)</p>

                            <div class="space-y-2">
                                <div v-for="(option, idx) in REVENUE_OPTIONS" :key="idx" class="flex items-center">
                                    <input
                                        type="radio"
                                        :id="`revenue-${idx}`"
                                        :value="option"
                                        v-model="form.monthly_revenue"
                                        name="monthly_revenue"
                                        class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                        :required="currentStep === 3"
                                    />
                                    <label :for="`revenue-${idx}`" class="ml-3 text-sm text-gray-700">
                                        {{ option }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Physical Retail Presence -->
                        <div class="space-y-4">
                            <h2 class="text-xl font-semibold text-gray-800">Physical Retail Presence</h2>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Do you own or operate physical retail stores? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-6">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.owns_physical_store"
                                        value="yes"
                                        name="owns_physical_store"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                        :required="currentStep === 3"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.owns_physical_store"
                                        value="no"
                                        name="owns_physical_store"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>
                            </div>

                            <div v-if="form.owns_physical_store === 'yes'">
                                <label for="retail_store_count" class="mb-1 block text-sm font-medium text-gray-700">
                                    Number of physical retail stores
                                </label>
                                <input
                                    id="retail_store_count"
                                    v-model="form.retail_store_count"
                                    placeholder="e.g., 3 stores"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                                    :required="form.owns_physical_store === 'yes' && currentStep === 3"
                                />
                            </div>
                        </div>

                        <!-- Supplier to Retailers -->
                        <div class="space-y-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Are you a supplier to retailers? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-6">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.is_supplier_to_retailers"
                                        value="yes"
                                        name="is_supplier_to_retailers"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                        :required="currentStep === 3"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.is_supplier_to_retailers"
                                        value="no"
                                        name="is_supplier_to_retailers"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>
                            </div>

                            <input
                                id="supplier_retail_count"
                                v-model.number="form.supplier_retail_count"
                                type="number"
                                min="0"
                                :required="form.is_supplier_to_retailers === 'yes' && currentStep === 3"
                                @input="form.supplier_retail_count = form.supplier_retail_count || 0"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                placeholder="e.g., 3 stores"
                                aria-label="Number of ecommerce or retail stores"
                            />
                        </div>

                        <!-- Other Marketplaces -->
                        <div class="mt-6 space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Do you operate seller accounts on any marketplace platforms?
                            </label>

                            <div class="flex gap-4">
                                <label class="flex items-center space-x-2">
                                    <input type="radio" value="yes" v-model="form.operates_other_marketplaces" class="form-radio text-primary" />
                                    <span>Yes</span>
                                </label>

                                <label class="flex items-center space-x-2">
                                    <input type="radio" value="no" v-model="form.operates_other_marketplaces" class="form-radio text-primary" />
                                    <span>No</span>
                                </label>
                            </div>

                            <div v-if="form.operates_other_marketplaces === 'yes'">
                                <input
                                    v-model="form.marketplace_details"
                                    type="text"
                                    class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                    placeholder="e.g., Jumia, Amazon, Shopify..."
                                    :required="form.operates_other_marketplaces === 'yes' && currentStep === 3"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Product Information -->
                    <div v-show="currentStep === 4" class="space-y-6">
                        <!-- Product Categories -->
                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">
                                Tell us about your product range & brands
                            </h2>

                            <div>
                                <label for="product_count" class="mb-1 block text-sm font-medium text-gray-700">
                                    How many different unique products does your business offer?
                                </label>
                                <input
                                    id="product_count"
                                    v-model.number="form.product_count"
                                    type="number"
                                    min="1"
                                    :required="currentStep === 4"
                                    @input="form.product_count = form.product_count || 1"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                    placeholder="e.g., 50"
                                    aria-label="Number of different products your business offers"
                                />
                            </div>

                            <div>
                                <label for="primary_category" class="mb-1 block text-sm font-medium text-gray-700">
                                    My primary category of products
                                </label>
                                <select
                                    id="primary_category"
                                    v-model="form.primary_product_category"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                    :required="currentStep === 4"
                                >
                                    <option value="" disabled selected>Please select</option>
                                    <option v-for="category in DEFAULT_CATEGORIES" :key="category" :value="category">
                                        {{ category }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Stock Handling -->
                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">Do you carry or hold stock?</h2>
                            <div class="space-y-3">
                                <label class="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        v-model="form.stock_handling"
                                        value="full_stock"
                                        name="stock_handling"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                        :required="currentStep === 4"
                                    />
                                    <span class="text-sm text-gray-700">Yes, on my whole product range</span>
                                </label>
                                <label class="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        v-model="form.stock_handling"
                                        value="partial_stock"
                                        name="stock_handling"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <span class="text-sm text-gray-700">Yes, on some of my product range</span>
                                </label>
                                <label class="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        v-model="form.stock_handling"
                                        value="on_demand"
                                        name="stock_handling"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <span class="text-sm text-gray-700">No, I order or manufacture on demand</span>
                                </label>
                            </div>
                        </div>

                        <!-- Product Description & Brands -->
                        <div class="space-y-6">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">
                                Tell us about your product range & brands
                            </h2>

                            <!-- Finished Product Type -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Our finished products are best described as:</label>
                                <p class="mb-3 text-sm text-gray-500">
                                    This info helps Buyalot provide services that support manufacturers, resellers, and importers.
                                </p>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            v-model="form.product_origin"
                                            value="imported"
                                            name="product_origin"
                                            class="border-gray-300 text-primary focus:ring-primary"
                                            :required="currentStep === 4"
                                        />
                                        <span class="text-sm text-gray-700">Imported</span>
                                    </label>
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            v-model="form.product_origin"
                                            value="local"
                                            name="product_origin"
                                            class="border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span class="text-sm text-gray-700">Manufactured locally</span>
                                    </label>
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            v-model="form.product_origin"
                                            value="mixed"
                                            name="product_origin"
                                            class="border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span class="text-sm text-gray-700">A mixture of import and local manufacturers</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Product Branding -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Our products are:</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            v-model="form.product_branding"
                                            value="combination"
                                            name="product_branding"
                                            class="border-gray-300 text-primary focus:ring-primary"
                                            :required="currentStep === 4"
                                        />
                                        <span class="text-sm text-gray-700">A combination of branded and unbranded products</span>
                                    </label>
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            v-model="form.product_branding"
                                            value="branded"
                                            name="product_branding"
                                            class="border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span class="text-sm text-gray-700">Branded</span>
                                    </label>
                                    <label class="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            v-model="form.product_branding"
                                            value="unbranded"
                                            name="product_branding"
                                            class="border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span class="text-sm text-gray-700">Unbranded</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Owned Brands -->
                            <div>
                                <label for="owned_brands" class="mb-1 block text-sm font-medium text-gray-700"
                                    >List the brand names that you own</label
                                >
                                <input
                                    id="owned_brands"
                                    v-model="form.owned_brands"
                                    type="text"
                                    placeholder="e.g., MyBrand, SuperWear"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                />
                                <p class="mt-1 text-sm text-gray-500">Any brand names that you directly own or hold trademarks on</p>
                            </div>

                            <!-- Licensed Brands -->
                            <div>
                                <label for="licensed_brands" class="mb-1 block text-sm font-medium text-gray-700"
                                    >List the brand names you resell or are licensed to use</label
                                >
                                <input
                                    id="licensed_brands"
                                    v-model="form.licensed_brands"
                                    type="text"
                                    placeholder="e.g., Nike, Samsung"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                />
                                <p class="mt-1 text-sm text-gray-500">We are a reseller or licensee of these brand names</p>
                            </div>

                            <!-- Website -->
                            <div>
                                <label for="product_website" class="mb-1 block text-sm font-medium text-gray-700">Website</label>
                                <input
                                    id="product_website"
                                    v-model="form.product_website"
                                    type="url"
                                    placeholder="https://yourbrand.co.ke"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                />
                                <p class="mt-1 text-sm text-gray-500">A valid URL link to your website</p>
                            </div>

                            <!-- Social Media -->
                            <div>
                                <label for="social_media" class="mb-1 block text-sm font-medium text-gray-700">Social media page</label>
                                <input
                                    id="social_media"
                                    v-model="form.social_media"
                                    type="url"
                                    placeholder="https://instagram.com/yourbrand"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-1 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                />
                                <p class="mt-1 text-sm text-gray-500">A URL link to your social media page</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Product Images & Summary -->
                    <div v-show="currentStep === 5" class="space-y-6">
                        <!-- Product Preview Upload -->
                        <div>
                            <label for="product_images" class="mb-2 block text-sm font-medium text-gray-700">
                                Upload a preview of your product range you wish to market on Takealot
                            </label>
                            <p class="mb-3 text-sm text-gray-500">
                                If no website URL is provided, kindly upload images of your physical products or stock.
                            </p>

                            <!-- Drag and Drop Area -->
                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                                class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white p-6 text-center transition hover:border-primary focus:outline-none"
                                :class="{ 'border-primary bg-blue-50': isDragging }"
                            >
                                <input
                                    id="product_images"
                                    ref="imageInput"
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    @change="handleFileChange"
                                    class="hidden"
                                />
                                <p class="mb-2 text-sm text-gray-600">Choose files or drag here</p>
                                <button
                                    type="button"
                                    @click="imageInput && imageInput.click()"
                                    class="hover:bg-primary-dark mt-2 rounded bg-primary px-4 py-1 text-white focus:outline-none"
                                >
                                    Browse Files
                                </button>
                            </div>

                            <!-- Preview Thumbnails -->
                            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <div v-for="(img, index) in imagePreviews" :key="index" class="relative">
                                    <img :src="img" class="h-32 w-full rounded-lg object-cover shadow" />
                                    <button
                                        @click="removeImage(index)"
                                        type="button"
                                        class="absolute top-1 right-1 rounded-full bg-white p-1 text-xs shadow hover:bg-red-500 hover:text-white"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Business Summary & Certifications (Single Textarea) -->
                        <div>
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">
                                Provide us with a brief summary of your business and products
                            </h2>

                            <ul class="mb-2 list-inside list-disc space-y-1 text-sm text-gray-500">
                                <li>What makes your business or products unique?</li>
                                <li>What products did you have in mind to market on the Takealot platform?</li>
                                <li>Do you have any feature requirements?</li>
                                <li>Does your business or products have any certifications? (e.g. ISO, Proudly SA, ICASA, NRCS)</li>
                            </ul>

                            <textarea
                                id="description"
                                v-model="form.description"
                                name="description"
                                rows="6"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                placeholder="Describe your business, products, and any unique aspects or certifications..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Step 6: Final Details -->
                    <div v-show="currentStep === 6" class="space-y-6">
                        <!-- How Did You Find Us -->
                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">How did you find us</h2>

                            <label for="discovery_source" class="mb-2 block text-sm font-medium text-gray-700">
                                Where did you hear about Takealot Marketplace?
                            </label>

                            <div class="grid grid-cols-1 gap-3">
                                <div v-for="source in DISCOVERY_SOURCES" :key="source" class="flex items-center space-x-3">
                                    <input
                                        type="radio"
                                        :id="`source-${source}`"
                                        v-model="form.discovery_source"
                                        :value="source"
                                        name="discovery_source"
                                        class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                        required
                                    />
                                    <label :for="`source-${source}`" class="text-sm text-gray-700">
                                        {{ source }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.discovery_source === 'Referral'" class="mt-4">
                            <label for="referrer_email" class="mb-1 block text-sm font-medium text-gray-700">
                                I was referred by (email address)
                            </label>
                            <input
                                id="referrer_email"
                                v-model="form.referrer_email"
                                type="email"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 transition focus:ring-2 focus:ring-primary focus:outline-none"
                                placeholder="e.g., colleague@example.com"
                                required
                            />
                        </div>

                        <div class="space-y-4">
                            <h2 class="border-b border-gray-100 pb-2 text-xl font-semibold text-gray-800">Distributor Sharing Consent</h2>

                            <p class="text-sm text-gray-600">
                                Opt in to share your contact details, product range and website with South African distributors who are sellers on
                                buyalot.com.
                                <br />
                                For international sellers who cannot sell directly on buyalot.com, allow us to share your contact information with our
                                sellers who may purchase and list your product range.
                            </p>

                            <div class="flex gap-6">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.share_with_distributors"
                                        value="no"
                                        name="share_with_distributors"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                        required
                                    />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>

                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.share_with_distributors"
                                        value="yes"
                                        name="share_with_distributors"
                                        class="border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start">
                                <input
                                    id="agreed_to_privacy"
                                    v-model="form.agreed_to_privacy"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                    required
                                />
                                <label for="agreed_to_privacy" class="ml-3 text-sm text-gray-700">
                                    I agree to the
                                    <a href="#" target="_blank" class="hover:text-primary-dark text-primary underline">Privacy Policy </a>.
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6">
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep"
                            class="rounded-lg border border-gray-300 bg-white px-6 py-2 font-medium text-gray-700 shadow-sm transition-colors duration-200 hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:outline-none"
                        >
                            Back
                        </button>
                        <div v-else></div>
                        <!-- Empty div to maintain space -->

                        <div class="mt-4 flex items-center justify-between">
                            <!-- Left: Continue -->
                            <button
                                v-if="currentStep < steps.length"
                                type="button"
                                @click="nextStep"
                                class="rounded-lg bg-primary px-6 py-2 font-medium text-white shadow-sm transition-colors duration-200 hover:bg-secondary focus:ring-2 focus:ring-primary focus:outline-none"
                            >
                                Continue
                            </button>
                            <div v-else></div>
                            <!-- Right: Reset + Submit -->
                            <div class="flex gap-4">
                                <button
                                    v-if="currentStep === steps.length"
                                    type="button"
                                    @click="resetApplication"
                                    class="rounded-lg bg-red-600 px-6 py-2 font-medium text-white shadow-sm transition-colors duration-200 hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:outline-none"
                                >
                                    Reset
                                </button>

                                <button
                                    v-if="currentStep === steps.length"
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-lg bg-primary px-6 py-2 font-medium text-white shadow-sm transition-colors duration-200 hover:bg-secondary focus:ring-2 focus:ring-primary focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <span v-if="form.processing">Processing...</span>
                                    <span v-else>Submit</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </MainLayout>
</template>
