<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Brand, BreadcrumbItem, Category, Subcategory, Unit, Variant, VariantCategory } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, type Ref, watch } from 'vue';

const title = 'Create Product';

const {
    brands,
    categories,
    subcategories,
    units,
    variantCategories: variantCategoriesProp,
} = usePage().props as unknown as {
    brands: Brand[];
    categories: Category[];
    subcategories: Subcategory[];
    units: Unit[];
    variantCategories: (VariantCategory & { variants?: Variant[] })[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Products', href: '/admin/products' },
    { title, href: '' },
];

const variantCategories = reactive(
    variantCategoriesProp.map((vc) => ({
        ...vc,
        variants: vc.variants ? vc.variants.map((v) => ({ ...v })) : [],
    })),
);

const variantErrors: Ref<Record<number, string[]>> = ref({});

const form = ref({
    brand_id: '',
    category_id: '',
    subcategory_id: '',
    unit_id: '',
    name: '',
    description: '',
    features: '',
    specifications: '',
    whats_in_the_box: '',
    price: '',
    discount: '',
    stock: '0',
    selectedVariants: {} as Record<number, number[]>,
});

variantCategories.forEach((vc) => {
    form.value.selectedVariants[vc.id] = [];
});

// Computed: filter subcategories by selected category
const filteredSubcategories = computed(() => {
    if (!form.value.category_id) return [];
    return subcategories.filter((subcat) => subcat.category_id === Number(form.value.category_id));
});

// Reset subcategory and brand when category changes
watch(
    () => form.value.category_id,
    () => {
        form.value.subcategory_id = '';
        form.value.brand_id = '';
    },
);

// filteredBrands depends on selected subcategory
const filteredBrands = computed(() => {
    if (!form.value.subcategory_id) return [];
    return brands.filter((brand) => brand.subcategory_id === Number(form.value.subcategory_id));
});

// Reset brand when subcategory changes
watch(
    () => form.value.subcategory_id,
    () => {
        form.value.brand_id = '';
    },
);

type ImageItem = {
    file: File | null;
    preview: string;
    is_primary: boolean;
};

const images = ref<ImageItem[]>([]);

function addImages(files: FileList | File[]) {
    for (const file of Array.from(files)) {
        if (!['image/png', 'image/jpeg', 'image/gif'].includes(file.type)) {
            console.warn(`Skipped unsupported file type: ${file.name} (${file.type})`);
            continue;
        }
        if (file.size > 10 * 1024 * 1024) {
            console.warn(`File too large: ${file.name} (${file.size} bytes)`);
            continue;
        }
        if (images.value.some((img) => img.file && img.file.name === file.name && img.file.size === file.size)) {
            console.warn(`Duplicate file skipped: ${file.name}`);
            continue;
        }

        const previewUrl = URL.createObjectURL(file);
        images.value.push({
            file,
            preview: previewUrl,
            is_primary: images.value.length === 0,
        });
    }
}

function onImagesChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files) return;
    addImages(target.files);
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    if (!event.dataTransfer?.files) return;
    addImages(event.dataTransfer.files);
}

function removeImage(index: number) {
    const image = images.value[index];
    if (image.preview) URL.revokeObjectURL(image.preview);
    images.value.splice(index, 1);

    if (images.value.length && !images.value.some((img) => img.is_primary)) {
        images.value[0].is_primary = true;
    }
}

function setPrimary(index: number) {
    images.value.forEach((img, i) => {
        img.is_primary = i === index;
    });
}

function handleImageError(index: number, event: Event) {
    console.error(`Failed to load image at index ${index}:`, event);
}

function addVariant(categoryIndex: number) {
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
    variantCategories[categoryIndex].variants.splice(variantIndex, 1);
    if (variantErrors.value[categoryIndex]) {
        variantErrors.value[categoryIndex].splice(variantIndex, 1);
    }
}

function onVariantInput(categoryIndex: number, variantIndex: number) {
    const category = variantCategories[categoryIndex];
    const variants = category.variants;
    const currentValue = variants[variantIndex].value.trim().toLowerCase();

    if (!currentValue) return;

    const isDuplicate = variants.some((v, i) => i !== variantIndex && v.value.trim().toLowerCase() === currentValue);

    if (isDuplicate) {
        variantErrors.value[categoryIndex] = [`Duplicate ${category.name.toLowerCase()} "${variants[variantIndex].value}" is not allowed.`];
        variants.splice(variantIndex, 1);
    } else {
        variantErrors.value[categoryIndex] = [];
    }
}

const isSubmitting = ref(false);

async function submit() {
    if (isSubmitting.value) return;

    isSubmitting.value = true;

    const formData = new FormData();

    formData.append('name', form.value.name);
    formData.append('description', form.value.description);
    formData.append('features', form.value.features);
    formData.append('specifications', form.value.specifications);
    formData.append('whats_in_the_box', form.value.whats_in_the_box);
    formData.append('price', form.value.price);
    formData.append('discount', form.value.discount || '0');
    formData.append('stock', form.value.stock || '0');

    if (form.value.brand_id) formData.append('brand_id', String(form.value.brand_id));
    if (form.value.subcategory_id) formData.append('subcategory_id', String(form.value.subcategory_id));
    if (form.value.unit_id) formData.append('unit_id', String(form.value.unit_id));
    if (form.value.category_id) formData.append('category_id', String(form.value.category_id));

    const variantsPayload = Object.entries(form.value.selectedVariants).map(([variantCategoryId, variantIds]) => ({
        variant_category_id: Number(variantCategoryId),
        variant_ids: variantIds,
    }));

    formData.append('variants', JSON.stringify(variantsPayload));
    formData.append('variant_categories', JSON.stringify(variantCategories));

    images.value.forEach((img, idx) => {
        if (img.file) {
            formData.append('images[]', img.file);
            if (img.is_primary) {
                formData.append('primary_image_index', String(idx));
            }
        }
    });

    try {
        await axios.post('/admin/products', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        window.location.href = '/admin/products';
    } catch (error) {
        console.error('Error submitting product:', error);
    } finally {
        isSubmitting.value = false;
    }
}

onBeforeUnmount(() => {
    images.value.forEach((img) => {
        if (img.preview) {
            URL.revokeObjectURL(img.preview);
        }
    });
});
</script>

<template>
    <Head>
        <title>{{ title }}</title>
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mx-auto w-full max-w-6xl space-y-8 rounded-xl bg-white p-6 shadow">
                <h2 class="text-center text-2xl font-bold text-gray-800">{{ title }}</h2>

                <form @submit.prevent="submit" enctype="multipart/form-data" class="w-full space-y-8">
                    <!-- Basic Information Section -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800">Basic Information</h3>

                        <div class="space-y-6">
                            <!-- Product Name and Category Row -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <!-- Product Name - takes 2/3 width -->
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Product Name*</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Enter product name"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>

                                <!-- Category - takes 1/3 width -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Category*</label>
                                    <select
                                        v-model="form.category_id"
                                        required
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
                                <!-- Subcategory -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Subcategory*</label>
                                    <select
                                        v-model="form.subcategory_id"
                                        required
                                        :disabled="!form.category_id"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)] disabled:cursor-not-allowed disabled:bg-gray-100"
                                    >
                                        <option disabled value="">Select Subcategory</option>
                                        <option v-for="subcategory in filteredSubcategories" :key="subcategory.id" :value="subcategory.id">
                                            {{ subcategory.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Brand -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Brand*</label>
                                    <select
                                        v-model="form.brand_id"
                                        required
                                        :disabled="!form.subcategory_id"
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
                                <!-- Unit -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Unit*</label>
                                    <select
                                        v-model="form.unit_id"
                                        required
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    >
                                        <option disabled value="">Select Unit</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Stock -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Stock*</label>
                                    <input
                                        v-model="form.stock"
                                        type="number"
                                        min="0"
                                        required
                                        placeholder="Enter stock quantity"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>
                            </div>

                            <!-- Price and Discount Row -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <!-- Price -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Price (KES)*</label>
                                    <input
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        placeholder="Enter price"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>

                                <!-- Discount -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Discount (%)</label>
                                    <input
                                        v-model="form.discount"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        placeholder="Enter discount percentage"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- The rest of the template remains unchanged -->

                    <!-- Product Content Section -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-6 text-lg font-semibold text-gray-800">Product Content</h3>

                        <div class="space-y-6">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                                <QuillEditor
                                    v-model:content="form.description"
                                    content-type="html"
                                    theme="snow"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Features</label>
                                <QuillEditor
                                    v-model:content="form.features"
                                    content-type="html"
                                    theme="snow"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Specifications</label>
                                <QuillEditor
                                    v-model:content="form.specifications"
                                    content-type="html"
                                    theme="snow"
                                    class="min-h-[150px] rounded-md border border-gray-300 bg-white"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">What's in the Box</label>
                                <QuillEditor
                                    v-model:content="form.whats_in_the_box"
                                    content-type="html"
                                    theme="snow"
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
                                                @change="() => onVariantInput(categoryIndex, variantIndex)"
                                                type="text"
                                                placeholder="Add variant name"
                                                class="flex-grow rounded-md border border-gray-300 px-3 py-1.5 shadow-sm focus:border-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                                autocomplete="off"
                                            />
                                            <button
                                                type="button"
                                                class="rounded-full p-1 text-red-600 hover:bg-red-50"
                                                @click="removeVariant(categoryIndex, variantIndex)"
                                                title="Remove variant"
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
                            <!-- Drag & Drop Area -->
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
                                />
                            </div>

                            <!-- Image Preview Grid -->
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                <div
                                    v-for="(img, index) in images"
                                    :key="index"
                                    class="group relative overflow-hidden rounded-md border border-gray-200"
                                >
                                    <img :src="img.preview" alt="Product Image" class="h-32 w-full" @error="handleImageError(index, $event)" />
                                    <!-- <div class="bg-opacity-0 group-hover:bg-opacity-10 absolute inset-0 bg-black transition"></div> -->
                                    <button
                                        type="button"
                                        @click="removeImage(index)"
                                        class="absolute top-2 right-2 rounded-full bg-red-600 p-1 text-white opacity-0 group-hover:opacity-100 hover:bg-red-700"
                                        title="Remove image"
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
                                                @change="setPrimary(index)"
                                                class="h-4 w-4 text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
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
