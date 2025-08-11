<template>
    <div
        class="relative flex h-full w-[calc(50%-0.5rem)] shrink-0 snap-start flex-col justify-between rounded-lg border bg-white p-3 shadow-sm transition hover:shadow-md sm:w-[calc(33%-0.5rem)] md:w-[calc(25%-0.5rem)] lg:w-[calc(16.66%-0.5rem)]"
    >
        <span v-if="product.hasDiscount" class="bg-opacity-75 absolute top-2 right-2 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white">
            {{ product.discount ? `${product.discount}% OFF` : 'ON SALE' }}
        </span>

        <Link :href="`/${encodeURIComponent(product.slug)}`" class="block flex-1">
            <img :src="product.image || 'https://via.placeholder.com/150'" :alt="product.name" class="mx-auto h-40 w-full object-contain" />

            <div class="mt-3">
                <h3 class="line-clamp-2 text-sm font-medium text-gray-700">
                    {{ product.name }}
                </h3>

                <div class="mt-1 flex items-center gap-2 text-sm">
                    <span class="font-semibold text-primary">
                        {{ formatPrice(product.discountedPrice) }}
                    </span>
                    <span v-if="product.hasDiscount" class="text-xs text-gray-400 line-through">
                        {{ formatPrice(product.price) }}
                    </span>
                </div>

                <div class="mt-1 flex justify-center gap-[2px] text-yellow-400">
                    <Star v-for="i in 5" :key="i" :class="i <= (product.rating ?? 0) ? 'fill-yellow-400' : 'fill-gray-200'" class="h-4 w-4" />
                </div>
            </div>
        </Link>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';

const props = defineProps<{
    product: {
        id: number;
        slug: string;
        name: string;
        image: string;
        price: number;
        discountedPrice: number;
        hasDiscount: boolean;
        discount?: number;
        rating?: number;
    };
}>();

const formatPrice = (amount: number): string => `KSh ${amount.toLocaleString()}`;
</script>
