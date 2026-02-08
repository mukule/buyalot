<script setup lang="ts">
import AppLayout from '@/layouts/CustomerAppSidebarLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Package, CheckCircle, Cog, Truck, Home, XCircle } from 'lucide-vue-next';

const page = usePage();
const order = computed<any>(() => (page.props as any).order || {});

const paying = ref(false);

// Order stages for the stepper (in progression order)
const ORDER_STAGES = [
  { key: 'pending', label: 'Order placed', icon: Package },
  { key: 'confirmed', label: 'Confirmed', icon: CheckCircle },
  { key: 'processing', label: 'Processing', icon: Cog },
  { key: 'out_for_delivery', label: 'Out for Delivery', icon: Truck },
  { key: 'delivered', label: 'Delivered', icon: Home },
] as const;

const STAGE_ORDER: Record<string, number> = {
  pending: 0,
  confirmed: 1,
  processing: 2,
  on_hold: 2,
  shipped: 3,
  out_for_delivery: 3,
  delivered: 4,
  returned: -1,
  partially_returned: -1,
  refunded: -1,
  partially_refunded: -1,
  cancelled: -1,
  failed: -1,
};

const currentStageIndex = computed(() => {
  const status = (order.value?.status || 'pending').toLowerCase();
  const idx = STAGE_ORDER[status] ?? 0;
  return idx >= 0 ? idx : 0;
});

const isTerminalNegative = computed(() => {
  const s = (order.value?.status || '').toLowerCase();
  return ['cancelled', 'refunded', 'partially_refunded', 'returned', 'partially_returned', 'failed'].includes(s);
});

function stageStatus(index: number): 'completed' | 'current' | 'upcoming' {
  if (isTerminalNegative.value) {
    const lastPositive = Math.max(0, currentStageIndex.value);
    if (index < lastPositive) return 'completed';
    if (index === lastPositive) return 'current';
    return 'upcoming';
  }
  if (index < currentStageIndex.value) return 'completed';
  if (index === currentStageIndex.value) return 'current';
  return 'upcoming';
}

const canPay = computed(() => {
  const o = order.value;
  return o && o.payment_status === 'pending' && !!o.total_amount && !!o.currency;
});

function formatMoney(amount: number | string, currency?: string) {
  const val = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
  return `${currency || order.value?.currency || 'KES'} ${Number(val).toFixed(2)}`;
}

async function payNow() {
  try {
    const o = order.value;
    if (!o?.id) return;
    let phone = (window as any)?.APP_DEFAULT_PHONE || '';
    if (!phone) {
      phone = prompt('Enter your M-Pesa phone number (e.g., 07xxxxxxxx or 2547xxxxxxxx):') || '';
    }
    if (!phone) return;

    paying.value = true;
    const axios = (window as any).axios || (await import('axios')).default;
    const payload: any = {
      payable_type: 'order',
      payable_id: o.id,
      amount: o.total_amount,
      currency: o.currency || 'KES',
      provider: 'mpesa',
      method: 'mobile_money',
      phone: phone,
    };
    const resp = await axios.post(route('payments.initiate'), payload, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      withCredentials: true,
    });
    if (resp.status >= 200 && resp.status < 300) {
      alert('Payment initiated. Please check your phone for the M-Pesa prompt and enter your PIN.');
    }
  } catch (e: any) {
    const resp = e?.response;
    if (resp?.status === 409 && resp?.data?.items?.length) {
      const items = resp.data.items as Array<{ product_variant_id: number; requested: number; available: number; product_name: string }>;
      const lines = items.map(i => `- ${i.product_name}: requested ${i.requested}, available ${i.available}`).join('\n');
      alert('Cannot proceed with payment due to insufficient stock for:\n' + lines);
    } else {
      alert(resp?.data?.message || 'Failed to initiate payment.');
    }
  } finally {
    paying.value = false;
  }
}

function statusBadgeClass(status?: string) {
  switch ((status || '').toLowerCase()) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'processing':
    case 'on_hold': return 'bg-blue-100 text-blue-800';
    case 'confirmed': return 'bg-indigo-100 text-indigo-800';
    case 'shipped':
    case 'out_for_delivery': return 'bg-purple-100 text-purple-800';
    case 'delivered': return 'bg-green-100 text-green-800';
    case 'returned':
    case 'partially_returned':
    case 'cancelled':
    case 'refunded':
    case 'partially_refunded':
    case 'failed': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}
</script>

<template>
  <Head :title="`Order ${order?.order_code || ''}`" />
  <AppLayout>
    <div class="p-4">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-800">Order Details</h1>
          <p class="text-sm text-gray-600">Order #: <span class="font-medium">{{ order?.order_code }}</span></p>
          <p class="text-xs text-gray-500">Placed on {{ order?.created_at }}</p>
        </div>
        <div class="space-x-2">
          <Link :href="route('orders.index')" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Back to My Orders</Link>
          <button
            v-if="canPay"
            :disabled="paying"
            @click="payNow"
            class="rounded bg-primary px-4 py-2 text-sm text-white hover:bg-primary/90 disabled:opacity-60"
          >{{ paying ? 'Processing…' : 'Pay Now' }}</button>
        </div>
      </div>

      <!-- Order stages stepper -->
      <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Order progress</h2>
        <div v-if="isTerminalNegative" class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
          <XCircle class="h-5 w-5 shrink-0" />
          <span class="capitalize">Order {{ order?.status }}</span>
        </div>
        <div class="relative flex items-start justify-between">
          <!-- Progress line behind steps -->
          <div
            class="absolute top-6 h-0.5 bg-gray-200"
            style="left: 24px; right: 24px;"
          />
          <div
            class="absolute top-6 h-0.5 overflow-hidden bg-emerald-500 transition-all duration-500"
            style="left: 24px; right: 24px;"
          >
            <div
              class="h-full bg-emerald-500"
              :style="{ width: `${(currentStageIndex / (ORDER_STAGES.length - 1)) * 100}%` }"
            />
          </div>
          <template v-for="(stage, index) in ORDER_STAGES" :key="stage.key">
            <div class="relative z-10 flex flex-col items-center">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                :class="{
                  'border-emerald-500 bg-emerald-500 text-white': stageStatus(index) === 'completed',
                  'border-primary bg-primary text-white ring-4 ring-primary/20': stageStatus(index) === 'current',
                  'border-gray-200 bg-gray-50 text-gray-400': stageStatus(index) === 'upcoming',
                }"
              >
                <component
                  :is="stageStatus(index) === 'completed' ? CheckCircle : stage.icon"
                  class="h-6 w-6"
                />
              </div>
              <p
                class="mt-2 max-w-[72px] text-center text-xs font-medium sm:max-w-none sm:text-sm"
                :class="{
                  'text-emerald-700': stageStatus(index) === 'completed',
                  'text-primary font-semibold': stageStatus(index) === 'current',
                  'text-gray-400': stageStatus(index) === 'upcoming',
                }"
              >
                {{ stage.label }}
              </p>
            </div>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Summary -->
        <div class="rounded-lg bg-white p-4 shadow lg:col-span-2">
          <h2 class="mb-4 text-lg font-semibold text-gray-800">Items</h2>
          <div v-if="order?.order_items?.length" class="divide-y">
            <div v-for="(item, idx) in order.order_items" :key="idx" class="flex items-start justify-between py-3">
              <div>
                <div class="font-medium text-gray-800">{{ item.product?.name || item.product_variant?.product?.name || item.productVariant?.product?.name || 'Product' }}</div>
                <div class="text-xs text-gray-500">Qty: {{ item.quantity }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-700">{{ formatMoney(item.unit_price) }} <span class="text-xs text-gray-500">each</span></div>
                <div class="text-sm font-semibold text-gray-900">{{ formatMoney(item.total_price) }}</div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-500">No items found for this order.</div>
        </div>

        <!-- Meta -->
        <div class="space-y-4">
          <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">Status</h3>
            <div class="flex flex-wrap gap-2 text-xs">
              <span :class="['inline-block rounded-full px-2 py-0.5', statusBadgeClass(order?.status)]">Order: {{ order?.status }}</span>
              <span :class="['inline-block rounded-full px-2 py-0.5', statusBadgeClass(order?.payment_status)]">Payment: {{ order?.payment_status }}</span>
              <span :class="['inline-block rounded-full px-2 py-0.5', statusBadgeClass(order?.fulfillment_status)]">Fulfillment: {{ order?.fulfillment_status }}</span>
            </div>
          </div>

          <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">Totals</h3>
            <div class="space-y-1 text-sm text-gray-700">
              <div class="flex justify-between"><span>Subtotal</span><span>{{ formatMoney(order?.subtotal) }}</span></div>
              <div class="flex justify-between"><span>Shipping</span><span>{{ formatMoney(order?.shipping_amount) }}</span></div>
              <div class="flex justify-between"><span>Tax</span><span>{{ formatMoney(order?.tax_amount) }}</span></div>
              <div class="flex justify-between" v-if="order?.discount_amount && Number(order.discount_amount) > 0"><span>Total Discount</span><span>- {{ formatMoney(order?.discount_amount) }}</span></div>
              <hr class="my-1 border-gray-200" />
              <div class="flex justify-between font-semibold text-gray-900"><span>Total</span><span>{{ formatMoney(order?.total_amount) }}</span></div>
            </div>
          </div>

          <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">Shipping Address</h3>
            <div class="text-sm text-gray-700">
              <div v-if="order?.shipping_address">
                <div class="font-medium">{{ order.shipping_address.first_name }} {{ order.shipping_address.last_name }}</div>
                <div>{{ order.shipping_address.address_line_1 }}</div>
                <div v-if="order.shipping_address.address_line_2">{{ order.shipping_address.address_line_2 }}</div>
                <div>{{ order.shipping_address.city }}, {{ order.shipping_address.state || order.shipping_address.state_province }} {{ order.shipping_address.postal_code }}</div>
                <div>{{ order.shipping_address.country_name || order.shipping_address.country || order.shipping_address.country_code }}</div>
                <div v-if="order.shipping_address.phone">Phone: {{ order.shipping_address.phone }}</div>
              </div>
              <div v-else class="text-gray-500">No shipping address on file.</div>
            </div>
          </div>

          <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="mb-2 text-sm font-semibold text-gray-800">Billing Address</h3>
            <div class="text-sm text-gray-700">
              <div v-if="order?.billing_address">
                <div class="font-medium">{{ order.billing_address.first_name }} {{ order.billing_address.last_name }}</div>
                <div>{{ order.billing_address.address_line_1 }}</div>
                <div v-if="order.billing_address.address_line_2">{{ order.billing_address.address_line_2 }}</div>
                <div>{{ order.billing_address.city }}, {{ order.billing_address.state || order.billing_address.state_province }} {{ order.billing_address.postal_code }}</div>
                <div>{{ order.billing_address.country_name || order.billing_address.country || order.billing_address.country_code }}</div>
                <div v-if="order.billing_address.phone">Phone: {{ order.billing_address.phone }}</div>
              </div>
              <div v-else class="text-gray-500">No billing address on file.</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="order?.notes" class="mt-4 rounded bg-white p-4 shadow">
        <h3 class="mb-2 text-sm font-semibold text-gray-800">Notes</h3>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ order.notes }}</p>
      </div>
    </div>
  </AppLayout>
</template>
