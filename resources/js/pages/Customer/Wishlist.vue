<script setup lang="ts">
import AppLayout from '@/layouts/CustomerAppSidebarLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Heart, ShoppingCart, Trash2, SearchIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { debounce } from 'lodash';

interface Product {
    id: number;
    name: string;
    slug: string;
    thumbnail?: string;
}

interface ProductVariant {
    id: number;
    sku: string;
    price: number;
    stock_quantity: number;
    product: Product;
}

interface WishlistItem {
    id: number;
    product_variant_id: number;
    created_at: string;
    productVariant: ProductVariant;
}

const page = usePage<{ wishlist: WishlistItem[] }>();
const searchQuery = ref('');
const debouncedQuery = ref(searchQuery.value);

watch(
    searchQuery,
    debounce((val: string) => {
        debouncedQuery.value = val;
    }, 300),
);

const filteredWishlist = computed(() => {
    const items = page.props.wishlist ?? [];
    const query = debouncedQuery.value.trim().toLowerCase();

    if (!query) return items;
    return items.filter((item) =>
        item.productVariant?.product?.name.toLowerCase().includes(query)
    );
});
//
// function removeFromWishlist(id: number) {
//     if (confirm('Are you sure you want to remove this item from your wishlist?')) {
//         router.delete(route('wishlist.destroy', { wishlist: id }), {
//             preserveScroll: true,
//         });
//     }
// }
function removeFromWishlist(id: number) {
    if (confirm('Are you sure you want to remove this item from your wishlist?')) {
        router.delete(route('wishlist.destroy', { wishlist: id }), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Item removed from wishlist');
            },
            onError: (err) => {
                console.error(err);
            },
        });
    }
}
function addToCart(item: WishlistItem) {
    router.post(
        route('cart.store'),
        {
            product_variant_id: item.product_variant_id,
            quantity: 1,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                removeFromWishlist(item.id);
            },
        }
    );
}

function viewProduct(slug: string) {
    router.get(route('product.details', { slug }));
}

const getProductImage = (item: WishlistItem) => {
    return item.productVariant?.product?.thumbnail || '/images/placeholder-product.png';
};

const isInStock = (item: WishlistItem) => {
    return item.productVariant?.stock_quantity > 0;
};
</script>

<template>
    <Head title="My Wishlist" />

    <AppLayout>
        <div class="p-4">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">My Wishlist</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ filteredWishlist.length }} {{ filteredWishlist.length === 1 ? 'item' : 'items' }}
                        </p>
                    </div>
                </div>

                <!-- Search -->
                <div v-if="page.props.wishlist?.length > 0" class="mb-6">
                    <label for="search" class="sr-only">Search wishlist</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <SearchIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            id="search"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products in wishlist..."
                            class="focus:border-primary-500 focus:ring-primary-500 block w-full rounded-md border border-gray-300 py-2 pl-10 text-sm"
                        />
                    </div>
                </div>

                <!-- Wishlist Items Grid -->
                <div v-if="filteredWishlist.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div
                        v-for="item in filteredWishlist"
                        :key="item.id"
                        class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                    >
                        <!-- Product Image -->
                        <div class="relative aspect-square w-full overflow-hidden bg-gray-100">
                            <img
                                :src="getProductImage(item)"
                                :alt="item?.productVariant?.product?.name"
                                class="h-full w-full object-cover object-center transition-transform group-hover:scale-105"
                            />

                            <!-- Out of Stock Overlay -->
                            <div
                                v-if="!isInStock(item)"
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50"
                            >
                                <span class="rounded bg-red-500 px-3 py-1 text-sm font-semibold text-white">
                                    Out of Stock
                                </span>
                            </div>

                            <!-- Remove Button -->
                            <button
                                @click="removeFromWishlist(item.id)"
                                class="absolute top-2 right-2 rounded-full bg-white p-2 shadow-md transition-colors hover:bg-red-50"
                                aria-label="Remove from wishlist"
                            >
                                <Heart class="h-5 w-5 fill-red-500 text-red-500" />
                            </button>
                        </div>

                        <!-- Product Info -->
                        <div class="flex flex-1 flex-col p-4">
                            <h3
                                class="cursor-pointer text-sm font-medium text-gray-900 line-clamp-2 hover:text-primary"
                                @click="viewProduct(item?.productVariant?.product?.slug)"
                            >
                                {{ item?.productVariant?.product?.name }}
                            </h3>

                            <div class="mt-2 flex items-baseline space-x-2">
                                <p class="text-lg font-semibold text-gray-900">
                                    KSh {{ item?.productVariant?.price }}
                                </p>
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                SKU: {{ item?.productVariant?.sku }}
                            </p>

                            <!-- Stock Status -->
                            <p
                                v-if="isInStock(item)"
                                class="mt-2 text-xs text-green-600"
                            >
                                In Stock ({{ item?.productVariant?.stock_quantity }} available)
                            </p>
                            <p v-else class="mt-2 text-xs text-red-600">Out of Stock</p>

                            <!-- Actions -->
                            <div class="mt-4 flex gap-2">
                                <button
                                    @click="addToCart(item)"
                                    :disabled="!isInStock(item)"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <ShoppingCart class="h-4 w-4" />
                                    Add to Cart
                                </button>
                                <button
                                    @click="removeFromWishlist(item.id)"
                                    class="rounded-md border border-gray-300 p-2 text-gray-600 transition-colors hover:bg-gray-50"
                                    aria-label="Remove"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center">
                    <Heart class="mx-auto h-16 w-16 text-gray-300" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Your wishlist is empty</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ searchQuery ? 'No products match your search.' : 'Start adding products you love to your wishlist!' }}
                    </p>
                    <button
                        v-if="!searchQuery"
                        @click="router.get('/')"
                        class="mt-6 inline-flex items-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90"
                    >
                        Continue Shopping
                    </button>
                    <button
                        v-else
                        @click="searchQuery = ''"
                        class="mt-6 inline-flex items-center rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Clear Search
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
