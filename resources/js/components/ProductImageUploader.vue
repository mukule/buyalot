<script setup lang="ts">
import CarImageBlurEditor from '@/components/marketplace/CarImageBlurEditor.vue';
import { ShieldAlert } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface ImageItem {
    file: File | null;
    preview: string; // Blob URL or existing image URL
    is_primary: boolean; // Flag for primary image
    url?: string; // Existing image URL for editing
    id?: number | string; // Existing image ID
}

const props = defineProps<{
    modelValue: ImageItem[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: ImageItem[]): void;
}>();

// --- Internal state ---
const primaryImage = ref<ImageItem | undefined>(undefined);
const galleryImages = ref<ImageItem[]>([]);

// --- Initialize from modelValue ---
function initializeImages() {
    primaryImage.value = props.modelValue.find((img) => img.is_primary);
    galleryImages.value = props.modelValue.filter((img) => !img.is_primary);
}

onMounted(initializeImages);

// Watch for external changes
watch(
    () => props.modelValue,
    (newVal) => {
        const internalCount = (primaryImage.value ? 1 : 0) + galleryImages.value.length;
        if (newVal.length !== internalCount) {
            initializeImages();
        }
    },
    { deep: true },
);

// Emit combined array whenever state changes
watch(
    [primaryImage, galleryImages],
    () => {
        const combined = [...(primaryImage.value ? [primaryImage.value] : []), ...galleryImages.value];
        emit('update:modelValue', combined);
    },
    { deep: true },
);

// --- Utility ---
function revokeHelper(img: ImageItem | undefined) {
    if (img?.preview && img.preview.startsWith('blob:')) {
        URL.revokeObjectURL(img.preview);
    }
}

// --- Actions ---
function setPrimaryFile(file: File) {
    if (!file.type.startsWith('image/') || file.size > 10 * 1024 * 1024) return;

    revokeHelper(primaryImage.value);

    primaryImage.value = {
        file,
        preview: URL.createObjectURL(file),
        is_primary: true,
        id: `primary-${Date.now()}`,
    };
}

function addGalleryImages(files: FileList | File[]) {
    Array.from(files).forEach((file) => {
        if (!file.type.startsWith('image/') || file.size > 10 * 1024 * 1024) return;

        const isDuplicate = galleryImages.value.some((img) => img.file?.name === file.name && img.file?.size === file.size);
        if (isDuplicate) return;

        galleryImages.value.push({
            file,
            preview: URL.createObjectURL(file),
            is_primary: false,
            id: `gallery-${Math.random().toString(36).slice(2, 9)}`,
        });
    });
}

function makePrimary(index: number) {
    const newPrimary = galleryImages.value[index];
    const oldPrimary = primaryImage.value;

    // Swap primary with gallery
    galleryImages.value.splice(index, 1);
    if (oldPrimary) galleryImages.value.unshift({ ...oldPrimary, is_primary: false });

    primaryImage.value = { ...newPrimary, is_primary: true };
}

function removePrimaryImage() {
    if (!confirm('Delete primary image?')) return;
    revokeHelper(primaryImage.value);
    primaryImage.value = undefined;
}

function removeGalleryImage(index: number) {
    if (!confirm('Delete this image?')) return;
    revokeHelper(galleryImages.value[index]);
    galleryImages.value.splice(index, 1);
}

// --- Privacy blur editor (number plate / sensitive areas) ---
const editorOpen = ref(false);
const editorSrc = ref('');
const editorFilename = ref('image');
const editorTarget = ref<{ kind: 'primary' | 'gallery'; index: number }>({ kind: 'primary', index: -1 });

function openBlurEditor(kind: 'primary' | 'gallery', index = -1) {
    const img = kind === 'primary' ? primaryImage.value : galleryImages.value[index];
    if (!img) return;
    editorSrc.value = img.preview || img.url || '';
    editorFilename.value = img.file?.name ?? 'car-image';
    editorTarget.value = { kind, index };
    editorOpen.value = true;
}

function onBlurSave(file: File) {
    const preview = URL.createObjectURL(file);
    if (editorTarget.value.kind === 'primary' && primaryImage.value) {
        revokeHelper(primaryImage.value);
        primaryImage.value = { ...primaryImage.value, file, preview };
    } else {
        const img = galleryImages.value[editorTarget.value.index];
        if (img) {
            revokeHelper(img);
            galleryImages.value[editorTarget.value.index] = { ...img, file, preview };
        }
    }
}

// --- Event Handlers ---
function onPrimaryChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.[0]) setPrimaryFile(target.files[0]);
}

function onGalleryChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) addGalleryImages(target.files);
}

function onGalleryDrop(event: DragEvent) {
    if (event.dataTransfer?.files) addGalleryImages(event.dataTransfer.files);
}

// Clean up object URLs
onBeforeUnmount(() => {
    revokeHelper(primaryImage.value);
    galleryImages.value.forEach(revokeHelper);
});
</script>

<template>
    <div class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800">Product Images</h3>

        <!-- Primary Image -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Primary Image (Main Display)</label>
            <div
                v-if="!primaryImage"
                class="relative cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center transition hover:border-blue-500 hover:bg-gray-100"
            >
                <p class="text-sm text-gray-600">Click or drag to upload the primary image</p>
                <input type="file" accept="image/*" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" @change="onPrimaryChange" />
            </div>

            <div v-else class="group relative w-full max-w-xs">
                <img :src="primaryImage.preview" class="h-48 w-full rounded-md border border-gray-200 object-cover" />
                <div
                    class="absolute inset-0 flex flex-col items-center justify-center gap-2 rounded-md bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <button
                        type="button"
                        @click="openBlurEditor('primary')"
                        class="flex items-center gap-1 rounded bg-white px-3 py-1 text-xs font-semibold text-gray-800 hover:bg-gray-100"
                    >
                        <ShieldAlert class="h-3.5 w-3.5" /> Blur plate
                    </button>
                    <button type="button" @click="removePrimaryImage" class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700">
                        Remove & Replace
                    </button>
                </div>
                <div class="mt-1 text-xs font-semibold text-blue-600 uppercase">Primary Image</div>
            </div>
        </div>

        <hr class="border-gray-100" />

        <!-- Gallery Images -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Gallery Images</label>
            <div
                class="relative cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-blue-500 hover:bg-gray-100"
                @dragover.prevent
                @dragenter.prevent
                @drop.prevent="onGalleryDrop"
            >
                <p class="text-sm text-gray-600">Add more images to the gallery</p>
                <input
                    type="file"
                    multiple
                    accept="image/*"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                    @change="onGalleryChange"
                />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                <div
                    v-for="(img, index) in galleryImages"
                    :key="img.id ?? index"
                    class="group relative overflow-hidden rounded-md border border-gray-200"
                >
                    <img :src="img.preview" class="h-32 w-full object-cover" />

                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <button
                            type="button"
                            @click="makePrimary(index)"
                            class="rounded bg-white px-2 py-1 text-[10px] font-bold text-gray-800 hover:bg-gray-100"
                        >
                            SET AS PRIMARY
                        </button>
                        <button
                            type="button"
                            @click="openBlurEditor('gallery', index)"
                            class="flex items-center gap-1 rounded bg-white px-2 py-1 text-[10px] font-bold text-gray-800 hover:bg-gray-100"
                        >
                            <ShieldAlert class="h-3 w-3" /> BLUR PLATE
                        </button>
                        <button
                            type="button"
                            @click="removeGalleryImage(index)"
                            class="rounded bg-red-600 px-2 py-1 text-[10px] text-white hover:bg-red-700"
                        >
                            DELETE
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <CarImageBlurEditor
            :open="editorOpen"
            :src="editorSrc"
            :filename="editorFilename"
            @close="editorOpen = false"
            @save="onBlurSave"
        />
    </div>
</template>
