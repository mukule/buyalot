<script setup lang="ts">
import AppLayout from '@/layouts/CustomerAppSidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Star, MessageSquare } from 'lucide-vue-next';

interface Product {
    id: number;
    name: string;
    slug: string;
    thumbnail?: string;
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    status: string;
    created_at: string;
    product: Product;
}

defineProps<{
    reviews: Review[];
}>();

const getStatusClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default: return 'bg-yellow-100 text-yellow-800';
    }
};
</script>

<template>
    <Head title="My Review History" />

    <AppLayout>
        <div class="p-4">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-800">My Review History</h1>
                    <p class="mt-1 text-sm text-gray-600">Track all your product reviews here.</p>
                </div>

                <div v-if="reviews.length > 0" class="space-y-6">
                    <div v-for="review in reviews" :key="review.id" class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row">
                        <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border bg-gray-100">
                            <img
                                :src="review.product.thumbnail || '/images/placeholder-product.png'"
                                :alt="review.product.name"
                                class="h-full w-full object-cover object-center"
                            />
                        </div>

                        <div class="flex flex-1 flex-col">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <Link
                                    :href="route('product.details', { slug: review.product.slug })"
                                    class="text-lg font-medium text-gray-900 hover:text-primary"
                                >
                                    {{ review.product.name }}
                                </Link>
                                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getStatusClass(review.status)]">
                                    {{ review.status }}
                                </span>
                            </div>

                            <div class="mt-1 flex items-center">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    :class="['h-4 w-4', i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300']"
                                />
                                <span class="ml-2 text-xs text-gray-500">{{ review.created_at }}</span>
                            </div>

                            <p class="mt-3 text-sm text-gray-700 italic">
                                "{{ review.comment }}"
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center">
                    <MessageSquare class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No reviews found</h3>
                    <p class="mt-2 text-sm text-gray-500">You haven't reviewed any products yet.</p>
                    <Link
                        :href="route('customer.reviews.pending')"
                        class="mt-6 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                    >
                        Review Pending Products
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
