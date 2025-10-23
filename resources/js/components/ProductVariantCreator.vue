<script setup lang="ts">
import { nextTick, reactive, watch } from 'vue';

interface VariantCategory {
    id: number;
    name: string;
    options?: { id: number; value: string }[];
}

interface VariantRow {
    values: Record<number, string>;
    regular_price: number;
    selling_price: number;
    stock: number;
}

const props = defineProps<{
    variantCategories: VariantCategory[];
    variantRows?: VariantRow[];
}>();

const emit = defineEmits<{
    (e: 'update:variantRows', value: VariantRow[]): void;
}>();

// Local reactive copy of categories
const localVariantCategories = reactive([...props.variantCategories]);

// Variant rows (reactive)
const variantRows = reactive<VariantRow[]>([]);

// Track suggestion dropdowns
const suggestionsOpen = reactive<Record<number, Record<number, boolean>>>({});
const filteredSuggestions = reactive<Record<number, Record<number, { id: number; value: string }[]>>>({});
const dropdownPositions = reactive<Record<number, Record<number, { top: number; left: number; width: number }>>>({});

// Initialize suggestion tracking for a row/category
function initSuggestionTracking(rowIndex: number, categoryId: number, initialOptions: { id: number; value: string }[] = []) {
    if (!suggestionsOpen[rowIndex]) suggestionsOpen[rowIndex] = {};
    if (!filteredSuggestions[rowIndex]) filteredSuggestions[rowIndex] = {};
    if (!dropdownPositions[rowIndex]) dropdownPositions[rowIndex] = {};

    suggestionsOpen[rowIndex][categoryId] = false;
    filteredSuggestions[rowIndex][categoryId] = initialOptions;
    dropdownPositions[rowIndex][categoryId] = { top: 0, left: 0, width: 0 };
}

// Initialize variantRows from prop if available
if (props.variantRows?.length) {
    props.variantRows.forEach((row, rowIndex) => {
        variantRows.push({
            values: { ...row.values },
            regular_price: row.regular_price,
            selling_price: row.selling_price,
            stock: row.stock,
        });

        // Initialize suggestion tracking for prefilled row
        localVariantCategories.forEach((c) => {
            initSuggestionTracking(rowIndex, c.id, c.options ?? []);
        });
    });
}

// Add new row
function addRow() {
    const newRow: VariantRow = {
        values: Object.fromEntries(localVariantCategories.map((c) => [c.id, ''])),
        regular_price: 0,
        selling_price: 0,
        stock: 0,
    };
    variantRows.push(newRow);
    const rowIndex = variantRows.length - 1;
    localVariantCategories.forEach((c) => initSuggestionTracking(rowIndex, c.id, c.options ?? []));
}

// Remove row
function removeRow(index: number) {
    variantRows.splice(index, 1);
    delete suggestionsOpen[index];
    delete filteredSuggestions[index];
    delete dropdownPositions[index];
}

// Update suggestions on input
function updateSuggestions(rowIndex: number, category: VariantCategory, event: Event) {
    const value = variantRows[rowIndex].values[category.id]?.toLowerCase() || '';
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

// Select a suggestion
function selectSuggestion(rowIndex: number, categoryId: number, value: string) {
    variantRows[rowIndex].values[categoryId] = value;
    suggestionsOpen[rowIndex][categoryId] = false;
}

// Hide suggestions on blur
function hideSuggestions(rowIndex: number, categoryId: number) {
    window.setTimeout(() => {
        suggestionsOpen[rowIndex][categoryId] = false;
    }, 100);
}

// Emit updates whenever variantRows change
watch(
    variantRows,
    (newVal) => {
        emit(
            'update:variantRows',
            newVal.map((row) => ({
                values: { ...row.values },
                regular_price: row.regular_price,
                selling_price: row.selling_price,
                stock: row.stock,
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
            <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-secondary" @click="addRow">Add Row</button>
        </div>

        <div class="mt-2 overflow-x-auto">
            <table class="min-w-full border border-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th v-for="c in localVariantCategories" :key="c.id" class="px-4 py-2 text-left">{{ c.name }}</th>
                        <th class="px-4 py-2 text-left">Regular Price</th>
                        <th class="px-4 py-2 text-left">Selling Price</th>
                        <th class="px-4 py-2 text-left">Stock</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in variantRows" :key="rowIndex" class="border-t">
                        <td v-for="c in localVariantCategories" :key="c.id" class="relative px-2 py-1">
                            <input
                                v-model="row.values[c.id]"
                                type="text"
                                placeholder="Enter value"
                                class="w-full rounded-md border border-gray-300 px-2 py-1"
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
                                    class="absolute z-50 max-h-40 overflow-auto rounded-md border bg-white shadow-lg"
                                >
                                    <li
                                        v-for="s in filteredSuggestions[rowIndex][c.id]"
                                        :key="s.id"
                                        class="cursor-pointer px-2 py-1 hover:bg-gray-200"
                                        @mousedown.prevent="selectSuggestion(rowIndex, c.id, s.value)"
                                    >
                                        {{ s.value }}
                                    </li>
                                </ul>
                            </teleport>
                        </td>
                        <td class="px-2 py-1">
                            <input
                                v-model.number="row.regular_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-gray-300 px-2 py-1"
                            />
                        </td>
                        <td class="px-2 py-1">
                            <input
                                v-model.number="row.selling_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-gray-300 px-2 py-1"
                            />
                        </td>
                        <td class="px-2 py-1">
                            <input v-model.number="row.stock" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1" />
                        </td>
                        <td class="px-2 py-1">
                            <button type="button" class="text-red-600 hover:underline" @click="removeRow(rowIndex)">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
