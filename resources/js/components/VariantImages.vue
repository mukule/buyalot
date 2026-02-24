<script setup lang="ts">
import type { ImageItem, VariantRow } from '@/types/product';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

interface VariantCategory {
    id: number;
    name: string;
}

/* ----------------------------------
   Props / Emits
----------------------------------- */
const props = defineProps<{
    variantRows?: VariantRow[];
    variantCategories?: VariantCategory[];
    maxGalleryImages?: number;
}>();

const emit = defineEmits<{
    (e: 'update:variantRows', value: VariantRow[]): void;
}>();

/* ----------------------------------
   Constants
----------------------------------- */
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const MAX_GALLERY_IMAGES = props.maxGalleryImages ?? 10;

/* ----------------------------------
   Variant Category Map
----------------------------------- */
const variantCategoryMap = computed<Record<string, string>>(() =>
    Object.fromEntries((props.variantCategories ?? []).map((cat) => [String(cat.id), cat.name])),
);

/* ----------------------------------
   Messages
----------------------------------- */
const messages = ref<Record<number, { type: 'error' | 'success'; text: string }>>({});

function showMessage(rowIndex: number, type: 'error' | 'success', text: string) {
    messages.value[rowIndex] = { type, text };
    setTimeout(() => {
        delete messages.value[rowIndex];
    }, 3000);
}

/* ----------------------------------
   Initialize Rows
----------------------------------- */
function initializeRows(rows?: VariantRow[]) {
    return (rows ?? []).map((row) => {
        const primary = row.images?.find((i) => i.is_primary);
        const gallery = row.images?.filter((i) => !i.is_primary) ?? [];

        const values: Record<string, string> = {};
        Object.entries(row.values ?? {}).forEach(([k, v]) => (values[String(k)] = v));

        return {
            ...row,
            values,
            buying_price: Number(row.buying_price) || 0,
            marked_price: Number(row.marked_price) || 0,
            stock: Number(row.stock) || 0,
            primaryImage: primary,
            galleryImages: gallery,
        };
    });
}

/* ----------------------------------
   Reactive State
----------------------------------- */
const variantRows = ref(initializeRows(props.variantRows));
const isDragging = ref<Record<number, boolean>>({});

/* ----------------------------------
   Watch prop once to re-initialize
----------------------------------- */
watch(
    () => props.variantRows,
    (newRows) => {
        variantRows.value = initializeRows(newRows);
    },
);

/* ----------------------------------
   Helpers
----------------------------------- */
function emitRows() {
    const updated = variantRows.value.map((row) => {
        const images = row.primaryImage ? [row.primaryImage, ...(row.galleryImages ?? [])] : [...(row.galleryImages ?? [])];
        const imagesWithVariantId = images.map((img, idx) => ({
            ...img,
            variant_id: row.id,
            sort_order: idx,
        }));

        return {
            ...row,
            buying_price: Number(row.buying_price) || 0,
            marked_price: Number(row.marked_price) || 0,
            stock: Number(row.stock) || 0,
            images: imagesWithVariantId,
        };
    });

    emit('update:variantRows', updated);
}

/* ----------------------------------
   File Validation
----------------------------------- */
function validateFile(file: File, rowIndex: number): boolean {
    if (!file.type.startsWith('image/')) {
        showMessage(rowIndex, 'error', 'Only image files are allowed');
        return false;
    }

    if (file.size > MAX_FILE_SIZE) {
        showMessage(rowIndex, 'error', 'File size must be less than 10MB');
        return false;
    }

    return true;
}

/* ----------------------------------
   Image Handling
----------------------------------- */
function addVariantImages(rowIndex: number, files: FileList | File[]) {
    const row = variantRows.value[rowIndex];
    const currentGalleryCount = row.galleryImages?.length ?? 0;
    let addedCount = 0;
    let skippedCount = 0;

    Array.from(files).forEach((file) => {
        if (!validateFile(file, rowIndex)) {
            skippedCount++;
            return;
        }

        if (currentGalleryCount + addedCount >= MAX_GALLERY_IMAGES) {
            showMessage(rowIndex, 'error', `Maximum ${MAX_GALLERY_IMAGES} gallery images allowed`);
            return;
        }

        const isDuplicate = row.galleryImages?.some(
            (img) => img.file && (img.file as File).name === file.name && (img.file as File).size === file.size,
        );

        if (isDuplicate) {
            skippedCount++;
            return;
        }

        const newImage: ImageItem = {
            file,
            preview: URL.createObjectURL(file),
            is_primary: false,
            id: `variant-${rowIndex}-${Date.now()}-${Math.random().toString(36).slice(2, 5)}`,
        };

        if (!row.primaryImage) {
            row.primaryImage = { ...newImage, is_primary: true };
            addedCount++;
        } else {
            row.galleryImages = row.galleryImages ?? [];
            row.galleryImages.push(newImage);
            addedCount++;
        }
    });

    if (addedCount > 0) showMessage(rowIndex, 'success', `${addedCount} image(s) added successfully`);
    if (skippedCount > 0 && addedCount === 0) showMessage(rowIndex, 'error', `${skippedCount} file(s) skipped`);

    if (addedCount > 0) emitRows();
}

function makePrimary(rowIndex: number, imgIndex: number) {
    const row = variantRows.value[rowIndex];
    if (!row.primaryImage || !row.galleryImages?.length) return;

    const newPrimary = row.galleryImages.splice(imgIndex, 1)[0];
    row.galleryImages.unshift({ ...row.primaryImage, is_primary: false });
    row.primaryImage = { ...newPrimary, is_primary: true };

    showMessage(rowIndex, 'success', 'Primary image updated');
    emitRows();
}

function removePrimary(rowIndex: number) {
    const row = variantRows.value[rowIndex];
    if (!row.primaryImage) return;
    if (!confirm('Delete primary image?')) return;

    if (row.primaryImage.preview?.startsWith('blob:')) URL.revokeObjectURL(row.primaryImage.preview);

    row.primaryImage = undefined;
    showMessage(rowIndex, 'success', 'Primary image removed');
    emitRows();
}

function removeGallery(rowIndex: number, imgIndex: number) {
    const row = variantRows.value[rowIndex];
    const img = row.galleryImages?.[imgIndex];
    if (!img) return;
    if (!confirm('Delete this image?')) return;

    if (img.preview?.startsWith('blob:')) URL.revokeObjectURL(img.preview);

    row.galleryImages?.splice(imgIndex, 1);
    showMessage(rowIndex, 'success', 'Image removed');
    emitRows();
}

function onPrimaryChange(event: Event, rowIndex: number) {
    const file = (event.target as HTMLInputElement)?.files?.[0];
    if (!file || !validateFile(file, rowIndex)) return;

    const row = variantRows.value[rowIndex];
    if (row.primaryImage?.preview?.startsWith('blob:')) URL.revokeObjectURL(row.primaryImage.preview);

    row.primaryImage = { file, preview: URL.createObjectURL(file), is_primary: true, id: `primary-${Date.now()}` };
    showMessage(rowIndex, 'success', 'Primary image updated');
    emitRows();

    (event.target as HTMLInputElement).value = '';
}

function onGalleryChange(event: Event, rowIndex: number) {
    const files = (event.target as HTMLInputElement)?.files;
    if (files) addVariantImages(rowIndex, files);

    (event.target as HTMLInputElement).value = '';
}

function onPrimaryDrop(event: DragEvent, rowIndex: number) {
    isDragging.value[rowIndex] = false;
    const file = event.dataTransfer?.files?.[0];
    if (!file || !validateFile(file, rowIndex)) return;

    const row = variantRows.value[rowIndex];
    if (row.primaryImage?.preview?.startsWith('blob:')) URL.revokeObjectURL(row.primaryImage.preview);

    row.primaryImage = { file, preview: URL.createObjectURL(file), is_primary: true, id: `primary-${Date.now()}` };
    showMessage(rowIndex, 'success', 'Primary image updated');
    emitRows();
}

function onGalleryDrop(event: DragEvent, rowIndex: number) {
    isDragging.value[rowIndex] = false;
    if (event.dataTransfer?.files) addVariantImages(rowIndex, event.dataTransfer.files);
}

function handleDragEnter(rowIndex: number) {
    isDragging.value[rowIndex] = true;
}
function handleDragLeave(rowIndex: number) {
    isDragging.value[rowIndex] = false;
}

/* ----------------------------------
   Cleanup
----------------------------------- */
onBeforeUnmount(() => {
    variantRows.value.forEach((row) => {
        if (row.primaryImage?.preview?.startsWith('blob:')) URL.revokeObjectURL(row.primaryImage.preview);
        row.galleryImages?.forEach((img) => {
            if (img.preview?.startsWith('blob:')) URL.revokeObjectURL(img.preview);
        });
    });
});
</script>

<template>
    <div class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-800">Variant Images</h3>

        <div v-for="(row, rowIndex) in variantRows" :key="row.id ?? rowIndex" class="space-y-4 rounded-lg border border-gray-200 p-4 shadow-sm">
            <!-- Variant Header -->
            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                <!-- Attribute Combination -->
                <div class="mt-2 text-xs text-gray-700">
                    <span v-for="(val, attrId) in row.values" :key="attrId" class="mr-3 inline-block">
                        <span class="font-medium"> {{ variantCategoryMap[attrId] || 'Attribute' }}: </span>
                        {{ val }}
                    </span>
                </div>
            </div>

            <!-- Messages -->
            <div
                v-if="messages[rowIndex]"
                :class="[
                    'rounded-md p-3 text-sm',
                    messages[rowIndex].type === 'error'
                        ? 'border border-red-200 bg-red-50 text-red-800'
                        : 'border border-green-200 bg-green-50 text-green-800',
                ]"
                role="alert"
            >
                {{ messages[rowIndex].text }}
            </div>

            <!-- Primary Image -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700"> Primary Image </label>

                <div
                    v-if="!row.primaryImage"
                    :class="[
                        'relative cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors',
                        isDragging[rowIndex] ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50 hover:border-gray-400',
                    ]"
                    @dragover.prevent
                    @dragenter.prevent="handleDragEnter(rowIndex)"
                    @dragleave.prevent="handleDragLeave(rowIndex)"
                    @drop.prevent="(e) => onPrimaryDrop(e, rowIndex)"
                >
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">Click or drag to upload the primary image</p>
                    <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                    <input
                        type="file"
                        accept="image/*"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="(e) => onPrimaryChange(e, rowIndex)"
                        aria-label="Upload primary image"
                    />
                </div>

                <div v-else class="group relative w-full max-w-xs">
                    <img
                        :src="row.primaryImage.preview"
                        :alt="`Primary image for variant ${row.id ?? rowIndex}`"
                        class="h-48 w-full rounded-md border border-gray-200 object-cover"
                    />

                    <div
                        class="absolute inset-0 flex items-center justify-center gap-2 rounded-md bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <label class="cursor-pointer rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700">
                            Replace
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="(e) => onPrimaryChange(e, rowIndex)"
                                aria-label="Replace primary image"
                            />
                        </label>
                        <button
                            type="button"
                            @click="removePrimary(rowIndex)"
                            class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700"
                            aria-label="Remove primary image"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="mt-1 text-xs font-semibold text-blue-600 uppercase">Primary Image</div>
                </div>
            </div>

            <!-- Gallery Images -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Gallery Images
                    <span class="ml-1 text-xs font-normal text-gray-500"> ({{ row.galleryImages?.length ?? 0 }}/{{ MAX_GALLERY_IMAGES }}) </span>
                </label>

                <div
                    v-if="(row.galleryImages?.length ?? 0) < MAX_GALLERY_IMAGES"
                    :class="[
                        'relative cursor-pointer rounded-lg border-2 border-dashed p-6 text-center transition-colors',
                        isDragging[rowIndex] ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50 hover:border-gray-400',
                    ]"
                    @dragover.prevent
                    @dragenter.prevent="handleDragEnter(rowIndex)"
                    @dragleave.prevent="handleDragLeave(rowIndex)"
                    @drop.prevent="(e) => onGalleryDrop(e, rowIndex)"
                >
                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <p class="mt-1 text-sm text-gray-600">Add more images to the gallery</p>
                    <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>

                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        @change="(e) => onGalleryChange(e, rowIndex)"
                        aria-label="Upload gallery images"
                    />
                </div>

                <div v-if="row.galleryImages?.length" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    <div
                        v-for="(img, index) in row.galleryImages"
                        :key="img.id ?? index"
                        class="group relative overflow-hidden rounded-md border border-gray-200"
                    >
                        <img :src="img.preview" :alt="`Gallery image ${index + 1}`" class="h-32 w-full object-cover" />

                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <button
                                type="button"
                                @click="makePrimary(rowIndex, index)"
                                class="rounded bg-white px-2 py-1 text-[10px] font-bold text-gray-800 hover:bg-gray-100"
                                aria-label="Set as primary image"
                            >
                                SET AS PRIMARY
                            </button>

                            <button
                                type="button"
                                @click="removeGallery(rowIndex, index)"
                                class="rounded bg-red-600 px-2 py-1 text-[10px] text-white hover:bg-red-700"
                                aria-label="Delete image"
                            >
                                DELETE
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!row.primaryImage" class="py-4 text-center text-sm text-gray-500">
                    No images yet. Upload a primary image or gallery images above.
                </div>
            </div>
        </div>
    </div>
</template>
