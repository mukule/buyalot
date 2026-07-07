<script setup lang="ts">
/**
 * Privacy blur editor for car photos. The seller drags a box over the number
 * plate (or any section) and it is pixelated/blurred straight into the image,
 * so the identity is sealed before upload. Fully client-side (canvas) — no data
 * leaves the browser. Outputs a flattened WebP File.
 *
 * NOTE: this is manual/assisted region blur. Automatic plate *detection* would
 * require an ANPR model or cloud vision API; the "Auto-blur plate" hook is left
 * for that integration.
 */
import { Eraser, Loader2, ScanLine, SquareDashedMousePointer, X } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{ open: boolean; src: string; filename?: string }>();
const emit = defineEmits<{ (e: 'close'): void; (e: 'save', file: File): void }>();

const base = ref<HTMLCanvasElement | null>(null);   // full-res image + baked blurs
const overlay = ref<HTMLCanvasElement | null>(null); // selection rectangle
const wrap = ref<HTMLDivElement | null>(null);
const loading = ref(false);
const hasSelection = ref(false);
const blurCount = ref(0);

const MAX = 1600;
let sel = { x: 0, y: 0, w: 0, h: 0 };
let drawing = false;
let startX = 0;
let startY = 0;

const load = () => {
    loading.value = true;
    blurCount.value = 0;
    hasSelection.value = false;
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
        const scale = Math.min(1, MAX / Math.max(img.naturalWidth, img.naturalHeight));
        const w = Math.round(img.naturalWidth * scale);
        const h = Math.round(img.naturalHeight * scale);
        for (const c of [base.value, overlay.value]) {
            if (!c) continue;
            c.width = w;
            c.height = h;
        }
        base.value?.getContext('2d')?.drawImage(img, 0, 0, w, h);
        loading.value = false;
    };
    img.onerror = () => (loading.value = false);
    img.src = props.src;
};

watch(
    () => props.open,
    (o) => {
        if (o) nextTick(load);
    },
);

// Map a pointer event to canvas pixel coordinates.
const toCanvas = (e: PointerEvent) => {
    const c = overlay.value!;
    const rect = c.getBoundingClientRect();
    return {
        x: ((e.clientX - rect.left) / rect.width) * c.width,
        y: ((e.clientY - rect.top) / rect.height) * c.height,
    };
};

const drawSel = () => {
    const ctx = overlay.value?.getContext('2d');
    if (!ctx || !overlay.value) return;
    ctx.clearRect(0, 0, overlay.value.width, overlay.value.height);
    if (sel.w && sel.h) {
        ctx.strokeStyle = '#ef4444';
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 4]);
        ctx.strokeRect(sel.x, sel.y, sel.w, sel.h);
        ctx.fillStyle = 'rgba(239,68,68,0.15)';
        ctx.fillRect(sel.x, sel.y, sel.w, sel.h);
    }
};

const onDown = (e: PointerEvent) => {
    drawing = true;
    const p = toCanvas(e);
    startX = p.x;
    startY = p.y;
    sel = { x: p.x, y: p.y, w: 0, h: 0 };
    overlay.value?.setPointerCapture?.(e.pointerId);
};
const onMove = (e: PointerEvent) => {
    if (!drawing) return;
    const p = toCanvas(e);
    sel = { x: Math.min(startX, p.x), y: Math.min(startY, p.y), w: Math.abs(p.x - startX), h: Math.abs(p.y - startY) };
    hasSelection.value = sel.w > 4 && sel.h > 4;
    drawSel();
};
const onUp = () => (drawing = false);

// Pixelate an arbitrary region into the base canvas.
const blurRect = (x: number, y: number, w: number, h: number) => {
    const ctx = base.value?.getContext('2d');
    if (!ctx || w < 2 || h < 2) return;
    const factor = Math.max(8, Math.round(Math.max(w, h) / 10));
    const tw = Math.max(1, Math.round(w / factor));
    const th = Math.max(1, Math.round(h / factor));

    const tmp = document.createElement('canvas');
    tmp.width = tw;
    tmp.height = th;
    const tctx = tmp.getContext('2d')!;
    tctx.imageSmoothingEnabled = false;
    tctx.drawImage(base.value!, x, y, w, h, 0, 0, tw, th);

    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(tmp, 0, 0, tw, th, x, y, w, h);
    ctx.imageSmoothingEnabled = true;
    blurCount.value++;
};

// Blur the current manual selection.
const applyBlur = () => {
    if (!hasSelection.value) return;
    blurRect(sel.x, sel.y, sel.w, sel.h);
    sel = { x: 0, y: 0, w: 0, h: 0 };
    hasSelection.value = false;
    drawSel();
};

// Auto-detect plate(s) via the ANPR model and blur them.
const detecting = ref(false);
const detectMsg = ref('');

const getCookie = (name: string): string => {
    const m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return m ? decodeURIComponent(m.pop()!) : '';
};

const autoDetect = () => {
    if (!base.value || detecting.value) return;
    detecting.value = true;
    detectMsg.value = '';
    base.value.toBlob(
        async (blob) => {
            if (!blob) {
                detecting.value = false;
                return;
            }
            try {
                const fd = new FormData();
                fd.append('image', blob, 'car.webp');
                const res = await fetch(route('marketplace.detect-plates'), {
                    method: 'POST',
                    headers: { 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'), Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: fd,
                });
                const data = await res.json();
                if (!data.enabled) {
                    detectMsg.value = 'Auto-detect is not configured — draw the box manually.';
                    return;
                }
                const boxes = data.boxes ?? [];
                if (!boxes.length) {
                    detectMsg.value = 'No plate detected — draw the box manually if needed.';
                    return;
                }
                const c = base.value!;
                for (const b of boxes) {
                    // Pad the detected box slightly so the whole plate is covered.
                    const pad = 0.04;
                    blurRect(
                        Math.max(0, (b.x - pad)) * c.width,
                        Math.max(0, (b.y - pad)) * c.height,
                        Math.min(1, b.w + pad * 2) * c.width,
                        Math.min(1, b.h + pad * 2) * c.height,
                    );
                }
                detectMsg.value = `${boxes.length} plate(s) detected and blurred.`;
            } catch {
                detectMsg.value = 'Detection failed — draw the box manually.';
            } finally {
                detecting.value = false;
            }
        },
        'image/webp',
        0.92,
    );
};

const save = () => {
    loading.value = true;
    base.value?.toBlob(
        (blob) => {
            loading.value = false;
            if (!blob) return;
            const name = (props.filename || 'car-image').replace(/\.[^.]+$/, '') + '-blurred.webp';
            emit('save', new File([blob], name, { type: 'image/webp' }));
            emit('close');
        },
        'image/webp',
        0.9,
    );
};
</script>

<template>
    <Transition name="fade">
        <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4" @click.self="emit('close')">
            <div class="w-full max-w-2xl rounded-xl bg-white p-3 shadow-xl">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <SquareDashedMousePointer class="h-4 w-4 text-primary" /> Blur number plate / sensitive area
                    </h3>
                    <button class="text-gray-500 hover:text-primary" @click="emit('close')"><X class="h-5 w-5" /></button>
                </div>

                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">Drag a box over the plate, or auto-detect it. Click “Blur selection” to apply.</p>
                    <button
                        type="button"
                        :disabled="detecting || loading"
                        class="flex items-center gap-1.5 rounded-md bg-secondary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-secondary/90 disabled:opacity-60"
                        @click="autoDetect"
                    >
                        <Loader2 v-if="detecting" class="h-3.5 w-3.5 animate-spin" />
                        <ScanLine v-else class="h-3.5 w-3.5" />
                        Auto-detect plate
                    </button>
                </div>
                <p v-if="detectMsg" class="mb-2 text-xs font-medium text-primary">{{ detectMsg }}</p>

                <div ref="wrap" class="relative w-full overflow-hidden rounded-lg bg-gray-100">
                    <canvas ref="base" class="block w-full"></canvas>
                    <canvas
                        ref="overlay"
                        class="absolute inset-0 w-full cursor-crosshair touch-none"
                        @pointerdown="onDown"
                        @pointermove="onMove"
                        @pointerup="onUp"
                        @pointercancel="onUp"
                    ></canvas>
                    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/60">
                        <Loader2 class="h-6 w-6 animate-spin text-primary" />
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-gray-500">{{ blurCount }} area(s) blurred</span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            :disabled="!hasSelection"
                            class="flex items-center gap-1.5 rounded-md border border-primary/40 px-3 py-1.5 text-sm font-medium text-primary transition hover:bg-primary/10 disabled:opacity-40"
                            @click="applyBlur"
                        >
                            <Eraser class="h-4 w-4" /> Blur selection
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-primary px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                            @click="save"
                        >
                            Save image
                        </button>
                    </div>
                </div>
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
