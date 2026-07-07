<script setup lang="ts">
/**
 * "Post a car / post an item" — marketplace selection + dynamic vertical fields.
 *
 * Used inside the admin/seller product form. Sellers pick one or more
 * marketplaces (Cars & Motors, Building & Construction); the matching
 * attribute fields appear. Selecting none = general "all products" (default).
 *
 * `marketplaces` (array) and `attributes` (object) are the reactive references
 * from the parent useForm and are mutated in place so they submit with the form.
 */
import { Car, ChevronDown, HardHat, Store } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface FormField {
    key: string;
    label: string;
    type: 'text' | 'number' | 'select' | 'checklist' | 'make' | 'model';
    options?: string[];
    required?: boolean;
    advanced?: boolean;
}
interface VerticalOption {
    key: string;
    label: string;
    form_fields: FormField[];
}
interface CarMake {
    name: string;
    models: string[];
}

const props = withDefaults(
    defineProps<{
        available: VerticalOption[];
        marketplaces: string[];
        attributes: Record<string, any>;
        carMakes?: CarMake[];
    }>(),
    { carMakes: () => [] },
);

const iconFor = (key: string) => (key === 'cars' ? Car : key === 'construction' ? HardHat : Store);

const isSelected = (key: string) => props.marketplaces.includes(key);

const toggle = (key: string) => {
    const i = props.marketplaces.indexOf(key);
    if (i === -1) props.marketplaces.push(key);
    else props.marketplaces.splice(i, 1);
};

// Distinct fields across the selected verticals (deduped by key).
const activeFields = computed<FormField[]>(() => {
    const seen = new Set<string>();
    const fields: FormField[] = [];
    for (const v of props.available) {
        if (!isSelected(v.key)) continue;
        for (const f of v.form_fields ?? []) {
            if (seen.has(f.key)) continue;
            seen.add(f.key);
            fields.push(f);
        }
    }
    return fields;
});

const primaryFields = computed(() => activeFields.value.filter((f) => !f.advanced));
const advancedFields = computed(() => activeFields.value.filter((f) => f.advanced));

const showAdvanced = ref(false);

// ── Cascading make / model ─────────────────────────────────────────────────
const makeNames = computed(() => props.carMakes.map((m) => m.name));

// Options for the make select, including the current value even if it isn't in
// the seeded list (so editing a legacy free-text make doesn't lose it).
const makeOptions = computed(() => {
    const current = props.attributes.make;
    const list = [...makeNames.value];
    if (current && !list.includes(current)) list.unshift(current);
    return list;
});

// Models for the currently-selected make (+ current value fallback as above).
const modelOptions = computed(() => {
    const make = props.carMakes.find((m) => m.name === props.attributes.make);
    const list = make ? [...make.models] : [];
    const current = props.attributes.model;
    if (current && !list.includes(current)) list.unshift(current);
    return list;
});

const onMakeChange = () => {
    // Clear the model when it no longer belongs to the newly-selected make.
    const make = props.carMakes.find((m) => m.name === props.attributes.make);
    if (!make || !make.models.includes(props.attributes.model)) {
        props.attributes.model = undefined;
    }
};

// Checklist helpers — attributes[key] is kept as a string[] of ticked options.
const isChecked = (key: string, opt: string) => Array.isArray(props.attributes[key]) && props.attributes[key].includes(opt);
const toggleCheck = (key: string, opt: string) => {
    const list: string[] = Array.isArray(props.attributes[key]) ? [...props.attributes[key]] : [];
    const i = list.indexOf(opt);
    if (i === -1) list.push(opt);
    else list.splice(i, 1);
    props.attributes[key] = list;
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-800">Marketplaces</h3>
        <p class="mb-3 text-xs text-gray-500">
            List this product in one or more specialised marketplaces. Leave all unchecked to keep it in
            <span class="font-medium">All Products</span> only.
        </p>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
                v-for="v in available"
                :key="v.key"
                type="button"
                :class="[
                    'flex items-center gap-3 rounded-lg border-2 px-3 py-2 text-left transition',
                    isSelected(v.key) ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-primary/50',
                ]"
                @click="toggle(v.key)"
            >
                <span
                    class="flex h-9 w-9 flex-none items-center justify-center rounded-lg"
                    :class="isSelected(v.key) ? 'bg-primary text-white' : 'bg-gray-100 text-primary'"
                >
                    <component :is="iconFor(v.key)" class="h-5 w-5" />
                </span>
                <span class="flex-1 text-sm font-medium text-gray-800">{{ v.label }}</span>
                <input type="checkbox" :checked="isSelected(v.key)" class="h-4 w-4 accent-[color:var(--primary)]" @click.stop="toggle(v.key)" />
            </button>
        </div>

        <!-- Dynamic vertical attribute fields -->
        <div v-if="activeFields.length" class="mt-5">
            <h4 class="mb-2 text-sm font-semibold text-gray-700">Listing details</h4>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <div v-for="field in primaryFields" :key="field.key">
                    <label class="block text-xs font-medium text-gray-600">
                        {{ field.label }}<span v-if="field.required" class="text-red-500">*</span>
                    </label>

                    <!-- Make (cascading) -->
                    <select
                        v-if="field.type === 'make'"
                        v-model="attributes.make"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                        @change="onMakeChange"
                    >
                        <option :value="undefined">Select make</option>
                        <option v-for="m in makeOptions" :key="m" :value="m">{{ m }}</option>
                    </select>

                    <!-- Model (depends on the selected make) -->
                    <select
                        v-else-if="field.type === 'model'"
                        v-model="attributes.model"
                        :disabled="!attributes.make"
                        class="w-full rounded-md border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:bg-gray-100"
                    >
                        <option :value="undefined">{{ attributes.make ? 'Select model' : 'Select a make first' }}</option>
                        <option v-for="m in modelOptions" :key="m" :value="m">{{ m }}</option>
                    </select>

                    <select
                        v-else-if="field.type === 'select'"
                        v-model="attributes[field.key]"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                    >
                        <option :value="undefined">Select {{ field.label.toLowerCase() }}</option>
                        <option v-for="opt in field.options ?? []" :key="opt" :value="opt">{{ opt }}</option>
                    </select>

                    <input
                        v-else
                        v-model="attributes[field.key]"
                        :type="field.type === 'number' ? 'number' : 'text'"
                        :inputmode="field.type === 'number' ? 'numeric' : undefined"
                        :placeholder="field.label"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <!-- View more: advanced / optional fields -->
            <div v-if="advancedFields.length" class="mt-4">
                <button
                    type="button"
                    class="flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    @click="showAdvanced = !showAdvanced"
                >
                    <ChevronDown class="h-4 w-4 transition" :class="showAdvanced ? 'rotate-180' : ''" />
                    {{ showAdvanced ? 'Hide extra details' : 'View more details' }}
                </button>

                <div v-if="showAdvanced" class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div
                        v-for="field in advancedFields"
                        :key="field.key"
                        :class="field.type === 'checklist' ? 'sm:col-span-2 md:col-span-3' : ''"
                    >
                        <label class="block text-xs font-medium text-gray-600">
                            {{ field.label }}<span v-if="field.required" class="text-red-500">*</span>
                        </label>

                        <!-- Checklist (multi-select feature chips) -->
                        <div v-if="field.type === 'checklist'" class="mt-1.5 flex flex-wrap gap-2">
                            <button
                                v-for="opt in field.options ?? []"
                                :key="opt"
                                type="button"
                                :class="[
                                    'rounded-full border px-3 py-1 text-xs font-medium transition',
                                    isChecked(field.key, opt)
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-gray-300 bg-white text-gray-600 hover:border-primary/60 hover:text-primary',
                                ]"
                                @click="toggleCheck(field.key, opt)"
                            >
                                {{ opt }}
                            </button>
                        </div>

                        <select
                            v-else-if="field.type === 'select'"
                            v-model="attributes[field.key]"
                            class="w-full rounded-md border px-3 py-2 text-sm"
                        >
                            <option :value="undefined">Select {{ field.label.toLowerCase() }}</option>
                            <option v-for="opt in field.options ?? []" :key="opt" :value="opt">{{ opt }}</option>
                        </select>

                        <input
                            v-else
                            v-model="attributes[field.key]"
                            :type="field.type === 'number' ? 'number' : 'text'"
                            :inputmode="field.type === 'number' ? 'numeric' : undefined"
                            :placeholder="field.label"
                            class="w-full rounded-md border px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
