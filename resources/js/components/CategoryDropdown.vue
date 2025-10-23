<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CategoryNode from './CategoryNode.vue';

interface Category {
    id: number | string;
    name: string;
    children?: Category[];
}

const props = defineProps<{
    label: string;
    categories: Category[];
    modelValue: string | number | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const open = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const search = ref('');

// limit how many categories to show before scrolling
const MAX_VISIBLE = 15;

const toggleDropdown = () => {
    open.value = !open.value;
    if (open.value) search.value = ''; // reset search each time
};

const selectCategory = (id: string | number) => {
    emit('update:modelValue', id);
    open.value = false;
};

// ✅ flatten helper (for finding selected label)
function flattenCategories(categories: Category[]): Category[] {
    return categories.flatMap((c) => [c, ...(c.children ? flattenCategories(c.children) : [])]);
}

// ✅ selected category
const selectedCategory = computed(() => {
    return flattenCategories(props.categories).find((c) => c.id === props.modelValue);
});

// ✅ filtered categories (by search)
const filteredCategories = computed(() => {
    if (!search.value) return props.categories;
    const q = search.value.toLowerCase();

    // recursively filter tree
    function filterTree(categories: Category[]): Category[] {
        return categories
            .map((c) => ({
                ...c,
                children: c.children ? filterTree(c.children) : [],
            }))
            .filter((c) => c.name.toLowerCase().includes(q) || (c.children && c.children.length > 0));
    }

    return filterTree(props.categories);
});

// ✅ click outside listener
const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative w-full">
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ label }}</label>

        <!-- Input / selected value -->
        <div @click="toggleDropdown" class="w-full cursor-pointer rounded-md border bg-white px-3 py-2 text-left">
            <span v-if="selectedCategory">
                {{ selectedCategory.name }}
            </span>
            <span v-else class="text-gray-400">Select Category</span>
        </div>

        <!-- Dropdown -->
        <div v-if="open" class="absolute z-20 mt-1 w-full rounded-md border bg-white shadow-lg">
            <!-- search input -->
            <div class="border-b p-2">
                <input v-model="search" type="text" placeholder="Search category..." class="w-full rounded-md border px-2 py-1 text-sm" />
            </div>

            <ul class="max-h-80 overflow-auto">
                <CategoryNode v-for="(cat, i) in filteredCategories" :key="cat.id" :category="cat" :selected="modelValue" @select="selectCategory" />

                <!-- optional: limit message -->
                <li v-if="filteredCategories.length > MAX_VISIBLE" class="p-2 text-sm text-gray-500 italic">
                    Showing first {{ MAX_VISIBLE }} results. Refine search...
                </li>
            </ul>
        </div>
    </div>
</template>
