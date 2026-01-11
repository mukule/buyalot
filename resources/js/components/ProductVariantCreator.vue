<script setup lang="ts">
import { nextTick, reactive, toRaw, watch } from 'vue';

interface VariantCategory {
    id: number;
    name: string;
    options?: { id: number; value: string }[];
}

interface VariantRow {
    id?: number | null;
    sku?: string | null;
    values: Record<number, string>;
    buying_price: number;
    marked_price: number;
    stock: number;
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

// Suggestions
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
            values: { ...row.values },
            buying_price: Number(row.buying_price) || 0,
            marked_price: Number(row.marked_price) || 0,
            stock: Number(row.stock) || 0,
        };
        variantRows.push(copiedRow);
        localVariantCategories.forEach((c) => initSuggestionTracking(rowIndex, c.id, c.options ?? []));
    });
}

// Add new variant
function addRow() {
    const newRow: VariantRow = {
        id: null,
        sku: null,
        values: Object.fromEntries(localVariantCategories.map((c) => [c.id, ''])),
        buying_price: 0,
        marked_price: 0,
        stock: 0,
    };
    variantRows.push(newRow);
    const rowIndex = variantRows.length - 1;
    localVariantCategories.forEach((c) => initSuggestionTracking(rowIndex, c.id, c.options ?? []));
}

// Remove variant
function removeRow(index: number) {
    variantRows.splice(index, 1);
    delete suggestionsOpen[index];
    delete filteredSuggestions[index];
    delete dropdownPositions[index];
}

// Suggestions logic
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

function selectSuggestion(rowIndex: number, categoryId: number, value: string) {
    variantRows[rowIndex].values[categoryId] = value;
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
            <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm text-white hover:bg-secondary" @click="addRow">Add Variant</button>
        </div>

        <div class="space-y-4">
            <div v-for="(row, rowIndex) in variantRows" :key="rowIndex" class="space-y-4 rounded-lg border border-gray-200 p-4 shadow-sm">
                <!-- Attributes Fieldset -->
                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-gray-700">Attributes</legend>
                    <div v-for="c in localVariantCategories" :key="c.id" class="relative">
                        <label class="block text-sm font-medium text-gray-600">{{ c.name }}</label>
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
                    </div>
                </fieldset>

                <!-- Prices Fieldset -->
                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-gray-700">Prices</legend>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Buying Price</label>
                            <input
                                v-model.number="row.buying_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-gray-300 px-2 py-1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Marked Price</label>
                            <input
                                v-model.number="row.marked_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-gray-300 px-2 py-1"
                            />
                        </div>
                    </div>
                </fieldset>

                <!-- Stock Fieldset -->
                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-gray-700">Stock</legend>
                    <input v-model.number="row.stock" type="number" min="0" class="w-full rounded-md border border-gray-300 px-2 py-1" />
                </fieldset>

                <!-- Actions -->
                <div class="flex justify-end">
                    <button type="button" class="text-red-600 hover:underline" @click="removeRow(rowIndex)">Remove</button>
                </div>
            </div>
        </div>
    </div>
</template>
