<script setup lang="ts">
import CategoryDropdown from '@/components/CategoryDropdown.vue';
import ProductImageUploader from '@/components/ProductImageUploader.vue';
import ProductVariantCreator from '@/components/ProductVariantCreator.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Types
interface OptionItem {
    id: number | string;
    name: string;
}

// Inertia props
const page = usePage().props as any;

const title = page.title ?? 'Create Product';
const breadcrumbs = page.breadcrumbs ?? [];
const categories = page.categories ?? [];
const brands = page.brands ?? [];
const units = page.units ?? [];
const variantCategories = page.variantCategories ?? [];
const productData = page.product ?? null; // ✅ Use product instead of draft

// Mapped options
const brandOptions: OptionItem[] = brands.map((b: any) => ({ id: b.id, name: b.name }));
const unitOptions: OptionItem[] = units.map((u: any) => ({ id: u.id, name: u.name }));

// Multi-step tabs
const steps = ['Basic Info', 'Content', 'Variants', 'Images'];
const currentStep = ref(0);

// Form setup
const form = useForm({
    product_id: productData?.product_id ?? null,
    step: 1,
    product_code: productData?.product_code ?? '',
    name: productData?.name ?? '',
    category_id: productData?.category_id ?? '',
    brand_id: productData?.brand_id ?? '',
    unit_id: productData?.unit_id ?? '',
    description: productData?.description ?? '',
    features: productData?.features ?? '',
    specifications: productData?.specifications ?? '',
    whats_in_the_box: productData?.whats_in_the_box ?? '',
    variant_rows: productData?.variant_rows ?? [],
    images: productData?.images ?? [],
});

// Editor
const editor = ClassicEditor;
const editorFields = ['description', 'features', 'specifications', 'whats_in_the_box'];

// Variants & Images
const variantRows = ref<any[]>(productData?.variant_rows ?? []);
const images = ref<any[]>(productData?.images ?? []);

const isSubmitting = ref(false);

onMounted(() => {
    if (productData) {
        currentStep.value = (productData.current_step ?? 1) - 1;
    }
});

// Submit per step
const submitStep = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    form.step = currentStep.value + 1;
    form.variant_rows = variantRows.value;
    form.images = images.value.map((i) => i.file ?? i);

    const url = route('admin.products.store');

    try {
        await form.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                const flash = (usePage().props as any).flash;
                const pid = (flash?.product_id ?? form.product_id) as number | null;
                const step = (flash?.step ?? null) as number | null;

                if (pid) form.product_id = pid;
                if (step) currentStep.value = step - 1;

                if (currentStep.value < steps.length - 1) {
                    currentStep.value++;
                } else {
                    form.reset();
                    variantRows.value = [];
                    images.value = [];
                    currentStep.value = 0;
                }
            },
            onError: (errors) => {
                console.error('❌ Validation/Server errors:', errors);
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (err) {
        console.error('❌ Unexpected error:', err);
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head>
        <title>{{ title }}</title>
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mx-auto w-full max-w-6xl space-y-6 rounded-xl bg-white p-6 shadow">
                <h2 class="text-center text-2xl font-bold text-gray-800">{{ title }}</h2>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            v-for="(step, index) in steps"
                            :key="index"
                            @click="currentStep = index"
                            type="button"
                            class="border-b-2 px-4 py-2 text-sm font-medium whitespace-nowrap"
                            :class="
                                currentStep === index
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            "
                        >
                            {{ step }}
                        </button>
                    </nav>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitStep" class="w-full">
                    <!-- Step 1: Basic Info -->
                    <div v-show="currentStep === 0" class="space-y-6">
                        <div class="space-y-6 rounded-lg border border-gray-200 p-6 shadow-sm">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label>Product Code</label>
                                    <input
                                        v-model="form.product_code"
                                        type="text"
                                        placeholder="Optional (auto-generated for new products)"
                                        class="w-full rounded-md border px-3 py-2"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label>Product Name*</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Enter product name"
                                        class="w-full rounded-md border px-3 py-2"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <CategoryDropdown v-model="form.category_id" :categories="categories" label="Category*" />
                                <div>
                                    <SearchableSelect v-model="form.brand_id" :options="brandOptions" label="Brand*" placeholder="Select Brand" />
                                </div>
                            </div>

                            <div>
                                <label>Unit*</label>
                                <select v-model="form.unit_id" class="w-full rounded-md border px-3 py-2">
                                    <option value="" disabled>Select Unit</option>
                                    <option v-for="u in unitOptions" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Content -->
                    <div v-show="currentStep === 1" class="space-y-6">
                        <div class="space-y-6 rounded-lg border border-gray-200 p-6 shadow-sm">
                            <div v-for="field in editorFields" :key="field">
                                <label>{{ field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}</label>
                                <CKEditor
                                    :editor="editor"
                                    v-model="form[field as keyof typeof form]"
                                    :config="{ toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'undo', 'redo'] }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Variants -->
                    <div v-show="currentStep === 2" class="rounded-lg border border-gray-200 p-6 shadow-sm">
                        <ProductVariantCreator v-model:variantRows="variantRows" :variantCategories="variantCategories" />
                    </div>

                    <!-- Step 4: Images -->
                    <div v-show="currentStep === 3" class="rounded-lg border border-gray-200 p-6 shadow-sm">
                        <ProductImageUploader v-model="images" :product-id="form.product_id" />
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" v-if="currentStep > 0" @click="currentStep--" class="rounded-md bg-gray-200 px-6 py-2.5 text-gray-700">
                            Previous
                        </button>
                        <div class="ml-auto">
                            <button type="submit" :disabled="isSubmitting" class="rounded-md bg-primary px-6 py-2.5 text-white">
                                <span v-if="isSubmitting">Saving...</span>
                                <span v-else>{{ currentStep < steps.length - 1 ? 'Save & Next' : 'Save Product' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
