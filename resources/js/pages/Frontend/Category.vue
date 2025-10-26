<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Heart, ShoppingCart, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Props
const props = defineProps<{
    category: {
        id: number;
        name: string;
        slug: string;
        getHierarchy?: any;
        parent_id?: number | null;
        breadcrumbs?: { id: number; name: string; slug: string }[];
    };
    breadcrumbs?: { id: number; name: string; slug: string }[];
    title?: string;
    products?: any; // paginator
}>();

// Breadcrumbs
const breadcrumbTrail = computed(() => props.breadcrumbs ?? []);
const productsArray = computed(() => props.products?.data ?? []);

// Page props for wishlist/cart
const page = usePage();
const isAddingWishlist = ref(false);
const isAddingToCart = ref(false);

const isInWishlist = (productId: number) => {
    const wishlistIds = page.props.auth?.wishlistVariantIds ?? [];
    return wishlistIds.includes(productId);
};

const isInCart = (productId: number) => {
    const cartItems = page.props.auth?.cartItems ?? [];
    return cartItems.some((item: any) => item.product_variant_id === productId);
};

const toggleWishlist = (productId: number) => {
    if (isAddingWishlist.value) return;
    isAddingWishlist.value = true;
    router.post(
        route('wishlist.store'),
        { product_variant_id: productId },
        {
            preserveScroll: true,
            onFinish: () => (isAddingWishlist.value = false),
        },
    );
};

const addToCart = (productId: number) => {
    if (isAddingToCart.value || isInCart(productId)) return;
    isAddingToCart.value = true;
    router.post(
        route('cart.store'),
        { product_variant_id: productId, quantity: 1 },
        {
            preserveScroll: true,
            onFinish: () => (isAddingToCart.value = false),
        },
    );
};

const formatPrice = (amount: number | string | null) => {
    if (amount === null || amount === undefined) return 'KSh 0';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return isNaN(num) ? 'KSh 0' : `KSh ${num.toLocaleString()}`;
};
</script>

<template>
    <MainLayout>
        <section class="mt-4 mb-4 flex flex-col gap-6">
            <!-- Banner -->
            <div class="relative w-full overflow-hidden rounded-sm">
                <img src="/storage/images/buyalot1.png" alt="Category banner" class="h-auto w-full object-cover" loading="lazy" />
            </div>

            <!-- Breadcrumb -->
            <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li>
                        <a href="/" class="text-primary hover:underline">Home</a>
                        <span class="mx-1">/</span>
                    </li>
                    <li v-for="(crumb, index) in breadcrumbTrail" :key="crumb.id" class="flex items-center">
                        <a :href="`/${crumb.slug}`" class="text-primary hover:underline">{{ crumb.name }}</a>
                        <span v-if="index < breadcrumbTrail.length - 1" class="mx-1"> / </span>
                    </li>
                    <li class="truncate font-semibold text-gray-800">/ {{ props.category.name }}</li>
                </ol>
            </nav>

            <!-- Products Grid -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4">
                <div
                    v-for="product in productsArray"
                    :key="product.id"
                    class="relative flex flex-col justify-between rounded-lg border bg-white p-3 shadow-sm transition-shadow duration-200 hover:shadow-md"
                >
                    <!-- Discount -->
                    <span v-if="product.discount" class="absolute top-2 right-2 z-10 rounded bg-secondary/75 px-2 py-1 text-xs font-bold text-white">
                        {{ product.discount }}% OFF
                    </span>

                    <!-- Image -->
                    <Link :href="`/products/${product.product_slug}?v=${product.id}`" class="block">
                        <div class="relative flex h-40 w-full justify-center overflow-hidden rounded-md bg-gray-50 sm:h-48 md:h-56">
                            <img
                                :src="product.primary_image_url ?? ''"
                                :alt="product.name"
                                loading="lazy"
                                class="h-full w-full object-contain opacity-0 mix-blend-multiply transition-opacity duration-500"
                                @load="($event.target as HTMLImageElement).classList.remove('opacity-0')"
                            />
                        </div>
                    </Link>

                    <!-- Info -->
                    <div class="mt-3 flex flex-col gap-1">
                        <h3 class="line-clamp-2 text-sm font-medium text-gray-700">{{ product.name }}</h3>
                        <span class="text-sm font-semibold text-primary">{{ formatPrice(product.selling_price) }}</span>

                        <!-- Rating + Actions -->
                        <div class="mt-1 flex items-center justify-between text-yellow-400">
                            <div class="flex gap-[2px]">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    :class="i <= (product.rating ?? 0) ? 'fill-yellow-400' : 'fill-gray-200'"
                                    class="h-3 w-3"
                                />
                            </div>

                            <div class="flex items-center gap-2">
                                <Heart
                                    :class="[
                                        'h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                                        isAddingWishlist ? 'cursor-wait opacity-50' : '',
                                        isInWishlist(product.id) ? 'bg-secondary text-white' : 'text-secondary hover:bg-primary hover:text-white',
                                    ]"
                                    @click.stop.prevent="toggleWishlist(product.id)"
                                />
                                <ShoppingCart
                                    :class="[
                                        'h-6 w-6 cursor-pointer rounded-full p-1 transition-all hover:scale-110',
                                        isAddingToCart ? 'cursor-wait opacity-50' : '',
                                        isInCart(product.id) ? 'bg-primary text-white' : 'text-primary hover:bg-secondary hover:text-white',
                                    ]"
                                    @click.stop.prevent="addToCart(product.id)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
