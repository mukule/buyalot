<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    logos: string[];
}>();

const brandsContainer = ref<HTMLDivElement | null>(null);

const scrollBrands = (direction: 'left' | 'right') => {
    if (!brandsContainer.value) return;
    const scrollAmount = brandsContainer.value.offsetWidth / 2;
    brandsContainer.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth',
    });
};
</script>

<template>
    <div class="relative mt-6">
        <!-- Left Scroll Button -->
        <button
            @click="scrollBrands('left')"
            class="absolute top-1/2 left-0 z-10 -translate-y-1/2 rounded-full bg-secondary p-2 shadow-md hover:bg-gray-100"
            aria-label="Scroll Left"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Brand Logos -->
        <div ref="brandsContainer" class="scrollbar-hide flex space-x-4 overflow-x-auto scroll-smooth px-2 pb-4">
            <div v-for="(brand, index) in logos" :key="index" class="w-1/2 flex-none px-2 py-2 sm:w-1/2 md:w-1/4">
                <Link :href="`/brands/${index}`">
                    <img :src="brand" alt="Brand Logo" class="mx-auto h-[70px] w-[160px] object-contain" />
                </Link>
            </div>
        </div>

        <!-- Right Scroll Button -->
        <button
            @click="scrollBrands('right')"
            class="absolute top-1/2 right-0 z-10 -translate-y-1/2 rounded-full bg-secondary p-2 shadow-md hover:bg-gray-100"
            aria-label="Scroll Right"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</template>
