<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { router, usePage } from '@inertiajs/vue3';

// --- Types ---
interface Product {
    id: number;
    name: string;
    primary_image_url?: string | null;
    images: string[];
}

interface ProductVariant {
    id: number;
    product: Product;
    regular_price: number;
    selling_price: number;
}

interface CartItem {
    id: number;
    product_variant_id: number;
    product_variant: ProductVariant;
    quantity: number;
    unit_price: number;
    total_price?: number;
    product_image_url?: string | null;
}

interface Cart {
    items: CartItem[];
}

// --- Props ---
const page = usePage();
const cart = (page.props as any).cart as Cart;

// --- Subtotal ---
const subtotal = cart.items.reduce((acc: number, item: CartItem) => acc + item.unit_price * item.quantity, 0);

// --- Cart actions ---
const increaseQty = (item: CartItem) => {
    router.post(
        route('cart.store'),
        { product_variant_id: item.product_variant.id, quantity: item.quantity + 1 },
        { onSuccess: () => window.location.reload() }, // refresh page after success
    );
};

const decreaseQty = (item: CartItem) => {
    const newQty = item.quantity - 1;
    router.post(
        route('cart.store'),
        { product_variant_id: item.product_variant.id, quantity: newQty },
        { onSuccess: () => window.location.reload() }, // refresh page after success
    );
};

// --- Format price ---
const formatPrice = (amount: number) => `KSh ${amount.toLocaleString()}`;
</script>

<template>
    <MainLayout>
        <section class="mx-auto p-4" style="max-width: 1200px">
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
                                        <h2 class="text-sm font-medium text-gray-700">{{ item.product_variant.product.name }}</h2>

                                        <!-- Price & Discount -->
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800">{{ formatPrice(item.product_variant.selling_price) }}</span>
                                            <span
                                                v-if="item.product_variant.regular_price > item.product_variant.selling_price"
                                                class="text-sm text-gray-500 line-through"
                                            >
                                                {{ formatPrice(item.product_variant.regular_price) }}
                                            </span>
                                            <span
                                                v-if="item.product_variant.regular_price > item.product_variant.selling_price"
                                                class="ml-2 rounded bg-secondary/75 px-2 py-0.5 text-xs font-bold text-white"
                                            >
                                                {{
                                                    Math.round(
                                                        ((item.product_variant.regular_price - item.product_variant.selling_price) /
                                                            item.product_variant.regular_price) *
                                                            100,
                                                    )
                                                }}% OFF
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

                                <div class="text-sm font-medium text-gray-700">KSh {{ (item.unit_price * item.quantity).toLocaleString() }}</div>
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
                            <span>Subtotal</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>

                        <hr class="border-gray-200" />

                        <div class="flex justify-between text-sm font-medium text-gray-700">
                            <span>Total</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>

                        <button
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
                        >
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-lg bg-white p-6 text-center text-gray-600 shadow">Your cart is empty.</div>
        </section>
    </MainLayout>
</template>
