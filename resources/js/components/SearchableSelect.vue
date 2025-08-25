<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface Option {
    id: number | string;
    name: string;
}

const props = defineProps<{
    label: string;
    modelValue: string | number | null;
    options: Option[];
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const search = ref('');
const open = ref(false);
const wrapper = ref<HTMLElement | null>(null);

const filtered = computed(
    () => props.options.filter((opt) => opt.name.toLowerCase().includes(search.value.toLowerCase())).slice(0, 10), // ✅ only show 10 at a time
);

const selectOption = (option: Option) => {
    emit('update:modelValue', option.id);
    open.value = false;
    search.value = '';
};

const handleClickOutside = (event: MouseEvent) => {
    if (wrapper.value && !wrapper.value.contains(event.target as Node)) {
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
    <div ref="wrapper" class="relative w-full">
        <label class="mb-1 block text-sm font-medium text-gray-700">{{ label }}</label>

        <div @click="open = !open" class="w-full cursor-pointer rounded-md border bg-white px-3 py-2 text-left">
            <span v-if="modelValue">
                {{ options.find((o) => o.id === modelValue)?.name }}
            </span>
            <span v-else class="text-gray-400">{{ placeholder ?? 'Select…' }}</span>
        </div>

        <!-- Dropdown -->
        <div v-if="open" class="absolute z-10 mt-1 w-full rounded-md border bg-white shadow-lg">
            <!-- Filter input -->
            <div class="border-b p-2">
                <input v-model="search" type="text" placeholder="Filter…" class="w-full rounded-md border px-2 py-1 text-sm" />
                <p class="mt-1 text-xs text-gray-500">Showing up to 10 results</p>
            </div>

            <ul class="max-h-60 overflow-auto">
                <li v-for="option in filtered" :key="option.id" @click="selectOption(option)" class="cursor-pointer px-3 py-2 hover:bg-gray-100">
                    {{ option.name }}
                </li>
                <li v-if="filtered.length === 0" class="px-3 py-2 text-gray-400">No results</li>
            </ul>
        </div>
    </div>
</template>
