<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        endsAt: string | null;
        label?: string;
        size?: 'sm' | 'md';
    }>(),
    { label: 'Ends in', size: 'sm' },
);

// Start at null so SSR and first client render match; tick only after mount.
const now = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    now.value = Date.now();
    timer = setInterval(() => (now.value = Date.now()), 1000);
});
onBeforeUnmount(() => timer && clearInterval(timer));

const remaining = computed(() => {
    if (!props.endsAt || now.value === null) return null;
    return Math.max(0, new Date(props.endsAt).getTime() - now.value);
});

const parts = computed(() => {
    const ms = remaining.value ?? 0;
    const total = Math.floor(ms / 1000);
    const days = Math.floor(total / 86400);
    const pad = (n: number) => String(n).padStart(2, '0');
    return {
        days,
        hours: pad(Math.floor((total % 86400) / 3600)),
        minutes: pad(Math.floor((total % 3600) / 60)),
        seconds: pad(total % 60),
    };
});
</script>

<template>
    <div v-if="endsAt && remaining !== null" class="flex items-center gap-2" :class="size === 'md' ? 'text-base' : 'text-sm'">
        <template v-if="remaining > 0">
            <span class="font-medium">{{ label }}</span>
            <span class="flex items-center gap-1 font-mono font-bold tabular-nums">
                <template v-if="parts.days > 0">
                    <span class="rounded bg-black/80 px-1.5 py-0.5 text-white">{{ parts.days }}d</span>
                </template>
                <span class="rounded bg-black/80 px-1.5 py-0.5 text-white">{{ parts.hours }}</span>:
                <span class="rounded bg-black/80 px-1.5 py-0.5 text-white">{{ parts.minutes }}</span>:
                <span class="rounded bg-black/80 px-1.5 py-0.5 text-white">{{ parts.seconds }}</span>
            </span>
        </template>
        <span v-else class="font-medium">Deal ended</span>
    </div>
</template>
