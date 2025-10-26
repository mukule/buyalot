<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { computed } from 'vue';

// Props passed from controller
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
}>();

// Compute breadcrumb trail
const breadcrumbTrail = computed(() => props.breadcrumbs ?? []);
</script>

<template>
    <MainLayout>
        <section class="mt-4 mb-4 flex flex-col gap-6">
            <div class="relative w-full overflow-hidden rounded-sm">
                <img src="/storage/images/buyalot1.png" alt="Category banner" class="h-auto w-full object-contain" loading="lazy" />
            </div>

            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1">
                    <li>
                        <a href="/" class="text-primary hover:underline">Home</a>
                        <span class="mx-1">/</span>
                    </li>
                    <li v-for="(crumb, index) in breadcrumbTrail" :key="crumb.id" class="flex items-center">
                        <a :href="`/${crumb.slug}`" class="text-primary hover:underline">
                            {{ crumb.name }}
                        </a>
                        <span v-if="index < breadcrumbTrail.length - 1" class="mx-1"> / </span>
                    </li>
                    <li class="truncate font-semibold text-gray-800">/ {{ category.name }}</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <header class="mb-8 border-b pb-4">
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ title || category.name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Browse products in the {{ category.name }} category.</p>
            </header>
        </section>
    </MainLayout>
</template>

<style scoped>
section {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
