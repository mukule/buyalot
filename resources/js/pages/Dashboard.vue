<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar, Pie } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    stats: Object,
    ordersByStatus: Object,
    productVariantPerformance: Array,
});

// --- Prepare chart data ---

// Group ordersByStatus for Chart.js
const labels = [...new Set(Object.values(props.ordersByStatus).flat().map((o) => o.date))];
const datasets = Object.keys(props.ordersByStatus).map((status, i) => ({
    label: status.replaceAll('_', ' ').toUpperCase(),
    backgroundColor: [
        '#29AB87', // Jungle green for 1st status
        '#34D399',
        '#A7F3D0',
        '#065F46',
        '#10B981',
    ][i % 5],
    data: labels.map((date) => {
        const found = props.ordersByStatus[status].find((o) => o.date === date);
        return found ? found.total : 0;
    }),
}));

const ordersChartData = { labels, datasets };

// Product variant performance
const pieData = {
    labels: props.productVariantPerformance.map((p) => p.name),
    datasets: [
        {
            data: props.productVariantPerformance.map((p) => p.total),
            backgroundColor: [
                '#29AB87',
                '#34D399',
                '#10B981',
                '#A7F3D0',
                '#065F46',
                '#16A34A',
                '#6EE7B7',
                '#047857',
                '#15803D',
                '#22C55E',
            ],
        },
    ],
};

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { color: '#374151' } },
        title: { display: false },
    },
};

const getGradient = (key) => {
    if (key.includes('customer') || key.includes('user'))
        return 'from-blue-500 to-blue-600'
    if (key.includes('order') && !key.includes('growth'))
        return 'from-green-500 to-green-600'
    if (key.includes('growth') || key.includes('trend'))
        return 'from-purple-500 to-purple-600'
    if (key.includes('revenue') || key.includes('sales'))
        return 'from-orange-500 to-orange-600'
    if (key.includes('product') || key.includes('inventory'))
        return 'from-pink-500 to-pink-600'
    return 'from-gray-500 to-gray-600'
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="p-4 space-y-6">

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <div
                    v-for="(value, key) in stats"
                    :key="key"
                    :class="[
                    'rounded-lg p-4 text-white shadow-md bg-gradient-to-r',
                    getGradient(key)
                ]"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 capitalize">{{ key.replaceAll('_', ' ') }}</p>
                            <p class="text-2xl font-bold">{{ value }}</p>
                        </div>

                        <!-- Icons -->
                        <svg
                            v-if="key.includes('customer') || key.includes('user')"
                            class="h-8 w-8 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>

                        <svg
                            v-else-if="key.includes('order') && !key.includes('growth')"
                            class="h-8 w-8 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.35 2.7a1 1 0 00.9 1.3H19m-12 0a1 1 0 100 2 1 1 0 000-2zm12 0a1 1 0 100 2 1 1 0 000-2z"
                            />
                        </svg>

                        <svg
                            v-else-if="key.includes('growth') || key.includes('trend')"
                            class="h-8 w-8 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 17l6-6 4 4 8-8M13 5h8v8"
                            />
                        </svg>

                        <svg
                            v-else-if="key.includes('revenue') || key.includes('sales')"
                            class="h-8 w-8 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 1.343-3 3h6c0-1.657-1.343-3-3-3zm0 8c1.657 0 3-1.343 3-3H9c0 1.657 1.343 3 3 3z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 2v2m0 16v2m8-10h2M2 12h2"
                            />
                        </svg>

                        <svg
                            v-else-if="key.includes('product') || key.includes('inventory')"
                            class="h-8 w-8 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"
                            />
                        </svg>

                        <svg
                            v-else
                            class="h-8 w-8 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                    </div>
                </div>
            </div>
            <!--end of stats cards-->

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Orders Chart -->
                <div class="rounded-lg bg-white shadow p-6">
                    <h2 class="text-lg font-semibold text-green-400 mb-4">
                        Orders by Status (This Month)
                    </h2>
                    <div class="h-[350px]">
                        <Bar :data="ordersChartData" :options="options" />
                    </div>
                </div>

                <!-- Pie Chart -->
                <div class="rounded-lg bg-white shadow p-6">
                    <h2 class="text-lg font-semibold text-green-400 mb-4">
                        Product Variant Performance
                    </h2>
                    <div class="h-[350px]">
                        <Pie :data="pieData" :options="options" />
                    </div>
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
