<script setup lang="ts">
import { defineEmits, defineProps, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface ImageItem {
    file: File | null;
    preview: string;
    is_primary: boolean;
    url?: string;
    id?: number;
}

const props = defineProps<{
    modelValue: ImageItem[];
    productId?: number; // still optional if needed for future
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: ImageItem[]): void;
}>();

const images = ref<ImageItem[]>([]);

// Ensure at least one primary image exists
function ensurePrimary() {
    if (!images.value.some((img) => img.is_primary) && images.value.length) {
        images.value[0].is_primary = true;
        console.log('Auto-fallback: first image set as primary');
    }
}

// Initialize from parent
onMounted(() => {
    images.value = props.modelValue.map((img) => ({
        file: img.file ?? null,
        preview: img.preview ?? img.url ?? '',
        is_primary: img.is_primary ?? false,
        url: img.url,
        id: img.id,
    }));
    ensurePrimary();
    console.log('Initialized images:', images.value);
});

// Sync from parent
watch(
    () => props.modelValue,
    (newVal) => {
        if (JSON.stringify(newVal) !== JSON.stringify(images.value)) {
            images.value = newVal.map((img) => ({
                file: img.file ?? null,
                preview: img.preview ?? img.url ?? '',
                is_primary: img.is_primary ?? false,
                url: img.url,
                id: img.id,
            }));
            ensurePrimary();
            console.log('Updated images from parent:', images.value);
        }
    },
    { deep: true },
);

// Emit changes whenever local images array changes
watch(images, (newVal) => emit('update:modelValue', newVal), { deep: true });

// Add new images
function addImages(files: FileList | File[]) {
    for (const file of Array.from(files)) {
        if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) continue;
        if (file.size > 10 * 1024 * 1024) continue;
        if (images.value.some((img) => img.file && img.file.name === file.name && img.file.size === file.size)) continue;

        const previewUrl = URL.createObjectURL(file);
        images.value.push({
            file,
            preview: previewUrl,
            is_primary: images.value.length === 0, // first image auto-primary
        });
        console.log('Added new image:', file.name);
    }
    ensurePrimary();
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

// Remove image (UI only)
function removeImage(index: number) {
    const image = images.value[index];
    console.log('Removing image locally at index:', index, image);

    if (!confirm('Are you sure you want to delete this image?')) {
        console.log('Deletion cancelled by user');
        return;
    }

    // Clean up preview URL if created from a file
    if (image.file && image.preview) {
        URL.revokeObjectURL(image.preview);
    }

    images.value.splice(index, 1);
    console.log('Image removed locally. Remaining images:', images.value);

    // Auto fallback: ensure at least one primary exists
    ensurePrimary();
}

// Set primary
function setPrimary(index: number) {
    images.value.forEach((img, i) => {
        img.is_primary = i === index;
    });
    console.log(`Image at index ${index} set as primary`);
}

// Handle image load errors
function handleImageError(index: number, event: Event) {
    console.error(`Failed to load image at index ${index}:`, event);
}

// Cleanup object URLs on unmount
onBeforeUnmount(() => {
    images.value.forEach((img) => {
        if (img.file && img.preview) URL.revokeObjectURL(img.preview);
    });
});
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-6 text-lg font-semibold text-gray-800">Product Images</h3>

        <div class="space-y-6">
            <!-- Upload area -->
            <div
                class="relative cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center transition hover:border-[color:var(--primary)] hover:bg-gray-100"
                @dragover.prevent
                @dragenter.prevent
                @drop.prevent="onDrop"
            >
                <div class="pointer-events-none flex flex-col items-center justify-center space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        />
                    </svg>
                    <p class="text-sm text-gray-600"><span class="font-medium text-[color:var(--primary)]">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF, WEBP up to 10MB</p>
                </div>
                <input
                    type="file"
                    multiple
                    accept="image/*"
                    @change="onImagesChange"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                />
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <div v-for="(img, index) in images" :key="img.id ?? index" class="group relative overflow-hidden rounded-md border border-gray-200">
                    <img :src="img.preview" alt="Product Image" class="h-32 w-full object-cover" @error="handleImageError(index, $event)" />

                    <button
                        type="button"
                        @click="removeImage(index)"
                        class="absolute top-2 right-2 rounded-full bg-red-600 p-1 text-white opacity-0 group-hover:opacity-100 hover:bg-red-700"
                        title="Delete image"
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
</template>
