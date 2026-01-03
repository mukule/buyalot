<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';
import { BarChart3, TrendingUp, Package, Monitor, Calendar } from 'lucide-vue-next';

const props = defineProps<{
    summary: any;
    bySource: any[];
    topProducts: any[];
    byRegister: any[];
    byAttendant: any[];
    filters: {
        start_date: string;
        end_date: string;
    };
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Sales Reports', href: '#' },
];

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

function applyFilters() {
    router.get(route('admin.reports.sales.index'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Sales Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">Sales Performance</h1>
                    <p class="text-muted-foreground">Monitor your business revenue and transaction trends</p>
                </div>

                <div class="flex items-end gap-3 bg-white p-3 rounded-lg shadow-sm border">
                    <div class="grid gap-1.5">
                        <Label for="start_date" class="text-xs">Start Date</Label>
                        <Input id="start_date" type="date" v-model="startDate" size="sm" class="h-8" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="end_date" class="text-xs">End Date</Label>
                        <Input id="end_date" type="date" v-model="endDate" size="sm" class="h-8" />
                    </div>
                    <Button size="sm" @click="applyFilters" class="h-8">
                        Apply
                    </Button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-green-500" />
                            Total Revenue
                        </CardDescription>
                        <CardTitle class="text-2xl">KES {{ Number(summary.total_sales || 0).toLocaleString() }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <BarChart3 class="h-4 w-4 text-blue-500" />
                            Total Orders
                        </CardDescription>
                        <CardTitle class="text-2xl">{{ Number(summary.total_orders || 0).toLocaleString() }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-purple-500" />
                            Tax Collected
                        </CardDescription>
                        <CardTitle class="text-2xl text-muted-foreground">KES {{ Number(summary.total_tax || 0).toLocaleString() }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <Package class="h-4 w-4 text-orange-500" />
                            Avg. Order Value
                        </CardDescription>
                        <CardTitle class="text-2xl">
                            KES {{ summary.total_orders > 0 ? Number(summary.total_sales / summary.total_orders).toLocaleString(undefined, {maximumFractionDigits: 0}) : 0 }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Top Products Table -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Top Selling Products
                        </CardTitle>
                        <CardDescription>Highest revenue generating products in the selected period</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="relative w-full overflow-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/50 text-left">
                                        <th class="p-3 font-medium">Product Name</th>
                                        <th class="p-3 font-medium text-center">Qty Sold</th>
                                        <th class="p-3 font-medium text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in topProducts" :key="product.name" class="border-b last:border-0 hover:bg-muted/30">
                                        <td class="p-3 font-medium">{{ product.name }}</td>
                                        <td class="p-3 text-center">{{ Number(product.total_quantity).toLocaleString() }}</td>
                                        <td class="p-3 text-right font-bold">KES {{ Number(product.total_sales).toLocaleString() }}</td>
                                    </tr>
                                    <tr v-if="topProducts.length === 0">
                                        <td colspan="3" class="p-8 text-center text-muted-foreground">No data available for this period.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Sales by Register & Source -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Monitor class="h-5 w-5" />
                                Sales by Register
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="reg in byRegister" :key="reg.name" class="flex items-center justify-between border-b pb-2 last:border-0">
                                <span class="text-sm font-medium">{{ reg.name }}</span>
                                <span class="font-bold">KES {{ Number(reg.total).toLocaleString() }}</span>
                            </div>
                            <div v-if="byRegister.length === 0" class="text-center py-4 text-sm text-muted-foreground">
                                No POS sales recorded.
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <TrendingUp class="h-5 w-5" />
                                Sales by Channel
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="source in bySource" :key="source.source" class="flex items-center justify-between border-b pb-2 last:border-0">
                                <span class="text-sm font-medium capitalize">{{ source.source }}</span>
                                <span class="font-bold">KES {{ Number(source.total).toLocaleString() }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Package class="h-5 w-5" />
                                Sales by Attendant
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="att in byAttendant" :key="att.name" class="flex items-center justify-between border-b pb-2 last:border-0">
                                <span class="text-sm font-medium">{{ att.name }}</span>
                                <span class="font-bold">KES {{ Number(att.total).toLocaleString() }}</span>
                            </div>
                            <div v-if="byAttendant.length === 0" class="text-center py-4 text-sm text-muted-foreground">
                                No sales data found.
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
