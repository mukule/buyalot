<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const stats = page.props.stats;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <h2 class="text-2xl font-semibold mb-2">Overview</h2>

            <!-- Stats Cards -->
<!--            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">-->
<!--                <div-->
<!--                    v-for="(value, key) in stats"-->
<!--                    :key="key"-->
<!--                    class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow"-->
<!--                >-->
<!--                    <p class="text-green-500 capitalize">{{ key.replace('_', ' ') }}</p>-->
<!--                    <h3 class="text-3xl font-bold text-green-800 dark:text-gray-100 mt-2">{{ value }}</h3>-->
<!--                </div>-->
<!--            </div>-->

            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div
                    v-for="(value, key) in stats"
                    :key="key"
                    class="rounded-lg bg-gradient-to-r from-green-600 to-green-700 p-4 text-white shadow-md hover:shadow-lg transition-all"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-100 capitalize">
                                {{ key.replaceAll('_', ' ') }}
                            </p>

                            <p class="text-2xl font-bold mt-1">
                                {{ value }}
                                <!-- Order growth indicator -->
                                <span
                                    v-if="key === 'order_growth'"
                                    :class="{
              'text-lime-300': stats.order_growth > 0,
              'text-red-300': stats.order_growth < 0,
              'text-gray-300': stats.order_growth === 0
            }"
                                    class="text-sm ml-2 font-semibold"
                                >
            ({{ stats.order_growth > 0 ? '+' : '' }}{{ stats.order_growth }}%)
          </span>
                            </p>
                        </div>

                        <!-- Use different icons for different stat types -->
                        <svg
                            v-if="key === 'orders'"
                            class="h-8 w-8 text-green-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h18v4H3V3zm0 6h18v12H3V9zm5 4h4v4H8v-4z"
                            />
                        </svg>

                        <svg
                            v-else-if="key === 'customers'"
                            class="h-8 w-8 text-green-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 12a5 5 0 100-10 5 5 0 000 10z"
                            />
                        </svg>

                        <svg
                            v-else-if="key === 'sellers'"
                            class="h-8 w-8 text-green-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10h18M9 21V3m6 18V3"
                            />
                        </svg>

                        <svg
                            v-else-if="key === 'warehouses'"
                            class="h-8 w-8 text-green-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 7l9-4 9 4v13a1 1 0 01-1 1H4a1 1 0 01-1-1V7z"
                            />
                        </svg>

                        <svg
                            v-else
                            class="h-8 w-8 text-green-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 20h9M3 20h9M12 4h9M3 4h9M3 12h18"
                            />
                        </svg>
                    </div>
                </div>
            </div>



            <!-- Placeholder for charts or extra widgets -->
            <div
                class="relative min-h-[40vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border mt-6"
            >
                <div class="flex items-center justify-center h-full text-gray-500">
                    Charts or activity summary coming soon...
                </div>
            </div>
        </div>
    </AppLayout>
</template>




<!--<script setup lang="ts">-->
<!--import AppLayout from '@/layouts/AppLayout.vue';-->
<!--import { type BreadcrumbItem } from '@/types';-->
<!--import { Head } from '@inertiajs/vue3';-->
<!--import PlaceholderPattern from '../components/PlaceholderPattern.vue';-->
<!--const breadcrumbs: BreadcrumbItem[] = [-->
<!--    {-->
<!--        title: 'Dashboard',-->
<!--        href: '/dashboard',-->
<!--    },-->
<!--];-->
<!--</script>-->

<!--<template>-->
<!--    <Head title="Dashboard" />-->

<!--    <AppLayout :breadcrumbs="breadcrumbs">-->
<!--        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">-->
<!--            <div class="grid auto-rows-min gap-4 md:grid-cols-3">-->
<!--                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">-->
<!--                    <PlaceholderPattern />-->
<!--                </div>-->
<!--                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">-->
<!--                    <PlaceholderPattern />-->
<!--                </div>-->
<!--                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">-->
<!--                    <PlaceholderPattern />-->
<!--                </div>-->
<!--            </div>-->
<!--            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">-->
<!--                <PlaceholderPattern />-->
<!--            </div>-->
<!--        </div>-->
<!--    </AppLayout>-->
<!--</template>-->
