<script setup lang="ts">
/**
 * 360° / spin viewer for cars. Uses the car's uploaded images as rotation
 * frames — drag left/right (or use the slider / arrow keys) to spin. This is
 * the practical "3D view" for a used-car marketplace: no 3D model needed, the
 * seller just uploads photos around the car (front → side → rear → …).
 */
import { Rotate3d, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ open: boolean; images: string[]; title?: string }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const frame = ref(0);
const dragging = ref(false);
let lastX = 0;
let accum = 0;

const total = computed(() => props.images.length);
const currentImage = computed(() => props.images[frame.value] ?? props.images[0] ?? '');

// Pixels of horizontal drag needed to advance one frame.
const STEP = 18;

const advance = (delta: number) => {
    if (total.value === 0) return;
    frame.value = ((frame.value + delta) % total.value + total.value) % total.value;
};

const onPointerDown = (e: PointerEvent) => {
    dragging.value = true;
    lastX = e.clientX;
    accum = 0;
    (e.target as HTMLElement).setPointerCapture?.(e.pointerId);
};
const onPointerMove = (e: PointerEvent) => {
    if (!dragging.value) return;
    accum += e.clientX - lastX;
    lastX = e.clientX;
    while (Math.abs(accum) >= STEP) {
        advance(accum > 0 ? 1 : -1);
        accum -= Math.sign(accum) * STEP;
    }
};
const onPointerUp = () => (dragging.value = false);

const onKey = (e: KeyboardEvent) => {
    if (!props.open) return;
    if (e.key === 'ArrowRight') advance(1);
    else if (e.key === 'ArrowLeft') advance(-1);
    else if (e.key === 'Escape') emit('close');
};

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Transition name="fade">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="emit('close')">
            <div class="relative w-full max-w-3xl rounded-xl bg-white p-3 shadow-xl">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <Rotate3d class="h-4 w-4 text-primary" /> 360° view <span v-if="title" class="text-gray-400">— {{ title }}</span>
                    </h3>
                    <button class="text-gray-500 hover:text-primary" @click="emit('close')"><X class="h-5 w-5" /></button>
                </div>

                <div
                    class="relative flex h-[60vh] cursor-ew-resize touch-none items-center justify-center overflow-hidden rounded-lg bg-gray-50 select-none"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @pointercancel="onPointerUp"
                    @pointerleave="onPointerUp"
                >
                    <img :src="currentImage" :alt="title" class="max-h-full max-w-full object-contain" draggable="false" />
                    <div class="pointer-events-none absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-xs text-white">
                        Drag to rotate · {{ frame + 1 }} / {{ total }}
                    </div>
                </div>

                <input
                    v-if="total > 1"
                    type="range"
                    :min="0"
                    :max="total - 1"
                    v-model.number="frame"
                    class="mt-3 w-full accent-[color:var(--primary)]"
                    aria-label="Rotate car"
                />
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
