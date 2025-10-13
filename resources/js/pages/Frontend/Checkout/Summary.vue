<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface SummaryItem {
  id: number;
  quantity: number;
  unit_price: number;
  line_subtotal: number;
  line_discount: number;
  product: { id: number; name: string; primary_image_url?: string | null };
  variant: { id: number; sku?: string; regular_price?: number; selling_price?: number };
}

interface Totals {
  subtotal: number;
  per_item_discount: number;
  coupon_discount: number;
  discount_total: number;
  shipping: number;
  tax: number;
  grand_total: number;
}

const page = usePage();
const cart = (page.props as any).cart as {
  items: SummaryItem[];
  counts: { unique_items: number; total_qty: number };
  totals: Totals;
  applied_coupon?: { type: string; code: string; name: string; amount: number } | null;
  coupon_code?: string | null;
  coupon_error?: string | null;
};

const couponInput = ref(cart.coupon_code || '');
const isApplyDisabled = computed(() => !couponInput.value || couponInput.value.trim().length === 0);

const formatPrice = (amount: number) => `KSh ${amount.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;

const applyCoupon = () => {
  if (isApplyDisabled.value) return;
  router.visit(route('checkout.summary'), {
    method: 'get',
    data: { coupon_code: couponInput.value.trim() },
    preserveScroll: true,
    preserveState: true,
  });
};

const proceedToPayment = () => {
  router.visit(route('checkout.payment'), {
    method: 'get',
    data: { coupon_code: cart.coupon_code || '' },
    preserveScroll: true,
    preserveState: true,
  });
};
</script>

<template>
  <MainLayout>
    <section class="mx-auto p-4" style="max-width: 1200px">
      <div class="flex flex-col gap-4 lg:flex-row">
        <!-- LEFT: Items -->
        <div class="w-full lg:w-8/12">
          <div class="space-y-4 rounded-lg bg-white p-4 shadow">
            <h1 class="text-lg font-semibold text-gray-800">Order Summary ({{ cart.counts.total_qty }} items)</h1>

            <template v-for="(item, index) in cart.items" :key="item.id">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <img
                    :src="item.product.primary_image_url || '/fallback-image.png'"
                    alt="Product Image"
                    class="h-16 w-16 rounded object-cover"
                  />
                  <div class="flex flex-col gap-1">
                    <h2 class="text-sm font-medium text-gray-700">
                      {{ item.product.name }}
                    </h2>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <span>Qty: {{ item.quantity }}</span>
                      <span>·</span>
                      <span>Unit: {{ formatPrice(item.unit_price) }}</span>
                    </div>
                    <div v-if="item.line_discount > 0" class="text-xs text-green-700">
                      You save {{ formatPrice(item.line_discount) }} on this item
                    </div>
                  </div>
                </div>
                <div class="text-sm font-semibold text-gray-800">{{ formatPrice(item.line_subtotal) }}</div>
              </div>
              <hr v-if="index < cart.items.length - 1" class="border-gray-200" />
            </template>
          </div>
        </div>

        <!-- RIGHT: Summary -->
        <div class="w-full lg:w-4/12">
          <div class="space-y-4 rounded-lg bg-white p-4 shadow">
            <h2 class="text-lg font-semibold text-gray-800">Checkout Summary</h2>

            <div class="space-y-2 text-sm text-gray-700">
              <div class="flex justify-between"><span>Subtotal</span><span>{{ formatPrice(cart.totals.subtotal) }}</span></div>
              <div class="flex justify-between" v-if="cart.totals.per_item_discount > 0">
                <span>Item discounts</span><span>-{{ formatPrice(cart.totals.per_item_discount) }}</span>
              </div>
              <div class="flex justify-between" v-if="cart.totals.coupon_discount > 0">
                <span>Coupon ({{ cart.applied_coupon?.code }})</span><span>-{{ formatPrice(cart.totals.coupon_discount) }}</span>
              </div>
              <div class="flex justify-between"><span>Shipping</span><span>{{ formatPrice(cart.totals.shipping) }}</span></div>
              <div class="flex justify-between" v-if="cart.totals.tax > 0"><span>Tax</span><span>{{ formatPrice(cart.totals.tax) }}</span></div>
            </div>

            <hr class="border-gray-200" />

            <div class="flex justify-between text-base font-semibold text-gray-800">
              <span>Total</span>
              <span>{{ formatPrice(cart.totals.grand_total) }}</span>
            </div>

            <!-- Coupon input -->
            <div class="mt-4">
              <label class="mb-1 block text-sm font-medium text-gray-700">Have a coupon?</label>
              <div class="flex gap-2">
                <input
                  v-model="couponInput"
                  type="text"
                  placeholder="Enter coupon code"
                  class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
                />
                <button @click="applyCoupon" :disabled="isApplyDisabled" class="rounded px-4 py-2 text-white" :class="isApplyDisabled ? 'bg-gray-400 cursor-not-allowed' : 'bg-gray-800 hover:bg-gray-900'">Apply</button>
              </div>
              <p v-if="cart.applied_coupon" class="mt-2 text-xs text-green-700">
                Applied: {{ cart.applied_coupon.name }} ({{ cart.applied_coupon.code }})
              </p>
              <p v-if="cart.coupon_error" class="mt-2 text-xs text-red-600">
                {{ cart.coupon_error }}
              </p>
            </div>

            <button
              @click="proceedToPayment"
              class="mt-4 flex w-full items-center justify-center gap-2 rounded bg-primary px-4 py-2 text-white hover:bg-primary/90"
            >
              Proceed to Payment
            </button>
            <p class="mt-2 text-xs text-gray-600 text-center">
              By proceeding, you are automatically accepting the
              <a :href="route('terms')" class="underline text-primary hover:text-primary/80">Terms &amp; Conditions</a>
            </p>
          </div>
        </div>
      </div>
    </section>
  </MainLayout>
</template>
