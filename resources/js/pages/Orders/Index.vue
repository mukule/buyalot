<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
    orders: Array<{
        id: number
        order_code: string
        subtotal: number
        tax_amount: number
        shipping_amount: number
        discount_amount: number
        total_amount: number
        status: string
        currency: string
        created_at: string
        order_items?: Array<{
            id: number
            quantity: number
            unit_price: number
            total_price: number
            product?: {
                id: number
                name: string
            }
            seller?: {
                id: number
                name: string
            }
        }>
    }>
}>()
</script>

<template>
    <Head title="My Orders" />

    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">My Orders</h1>

        <div v-if="orders.length === 0" class="text-gray-500">
            You don’t have any orders yet.
        </div>

        <div v-else class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="min-w-full text-sm text-left border-collapse">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border-b">Order Code</th>
                    <th class="px-4 py-2 border-b">Date</th>
                    <th class="px-4 py-2 border-b">Items</th>
                    <th class="px-4 py-2 border-b">Total</th>
                    <th class="px-4 py-2 border-b">Status</th>
                    <th class="px-4 py-2 border-b">Action</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="order in orders" :key="order.id" class="border-t">
                    <td class="px-4 py-2">{{ order.order_code }}</td>
                    <td class="px-4 py-2">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    <td class="px-4 py-2">
                        <ul>
                            <li
                                v-for="item in order.order_items ?? []"
                                :key="item.id"
                                class="text-gray-700"
                            >
                                {{ item.product?.name ?? 'Unknown Product' }} (x{{ item.quantity }})
                            </li>
                        </ul>
                    </td>
                    <td class="px-4 py-2 font-semibold">
                        {{ order.currency }} {{ order.total_amount.toFixed(2) }}
                    </td>
                    <td class="px-4 py-2">
              <span
                  :class="[
                  'px-2 py-1 rounded text-xs font-semibold',
                  order.status === 'pending' && 'bg-yellow-100 text-yellow-800',
                  order.status === 'completed' && 'bg-green-100 text-green-800',
                  order.status === 'cancelled' && 'bg-red-100 text-red-800'
                ]"
              >
                {{ order.status }}
              </span>
                    </td>
                    <td class="px-4 py-2">
                        <Link
                            :href="`/orders/${order.id}`"
                            class="text-blue-600 hover:underline"
                        >
                            View
                        </Link>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
