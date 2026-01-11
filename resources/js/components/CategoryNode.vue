<script setup lang="ts">
import { computed, ref } from 'vue';

interface Category {
    id: number | string;
    name: string;
    children?: Category[];
}

const props = defineProps<{
    category: Category;
    selected: string | number | null;
}>();

const emit = defineEmits<{
    (e: 'select', id: string | number): void;
}>();

const expanded = ref(false);

const toggleExpand = (event: MouseEvent) => {
    event.stopPropagation();
    if (hasChildren.value) {
        expanded.value = !expanded.value;
    }
};

const handleSelect = (event: MouseEvent) => {
    event.stopPropagation();
    if (isLeaf.value) {
        emit('select', props.category.id);
    } else {
        // If not leaf, expand/collapse instead
        expanded.value = !expanded.value;
    }
};

// Computed properties
const hasChildren = computed(() => {
    return props.category.children && props.category.children.length > 0;
});

const isLeaf = computed(() => {
    return !hasChildren.value;
});
</script>

<template>
    <li class="px-3 py-1">
        <div
            class="flex cursor-pointer items-center justify-between rounded-md px-2 py-1 transition"
            :class="[isLeaf ? 'hover:bg-gray-100' : 'hover:bg-blue-50', selected === category.id ? 'bg-primary/10 font-medium text-primary' : '']"
            @click="handleSelect"
        >
            <div class="flex items-center gap-2">
                <!-- Expand/collapse icon for non-leaf categories -->
                <span v-if="hasChildren" class="flex h-5 w-5 items-center justify-center text-gray-500">
                    <svg v-if="expanded" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>

                <!-- Leaf indicator -->
                <span v-if="isLeaf" class="h-2 w-2 rounded-full bg-green-500"></span>

                <!-- Category name -->
                <span :class="{ 'text-gray-400': !isLeaf }">
                    {{ category.name }}
                </span>

                <!-- Badge for non-leaf -->
                <span v-if="hasChildren" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                    {{ category.children?.length }}
                </span>
            </div>

            <!-- Selection indicator -->
            <span v-if="selected === category.id && isLeaf" class="text-green-500"> ✓ </span>
        </div>

        <!-- Children (only for non-leaf categories) -->
        <ul v-if="expanded && hasChildren" class="ml-6 border-l border-gray-200 pl-3">
            <CategoryNode
                v-for="child in category.children"
                :key="child.id"
                :category="child"
                :selected="selected"
                @select="emit('select', $event)"
            />
        </ul>
    </li>
</template>
