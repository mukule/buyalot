<template>
    <div :class="['rounded-xl bg-white p-4 shadow', customClass]">
        <div v-if="title" class="mb-2 flex items-center gap-2 text-lg font-semibold text-gray-800">
            <component v-if="icon" :is="icon" class="text-secondary-500 h-5 w-5" />
            <span>{{ title }}</span>
        </div>
        <div>
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    title?: string;
    icon?: any;
    customClass?: string;
}>();
</script>
