<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

interface Variant { id: number; sku?: string | null; label?: string | null }
interface Product { id: number; name: string }
interface Seller { id: number; name: string }
interface OrderItemPayload {
  id: number;
  quantity: number;
  unit_price: number;
  total_price: number;
  product?: Product | null;
  variant?: Variant | null;
  seller?: Seller | null;
}
interface OrderPayload {
  id: number;
  ulid?: string;
  order_code: string;
  status: string;
  payment_status: string;
  fulfillment_status: string;
  currency: string;
  subtotal: number;
  tax_amount: number;
  shipping_amount: number;
  discount_amount: number;
  total_amount: number;
  created_at?: string | null;
  customer?: { id: number; first_name: string; last_name: string; email?: string | null } | null;
  shipping_address?: any;
  billing_address?: any;
  order_items: OrderItemPayload[];
}

const props = defineProps<{ order: OrderPayload }>();

function money(val: number, currency: string) {
  const num = Number(val ?? 0);
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(num);
  } catch {
    return `${currency} ${num.toFixed(2)}`;
  }
}
</script>

<template>
  <Head :title="`Order ${props.order.order_code}`" />
  <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/admin/dashboard' }, { title: 'Orders', href: '/admin/orders' }, { title: `Order ${props.order.order_code}`, href: `/admin/orders/${props.order.ulid ?? props.order.id}` }]">
    <div class="p-6">
      <h1 class="mb-4 text-2xl font-bold">Order #{{ props.order.order_code }}</h1>

      <!-- Summary -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Status</h2>
          <p class="capitalize">{{ props.order.status }}</p>
        </div>
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Payment</h2>
          <p class="capitalize">{{ props.order.payment_status }}</p>
        </div>
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Fulfillment</h2>
          <p class="capitalize">{{ props.order.fulfillment_status }}</p>
        </div>
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Total</h2>
          <p class="font-medium">{{ money(props.order.total_amount, props.order.currency) }}</p>
        </div>
      </div>

      <!-- Customer & Addresses -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Customer</h2>
          <p v-if="props.order.customer">{{ props.order.customer.first_name }} {{ props.order.customer.last_name }}</p>
          <p v-if="props.order.customer && props.order.customer.email">{{ props.order.customer.email }}</p>
        </div>
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Shipping Address</h2>
          <p>{{ props.order.shipping_address?.address_line_1 }}</p>
          <p>{{ props.order.shipping_address?.city }}<span v-if="props.order.shipping_address?.country">, {{ props.order.shipping_address?.country }}</span></p>
        </div>
      </div>

      <!-- Items -->
      <div class="mb-6 rounded bg-white p-4 shadow">
        <h2 class="mb-2 font-semibold">Items</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full table-auto text-left text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2">Product</th>
                <th class="px-4 py-2">Variant</th>
                <th class="px-4 py-2">Quantity</th>
                <th class="px-4 py-2">Unit Price</th>
                <th class="px-4 py-2">Total</th>
                <th class="px-4 py-2">Seller</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="item in props.order.order_items" :key="item.id">
                <td class="px-4 py-2">{{ item.product?.name ?? '—' }}</td>
                <td class="px-4 py-2">{{ item.variant?.label ?? item.variant?.sku ?? '—' }}</td>
                <td class="px-4 py-2">{{ item.quantity }}</td>
                <td class="px-4 py-2">{{ money(item.unit_price, props.order.currency) }}</td>
                <td class="px-4 py-2">{{ money(item.total_price, props.order.currency) }}</td>
                <td class="px-4 py-2">{{ item.seller?.name ?? '—' }}</td>
              </tr>
              <tr v-if="props.order.order_items.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-gray-500">No items</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
