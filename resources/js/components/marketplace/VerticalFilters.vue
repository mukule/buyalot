<script setup lang="ts">
/**
 * Renders a vertical's filter widgets from its config-driven schema.
 * Works both in the desktop sidebar and the mobile bottom-sheet drawer.
 *
 * `form` is a reactive object owned by the parent, keyed by query-string param
 * names (select filters by their `key`; range filters by their `min`/`max`
 * param names). Selects emit `apply` (immediate); range inputs emit
 * `apply-debounced` so the parent can throttle navigation while typing.
 */
import YearRangeSelect from '@/components/marketplace/YearRangeSelect.vue';

interface FilterDef {
    key: string;
    label: string;
    type: 'select' | 'range' | 'text';
    min?: string;
    max?: string;
    ui?: string;
    options?: { value: string | number; label: string }[];
}

const props = withDefaults(
    defineProps<{
        schema: FilterDef[];
        options: Record<string, (string | number)[]>;
        form: Record<string, any>;
        // 1 = narrow single column (desktop sidebar); 2 = compact grid (drawer).
        columns?: number;
    }>(),
    { columns: 1 },
);

const emit = defineEmits<{
    (e: 'apply'): void;
    (e: 'apply-debounced'): void;
}>();

// A select's options come from its config (fixed value/label pairs) or, for
// attribute-backed selects, from the distinct values passed in `options`.
const selectOptions = (filter: FilterDef): { value: string | number; label: string }[] => {
    if (filter.options?.length) return filter.options;
    return (props.options[filter.key] ?? []).map((o) => ({ value: o, label: String(o) }));
};

// Range inputs: derive a unit hint from the label (e.g. "Mileage (km)" → "km")
// for the placeholders, and validate that min ≤ max before applying.
const rangeUnit = (filter: FilterDef): string => {
    const m = filter.label.match(/\(([^)]+)\)/);
    return m ? m[1] : '';
};
const rangePlaceholder = (filter: FilterDef, which: 'min' | 'max'): string => {
    const unit = rangeUnit(filter);
    const base = which === 'min' ? 'Min' : 'Max';
    return unit ? `${base} ${unit}` : base;
};
const isSet = (v: any) => v !== null && v !== '' && v !== undefined;
const rangeInvalid = (filter: FilterDef): boolean => {
    const min = props.form[filter.min!];
    const max = props.form[filter.max!];
    return isSet(min) && isSet(max) && Number(min) > Number(max);
};
const onRangeInput = (filter: FilterDef) => {
    // Don't navigate while the range is invalid (min > max).
    if (!rangeInvalid(filter)) emit('apply-debounced');
};

const hasActive = () =>
    Object.entries(props.form).some(([k, v]) => k !== 'sort' && v !== null && v !== '' && v !== undefined);

const clearAll = () => {
    for (const key of Object.keys(props.form)) {
        if (key === 'sort') continue;
        props.form[key] = null;
    }
    emit('apply');
};
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">Filters</h2>
            <button
                v-if="hasActive()"
                type="button"
                class="text-xs font-medium text-secondary hover:underline"
                @click="clearAll"
            >
                Clear all
            </button>
        </div>

        <div :class="columns === 2 ? 'grid grid-cols-2 gap-x-4 gap-y-3' : 'space-y-4'">
            <div
                v-for="filter in schema"
                :key="filter.key"
                :class="[
                    columns === 2 ? '' : 'border-b border-gray-100 pb-4 last:border-0',
                    columns === 2 && filter.type === 'range' ? 'col-span-2' : '',
                ]"
            >
                <h3 class="mb-1.5 text-sm font-medium text-gray-700">{{ filter.label }}</h3>

            <!-- Text (e.g. Stock ID) -->
            <input
                v-if="filter.type === 'text'"
                v-model="form[filter.key]"
                type="text"
                :placeholder="`Search ${filter.label.toLowerCase()}`"
                class="w-full rounded-md border border-gray-300 px-2 py-2 text-sm text-gray-700 focus:border-primary focus:ring-0"
                @input="emit('apply-debounced')"
            />

            <!-- Select -->
            <select
                v-else-if="filter.type === 'select'"
                v-model="form[filter.key]"
                class="w-full rounded-md border border-gray-300 px-2 py-2 text-sm text-gray-700 focus:border-primary focus:ring-0"
                @change="emit('apply')"
            >
                <option :value="null">Any {{ filter.label.toLowerCase() }}</option>
                <option v-for="opt in selectOptions(filter)" :key="String(opt.value)" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>

            <!-- Year range (dropdowns, auto-focus 2015) -->
            <YearRangeSelect
                v-else-if="filter.type === 'range' && filter.ui === 'year'"
                :min="form[filter.min!]"
                :max="form[filter.max!]"
                @update:min="form[filter.min!] = $event"
                @update:max="form[filter.max!] = $event"
                @change="emit('apply')"
            />

            <!-- Range -->
            <div v-else-if="filter.type === 'range'">
                <div class="flex items-center gap-2">
                    <input
                        v-model="form[filter.min!]"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        :placeholder="rangePlaceholder(filter, 'min')"
                        :class="[
                            'w-1/2 rounded-md border px-2 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0',
                            rangeInvalid(filter) ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary',
                        ]"
                        @input="onRangeInput(filter)"
                    />
                    <span class="text-gray-400">–</span>
                    <input
                        v-model="form[filter.max!]"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        :placeholder="rangePlaceholder(filter, 'max')"
                        :class="[
                            'w-1/2 rounded-md border px-2 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0',
                            rangeInvalid(filter) ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary',
                        ]"
                        @input="onRangeInput(filter)"
                    />
                </div>
                <p v-if="rangeInvalid(filter)" class="mt-1 text-xs text-red-500">Min can’t be greater than max.</p>
            </div>
            </div>
        </div>
    </div>
</template>
