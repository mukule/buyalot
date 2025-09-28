<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    order: Object,
    riders: Array,
})

const form = useForm({
    rider_id: props.order.rider_id || '',
})

const assignRider = () => {
    form.post(route('orders.assignRider', props.order.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('rider_id'),
    })
}
</script>

<template>
    <Head :title="`Order ${order.order_code}`" />

    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Order #{{ order.order_code }}</h1>

        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-semibold">Status</h2>
                <p class="capitalize">{{ order.status }}</p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-semibold">Payment</h2>
                <p class="capitalize">{{ order.payment_status }}</p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-semibold">Fulfillment</h2>
                <p class="capitalize">{{ order.fulfillment_status }}</p>
            </div>
        </div>

        <!-- Customer & Addresses -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-semibold">Customer</h2>
                <p>{{ order.customer.first_name }} {{ order.customer.last_name }}</p>
                <p>{{ order.customer.email }}</p>
            </div>
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-semibold">Shipping Address</h2>
                <p>{{ order.shipping_address?.address_line1 }}</p>
                <p>{{ order.shipping_address?.city }}, {{ order.shipping_address?.country }}</p>
            </div>
        </div>

        <!-- Items -->
        <div class="bg-white p-4 rounded shadow mb-6">
            <h2 class="font-semibold mb-2">Items</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Product</th>
                    <th class="px-4 py-2 text-left">Quantity</th>
                    <th class="px-4 py-2 text-left">Price</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <tr v-for="item in order.order_items" :key="item.id">
                    <td class="px-4 py-2">{{ item.product_variant?.product?.name }}</td>
                    <td class="px-4 py-2">{{ item.quantity }}</td>
                    <td class="px-4 py-2">{{ item.price }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Assign Rider -->
        <div class="bg-white p-4 rounded shadow">
            <h2 class="font-semibold mb-2">Assign Rider</h2>
            <form @submit.prevent="assignRider" class="flex gap-2">
                <select v-model="form.rider_id" class="rounded border-gray-300">
                    <option value="">-- Select Rider --</option>
                    <option v-for="r in riders" :key="r.id" :value="r.id">
                        {{ r.name }}
                    </option>
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Assign</button>
            </form>
        </div>
    </div>
</template>
