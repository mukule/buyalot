<script setup lang="ts">
import { defineEmits, defineProps } from 'vue';

const props = defineProps({
    href: { type: String, required: true },
    label: { type: String, default: '' },
    isPrimary: { type: Boolean, default: true },
    className: { type: String, default: '' },
    icon: { type: [String, Array], default: null },
});

const emit = defineEmits(['click']);

const baseClasses = 'inline-block px-4 py-2 rounded font-semibold transition-colors duration-200 text-center';

const primaryClasses = 'bg-[color:var(--secondary)] text-white hover:text-[color:var(--secondary-foreground)] hover:bg-transparent';

const secondaryClasses = 'bg-transparent text-white border border-[color:var(--secondary)] hover:bg-[color:var(--secondary)] hover:text-white';
</script>

<template>
    <a :href="href" :class="[baseClasses, isPrimary ? primaryClasses : secondaryClasses, className]" @click="$emit('click', $event)">
        <slot>{{ label }}</slot>
        <font-awesome-icon v-if="icon" :icon="icon" class="ml-2" />
    </a>
</template>
