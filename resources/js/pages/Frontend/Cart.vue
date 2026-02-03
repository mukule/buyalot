<script setup lang="ts">
import ProductCarouselSection from '@/components/ProductCarouselSection.vue';
import MainLayout from '@/layouts/MainLayout.vue';
import type { SimplifiedProduct } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

// ---------------- TYPES ----------------
interface Product {
    id: number;
    name: string;
    slug: string;
    primary_image_url?: string | null;
}

interface ProductVariant {
    id: number;
    product: Product;
}

interface CartItem {
    id: number;
    product_variant_id: number;
    product_variant: ProductVariant;
    quantity: number;
    unit_price: number;
    marked_price: number;
    total_price: number;
    discount_amount?: number;
    discount_percentage?: number;
    product_image_url?: string | null;
}

interface Cart {
    items: CartItem[];
}

interface CartSummary {
    total_amount: number;
    total_discount: number;
    total_payable: number;
}

// ---------------- PAGE PROPS ----------------
const page = usePage();
const cart = (page.props as any).cart as Cart;
const summary = (page.props as any).summary as CartSummary;

// Related products from backend
const relatedProducts = (page.props as any).relatedProducts ?? [];

// ---------------- RELATED PRODUCTS ----------------
const simplifiedRelatedProducts = computed<SimplifiedProduct[]>(() =>
    relatedProducts.map((p: any) => ({
        id: p.id,
        hashid: p.hashid ?? p.id,
        name: p.name,
        product_slug: p.product_slug,
        image: p.primary_image_url || p.image_urls?.[0] || '/fallback-image.png',
        marked_price: p.marked_price ?? p.final_price ?? 0,
        final_price: p.final_price ?? p.marked_price ?? 0,
        discount_percent:
            p.discount_percent ?? (p.marked_price && p.final_price ? Math.round(((p.marked_price - p.final_price) / p.marked_price) * 100) : 0),
        has_discount: p.has_discount ?? (p.discount_percent ? true : false),
    })),
);

// ---------------- CART ACTIONS ----------------
const increaseQty = (item: CartItem) => {
    router.post(
        route('cart.store'),
        { product_variant_id: item.product_variant.id, quantity: item.quantity + 1 },
        { onSuccess: () => window.location.reload() },
    );
};

const decreaseQty = (item: CartItem) => {
    const newQty = item.quantity - 1;
    router.post(
        route('cart.store'),
        { product_variant_id: item.product_variant.id, quantity: newQty },
        { onSuccess: () => window.location.reload() },
    );
};

// ---------------- FORMATTERS ----------------
const formatPrice = (amount: number | string) =>
    `KSh ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const formatDiscountPercentage = (percentage?: number) => (percentage && percentage > 0 ? `${Math.round(percentage)}% OFF` : '');

// Navigate to product
const goToProduct = (product: SimplifiedProduct) => {
    router.visit(route('products.show', { slug: product.product_slug }));
};
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex flex-col overflow-x-hidden">
            <div v-if="cart.items.length" class="flex flex-col gap-4 lg:flex-row">
                <div class="w-full lg:w-9/12">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <template v-for="(item, index) in cart.items" :key="item.id">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <img
                                        :src="item.product_image_url || item.product_variant.product.primary_image_url || '/fallback-image.png'"
                                        class="h-16 w-16 rounded object-cover"
                                        alt="Product Image"
                                    />

                                    <div class="flex flex-col gap-1">
                                        <Link
                                            :href="`/products/${encodeURIComponent(item.product_variant.product.slug)}?v=${encodeURIComponent(item.product_variant.id)}`"
                                            class="text-sm font-medium text-gray-800 hover:underline"
                                        >
                                            {{ item.product_variant.product.name }}
                                        </Link>

                                        <!-- PRICE -->
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800">
                                                {{ formatPrice(item.unit_price) }}
                                            </span>

                                            <span v-if="item.discount_amount && item.discount_amount > 0" class="text-sm text-gray-500 line-through">
                                                {{ formatPrice(item.marked_price) }}
                                            </span>

                                            <span
                                                v-if="item.discount_amount && item.discount_amount > 0"
                                                class="ml-2 rounded bg-secondary px-2 py-0.5 text-xs font-bold text-white"
                                            >
                                                {{ formatDiscountPercentage(item.discount_percentage) }}
                                            </span>
                                        </div>

                                        <div class="mt-1 flex items-center gap-2">
                                            <button
                                                @click="decreaseQty(item)"
                                                class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300"
                                            >
                                                -
                                            </button>
                                            <span class="min-w-[24px] text-center">
                                                {{ item.quantity }}
                                            </span>
                                            <button
                                                @click="increaseQty(item)"
                                                class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-sm font-medium text-gray-700">
                                    {{ formatPrice(item.total_price) }}
                                </div>
                            </div>

                            <hr v-if="index < cart.items.length - 1" class="border-gray-200" />
                        </template>
                    </div>
                </div>

                <div class="w-full lg:w-3/12">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Cart Summary</h2>

                        <div class="flex justify-between text-gray-700">
                            <span>Total Amount</span>
                            <span>{{ formatPrice(summary.total_amount) }}</span>
                        </div>

                        <div class="flex justify-between text-gray-700">
                            <span>Total Discount</span>
                            <span>{{ formatPrice(summary.total_discount) }}</span>
                        </div>

                        <hr class="border-gray-200" />

                        <div class="flex justify-between font-medium text-gray-800">
                            <span>Total Payable</span>
                            <span>{{ formatPrice(summary.total_payable) }}</span>
                        </div>

                        <button
                            @click="router.visit(route('checkout.summary'))"
                            class="mt-4 w-full rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                        >
                            Proceed to Checkout
                        </button>

                        <p class="mt-2 text-center text-xs text-gray-600">
                            By proceeding, you accept the
                            <a :href="route('terms')" class="text-primary underline"> Terms &amp; Conditions </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- EMPTY CART -->
            <div v-else class="rounded-lg bg-white p-6 text-center text-gray-600 shadow">Your cart is empty.</div>
        </section>

        <section class="mx-auto mt-8 mb-8" v-if="simplifiedRelatedProducts.length">
            <ProductCarouselSection
                title="Related Products"
                :products="simplifiedRelatedProducts"
                @click-item="goToProduct"
                :slug="simplifiedRelatedProducts[0]?.category_slug ?? '/'"
            />
        </section>
    </MainLayout>
</template>
