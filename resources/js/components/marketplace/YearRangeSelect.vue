<script setup lang="ts">
/**
 * Year range picker for the Cars marketplace: two dropdowns (min / max) listing
 * every year from the current year back to 1980. When a dropdown is opened with
 * nothing selected the list auto-scrolls to and highlights 2015, so the common
 * starting point is right under the cursor. Selecting "Any" clears that bound.
 */
import { ChevronDown } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    min: number | string | null;
    max: number | string | null;
}>();

const emit = defineEmits<{
    (e: 'update:min', v: number | null): void;
    (e: 'update:max', v: number | null): void;
    (e: 'change'): void;
}>();

const FOCUS_YEAR = 2015;
const MIN_YEAR = 1980;
// Date.now() is fine in the browser; this component only runs client-side.
const currentYear = new Date().getFullYear();

// Ascending: earliest year (1980) at the top, newest at the bottom — the user
// scrolls downwards to reach newer years.
const years = computed<number[]>(() => {
    const out: number[] = [];
    for (let y = MIN_YEAR; y <= currentYear; y++) out.push(y);
    return out;
});

const openKey = ref<'min' | 'max' | null>(null);
const root = ref<HTMLElement | null>(null);
const minList = ref<HTMLElement | null>(null);
const maxList = ref<HTMLElement | null>(null);

const label = (v: number | string | null, fallback: string) => (v === null || v === '' ? fallback : String(v));

const toggle = async (which: 'min' | 'max') => {
    openKey.value = openKey.value === which ? null : which;
    if (openKey.value) {
        await nextTick();
        // Auto-focus 2015: centre it in the freshly opened list.
        const listEl = which === 'min' ? minList.value : maxList.value;
        const target = listEl?.querySelector<HTMLElement>('[data-focus-year="true"]');
        target?.scrollIntoView({ block: 'center' });
    }
};

const pick = (which: 'min' | 'max', value: number | null) => {
    emit(which === 'min' ? 'update:min' : 'update:max', value);
    openKey.value = null;
    emit('change');
};

const onClickOutside = (e: MouseEvent) => {
    if (root.value && !root.value.contains(e.target as Node)) openKey.value = null;
};
onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));

const isFocusYear = (y: number, selected: number | string | null) =>
    (selected === null || selected === '') && y === FOCUS_YEAR;
</script>

<template>
    <div ref="root" class="flex items-center gap-2">
        <!-- Min -->
        <div class="relative w-1/2">
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-md border border-gray-300 px-2 py-2 text-sm text-gray-700 transition hover:border-primary focus:border-primary focus:ring-0"
                @click.stop="toggle('min')"
            >
                <span :class="min === null || min === '' ? 'text-gray-400' : ''">{{ label(min, 'From') }}</span>
                <ChevronDown class="h-4 w-4 text-gray-400" :class="openKey === 'min' ? 'rotate-180' : ''" />
            </button>
            <div
                v-if="openKey === 'min'"
                ref="minList"
                class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
            >
                <button type="button" class="block w-full px-3 py-1.5 text-left text-sm text-gray-500 hover:bg-gray-50" @click.stop="pick('min', null)">
                    Any
                </button>
                <button
                    v-for="y in years"
                    :key="y"
                    type="button"
                    :data-focus-year="isFocusYear(y, min)"
                    :class="[
                        'block w-full px-3 py-1.5 text-left text-sm hover:bg-primary/10',
                        String(min) === String(y) ? 'bg-primary/10 font-semibold text-primary' : isFocusYear(y, min) ? 'bg-gray-100 font-medium text-gray-800' : 'text-gray-700',
                    ]"
                    @click.stop="pick('min', y)"
                >
                    {{ y }}
                </button>
            </div>
        </div>

        <span class="text-gray-400">–</span>

        <!-- Max -->
        <div class="relative w-1/2">
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-md border border-gray-300 px-2 py-2 text-sm text-gray-700 transition hover:border-primary focus:border-primary focus:ring-0"
                @click.stop="toggle('max')"
            >
                <span :class="max === null || max === '' ? 'text-gray-400' : ''">{{ label(max, 'To') }}</span>
                <ChevronDown class="h-4 w-4 text-gray-400" :class="openKey === 'max' ? 'rotate-180' : ''" />
            </button>
            <div
                v-if="openKey === 'max'"
                ref="maxList"
                class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
            >
                <button type="button" class="block w-full px-3 py-1.5 text-left text-sm text-gray-500 hover:bg-gray-50" @click.stop="pick('max', null)">
                    Any
                </button>
                <button
                    v-for="y in years"
                    :key="y"
                    type="button"
                    :data-focus-year="isFocusYear(y, max)"
                    :class="[
                        'block w-full px-3 py-1.5 text-left text-sm hover:bg-primary/10',
                        String(max) === String(y) ? 'bg-primary/10 font-semibold text-primary' : isFocusYear(y, max) ? 'bg-gray-100 font-medium text-gray-800' : 'text-gray-700',
                    ]"
                    @click.stop="pick('max', y)"
                >
                    {{ y }}
                </button>
            </div>
        </div>
    </div>
</template>
