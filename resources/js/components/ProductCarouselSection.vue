<template>
    <div class="relative rounded-lg bg-white p-4 shadow-sm">
        <!-- Header -->
        <div class="mb-4 flex items-center justify-between px-2">
            <h2 class="text-xl font-bold text-gray-800">{{ title }}</h2>
            <button
                class="flex items-center rounded-md border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white"
            >
                View all
                <ChevronRight class="ml-1 h-4 w-4" />
            </button>
        </div>

        <div class="relative">
            <!-- Left Scroll Button -->
            <button
                @click="scroll('left')"
                class="absolute top-1/2 left-0 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-secondary shadow-md hover:bg-primary hover:text-white"
                aria-label="Scroll Left"
            >
                <ChevronLeft class="h-5 w-5 text-white" />
            </button>

            <!-- Product Cards -->
            <div ref="container" class="scrollbar-hide flex snap-x gap-4 overflow-x-auto scroll-smooth px-2 py-4">
                <ProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>

            <!-- Right Scroll Button -->
            <button
                @click="scroll('right')"
                class="absolute top-1/2 right-0 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-secondary shadow-md hover:bg-primary hover:text-white"
                aria-label="Scroll Right"
            >
                <ChevronRight class="h-5 w-5 text-white" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { SimplifiedProduct } from '@/types';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';
import ProductCard from './ProductCard.vue';

const props = defineProps<{
    title: string;
    products: SimplifiedProduct[];
}>();

const container = ref<HTMLElement | null>(null);

const scroll = (direction: 'left' | 'right') => {
    const el = container.value;
    if (!el) return;

    const scrollAmount = el.offsetWidth * 0.9;
    el.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth',
    });
};
</script>
