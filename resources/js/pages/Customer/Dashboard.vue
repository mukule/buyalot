<script setup lang="ts">
import CustomerDashboardSidebar from '@/components/AppCustomerSidebar.vue';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, Edit } from 'lucide-vue-next';
import { computed, inject } from 'vue';

import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();

const user = computed(() => page.props.auth?.user);

interface Props {
    customer?: any;
    addresses?: any[];
    orders?: any[];
    loyaltyPoints?: number;
}

const props = withDefaults(defineProps<Props>(), {
    customer: null,
    addresses: () => [],
    orders: () => [],
    loyaltyPoints: 0,
});

// Ziggy route helper
const route = inject<((name: string, params?: any) => string) | undefined>('route');

// Default address
const defaultAddress = computed(() => {
    return props.addresses?.find((addr) => addr.is_default) || props.addresses?.[0];
});

// Recent orders
const recentOrders = computed(() => {
    return props.orders?.slice(0, 3) || [];
});
</script>

<template>
    <SidebarProvider>
        <CustomerDashboardSidebar />
        <SidebarInset>
            <!-- Header with Breadcrumb -->
            <header class="flex h-16 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem class="hidden md:block">
                            <BreadcrumbLink as-child>
                                <Link :href="route ? route('customers.dashboard', { customer: user?.id }) : '/'">
                                    Dashboard
                                </Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator class="hidden md:block" />
                        <BreadcrumbItem>
                            <BreadcrumbPage>Account Overview</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-6">
                <div class="space-y-6">
                    <!-- Page Title -->
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <h1 class="text-2xl font-semibold text-gray-900">Account Overview</h1>
                    </div>

                    <!-- Account Details and Address Book Row -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <!-- Account Details -->
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                    ACCOUNT DETAILS
                                </h2>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-lg font-medium text-gray-900">{{ user?.name || 'Guest User' }}</p>
                                    <p class="text-sm text-gray-600">{{ user?.email || 'No email provided' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Address Book -->
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                    ADDRESS BOOK
                                </h2>
                                <Link
                                    :href="
                                        route
                                            ? route('customers.addresses.index', { customer: user?.id })
                                            : `/customers/${user?.id}/addresses`
                                    "
                                    class="text-primary hover:underline"
                                >
                                    <Edit class="h-4 w-4" />
                                </Link>
                            </div>
                            <div v-if="defaultAddress" class="space-y-2">
                                <p class="text-sm font-medium text-gray-700">Your default shipping address:</p>
                                <div class="text-sm text-gray-600">
                                    <p>{{ defaultAddress.full_name || user?.name }}</p>
                                    <p>{{ defaultAddress.address_line_1 }}</p>
                                    <p v-if="defaultAddress.address_line_2">{{ defaultAddress.address_line_2 }}</p>
                                    <p>{{ defaultAddress.city }}, {{ defaultAddress.state }}</p>
                                    <p>{{ defaultAddress.phone_number }}</p>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-600">
                                <p>No default shipping address set.</p>
                                <Link
                                    :href="
                                        route
                                            ? route('customers.addresses.create', { customer: user?.id })
                                            : `/customers/${user?.id}/addresses/create`
                                    "
                                    class="mt-2 inline-block text-primary hover:underline"
                                >
                                    Add an address
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Store Credit and Newsletter Row -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <!-- Buyalot Store Credit / Loyalty Points -->
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <div class="mb-4">
                                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                    BUYALOT LOYALTY POINTS
                                </h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <CreditCard class="h-5 w-5 text-primary" />
                                <p class="text-lg font-medium text-gray-900">
                                    Loyalty points balance: <span class="text-primary">{{ loyaltyPoints }}</span> pts
                                </p>
                            </div>
                            <Link
                                :href="
                                    route
                                        ? route('customers.loyalty-points.index', { customer: user?.id })
                                        : `/customers/${user?.id}/loyalty-points`
                                "
                                class="mt-3 inline-block text-sm text-primary hover:underline"
                            >
                                View details
                            </Link>
                        </div>

                        <!-- Newsletter Preferences -->
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <div class="mb-4">
                                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                    NEWSLETTER PREFERENCES
                                </h2>
                            </div>
                            <p class="mb-3 text-sm text-gray-600">
                                Manage your email communications to stay updated with the latest news and offers.
                            </p>
                            <Link href="#" class="inline-block text-sm text-primary hover:underline">
                                Edit Newsletter preferences
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div v-if="recentOrders.length > 0" class="rounded-lg border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">RECENT ORDERS</h2>
                            <Link
                                :href="route ? route('orders.index') : '/orders/my-orders'"
                                class="text-sm text-primary hover:underline"
                            >
                                View all
                            </Link>
                        </div>
                        <div class="space-y-4">
                            <div
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="flex items-center justify-between border-b border-gray-200 pb-4 last:border-0"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">Order #{{ order.order_number }}</p>
                                    <p class="text-sm text-gray-600">{{ order.created_at }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900">KSh {{ order.total }}</p>
                                    <p class="text-sm text-gray-600">{{ order.status }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
