<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

// --- Types ---
interface Product {
    id: number;
    name: string;
    slug: string;
    primary_image_url?: string | null;
    images: string[];
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

// --- Props ---
const page = usePage();
const cart = (page.props as any).cart as Cart;
const summary = (page.props as any).summary as CartSummary;

// --- Cart actions ---
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

// --- Format price (always show 2 decimals) ---
const formatPrice = (amount: number | string) =>
    `KSh ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// --- Format discount amount ---
const formatDiscount = (amount: number) => `KSh ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// --- Format discount percentage ---
const formatDiscountPercentage = (percentage: number | undefined) => {
    if (!percentage || percentage <= 0) return '';
    return `${Math.round(percentage)}% OFF`;
};
</script>

<template>
    <MainLayout>
        <section class="mx-auto mt-4 mb-4 flex max-w-7xl flex-col overflow-x-hidden">
            <div v-if="cart.items.length" class="flex flex-col gap-4 lg:flex-row">
                <!-- LEFT: Cart Items -->
                <div class="w-full lg:w-9/12">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <template v-for="(item, index) in cart.items" :key="item.id">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <img
                                        :src="item.product_image_url || item.product_variant.product.primary_image_url || '/fallback-image.png'"
                                        alt="Product Image"
                                        class="h-16 w-16 rounded object-cover"
                                    />

                                    <div class="flex flex-col gap-1">
                                        <!-- CLICKABLE NAME → DETAILED VARIANT PAGE -->

                                        <Link
                                            :href="`/products/${encodeURIComponent(item.product_variant.product.slug)}?v=${encodeURIComponent(item.product_variant.id)}`"
                                            class="text-grey text-sm font-medium hover:underline"
                                        >
                                            {{ item.product_variant.product.name }}
                                        </Link>

                                        <!-- Price & Discount -->
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800">{{ formatPrice(item.unit_price) }}</span>
                                            <span v-if="item.discount_amount && item.discount_amount > 0" class="text-sm text-gray-500 line-through">
                                                {{ formatPrice(item.marked_price) }}
                                            </span>
                                            <span
                                                v-if="item.discount_amount && item.discount_amount > 0"
                                                class="ml-2 rounded bg-secondary/75 px-2 py-0.5 text-xs font-bold text-white"
                                            >
                                                {{ formatDiscountPercentage(item.discount_percentage) }}
                                            </span>
                                        </div>

                                        <!-- Quantity -->
                                        <div class="mt-1 flex items-center gap-2">
                                            <button
                                                @click="decreaseQty(item)"
                                                class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300"
                                            >
                                                -
                                            </button>
                                            <span class="min-w-[24px] text-center">{{ item.quantity }}</span>
                                            <button
                                                @click="increaseQty(item)"
                                                class="flex h-6 w-6 items-center justify-center rounded bg-gray-200 hover:bg-gray-300"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-sm font-medium text-gray-700">{{ formatPrice(item.total_price) }}</div>
                            </div>

                            <hr v-if="index < cart.items.length - 1" class="border-gray-200" />
                        </template>
                    </div>
                </div>

                <!-- RIGHT: Cart Summary -->
                <div class="w-full lg:w-3/12">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow">
                        <h2 class="text-lg font-semibold text-gray-800">Cart Summary</h2>

                        <div class="flex justify-between text-gray-700">
                            <span>Total Amount</span>
                            <span>{{ formatPrice(summary.total_amount) }}</span>
                        </div>

                        <div class="flex justify-between text-gray-700">
                            <span>Total Discount</span>
                            <span>{{ formatDiscount(summary.total_discount) }}</span>
                        </div>

                        <hr class="border-gray-200" />

                        <div class="flex justify-between text-sm font-medium text-gray-700">
                            <span>Total Payable</span>
                            <span>{{ formatPrice(summary.total_payable) }}</span>
                        </div>

                        <button
                            @click="router.visit(route('checkout.summary'))"
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                        >
                            Proceed to Checkout
                        </button>

                        <p class="mt-2 text-center text-xs text-gray-600">
                            By proceeding, you are automatically accepting the
                            <a :href="route('terms')" class="text-primary underline hover:text-primary/80">Terms &amp; Conditions</a>
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-lg bg-white p-6 text-center text-gray-600 shadow">Your cart is empty.</div>
        </section>
    </MainLayout>
</template>
