<script setup lang="ts">
import AppLayout from '@/layouts/CustomerAppSidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag, Star } from 'lucide-vue-next';

interface Product {
    id: number;
    name: string;
    slug: string;
    thumbnail?: string;
    order_date: string;
}

defineProps<{
    products: Product[];
}>();
</script>

<template>
    <Head title="Pending Reviews" />

    <AppLayout>
        <div class="p-4">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800">Pending Reviews</h1>
                    <p class="mt-1 text-sm text-gray-600">Share your feedback on these recently purchased products.</p>
                </div>

                <div v-if="products.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="product in products" :key="product.id" class="flex flex-col overflow-hidden rounded-lg border bg-white shadow-sm transition-shadow hover:shadow-md">
                        <div class="aspect-square w-full bg-gray-100">
                            <img
                                :src="product.thumbnail || '/images/placeholder-product.png'"
                                :alt="product.name"
                                class="h-full w-full object-cover object-center"
                            />
                        </div>

                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="text-sm font-medium text-gray-900 line-clamp-2">
                                {{ product.name }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">Purchased on {{ product.order_date }}</p>

                            <div class="mt-auto pt-4">
                                <Link
                                    :href="route('product.details', { slug: product.slug, scroll: 'reviews' })"
                                    class="flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                                >
                                    <Star class="h-4 w-4" />
                                    Review Now
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center">
                    <ShoppingBag class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No pending reviews</h3>
                    <p class="mt-2 text-sm text-gray-500">You've reviewed all your recent purchases. Great job!</p>
                    <Link
                        :href="route('home')"
                        class="mt-6 inline-flex items-center rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
