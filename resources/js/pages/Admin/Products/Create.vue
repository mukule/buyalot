<script setup lang="ts">
import CategoryDropdown from '@/components/CategoryDropdown.vue';
import ProductImageUploader from '@/components/ProductImageUploader.vue';
import ProductVariantCreator from '@/components/ProductVariantCreator.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { computed, onMounted, reactive, watch } from 'vue';

// Types
interface OptionItem {
    id: number | string;
    name: string;
}

interface VariantRow {
    id: number | null;
    sku: string | null;
    values: Record<number, string>;
    buying_price: number;
    marked_price: number;
    stock: number;
}

interface ProductFormBase {
    product_id: number | null;
    step: number;
    product_code: string;
    name: string;
    category_id: string;
    brand_id: string;
    unit_id: string;
    variant_rows: VariantRow[];
    images: any[];
    video_url?: string | null;
}

interface ProductFormEditorFields {
    description: any;
    features: any;
    specifications: any;
    whats_in_the_box: any;
}

type ProductForm = ProductFormBase & ProductFormEditorFields;

// Inertia props
const page = usePage();

const title = (page.props as any).title ?? 'Create Product';
const breadcrumbs = (page.props as any).breadcrumbs ?? [];
const categories = (page.props as any).categories ?? [];
const brands = (page.props as any).brands ?? [];
const units = (page.props as any).units ?? [];
const variantCategories = (page.props as any).variantCategories ?? [];
const product = (page.props as any).product ?? null;

// Options
const brandOptions: OptionItem[] = brands.map((b: any) => ({ id: b.id, name: b.name }));
const unitOptions: OptionItem[] = units.map((u: any) => ({ id: u.id, name: u.name }));

// Steps
const steps = ['Basic Info', 'Content', 'Variants', 'Images'];
const currentStep = reactive({ value: 0 });
const completedSteps = reactive<number[]>([]);

// Form Initialization
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
    video_url: product?.video_url ?? null,
});

const videoPreview = computed(() => {
    if (!form.video_url) return null;
    try {
        const parsed = new URL(form.video_url);
        const hostname = parsed.hostname.toLowerCase();
        let videoId: string | null = null;
        if (hostname.includes('youtube.com')) videoId = parsed.searchParams.get('v');
        else if (hostname === 'youtu.be') videoId = parsed.pathname.substring(1);
        return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
    } catch {
        return null;
    }
});

const editorFields: (keyof ProductFormEditorFields)[] = ['description', 'features', 'specifications', 'whats_in_the_box'];

const variantRows = reactive<VariantRow[]>(
    (product?.variant_rows ?? []).map((row: any) => ({
        id: row.id ?? null,
        sku: row.sku ?? null,
        values: { ...row.values },
        buying_price: row.buying_price ?? 0,
        marked_price: row.marked_price ?? 0,
        stock: row.stock ?? 0,
    })),
);

const images = reactive<any[]>(product?.images ?? []);
const isSubmitting = reactive({ value: false });

// Initialize logic
const initializeFromProduct = () => {
    if (product?.current_step) {
        const stepIndex = product.current_step - 1;
        currentStep.value = stepIndex;
        const maxCompleted = (product?.max_step_completed ?? product.current_step) - 1;
        for (let i = 0; i <= maxCompleted; i++) {
            if (!completedSteps.includes(i)) completedSteps.push(i);
        }
    }
};

onMounted(() => {
    const flash = (page.props as any).flash;
    if (flash?.step) {
        const flashStep = flash.step - 1;
        currentStep.value = flashStep;
        if (flash.product_id) form.product_id = flash.product_id;
        for (let i = 0; i <= flashStep; i++) {
            if (!completedSteps.includes(i)) completedSteps.push(i);
        }
    } else {
        initializeFromProduct();
    }
});

// Watch for flash updates (Crucial for the Refresh-Fix)
watch(
    () => (page.props as any).flash,
    (flash) => {
        if (flash?.product_id) {
            form.product_id = flash.product_id;
        }
        if (flash?.step) {
            const stepIndex = flash.step - 1;
            if (!completedSteps.includes(stepIndex - 1)) {
                completedSteps.push(stepIndex - 1);
            }
        }
    },
    { deep: true },
);

const isTabEnabled = (index: number): boolean => {
    const lastCompletedStep = completedSteps.length ? Math.max(...completedSteps) : -1;
    return index === currentStep.value || completedSteps.includes(index) || index === lastCompletedStep + 1;
};

const handleTabClick = (index: number) => {
    if (!isTabEnabled(index)) return;
    if (index !== currentStep.value && form.isDirty) {
        if (!confirm('You have unsaved changes. Are you sure?')) return;
    }
    currentStep.value = index;
};

// SUBMIT STEP - THE REFRESH FIX IS HERE
const submitStep = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    form.step = currentStep.value + 1;
    form.variant_rows = variantRows;
    form.images = images.map((i) => i.file ?? i);

    await form.post(route('admin.products.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (pageResponse) => {
            // FIX: Immediately sync the product_id from the response
            const flash = (pageResponse.props as any).flash;
            if (flash?.product_id) {
                form.product_id = flash.product_id;
            }

            if (!completedSteps.includes(currentStep.value)) {
                completedSteps.push(currentStep.value);
            }

            if (currentStep.value < steps.length - 1) {
                currentStep.value += 1;
            }
            form.clearErrors();
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
                                currentStep.value === index
                                    ? 'border-primary text-primary'
                                    : isTabEnabled(index)
                                      ? 'cursor-pointer border-transparent text-gray-700 hover:border-gray-300 hover:text-gray-900'
                                      : 'cursor-not-allowed border-transparent text-gray-400',
                            ]"
                            :disabled="!isTabEnabled(index)"
                        >
                            {{ step }}
                            <span v-if="completedSteps.includes(index) && currentStep.value !== index" class="ml-1 text-green-500">✓</span>
                        </button>
                    </nav>
                </div>

                <form @submit.prevent="submitStep" class="w-full space-y-6">
                    <!-- Step 1: Basic Info -->
                    <div v-show="currentStep.value === 0" class="space-y-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Product Code</label>
                                <input v-model="form.product_code" type="text" placeholder="Optional" class="w-full rounded-md border px-3 py-2" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Product Name*</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter product name"
                                    required
                                    class="w-full rounded-md border px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Unit*</label>
                                <select v-model="form.unit_id" class="w-full rounded-md border px-3 py-2">
                                    <option value="" disabled>Select Unit</option>
                                    <option v-for="u in unitOptions" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                            <div>
                                <SearchableSelect v-model="form.brand_id" :options="brandOptions" label="Brand*" placeholder="Select Brand" />
                            </div>
                            <div class="md:col-span-2">
                                <CategoryDropdown v-model="form.category_id" :categories="categories" label="Category*" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Content -->
                    <div v-show="currentStep.value === 1" class="space-y-6">
                        <!-- Editor fields -->
                        <div v-for="field in editorFields" :key="field" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">{{
                                field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
                            }}</label>
                            <QuillEditor
                                v-model:content="form[field]"
                                contentType="html"
                                theme="snow"
                                placeholder="Write here..."
                                class="min-h-[200px] rounded-md border border-gray-200"
                            />
                        </div>

                        <!-- YouTube Video URL -->
                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">YouTube Video URL (optional)</label>
                            <input
                                type="url"
                                v-model="form.video_url"
                                placeholder="https://www.youtube.com/watch?v=VIDEO_ID"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:outline-none"
                            />

                            <!-- Video Preview -->
                            <div v-if="videoPreview" class="mt-2 w-full max-w-md overflow-hidden rounded-md border">
                                <iframe
                                    :src="videoPreview"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    class="h-48 w-full"
                                ></iframe>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Variants -->
                    <div v-show="currentStep.value === 2">
                        <ProductVariantCreator v-model:variantRows="variantRows" :variantCategories="variantCategories" />
                    </div>

                    <!-- Step 4: Images -->
                    <div v-show="currentStep.value === 3">
                        <ProductImageUploader v-model="images" />
                    </div>

                    <!-- Navigation -->
                    <div class="flex justify-between pt-6">
                        <button
                            type="button"
                            v-if="currentStep.value > 0"
                            @click="handleTabClick(currentStep.value - 1)"
                            class="rounded-md bg-gray-200 px-6 py-2.5"
                        >
                            Previous
                        </button>
                        <button type="submit" class="ml-auto rounded-md bg-primary px-6 py-2.5 text-white">
                            {{ currentStep.value < steps.length - 1 ? 'Save & Next' : 'Save Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
