<script setup lang="ts">
import { ref } from 'vue';

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

const toggleExpand = () => {
    expanded.value = !expanded.value;
};

const handleSelect = () => {
    emit('select', props.category.id);
};
</script>

<template>
    <li class="px-3 py-1">
        <div
            class="flex cursor-pointer items-center justify-between rounded-md px-2 py-1 transition hover:bg-gray-100"
            :class="selected === category.id ? 'bg-primary/10 font-medium text-primary' : ''"
            @click="handleSelect"
        >
            <span>{{ category.name }}</span>

            <!-- expand toggle -->
            <button
                v-if="category.children && category.children.length"
                type="button"
                class="ml-2 text-gray-400 hover:text-gray-600"
                @click.stop="toggleExpand"
            >
                {{ expanded ? '−' : '+' }}
            </button>
        </div>

        <!-- children -->
        <ul v-if="expanded && category.children?.length" class="ml-4 border-l pl-2">
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
