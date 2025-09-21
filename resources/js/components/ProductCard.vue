<script setup lang="ts">
import type { SimplifiedProduct } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Heart, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    product: SimplifiedProduct;
}>();

const page = usePage();
const isAdding = ref(false);

const isInWishlist = computed(() => {
    const wishlistIds = page.props.auth?.wishlistVariantIds ?? [];
    return wishlistIds.includes(props.product.id);
});

const formatPrice = (amount: number | string | null): string => {
    if (amount === null || amount === undefined) return 'KSh 0';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return isNaN(num) ? 'KSh 0' : `KSh ${num.toLocaleString()}`;
};

const toggleWishlist = () => {
    if (isAdding.value) return;

    isAdding.value = true;

    router.post(
        route('wishlist.store'),
        { product_variant_id: props.product.id },
        {
            preserveScroll: true,
            onSuccess: () => console.log('Wishlist updated'),
            onFinish: () => (isAdding.value = false),
            onError: () => console.log('Failed to update wishlist'),
        },
    );
};
</script>

<template>
    <div
        class="relative flex h-full w-[calc(50%-0.5rem)] shrink-0 snap-start flex-col justify-between rounded-lg border bg-white p-3 shadow-sm transition-transform duration-300 hover:scale-105 hover:shadow-md sm:w-[calc(33%-0.5rem)] md:w-[calc(25%-0.5rem)] lg:w-[calc(16.66%-0.5rem)]"
    >
        <!-- Discount Badge -->
        <span v-if="product.discount" class="absolute top-2 right-2 z-10 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white">
            {{ product.discount }}% OFF
        </span>

        <!-- Link to product detail -->
        <Link :href="`/products/${encodeURIComponent(product.product_slug)}?v=${encodeURIComponent(product.id)}`" class="relative block flex-1">
            <img
                :src="product.image || '/fallback-image.png'"
                :alt="product.name"
                class="mx-auto h-40 w-full object-contain transition-transform duration-300 hover:scale-105"
            />

            <div class="mt-3">
                <h3 class="line-clamp-2 text-sm font-medium text-gray-700">
                    {{ product.name }}
                </h3>

                <div class="mt-1 text-sm">
                    <span class="font-semibold text-primary">
                        {{ formatPrice(product.selling_price) }}
                    </span>
                </div>

                <div class="mt-1 flex items-center justify-between text-yellow-400">
                    <div class="flex gap-[2px]">
                        <Star v-for="i in 5" :key="i" :class="i <= (product.rating ?? 0) ? 'fill-yellow-400' : 'fill-gray-200'" class="h-4 w-4" />
                    </div>

                    <Heart
                        :class="[
                            'relative z-10 h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                            isAdding ? 'cursor-wait opacity-50' : '',
                            isInWishlist ? 'bg-secondary text-white' : 'text-secondary hover:bg-primary hover:text-white',
                        ]"
                        @click.stop.prevent="toggleWishlist"
                    />
                </div>
            </div>
        </Link>
    </div>
</template>
