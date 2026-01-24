<template>
    <teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>

            <!-- Modal box -->
            <div class="relative z-10 w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
                <slot />
            </div>
        </div>
    </teleport>
</template>

<script setup lang="ts">
defineProps<{
    show: boolean;
}>();

defineEmits<{
    (e: 'close'): void;
}>();
</script>
