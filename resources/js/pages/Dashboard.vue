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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="p-4 space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div
                    v-for="(value, key) in stats"
                    :key="key"
                    class="rounded-lg bg-gradient-to-r from-[#29AB87] to-emerald-600 p-4 text-white shadow-md"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-80 capitalize">{{ key.replaceAll('_', ' ') }}</p>
                            <p class="text-2xl font-bold">{{ value }}</p>
                        </div>
                        <svg
                            class="h-8 w-8 text-emerald-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            ></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Orders Chart -->
                <div class="rounded-lg bg-white shadow p-6">
                    <h2 class="text-lg font-semibold text-[#29AB87] mb-4">
                        Orders by Status (This Month)
                    </h2>
                    <div class="h-[350px]">
                        <Bar :data="ordersChartData" :options="options" />
                    </div>
                </div>

                <!-- Pie Chart -->
                <div class="rounded-lg bg-white shadow p-6">
                    <h2 class="text-lg font-semibold text-[#29AB87] mb-4">
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
