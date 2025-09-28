<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    orders: Object,
    filters: Object,
    statusOptions: Array,
    paymentStatusOptions: Array,
    fulfillmentStatusOptions: Array,
})

const form = useForm({
    status: props.filters.status || '',
    payment_status: props.filters.payment_status || '',
    fulfillment_status: props.filters.fulfillment_status || '',
    order_code: props.filters.order_code || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})
</script>

<template>
    <Head title="Orders" />

    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Orders</h1>

        <!-- Filters -->
        <form @submit.prevent="form.get(route('orders.index'))" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium">Order Code</label>
                <input v-model="form.order_code" type="text" class="mt-1 w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium">Status</label>
                <select v-model="form.status" class="mt-1 w-full rounded border-gray-300">
                    <option value="">All</option>
                    <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Payment Status</label>
                <select v-model="form.payment_status" class="mt-1 w-full rounded border-gray-300">
                    <option value="">All</option>
                    <option v-for="p in paymentStatusOptions" :key="p" :value="p">{{ p }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Fulfillment</label>
                <select v-model="form.fulfillment_status" class="mt-1 w-full rounded border-gray-300">
                    <option value="">All</option>
                    <option v-for="f in fulfillmentStatusOptions" :key="f" :value="f">{{ f }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">From</label>
                <input v-model="form.date_from" type="date" class="mt-1 w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium">To</label>
                <input v-model="form.date_to" type="date" class="mt-1 w-full rounded border-gray-300" />
            </div>
            <div class="col-span-2 md:col-span-4 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded shadow">Filter</button>
            </div>
        </form>

        <!-- Orders Table -->
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium">Code</th>
                    <th class="px-4 py-2 text-left text-sm font-medium">Customer</th>
                    <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
                    <th class="px-4 py-2 text-left text-sm font-medium">Payment</th>
                    <th class="px-4 py-2 text-left text-sm font-medium">Total</th>
                    <th class="px-4 py-2 text-left text-sm font-medium">Date</th>
                    <th class="px-4 py-2"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <tr v-for="order in orders.data" :key="order.id">
                    <td class="px-4 py-2">{{ order.order_code }}</td>
                    <td class="px-4 py-2">{{ order.customer?.first_name }} {{ order.customer?.last_name }}</td>
                    <td class="px-4 py-2 capitalize">{{ order.status }}</td>
                    <td class="px-4 py-2 capitalize">{{ order.payment_status }}</td>
                    <td class="px-4 py-2 font-semibold">
                        {{ order.total_amount != null ? Number(order.total_amount).toFixed(2) : '0.00' }}
                    </td>

                    <td class="px-4 py-2">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    <td class="px-4 py-2 text-right">
                        <Link :href="route('orders.show', order.id)" class="text-indigo-600 hover:underline">View</Link>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="orders.links" class="mt-4" />
    </div>
</template>
