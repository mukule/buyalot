<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Brand, BreadcrumbItem, Category, Product, Subcategory, Unit, Variant, VariantCategory } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, watch, type Ref } from 'vue';

import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

const editor = ClassicEditor;

type ProductWithExtras = Product & {
    images: { id: number; hashid: string; image_path: string; is_primary: boolean }[];
    variants?: Variant[];
};

const pageProps = usePage().props as unknown as {
    product?: ProductWithExtras;
    brands?: Brand[];
    categories?: Category[];
    subcategories?: Subcategory[];
    units?: Unit[];
    variantCategories?: (VariantCategory & { variants?: Variant[] })[];
};

const product = pageProps.product ?? {
    id: 0,
    hashid: '',
    name: '',
    brand_id: null,
    subcategory: null,
    subcategory_id: null,
    unit_id: null,
    description: '',
    features: '',
    specifications: '',
    whats_in_the_box: '',
    price: 0,
    discount: 0,
    stock: 0,
    variants: [],
    images: [],
};

const brands = pageProps.brands ?? [];
const categories = pageProps.categories ?? [];
const subcategories = pageProps.subcategories ?? [];
const units = pageProps.units ?? [];
const variantCategoriesProp = pageProps.variantCategories ?? [];

const title = `Edit Product: ${product.name}`;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Products', href: '/admin/products' },
    { title, href: '' },
];

// Reactive variant categories (clone deeply)
const variantCategories = reactive(
    variantCategoriesProp.map((vc) => ({
        ...vc,
        variants: vc.variants?.map((v) => ({ ...v })) ?? [],
    })),
);

const variantErrors: Ref<Record<number, string[]>> = ref({});

// Form state as ref
const form = ref({
    brand_id: product.brand_id ? String(product.brand_id) : '',
    category_id: product.subcategory ? String(product.subcategory.category_id) : '',
    subcategory_id: product.subcategory_id ? String(product.subcategory_id) : '',
    unit_id: product.unit_id ? String(product.unit_id) : '',
    name: product.name ?? '',

    description: product.description ?? '',
    features: product.features ?? '',
    specifications: product.specifications ?? '',
    whats_in_the_box: product.whats_in_the_box ?? '',

    price: product.price?.toString() ?? '',
    discount: product.discount?.toString() ?? '',
    stock: product.stock?.toString() ?? '0',

    selectedVariants: {} as Record<number, number[]>,
});

// Initialize selectedVariants safely
variantCategories.forEach((vc) => {
    const selected = (product.variants ?? []).filter((v) => v.variant_category_id === vc.id).map((v) => v.id);
    form.value.selectedVariants[vc.id] = selected;
});

// Computed subcategories and brands
const filteredSubcategories = computed(() =>
    form.value.category_id ? subcategories.filter((s) => s.category_id === Number(form.value.category_id)) : [],
);
watch(
    () => form.value.category_id,
    () => {
        form.value.subcategory_id = '';
        form.value.brand_id = '';
    },
);

const filteredBrands = computed(() =>
    form.value.subcategory_id ? brands.filter((b) => b.subcategory_id === Number(form.value.subcategory_id)) : [],
);
watch(
    () => form.value.subcategory_id,
    () => {
        form.value.brand_id = '';
    },
);

// Image type and state
type ImageItem = {
    id?: number;
    file: File | null;
    preview: string;
    is_primary: boolean;
    image_path?: string;
};

// Base URL
const baseUrl = import.meta.env.VITE_APP_BASE_URL || '';

const images = ref<ImageItem[]>([]);
(product.images ?? []).forEach((img) => {
    images.value.push({
        id: img.id,
        file: null,
        preview: `${baseUrl}/storage/${img.image_path}`,
        is_primary: img.is_primary,
        image_path: img.image_path,
    });
});

function addImages(files: FileList | File[]) {
    console.log('[addImages] Adding images:', files);
    for (const file of Array.from(files)) {
        if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
            console.warn(`[addImages] Skipped unsupported file type: ${file.type}`);
            continue;
        }
        if (file.size > 10 * 1024 * 1024) {
            console.warn(`[addImages] Skipped file larger than 10MB: ${file.name}`);
            continue;
        }
        const previewUrl = URL.createObjectURL(file);
        images.value.push({
            file,
            preview: previewUrl,
            is_primary: images.value.length === 0,
        });
        console.log(`[addImages] Added image: ${file.name}`);
    }
}
function onImagesChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target?.files) {
        console.log('[onImagesChange] Files selected:', target.files);
        addImages(target.files);
    }
}
function onDrop(event: DragEvent) {
    event.preventDefault();
    if (event.dataTransfer?.files) {
        console.log('[onDrop] Files dropped:', event.dataTransfer.files);
        addImages(event.dataTransfer.files);
    }
}
function removeImage(index: number) {
    console.log(`[removeImage] Removing image at index: ${index}`);
    const image = images.value[index];
    if (image.preview && !image.id) URL.revokeObjectURL(image.preview);
    images.value.splice(index, 1);
    if (!images.value.some((img) => img.is_primary) && images.value[0]) {
        images.value[0].is_primary = true;
    }
}
function setPrimary(index: number) {
    console.log(`[setPrimary] Setting primary image to index: ${index}`);
    images.value.forEach((img, i) => (img.is_primary = i === index));
}
function handleImageError(index: number, e: Event) {
    console.error(`Image ${index} failed to load:`, e);
}

// Variant editing (logs inside as needed)
function addVariant(categoryIndex: number) {
    console.log(`[addVariant] Adding variant to category index: ${categoryIndex}`);
    variantCategories[categoryIndex].variants.push({
        id: null as unknown as number,
        variant_category_id: variantCategories[categoryIndex].id,
        value: '',
        is_active: true,
        created_at: '',
        updated_at: '',
    });
}
function removeVariant(categoryIndex: number, variantIndex: number) {
    console.log(`[removeVariant] Removing variant at index ${variantIndex} from category ${categoryIndex}`);
    variantCategories[categoryIndex].variants.splice(variantIndex, 1);
    variantErrors.value[categoryIndex]?.splice(variantIndex, 1);
}
function onVariantInput(categoryIndex: number, variantIndex: number) {
    const category = variantCategories[categoryIndex];
    const variants = category.variants;
    const current = variants[variantIndex].value.trim().toLowerCase();
    if (!current) return;

    const isDuplicate = variants.some((v, i) => i !== variantIndex && v.value.trim().toLowerCase() === current);
    if (isDuplicate) {
        console.warn(`[onVariantInput] Duplicate variant detected in category ${category.name}: "${variants[variantIndex].value}"`);
        variantErrors.value[categoryIndex] = [`Duplicate ${category.name.toLowerCase()} "${variants[variantIndex].value}"`];
        variants.splice(variantIndex, 1);
    } else {
        variantErrors.value[categoryIndex] = [];
    }
}

// Submission with logs
const isSubmitting = ref(false);

async function submit() {
    console.log('[submit] Submission started');
    if (isSubmitting.value) {
        console.log('[submit] Submission already in progress. Aborting.');
        return;
    }
    isSubmitting.value = true;

    try {
        const formData = new FormData();
        formData.append('name', form.value.name);
        formData.append('description', form.value.description);
        formData.append('features', form.value.features);
        formData.append('specifications', form.value.specifications);
        formData.append('whats_in_the_box', form.value.whats_in_the_box);
        formData.append('price', form.value.price);
        formData.append('discount', form.value.discount || '0');
        formData.append('stock', form.value.stock || '0');

        if (form.value.brand_id) formData.append('brand_id', form.value.brand_id);
        if (form.value.category_id) formData.append('category_id', form.value.category_id);
        if (form.value.subcategory_id) formData.append('subcategory_id', form.value.subcategory_id);
        if (form.value.unit_id) formData.append('unit_id', form.value.unit_id);

        const variantsPayload = Object.entries(form.value.selectedVariants).map(([vcId, variantIds]) => ({
            variant_category_id: Number(vcId),
            variant_ids: variantIds,
        }));
        formData.append('variants', JSON.stringify(variantsPayload));
        formData.append('variant_categories', JSON.stringify(variantCategories));

        images.value.forEach((img, i) => {
            if (img.file) {
                formData.append('images[]', img.file);
                if (img.is_primary) {
                    formData.append('primary_image_index', String(i));
                }
            } else if (img.id) {
                formData.append('existing_images[]', String(img.id));
                if (img.is_primary) {
                    formData.append('primary_image_index', String(i));
                }
            }
        });

        // Log formData keys and values for debug
        for (const pair of formData.entries()) {
            console.log(`[submit] formData key=${pair[0]} value=`, pair[1]);
        }

        const url = `/admin/products/${product.hashid}`;
        console.log('[submit] Sending POST request with method override to:', url);

        const response = await axios.post(url, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            params: { _method: 'PUT' }, // This is the key for Laravel method spoofing
        });

        console.log('[submit] Update successful:', response.data);

        router.get('/admin/products'); // Navigate with inertia
    } catch (err) {
        if (axios.isAxiosError(err)) {
            console.error('[submit] Axios error:', err.response?.data || err.message);
        } else {
            console.error('[submit] Unexpected error:', err);
        }
    } finally {
        isSubmitting.value = false;
        console.log('[submit] Submission ended');
    }
}

onBeforeUnmount(() => {
    console.log('[onBeforeUnmount] Cleaning up object URLs');
    images.value.forEach((img) => {
        if (!img.id && img.preview) {
            URL.revokeObjectURL(img.preview);
        }
    });
});
</script>

<template>
    <Head>
        <title v-text="title" />
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mx-auto w-full max-w-6xl space-y-8 rounded-xl bg-white p-6 shadow">
                <h2 class="text-center text-2xl font-bold text-gray-800" v-text="title"></h2>

                <form @submit.prevent="submit" enctype="multipart/form-data" class="w-full space-y-8" :aria-busy="isSubmitting">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800">Basic Information</h3>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="md:col-span-2">
                                    <label for="product-name" class="mb-1 block text-sm font-medium text-gray-700">Product Name*</label>
                                    <input
                                        id="product-name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Enter product name"
                                        :disabled="isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>

                                <div>
                                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Category*</label>
                                    <select
                                        id="category"
                                        v-model="form.category_id"
                                        required
                                        :disabled="isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    >
                                        <option disabled value="">Select Category</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Subcategory / Brand Row -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="subcategory" class="mb-1 block text-sm font-medium text-gray-700">Subcategory*</label>
                                    <select
                                        id="subcategory"
                                        v-model="form.subcategory_id"
                                        required
                                        :disabled="!form.category_id || isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)] disabled:cursor-not-allowed disabled:bg-gray-100"
                                    >
                                        <option disabled value="">Select Subcategory</option>
                                        <option v-for="subcategory in filteredSubcategories" :key="subcategory.id" :value="subcategory.id">
                                            {{ subcategory.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label for="brand" class="mb-1 block text-sm font-medium text-gray-700">Brand*</label>
                                    <select
                                        id="brand"
                                        v-model="form.brand_id"
                                        required
                                        :disabled="!form.subcategory_id || isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)] disabled:cursor-not-allowed disabled:bg-gray-100"
                                    >
                                        <option disabled value="">Select Brand</option>
                                        <option v-for="brand in filteredBrands" :key="brand.id" :value="brand.id">
                                            {{ brand.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Unit and Stock Row -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="unit" class="mb-1 block text-sm font-medium text-gray-700">Unit*</label>
                                    <select
                                        id="unit"
                                        v-model="form.unit_id"
                                        required
                                        :disabled="isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    >
                                        <option disabled value="">Select Unit</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label for="stock" class="mb-1 block text-sm font-medium text-gray-700">Stock*</label>
                                    <input
                                        id="stock"
                                        v-model="form.stock"
                                        type="number"
                                        min="0"
                                        required
                                        placeholder="Enter stock quantity"
                                        :disabled="isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>
                            </div>

                            <!-- Price and Discount Row -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="price" class="mb-1 block text-sm font-medium text-gray-700">Price (KES)*</label>
                                    <input
                                        id="price"
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        placeholder="Enter price"
                                        :disabled="isSubmitting"
                                        aria-required="true"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>

                                <div>
                                    <label for="discount" class="mb-1 block text-sm font-medium text-gray-700">Discount (%)</label>
                                    <input
                                        id="discount"
                                        v-model="form.discount"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        placeholder="Enter discount percentage"
                                        :disabled="isSubmitting"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Content Section -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800">Product Content</h3>

                        <div class="space-y-6">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="description">Description</label>
                                <CKEditor
                                    id="description"
                                    v-model="form.description"
                                    :editor="editor"
                                    :disabled="isSubmitting"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="features">Features</label>
                                <CKEditor
                                    id="features"
                                    v-model="form.features"
                                    :editor="editor"
                                    :disabled="isSubmitting"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="specifications">Specifications</label>
                                <CKEditor
                                    id="specifications"
                                    v-model="form.specifications"
                                    :editor="editor"
                                    :disabled="isSubmitting"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700" for="whats-in-the-box">What's in the Box</label>
                                <CKEditor
                                    id="whats-in-the-box"
                                    v-model="form.whats_in_the_box"
                                    :editor="editor"
                                    :disabled="isSubmitting"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Variants Section -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800">Product Variants</h3>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div
                                    v-for="(variantCategory, categoryIndex) in variantCategories"
                                    :key="variantCategory.id"
                                    class="rounded-lg border p-4"
                                    :class="{ 'border-red-300': variantErrors[categoryIndex]?.length }"
                                >
                                    <h3 class="mb-3 flex items-center gap-2 font-semibold text-gray-700">
                                        {{ variantCategory.name }}
                                        <span class="text-xs text-red-600" v-if="variantErrors[categoryIndex]?.length">(Required)</span>
                                    </h3>

                                    <p v-if="variantErrors[categoryIndex]?.length" class="mb-2 text-sm text-red-600">
                                        {{ variantErrors[categoryIndex][0] }}
                                    </p>

                                    <div class="space-y-3">
                                        <div
                                            v-for="(variant, variantIndex) in variantCategory.variants"
                                            :key="variant.id ?? variantIndex"
                                            class="flex items-center gap-2"
                                        >
                                            <input
                                                v-model="variant.value"
                                                @change="onVariantInput(categoryIndex, variantIndex)"
                                                type="text"
                                                placeholder="Add variant name"
                                                class="flex-grow rounded-md border border-gray-300 px-3 py-1.5 shadow-sm focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                                autocomplete="off"
                                                :disabled="isSubmitting"
                                                aria-label="Variant name"
                                            />
                                            <button
                                                type="button"
                                                class="rounded-full p-1 text-red-600 hover:bg-red-50"
                                                @click="removeVariant(categoryIndex, variantIndex)"
                                                title="Remove variant"
                                                :disabled="isSubmitting"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="mt-2 flex items-center gap-1 rounded-md bg-gray-100 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-200"
                                            @click="addVariant(categoryIndex)"
                                            :disabled="isSubmitting"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add {{ variantCategory.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Images Section -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800">Product Images</h3>

                        <div class="space-y-6">
                            <div
                                class="relative cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center transition hover:border-[color:var(--primary)] hover:bg-gray-100"
                                @dragover.prevent
                                @dragenter.prevent
                                @drop.prevent="onDrop"
                            >
                                <div class="pointer-events-none flex flex-col items-center justify-center space-y-2">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-10 w-10 text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                        />
                                    </svg>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-medium text-[color:var(--primary)]">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </div>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    @change="onImagesChange"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                    :disabled="isSubmitting"
                                    aria-label="Upload product images"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                <div
                                    v-for="(img, index) in images"
                                    :key="img.id ?? index"
                                    class="group relative overflow-hidden rounded-md border border-gray-200"
                                >
                                    <img :src="img.preview" alt="Product Image" class="h-32 w-full" @error="handleImageError(index, $event)" />
                                    <button
                                        type="button"
                                        @click="removeImage(index)"
                                        class="absolute top-2 right-2 rounded-full bg-red-600 p-1 text-white opacity-0 group-hover:opacity-100 hover:bg-red-700"
                                        title="Remove image"
                                        :disabled="isSubmitting"
                                        aria-label="Remove image"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <div class="p-2">
                                        <label class="flex cursor-pointer items-center justify-center gap-1.5 text-sm">
                                            <input
                                                type="radio"
                                                name="primaryImage"
                                                :checked="img.is_primary"
                                                @change="() => setPrimary(index)"
                                                class="h-4 w-4 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                                :disabled="isSubmitting"
                                                aria-label="Set as primary image"
                                            />
                                            <span>Primary</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="rounded-md bg-[color:var(--primary)] px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[color:var(--secondary)] focus:ring-2 focus:ring-[color:var(--primary)] focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-live="polite"
                        >
                            <span v-if="isSubmitting">Saving...</span>
                            <span v-else>Save Product</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
