<script setup lang="ts">
import type { SimplifiedProduct } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Heart, ShoppingCart, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    product: SimplifiedProduct;
}>();

const page = usePage();
const isAddingWishlist = ref(false);
const isAddingToCart = ref(false);

const isInWishlist = computed(() => {
    const wishlistIds = page.props.auth?.wishlistVariantIds ?? [];
    return wishlistIds.includes(props.product.id);
});

const isInCart = computed(() => {
    const cartItems = page.props.auth?.cartItems ?? [];
    return cartItems.some((item: any) => item.product_variant_id === props.product.id);
});

const formatPrice = (amount: number | string | null): string => {
    if (amount === null || amount === undefined) return 'KSh 0';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return isNaN(num) ? 'KSh 0' : `KSh ${num.toLocaleString()}`;
};

// Toggle wishlist
const toggleWishlist = () => {
    if (isAddingWishlist.value) return;

    isAddingWishlist.value = true;

    router.post(
        route('wishlist.store'),
        { product_variant_id: props.product.id },
        {
            preserveScroll: true,
            onFinish: () => (isAddingWishlist.value = false),
        },
    );
};

const addToCart = () => {
    if (isAddingToCart.value || isInCart.value) return;

    isAddingToCart.value = true;

    router.post(
        route('cart.store'),
        { product_variant_id: props.product.id, quantity: 1 },
        {
            preserveScroll: true,
            onFinish: () => (isAddingToCart.value = false),
        },
    );
};
</script>

<template>
    <div
        class="relative flex h-full w-[calc(50%-0.5rem)] shrink-0 snap-start flex-col rounded-lg border bg-white p-3 shadow-sm transition-transform duration-300 hover:scale-105 hover:shadow-md sm:w-[calc(33%-0.5rem)] md:w-[calc(25%-0.5rem)] lg:w-[calc(16.66%-0.5rem)]"
    >
        <!-- Loading Overlay -->
        <div v-if="isAddingWishlist || isAddingToCart" class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-white/60">
            <span class="loader"></span>
        </div>

        <!-- Discount Badge -->
        <span
            v-if="product.has_discount && product.discount_percent > 0"
            class="absolute top-2 right-2 z-10 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white"
        >
            {{ Math.round(product.discount_percent) }} % OFF
        </span>

        <Link
            :href="`/products/${encodeURIComponent(product.product_slug)}?v=${encodeURIComponent(product.id)}`"
            class="relative flex flex-1 flex-col"
        >
            <!-- Image -->
            <div class="relative aspect-square w-full overflow-hidden rounded-md bg-gray-100">
                <img
                    :src="product.image ?? ''"
                    :alt="product.name"
                    loading="lazy"
                    class="h-full w-full object-contain opacity-0 transition-opacity duration-500"
                    @load="($event.target as HTMLImageElement)?.classList.remove('opacity-0')"
                />
            </div>

            <!-- Content -->
            <div class="mt-3 flex flex-1 flex-col">
                <!-- Product Name -->
                <h3 class="line-clamp-2 text-sm font-medium text-gray-700">
                    {{ product.name }}
                </h3>

                <!-- Brand -->
                <div v-if="product.brand" class="mt-1 text-xs text-secondary">
                    {{ product.brand }}
                </div>

                <!-- Price -->
                <div class="mt-2 text-sm">
                    <span class="font-semibold text-primary">
                        {{ formatPrice(product.final_price) }}
                    </span>
                </div>

                <!-- Bottom Section -->
                <div class="mt-auto pt-3">
                    <div class="flex items-center justify-between text-yellow-400">
                        <!-- Stars -->
                        <div class="flex gap-[2px]">
                            <Star v-for="i in 5" :key="i" :class="i <= (product.rating ?? 0) ? 'fill-yellow-400' : 'fill-gray-200'" class="h-3 w-3" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <Heart
                                :class="[
                                    'h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                                    isAddingWishlist ? 'cursor-wait opacity-50' : '',
                                    isInWishlist ? 'bg-secondary text-white' : 'text-secondary hover:bg-primary hover:text-white',
                                ]"
                                @click.stop.prevent="toggleWishlist"
                            />

                            <ShoppingCart
                                :class="[
                                    'h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                                    isAddingToCart ? 'cursor-wait opacity-50' : '',
                                    isInCart ? 'bg-primary text-white' : 'text-primary hover:bg-secondary hover:text-white',
                                ]"
                                @click.stop.prevent="addToCart"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>

<style scoped>
.loader {
    border: 3px solid rgba(0, 0, 0, 0.1);
    border-left-color: #4b5563;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
