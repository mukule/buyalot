<script setup lang="ts">
import CategoryDropdown from '@/components/CategoryDropdown.vue';
import ProductImageUploader from '@/components/ProductImageUploader.vue';
import ProductVariantCreator from '@/components/ProductVariantCreator.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { onMounted, ref, watch } from 'vue';

// Types
interface OptionItem {
    id: number | string;
    name: string;
}

// Base form interface
interface ProductFormBase {
    product_id: number | null;
    step: number;
    product_code: string;
    name: string;
    category_id: string;
    brand_id: string;
    unit_id: string;
    variant_rows: any[];
    images: any[];
}

// Editor fields interface (using 'any' for Quill compatibility)
interface ProductFormEditorFields {
    description: any;
    features: any;
    specifications: any;
    whats_in_the_box: any;
}

// Combined form type
type ProductForm = ProductFormBase & ProductFormEditorFields;

type FormErrors = Partial<Record<keyof ProductForm, string>>;

// Inertia props
const page = usePage().props as any;

const title = page.title ?? 'Create Product';
const breadcrumbs = page.breadcrumbs ?? [];
const categories = page.categories ?? [];
const brands = page.brands ?? [];
const units = page.units ?? [];
const variantCategories = page.variantCategories ?? [];
const product = page.product ?? null;

// Options
const brandOptions: OptionItem[] = brands.map((b: any) => ({ id: b.id, name: b.name }));
const unitOptions: OptionItem[] = units.map((u: any) => ({ id: u.id, name: u.name }));

// Steps
const steps = ['Basic Info', 'Content', 'Variants', 'Images'];
const currentStep = ref(0);

// Track completed steps (steps that have been successfully saved)
const completedSteps = ref<number[]>([]);

// Form with proper typing
const form = useForm<ProductForm>({
    product_id: product?.id ?? null,
    step: 1,
    product_code: product?.product_code ?? '',
    name: product?.name ?? '',
    category_id: product?.category_id ?? '',
    brand_id: product?.brand_id ?? '',
    unit_id: product?.unit_id ?? '',
    description: product?.description ?? '',
    features: product?.features ?? '',
    specifications: product?.specifications ?? '',
    whats_in_the_box: product?.whats_in_the_box ?? '',
    variant_rows: product?.variant_rows ?? [],
    images: product?.images ?? [],
});

// Editor fields with proper typing
const editorFields: (keyof ProductFormEditorFields)[] = ['description', 'features', 'specifications', 'whats_in_the_box'];

// Variants & Images
const variantRows = ref<any[]>(product?.variant_rows ?? []);
const images = ref<any[]>(product?.images ?? []);

const isSubmitting = ref(false);

// Initialize completed steps from existing product
const initializeCompletedSteps = () => {
    if (product?.max_step_completed) {
        // User has completed up to this step
        const maxCompletedStep = product.max_step_completed - 1; // Convert to 0-based index

        // Mark all steps up to max_step_completed as completed
        for (let i = 0; i <= maxCompletedStep; i++) {
            if (!completedSteps.value.includes(i)) {
                completedSteps.value.push(i);
            }
        }

        // Set current step (use current_step for where they left off)
        const currentStepIndex = product.current_step - 1;
        currentStep.value = currentStepIndex;

        // If current step is ahead of max completed, don't mark it as completed
        // Only mark it as completed if it's within the completed range
        if (currentStepIndex <= maxCompletedStep && !completedSteps.value.includes(currentStepIndex)) {
            completedSteps.value.push(currentStepIndex);
        }
    } else if (product?.current_step) {
        // Fallback: only current_step available (for backward compatibility)
        const currentStepIndex = product.current_step - 1;
        currentStep.value = currentStepIndex;

        // Assume all previous steps are completed
        for (let i = 0; i < currentStepIndex; i++) {
            if (!completedSteps.value.includes(i)) {
                completedSteps.value.push(i);
            }
        }

        // Also mark current step if it's been saved
        completedSteps.value.push(currentStepIndex);
    }
};

// Restore step from backend
onMounted(() => {
    initializeCompletedSteps();
});

// Watch for page props changes
watch(
    () => page,
    (newPage) => {
        const flash = newPage.flash;
        if (flash?.step) {
            const stepIndex = flash.step - 1; // Convert to 0-based

            // Mark this step as completed when successfully saved
            if (!completedSteps.value.includes(stepIndex)) {
                completedSteps.value.push(stepIndex);
            }

            // Also mark all previous steps as completed
            for (let i = 0; i < stepIndex; i++) {
                if (!completedSteps.value.includes(i)) {
                    completedSteps.value.push(i);
                }
            }

            currentStep.value = stepIndex;
        }
        if (flash?.product_id) {
            form.product_id = flash.product_id;
        }
    },
    { deep: true },
);

// Check if a tab is enabled (using max_step_completed logic)
const isTabEnabled = (index: number): boolean => {
    // Tab is enabled if:
    // 1. It's the current step, OR
    // 2. It's a previously completed step (index is in completedSteps)
    // 3. OR it's the next step after the last completed one (allow forward progression)
    const isNextStepAfterLastCompleted = index === completedSteps.value.length;

    return index === currentStep.value || completedSteps.value.includes(index) || isNextStepAfterLastCompleted;
};

// Handle tab click with validation
const handleTabClick = (index: number) => {
    // Don't do anything if tab is disabled
    if (!isTabEnabled(index)) return;

    // If trying to navigate to a step beyond completed ones, warn about unsaved progress
    if (index > Math.max(...completedSteps.value, -1)) {
        if (!confirm("This step hasn't been saved yet. You'll need to complete previous steps first.")) {
            return;
        }
    }

    // If trying to navigate away from current step with unsaved changes, warn user
    if (index !== currentStep.value && form.isDirty) {
        if (!confirm('You have unsaved changes. Are you sure you want to leave this step?')) {
            return;
        }
    }

    currentStep.value = index;
};

// Error helper
const getError = (key: keyof ProductForm | string): string | null => {
    // Check form.errors with type assertion
    const errors = form.errors as any;
    if (errors && errors[key]) {
        return String(errors[key]);
    }

    // Check page.errors (session errors from backend)
    const pageErrors = (usePage().props as any).errors;
    if (pageErrors && pageErrors[key]) {
        return Array.isArray(pageErrors[key]) ? pageErrors[key].join(', ') : String(pageErrors[key]);
    }

    return null;
};

// Submit
const submitStep = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    // Always submit current step explicitly
    form.step = currentStep.value + 1;
    form.variant_rows = variantRows.value;
    form.images = images.value.map((i) => i.file ?? i);

    await form.post(route('admin.products.store'), {
        preserveScroll: true,
        preserveState: true,

        // Backend SUCCESS only (2xx responses)
        onSuccess: () => {
            const props = usePage().props as any;
            const flash = props.flash;

            if (flash?.product_id) {
                form.product_id = flash.product_id;
            }

            if (flash?.step) {
                const stepIndex = flash.step - 1;

                // Mark this step as completed
                if (!completedSteps.value.includes(stepIndex)) {
                    completedSteps.value.push(stepIndex);
                }

                // Also mark all previous steps as completed
                for (let i = 0; i < stepIndex; i++) {
                    if (!completedSteps.value.includes(i)) {
                        completedSteps.value.push(i);
                    }
                }

                currentStep.value = stepIndex;
            }

            // Clear errors on successful step
            form.clearErrors();

            // Final step completed
            if (currentStep.value >= steps.length - 1) {
                // Optional: Show success message before resetting
                setTimeout(() => {
                    form.reset();
                    variantRows.value = [];
                    images.value = [];
                    currentStep.value = 0;
                    completedSteps.value = [];
                }, 1000);
            }
        },

        // Validation errors (422) or other errors
        onError: (errors) => {
            console.log('Form submission errors:', errors);

            // Check if step was preserved from backend via flash
            const props = usePage().props as any;
            const flash = props.flash;

            if (flash?.step) {
                currentStep.value = flash.step - 1;
            } else {
                // Fallback to staying on current step
                currentStep.value = form.step - 1;
            }
        },

        onFinish: () => {
            isSubmitting.value = false;
        },
    });
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

                <!-- Global error message (optional) -->
                <div v-if="form.hasErrors" class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc space-y-1 pl-5">
                                    <li v-for="(error, field) in form.errors" :key="field">
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            v-for="(step, index) in steps"
                            :key="index"
                            @click="handleTabClick(index)"
                            type="button"
                            class="border-b-2 px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors"
                            :class="[
                                currentStep === index
                                    ? 'border-primary text-primary'
                                    : isTabEnabled(index)
                                      ? 'cursor-pointer border-transparent text-gray-700 hover:border-gray-300 hover:text-gray-900'
                                      : 'cursor-not-allowed border-transparent text-gray-400',
                            ]"
                            :disabled="!isTabEnabled(index)"
                        >
                            {{ step }}
                            <!-- Completed step indicator -->
                            <span v-if="completedSteps.includes(index) && currentStep !== index" class="ml-1 text-green-500">✓</span>
                        </button>
                    </nav>
                </div>

                <!-- Step progress indicator -->
                <div class="rounded-lg bg-gray-50 p-4">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium">Progress</span>
                        <span class="text-gray-600">
                            {{ completedSteps.length }} of {{ steps.length }} steps completed
                            <span v-if="product?.max_step_completed" class="text-gray-400"> </span>
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                        <div
                            class="h-full rounded-full bg-green-500 transition-all duration-300"
                            :style="{ width: `${(completedSteps.length / steps.length) * 100}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitStep" class="w-full">
                    <!-- Step 1: Basic Info -->
                    <div v-show="currentStep === 0" class="space-y-6">
                        <div class="space-y-6 rounded-lg border border-gray-200 p-6 shadow-sm">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Product Code</label>
                                    <input
                                        v-model="form.product_code"
                                        type="text"
                                        placeholder="Optional (auto-generated for new products)"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': getError('product_code') }"
                                    />
                                    <p v-if="getError('product_code')" class="mt-1 text-sm text-red-600">{{ getError('product_code') }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Product Name*</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Enter product name"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': getError('name') }"
                                    />
                                    <p v-if="getError('name')" class="mt-1 text-sm text-red-600">{{ getError('name') }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Unit*</label>
                                    <select
                                        v-model="form.unit_id"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': getError('unit_id') }"
                                    >
                                        <option value="" disabled>Select Unit</option>
                                        <option v-for="u in unitOptions" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                    <p v-if="getError('unit_id')" class="mt-1 text-sm text-red-600">{{ getError('unit_id') }}</p>
                                </div>

                                <div>
                                    <SearchableSelect
                                        v-model="form.brand_id"
                                        :options="brandOptions"
                                        label="Brand*"
                                        placeholder="Select Brand"
                                        :error="getError('brand_id')"
                                    />
                                    <p v-if="getError('brand_id')" class="mt-1 text-sm text-red-600">{{ getError('brand_id') }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <CategoryDropdown
                                        v-model="form.category_id"
                                        :categories="categories"
                                        label="Category*"
                                        :error="getError('category_id')"
                                    />
                                    <p v-if="getError('category_id')" class="mt-1 text-sm text-red-600">{{ getError('category_id') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Content -->
                    <div v-show="currentStep === 1" class="space-y-6">
                        <div class="space-y-6 rounded-lg border border-gray-200 p-6 shadow-sm">
                            <div v-for="field in editorFields" :key="field" class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
                                </label>
                                <div :class="{ 'rounded-md border-red-300': getError(field) }">
                                    <QuillEditor
                                        v-model:content="form[field]"
                                        contentType="html"
                                        theme="snow"
                                        placeholder="Write here..."
                                        class="min-h-[200px] rounded-md border border-gray-200 bg-white"
                                    />
                                </div>
                                <p v-if="getError(field)" class="mt-1 text-sm text-red-600">{{ getError(field) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Variants -->
                    <div v-show="currentStep === 2" class="rounded-lg border border-gray-200 p-6 shadow-sm">
                        <ProductVariantCreator v-model:variantRows="variantRows" :variantCategories="variantCategories" />
                        <!-- Show variant errors if any -->
                        <div v-if="getError('variant_rows')" class="mt-4 rounded-md bg-red-50 p-3">
                            <p class="text-sm text-red-600">{{ getError('variant_rows') }}</p>
                        </div>
                    </div>

                    <!-- Step 4: Images -->
                    <div v-show="currentStep === 3" class="rounded-lg border border-gray-200 p-6 shadow-sm">
                        <ProductImageUploader v-model="images" />
                        <!-- Show image errors if any -->
                        <div v-if="getError('images')" class="mt-4 rounded-md bg-red-50 p-3">
                            <p class="text-sm text-red-600">{{ getError('images') }}</p>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6">
                        <button
                            type="button"
                            v-if="currentStep > 0"
                            @click="handleTabClick(currentStep - 1)"
                            class="rounded-md bg-gray-200 px-6 py-2.5 text-gray-700 hover:bg-gray-300"
                            :disabled="isSubmitting"
                        >
                            Previous
                        </button>
                        <div class="ml-auto">
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="hover:bg-primary-dark rounded-md bg-primary px-6 py-2.5 text-white disabled:opacity-50"
                            >
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
