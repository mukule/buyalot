<template>
    <div
        class="relative flex h-full w-[calc(50%-0.5rem)] shrink-0 snap-start flex-col justify-between rounded-lg border bg-white p-3 shadow-sm transition hover:shadow-md sm:w-[calc(33%-0.5rem)] md:w-[calc(25%-0.5rem)] lg:w-[calc(16.66%-0.5rem)]"
    >
        <!-- Sale Badge -->
        <span v-if="product.onSale" class="absolute top-2 right-2 rounded bg-secondary px-2 py-1 text-xs font-bold text-white"> 10% OFF </span>

        <!-- Clickable Product Info -->
        <Link :href="`/product/${product.id}`" class="block flex-1">
            <img :src="product.image" :alt="product.name" class="mx-auto h-40 w-full object-contain" />

            <div class="mt-3">
                <h3 class="line-clamp-2 text-sm font-medium text-gray-700">{{ product.name }}</h3>

                <div class="mt-1 flex items-center gap-2 text-sm">
                    <span class="font-semibold text-primary">
                        {{ formatCurrency(product.onSale ? Math.round(product.price * 0.9) : product.price) }}
                    </span>
                    <span v-if="product.onSale" class="text-xs text-gray-400 line-through">
                        {{ formatCurrency(product.price) }}
                    </span>
                </div>

                <div class="mt-1 flex items-center gap-[2px] text-yellow-400">
                    <Star v-for="i in 5" :key="i" :class="i <= (product.rating ?? 0) ? 'fill-yellow-400' : 'fill-gray-200'" class="h-4 w-4" />
                </div>
            </div>
        </Link>

        <!-- Add to Cart -->
        <button
            class="mt-3 w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary/90"
            @click="addToCart(product.id)"
        >
            Add to Cart
        </button>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';

const props = defineProps<{
    product: {
        id: number;
        name: string;
        image: string;
        price: number;
        onSale?: boolean;
        rating?: number;
    };
}>();

const formatCurrency = (amount: number) => `KSh ${amount.toLocaleString()}`;

const addToCart = (productId: number) => {
    alert(`Added product ${productId} to cart!`);
};
</script>
