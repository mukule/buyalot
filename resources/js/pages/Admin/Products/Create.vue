<script setup lang="ts">
import CategoryDropdown from '@/components/CategoryDropdown.vue';
import MarketplaceListingFields from '@/components/marketplace/MarketplaceListingFields.vue';
import ProductImageUploader from '@/components/ProductImageUploader.vue';
import ProductVariantCreator from '@/components/ProductVariantCreator.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import VariantImages from '@/components/VariantImages.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ImageItem, VariantRow } from '@/types/product';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, onMounted, reactive, ref, watch } from 'vue';

// Lazy-loaded so the CKEditor bundle stays out of the main chunk.
const RichTextEditor = defineAsyncComponent(() => import('@/components/RichTextEditor.vue'));

// ------------------ Types ------------------
interface OptionItem {
    id: number | string;
    name: string;
}


// Extend VariantRow for frontend Step 5
interface VariantRowWithImages extends VariantRow {
    primaryImage?: ImageItem;
    galleryImages?: ImageItem[];
}

interface VariantImagePayload {
    id: number | null;
    variant_id: number | null;
    file: File | null;
    is_primary: boolean;
    sort_order: number;
}

// ------------------ Form Types ------------------
interface ProductFormBase {
    product_id: number | null;
    step: number;
    product_code: string;
    name: string;
    category_id: string;
    brand_id: string;
    unit_id: string;
    variant_rows: VariantRow[];
    variant_images: VariantImagePayload[];
    images: ImageItem[];
    video_url?: string | null;
    package_size: string;
    marketplaces: string[];
    attributes: Record<string, any>;
}

interface ProductFormEditorFields {
    description: any;
    features: any;
    specifications: any;
    whats_in_the_box: any;
}

type ProductForm = ProductFormBase & ProductFormEditorFields;

// ------------------ Page Props ------------------
const page = usePage();
const title = (page.props as any).title ?? 'Create Product';
const breadcrumbs = (page.props as any).breadcrumbs ?? [];
const categories = (page.props as any).categories ?? [];
const brands = (page.props as any).brands ?? [];
const units = (page.props as any).units ?? [];
const variantCategories = (page.props as any).variantCategories ?? [];
const availableMarketplaces = (page.props as any).availableMarketplaces ?? [];
const carMakes = (page.props as any).carMakes ?? [];
const product = (page.props as any).product ?? null;

// Options
const brandOptions: OptionItem[] = brands.map((b: any) => ({ id: b.id, name: b.name }));
const unitOptions: OptionItem[] = units.map((u: any) => ({ id: u.id, name: u.name }));

// Steps
const steps = ['Basic Info', 'Content', 'Variants', 'Images', 'Variant Images'];
const currentStep = reactive({ value: 0 });
const completedSteps = reactive<number[]>([]);

// ------------------ Helper to map images ------------------
const mapToImageItems = (images: any[]): ImageItem[] =>
    images.map((img) => ({
        id: img.id,
        url: img.url,
        is_primary: img.is_primary ?? false,
        file: null as File | null,
        preview: img.url,
    }));

// ------------------ Variants / Images ------------------
const variantRows = ref<VariantRow[]>(
    (product?.variant_rows ?? []).map((row: VariantRow) => ({
        ...row,
        values: Object.fromEntries(Object.entries(row.values ?? {}).map(([k, v]) => [String(k), v])),
        buying_price: Number(row.buying_price) || 0,
        marked_price: Number(row.marked_price) || 0,
        stock: Number(row.stock) || 0,
        images: mapToImageItems(row.images ?? []),
    })),
);

const images = ref<ImageItem[]>(mapToImageItems(product?.images ?? []));

// Step 5: Variant rows with primaryImage / galleryImages
const variantRowsWithImages = ref<VariantRowWithImages[]>(
    (product?.variant_rows ?? []).map((row: VariantRow) => {
        const rowImages = mapToImageItems(row.images ?? []);
        const primary = rowImages.find((i) => i.is_primary);
        const gallery = rowImages.filter((i) => !i.is_primary);

        return {
            ...row,
            values: Object.fromEntries(Object.entries(row.values ?? {}).map(([k, v]) => [String(k), v])),
            buying_price: Number(row.buying_price) || 0,
            marked_price: Number(row.marked_price) || 0,
            stock: Number(row.stock) || 0,
            images: rowImages,
            primaryImage: primary,
            galleryImages: gallery,
        };
    }),
);

// ------------------ Form ------------------
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
    variant_rows: variantRows.value,
    images: images.value,
    package_size: product?.package_size ?? 'small',
    video_url: product?.video_url ?? null,
    variant_images: [],
    marketplaces: product?.marketplaces ?? [],
    attributes: product?.attributes ?? {},
});

// ------------------ Video Preview ------------------
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

// ------------------ Submit & Navigation ------------------
const isSubmitting = reactive({ value: false });

const initializeFromProduct = () => {
    if (product?.current_step) {
        const stepIndex = product.current_step - 1;
        currentStep.value = stepIndex;
        const maxCompleted = (product?.max_step_completed ?? product.current_step) - 1;
        for (let i = 0; i <= maxCompleted; i++) completedSteps.push(i);
    }
};

onMounted(() => {
    const flash = (page.props as any).flash;
    if (flash?.step) {
        const flashStep = flash.step - 1;
        currentStep.value = flashStep;
        if (flash.product_id) form.product_id = flash.product_id;
        for (let i = 0; i <= flashStep; i++) completedSteps.push(i);
    } else {
        initializeFromProduct();
    }
});

watch(
    () => (page.props as any).flash,
    (flash) => {
        if (flash?.product_id) form.product_id = flash.product_id;
        if (flash?.step) {
            const stepIndex = flash.step - 1;
            if (!completedSteps.includes(stepIndex - 1)) completedSteps.push(stepIndex - 1);
        }
        // ✅ Update variantRows when variants are created in Step 3
        if (flash?.variant_rows) {
            variantRows.value = flash.variant_rows.map((row: any) => ({
                ...row,
                values: Object.fromEntries(Object.entries(row.values ?? {}).map(([k, v]) => [String(k), v])),
                buying_price: Number(row.buying_price) || 0,
                marked_price: Number(row.marked_price) || 0,
                stock: Number(row.stock) || 0,
                images: mapToImageItems(row.images ?? []),
            }));
        }
    },
    { deep: true },
);

// ✅ Sync variantRows from Step 3 to variantRowsWithImages for Step 5
watch(
    variantRows,
    (newRows) => {
        // When variants are updated in Step 3, sync them to Step 5
        variantRowsWithImages.value = newRows.map((row) => {
            const rowImages = row.images ?? [];
            const primary = rowImages.find((i) => i.is_primary);
            const gallery = rowImages.filter((i) => !i.is_primary);

            return {
                ...row,
                primaryImage: primary,
                galleryImages: gallery,
            };
        });
    },
    { deep: true },
);

const isTabEnabled = (index: number) => {
    const lastCompletedStep = completedSteps.length ? Math.max(...completedSteps) : -1;
    return index === currentStep.value || completedSteps.includes(index) || index === lastCompletedStep + 1;
};

const handleTabClick = (index: number) => {
    if (!isTabEnabled(index)) return;
    if (index !== currentStep.value && form.isDirty && !confirm('You have unsaved changes. Are you sure?')) return;
    currentStep.value = index;
};

// ------------------ Submit Step ------------------
const submitStep = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    const step = currentStep.value + 1;

    // ------------------ Step 3: Variants ------------------
    if (currentStep.value === 2) {
        form.step = step;
        form.variant_rows = variantRows.value;

        await form.post(route('admin.products.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: handleSuccess,
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
        return;
    }

    // ------------------ Step 4: Product Images ------------------
    if (currentStep.value === 3) {
        form.step = step;
        form.images = images.value;

        // Ensure at least one primary image
        if (!form.images.some((i) => i.is_primary) && form.images.length) {
            form.images[0].is_primary = true;
        }

        await form.post(route('admin.products.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: handleSuccess,
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
        return;
    }

    // ------------------ Step 5: Variant Images ------------------
    if (currentStep.value === 4) {
        const formData = new FormData();

        // Ensure product_id exists
        if (!form.product_id) {
            console.warn('Warning: product_id is missing for Step 5. Step 3 must return product_id!');
        }

        formData.append('product_id', String(form.product_id ?? ''));
        formData.append('step', String(step));

        // Flatten variant images
        const flatVariantImages: VariantImagePayload[] = variantRowsWithImages.value.flatMap((row) => {
            const imgs = row.images ?? [];
            return imgs.map((img, idx) => ({
                id: typeof img.id === 'number' ? img.id : null,
                variant_id: typeof row.id === 'number' ? row.id : parseInt(String(row.id)),
                file: img.file instanceof File ? img.file : null,
                is_primary: !!img.is_primary,
                sort_order: idx,
            }));
        });

        flatVariantImages.forEach((img, index) => {
            formData.append(`variant_images[${index}][id]`, img.id !== null ? String(img.id) : '');
            formData.append(`variant_images[${index}][variant_id]`, String(img.variant_id));
            formData.append(`variant_images[${index}][is_primary]`, img.is_primary ? '1' : '0');
            formData.append(`variant_images[${index}][sort_order]`, String(img.sort_order));

            if (img.file instanceof File) {
                formData.append(`variant_images[${index}][file]`, img.file);
            }
        });

        // Add variant_rows separately
        const variantRowsData = variantRowsWithImages.value.map((row) => ({
            id: row.id,
            sku: row.sku,
            buying_price: Number(row.buying_price) || 0,
            marked_price: Number(row.marked_price) || 0,
            stock: Number(row.stock) || 0,
            values: row.values,
        }));

        formData.append('variant_rows', JSON.stringify(variantRowsData));

        // Debugging log
        console.log('Step 5 FormData payload:', {
            product_id: form.product_id,
            step,
            variant_images_count: flatVariantImages.length,
            variant_rows_count: variantRowsData.length,
        });

        // Use router.post for FormData upload
        router.post(route('admin.products.store'), formData, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (pageResponse) => {
                const flash = (pageResponse.props as any).flash;
                if (flash?.product_id) form.product_id = flash.product_id;

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
        return;
    }

    // ------------------ Steps 1 & 2: Basic Info & Content ------------------
    form.step = step;

    await form.post(route('admin.products.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: handleSuccess,
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

function handleSuccess(pageResponse: any) {
    const flash = (pageResponse.props as any).flash;
    if (flash?.product_id) form.product_id = flash.product_id;

    if (!completedSteps.includes(currentStep.value)) {
        completedSteps.push(currentStep.value);
    }

    if (currentStep.value < steps.length - 1) {
        currentStep.value += 1;
    }

    form.clearErrors();
}
</script>

<template>
    <Head
        ><title>{{ title }}</title></Head
    >

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto w-full max-w-6xl space-y-8 rounded-xl border border-gray-200 bg-white p-8 shadow-lg">
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900">{{ title }}</h2>
                    <p class="mt-2 text-sm text-gray-500">Follow the steps below to create your product</p>
                </div>

                <!-- Tabs -->
                <div class="mb-8">
                    <div class="flex justify-center">
                        <nav class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1" aria-label="Tabs">
                            <button
                                v-for="(step, index) in steps"
                                :key="index"
                                @click="handleTabClick(index)"
                                type="button"
                                class="relative rounded-md px-6 py-2.5 text-sm font-medium whitespace-nowrap transition-all duration-200"
                                :class="[
                                    currentStep.value === index
                                        ? 'bg-primary text-white shadow-sm'
                                        : isTabEnabled(index)
                                          ? 'cursor-pointer text-gray-700 hover:bg-white hover:text-primary hover:shadow-sm'
                                          : 'cursor-not-allowed text-gray-400 opacity-50',
                                ]"
                                :disabled="!isTabEnabled(index)"
                            >
                                <span class="flex items-center gap-2">
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                                        :class="[
                                            currentStep.value === index
                                                ? 'bg-white/20 text-white'
                                                : completedSteps.includes(index)
                                                  ? 'bg-green-100 text-green-600'
                                                  : 'bg-gray-200 text-gray-600',
                                        ]"
                                    >
                                        <svg
                                            v-if="completedSteps.includes(index) && currentStep.value !== index"
                                            class="h-4 w-4"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <span v-else>{{ index + 1 }}</span>
                                    </span>
                                    <span>{{ step }}</span>
                                </span>
                            </button>
                        </nav>
                    </div>
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
                            <label class="block text-sm font-medium text-gray-700">Package Size*</label>
                            <select v-model="form.package_size" class="w-full rounded-md border px-3 py-2">
                                <option value="small">Small</option>
                                <option value="medium">Medium</option>
                                <option value="large">Large</option>
                            </select>
                        </div>

                            <div>
                                <SearchableSelect v-model="form.brand_id" :options="brandOptions" label="Brand*" placeholder="Select Brand" />
                            </div>
                            <div class="md:col-span-2">
                                <CategoryDropdown v-model="form.category_id" :categories="categories" label="Category*" />
                            </div>
                        </div>

                        <!-- Marketplaces + vertical listing details (post a car / item) -->
                        <MarketplaceListingFields
                            v-if="availableMarketplaces.length"
                            :available="availableMarketplaces"
                            :car-makes="carMakes"
                            :marketplaces="form.marketplaces"
                            :attributes="form.attributes"
                        />
                    </div>

                    <!-- Step 2: Content -->
                    <div v-show="currentStep.value === 1" class="space-y-6">
                        <div v-for="field in editorFields" :key="field" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">{{
                                field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
                            }}</label>
                            <RichTextEditor
                                v-model="form[field]"
                                placeholder="Write here..."
                                class="min-h-[200px] rounded-md border border-gray-200"
                            />
                        </div>
                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">YouTube Video URL (optional)</label>
                            <input
                                type="url"
                                v-model="form.video_url"
                                placeholder="https://www.youtube.com/watch?v=VIDEO_ID"
                                class="block w-full rounded-md border px-3 py-2"
                            />
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

                    <!-- Step 5: Variant Images -->
                    <div v-show="currentStep.value === 4">
                        <VariantImages v-model:variantRows="variantRowsWithImages" :variantCategories="variantCategories" />
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                        <button
                            type="button"
                            v-if="currentStep.value > 0"
                            @click="handleTabClick(currentStep.value - 1)"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all hover:border-gray-400 hover:bg-gray-50"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Previous
                        </button>
                        <div v-else></div>

                        <button
                            type="submit"
                            :disabled="isSubmitting.value"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-primary/90 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="isSubmitting.value" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Saving...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                {{ currentStep.value < steps.length - 1 ? 'Save & Continue' : 'Finish & Save Product' }}
                                <svg
                                    v-if="currentStep.value < steps.length - 1"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
