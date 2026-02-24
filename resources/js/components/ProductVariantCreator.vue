<script setup lang="ts">
import type { ImageItem } from '@/types/product';
import { nextTick, reactive, toRaw, watch } from 'vue';

interface VariantCategory {
    id: number;
    name: string;
    options?: { id: number; value: string }[];
}

interface VariantRow {
    id?: number | null;
    sku?: string | null;
    values: Record<string, string>; // ✅ string keys now
    buying_price: number;
    marked_price: number;
    stock: number;
    images?: ImageItem[]; // optional for Step 3
}

const props = defineProps<{
    variantCategories: VariantCategory[];
    variantRows?: VariantRow[];
}>();

const emit = defineEmits<{
    (e: 'update:variantRows', value: VariantRow[]): void;
}>();

// Local reactive state
const localVariantCategories = reactive([...props.variantCategories]);
const variantRows = reactive<VariantRow[]>([]);

// Suggestions tracking
const suggestionsOpen = reactive<Record<number, Record<number, boolean>>>({});
const filteredSuggestions = reactive<Record<number, Record<number, { id: number; value: string }[]>>>({});
const dropdownPositions = reactive<Record<number, Record<number, { top: number; left: number; width: number }>>>({});

function initSuggestionTracking(rowIndex: number, categoryId: number, initialOptions: { id: number; value: string }[] = []) {
    if (!suggestionsOpen[rowIndex]) suggestionsOpen[rowIndex] = {};
    if (!filteredSuggestions[rowIndex]) filteredSuggestions[rowIndex] = {};
    if (!dropdownPositions[rowIndex]) dropdownPositions[rowIndex] = {};

    suggestionsOpen[rowIndex][categoryId] = false;
    filteredSuggestions[rowIndex][categoryId] = initialOptions;
    dropdownPositions[rowIndex][categoryId] = { top: 0, left: 0, width: 0 };
}

// Initialize from props
if (props.variantRows?.length) {
    props.variantRows.forEach((row, rowIndex) => {
        const copiedRow: VariantRow = {
            id: row.id ?? null,
            sku: row.sku ?? null,
            values: { ...row.values }, // string keys
            buying_price: Number(row.buying_price) || 0,
            marked_price: Number(row.marked_price) || 0,
            stock: Number(row.stock) || 0,
            images: row.images ?? [],
        };
        variantRows.push(copiedRow);
        localVariantCategories.forEach((c) => initSuggestionTracking(rowIndex, c.id, c.options ?? []));
    });
} else {
    // ✅ Initialize with one empty row if no rows provided
    const defaultRow: VariantRow = {
        id: null,
        sku: null,
        values: Object.fromEntries(localVariantCategories.map((c) => [String(c.id), ''])),
        buying_price: 0,
        marked_price: 0,
        stock: 0,
        images: [],
    };
    variantRows.push(defaultRow);
    localVariantCategories.forEach((c) => initSuggestionTracking(0, c.id, c.options ?? []));
}

// Add new variant
function addRow() {
    const newRow: VariantRow = {
        id: null,
        sku: null,
        values: Object.fromEntries(localVariantCategories.map((c) => [String(c.id), ''])), // string keys
        buying_price: 0,
        marked_price: 0,
        stock: 0,
        images: [],
    };
    variantRows.push(newRow);
    const rowIndex = variantRows.length - 1;
    localVariantCategories.forEach((c) => initSuggestionTracking(rowIndex, c.id, c.options ?? []));
}

// Remove variant
function removeRow(index: number) {
    // ✅ Prevent removing the last row
    if (variantRows.length <= 1) {
        alert('You must have at least one variant');
        return;
    }

    if (!confirm('Are you sure you want to remove this variant?')) {
        return;
    }

    variantRows.splice(index, 1);
    delete suggestionsOpen[index];
    delete filteredSuggestions[index];
    delete dropdownPositions[index];
}

// Suggestions logic
function updateSuggestions(rowIndex: number, category: VariantCategory, event: Event) {
    const value = variantRows[rowIndex].values[String(category.id)]?.toLowerCase() || '';
    if (!category.options) return;

    filteredSuggestions[rowIndex][category.id] = category.options.filter((o) => o.value.toLowerCase().includes(value)).slice(0, 5);

    suggestionsOpen[rowIndex][category.id] = filteredSuggestions[rowIndex][category.id].length > 0;

    const target = event.target as HTMLInputElement;
    const rect = target.getBoundingClientRect();
    nextTick(() => {
        dropdownPositions[rowIndex][category.id] = {
            top: rect.bottom + window.scrollY,
            left: rect.left + window.scrollX,
            width: rect.width,
        };
    });
}

function selectSuggestion(rowIndex: number, categoryId: number, value: string) {
    variantRows[rowIndex].values[String(categoryId)] = value;
    suggestionsOpen[rowIndex][categoryId] = false;
}

function hideSuggestions(rowIndex: number, categoryId: number) {
    setTimeout(() => {
        suggestionsOpen[rowIndex][categoryId] = false;
    }, 100);
}

// Emit updates whenever variantRows change
watch(
    variantRows,
    () => {
        emit(
            'update:variantRows',
            toRaw(variantRows).map((row) => ({
                id: row.id ?? null,
                sku: row.sku ?? null,
                values: { ...row.values },
                buying_price: row.buying_price,
                marked_price: row.marked_price,
                stock: row.stock,
                images: row.images ?? [],
            })),
        );
    },
    { deep: true },
);
</script>

<template>
    <div class="space-y-6">
        <div class="mb-2 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Product Variants</h3>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-primary/90 hover:shadow-md"
                @click="addRow"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Variant
            </button>
        </div>

        <div class="space-y-4">
            <div v-for="(row, rowIndex) in variantRows" :key="rowIndex" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                    <h4 class="text-base font-semibold text-gray-900">Variant #{{ rowIndex + 1 }}</h4>
                    <button
                        type="button"
                        :disabled="variantRows.length <= 1"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="removeRow(rowIndex)"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                        Remove
                    </button>
                </div>

                <!-- Attributes -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-700">Variant Attributes</legend>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="c in localVariantCategories" :key="c.id" class="relative">
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ c.name }}</label>
                            <input
                                v-model="row.values[String(c.id)]"
                                type="text"
                                placeholder="Enter value"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                @input="updateSuggestions(rowIndex, c, $event)"
                                @focus="updateSuggestions(rowIndex, c, $event)"
                                @blur="hideSuggestions(rowIndex, c.id)"
                            />
                            <teleport to="body">
                                <ul
                                    v-if="suggestionsOpen[rowIndex]?.[c.id]"
                                    :style="{
                                        top: `${dropdownPositions[rowIndex][c.id]?.top}px`,
                                        left: `${dropdownPositions[rowIndex][c.id]?.left}px`,
                                        width: `${dropdownPositions[rowIndex][c.id]?.width}px`,
                                    }"
                                    class="absolute z-50 max-h-40 overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
                                >
                                    <li
                                        v-for="s in filteredSuggestions[rowIndex][c.id]"
                                        :key="s.id"
                                        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100"
                                        @mousedown.prevent="selectSuggestion(rowIndex, c.id, s.value)"
                                    >
                                        {{ s.value }}
                                    </li>
                                </ul>
                            </teleport>
                        </div>
                    </div>
                </fieldset>

                <!-- Prices -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-700">Pricing</legend>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Recommended Retail Price</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">KSh</span>
                                <input
                                    v-model.number="row.marked_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="w-full rounded-md border border-gray-300 py-2 pr-3 pl-12 text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Selling Price</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">KSh</span>
                                <input
                                    v-model.number="row.buying_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="w-full rounded-md border border-gray-300 py-2 pr-3 pl-12 text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Discount Indicator -->
                    <div
                        v-if="row.marked_price > 0 && row.buying_price > 0 && row.marked_price > row.buying_price"
                        class="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700"
                    >
                        <span class="font-medium">Discount:</span>
                        KSh {{ (row.marked_price - row.buying_price).toFixed(2) }} ({{
                            (((row.marked_price - row.buying_price) / row.marked_price) * 100).toFixed(1)
                        }}% off)
                    </div>

                    <!-- Warning if selling price is higher than RRP -->
                    <div
                        v-if="row.buying_price > 0 && row.marked_price > 0 && row.buying_price > row.marked_price"
                        class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700"
                    >
                        <span class="font-medium">Warning:</span>
                        Selling price is higher than recommended retail price by KSh {{ (row.buying_price - row.marked_price).toFixed(2) }}
                    </div>
                </fieldset>

                <!-- Stock -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-gray-700">Inventory</legend>
                    <div class="max-w-xs">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Stock Quantity</label>
                        <input
                            v-model.number="row.stock"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        />
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</template>
