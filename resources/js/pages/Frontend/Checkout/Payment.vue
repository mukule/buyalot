<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

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
const props = page.props as any;
const cart = props.cart as {
  items: SummaryItem[];
  counts: { unique_items: number; total_qty: number };
  totals: Totals;
  applied_coupon?: { type: string; code: string; name: string; amount: number } | null;
  coupon_code?: string | null;
};
const defaultPhone = (props.default_phone as string) || '';

// Addresses
type Address = {
  id: number;
  type?: string | null;
  label?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  company?: string | null;
  address_line_1?: string | null;
  address_line_2?: string | null;
  city?: string | null;
  state?: string | null;
  postal_code?: string | null;
  country?: string | null;
  phone?: string | null;
  is_default?: boolean;
  coordinates?: { lat?: number; lng?: number; accuracy?: number } | null;
  delivery_instructions?: string | null;
};
const addresses = ref<Address[]>(Array.isArray(props.customer_addresses) ? props.customer_addresses : []);
const selectedAddressId = ref<number | null>((props.shipping_address_id as number | null) ?? null);
const showAddressForm = ref(false);
const newAddress = ref<Partial<Address>>({
  type: 'shipping',
  label: 'My address',
  first_name: (props?.auth?.user?.name as string || '').split(' ')[0] || '',
  last_name: (props?.auth?.user?.name as string || '').split(' ').slice(1).join(' ') || '',
  address_line_1: '',
  address_line_2: '',
  city: '',
  state: '',
  postal_code: '',
  country: 'KE',
  phone: defaultPhone,
  is_default: true,
  coordinates: null,
  delivery_instructions: '',
});

const formatPrice = (amount: number) => `KSh ${amount.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;

// Phone handling
const phone = ref<string>(defaultPhone);

// UI State
const initiating = ref(false);
const polling = ref(false);
const progress = ref(0);
const status = ref<'idle' | 'initiating' | 'polling' | 'success' | 'failed'>('idle');
const message = ref<string>('');
const insufficientItems = ref<Array<{ product_variant_id: number; requested: number; available: number; product_name: string }>>([]);

// Payment refs
const paymentId = ref<string | null>(null);
const paymentReference = ref<string | null>(null);
// Track created order so user can retry payment without recreating order
const currentOrder = ref<null | { id: number; ulid?: string; total_amount: number; currency?: string }>(null);

const canPay = computed(() => !!phone.value && phone.value.trim().length >= 9 && !!selectedAddressId.value && !initiating.value && !polling.value);

let pollTimer: any = null;


async function saveNewAddress() {
  try {
    initiating.value = true;
    const axios = (window as any).axios || (await import('axios')).default;
    const payload: any = { ...newAddress.value };
    // Ensure coordinates shape
    if (payload.coordinates && (payload.coordinates.lat || payload.coordinates.lng)) {
      payload.coordinates = { lat: payload.coordinates.lat, lng: payload.coordinates.lng, accuracy: payload.coordinates.accuracy };
    }
    const { data, status: httpStatus } = await axios.post('/me/addresses', payload, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      withCredentials: true,
    });
    if (httpStatus >= 200 && httpStatus < 300 && data?.address) {
      addresses.value.unshift(data.address);
      selectedAddressId.value = data.address.id;
      showAddressForm.value = false;
      status.value = 'idle';
      message.value = 'Address saved.';
    } else {
      status.value = 'failed';
      message.value = data?.message || 'Failed to save address';
    }
  } catch (e: any) {
    status.value = 'failed';
    message.value = e?.response?.data?.message || 'Failed to save address';
  } finally {
    initiating.value = false;
  }
}

async function makeDefaultAddress(id: number) {
  try {
    const axios = (window as any).axios || (await import('axios')).default;
    await axios.post(`/me/addresses/${id}/make-default`, {}, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      withCredentials: true,
    });
    selectedAddressId.value = id;
    addresses.value = addresses.value.map(a => ({ ...a, is_default: a.id === id }));
  } catch {}
}

async function reverseGeocode(lat: number, lng: number): Promise<Partial<Address>> {
  try {
    const endpoint = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&addressdetails=1`;
    const res = await fetch(endpoint, {
      headers: {
        'Accept': 'application/json',
        // Nominatim requires an identifying User-Agent
        'User-Agent': 'buyalot-checkout/1.0 (reverse-geocode)'
      }
    });
    if (!res.ok) throw new Error('Reverse geocoding failed');
    const data = await res.json();
    const a = data?.address || {};
    const road = a.road || a.pedestrian || a.path || a.footway || a.cycleway || a.residential || a.neighbourhood || a.suburb || a.hamlet;
    const house = a.house_number || '';
    const line1 = [house, road].filter(Boolean).join(' ').trim() || 'Pinned location';
    const city = a.city || a.town || a.village || a.suburb || a.county || '';
    const state = a.state || a.state_district || a.region || a.province || '';
    const postal = a.postcode || '';
    const countryCode = (a.country_code || 'KE').toUpperCase();
    return {
      address_line_1: line1,
      city,
      state,
      postal_code: postal,
      country: countryCode,
    };
  } catch {
    return {};
  }
}

function useCurrentLocation() {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }
  navigator.geolocation.getCurrentPosition(async (pos) => {
    const { latitude, longitude, accuracy } = pos.coords as any;
    // attach coordinates
    newAddress.value.coordinates = { lat: latitude, lng: longitude, accuracy };

    // fetch and autofill address fields
    const details = await reverseGeocode(latitude, longitude);
    if (details) {
      if (details.address_line_1) newAddress.value.address_line_1 = details.address_line_1 as string;
      if (details.city !== undefined) newAddress.value.city = (details.city as string) || newAddress.value.city || '';
      if (details.state !== undefined) newAddress.value.state = (details.state as string) || newAddress.value.state || '';
      if (details.postal_code !== undefined) newAddress.value.postal_code = (details.postal_code as string) || newAddress.value.postal_code || '';
      if (details.country !== undefined) newAddress.value.country = (details.country as string) || newAddress.value.country || 'KE';
    }
    if (!newAddress.value.label) newAddress.value.label = 'Current location';
    if (!newAddress.value.address_line_1) newAddress.value.address_line_1 = 'Pinned location';
  }, () => {
    alert('Unable to retrieve your location.');
  }, { enableHighAccuracy: true, timeout: 10000 });
}

onMounted(() => {
  status.value = 'idle';
  message.value = '';
});

async function initiateForOrder(order: { id: number; ulid?: string; total_amount: number; currency?: string }) {
  try {
    const axios = (window as any).axios || (await import('axios')).default;
    const initiatePayload: any = {
      payable_type: 'order',
      payable_id: order.id,
      amount: order.total_amount,
      currency: order.currency || 'KES',
      provider: 'mpesa',
      method: 'mobile_money',
      phone: phone.value.trim(),
      metadata: { order_ulid: order.ulid, customer_id: (page.props as any)?.auth?.customer_id || undefined },
    };

    const { data: data2, status: httpStatus2 } = await axios.post(route('payments.initiate'), initiatePayload, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      withCredentials: true,
    });

    if (httpStatus2 < 200 || httpStatus2 >= 300 || !data2) {
      const errMsg = (data2 && (data2.message || data2.error)) || 'Failed to initiate payment.';
      status.value = 'failed';
      message.value = errMsg;
      initiating.value = false;
      return;
    }

    const pmt = data2?.payment;
    if (!pmt || !pmt.id) {
      status.value = 'failed';
      message.value = 'Payment was not returned by server.';
      initiating.value = false;
      return;
    }

    paymentId.value = pmt.id;
    paymentReference.value = pmt.reference;

    status.value = 'polling';
    initiating.value = false;
    polling.value = true;
    message.value = 'Awaiting your M-Pesa approval. Check your phone and enter your PIN to complete the payment.';
    startProgressBar();
    await pollVerifyUntilComplete(pmt.id);
  } catch (e: any) {
    status.value = 'failed';
    initiating.value = false;
    polling.value = false;
    message.value = e.response.data.message || 'Unexpected error while initiating payment. Please try again.';
    console.error(e);
  }
}

async function startPayment() {
  if (!canPay.value) return;
  try {
    status.value = 'initiating';
    initiating.value = true;
    if (currentOrder.value) {
      message.value = 'Retrying payment for your existing order...';
      await initiateForOrder(currentOrder.value);
      return;
    }
    message.value = 'Creating your order and triggering STK Push... You will receive a prompt on your phone to authorize the payment.';

    // Build minimal payload for order creation; server will validate and compute prices.
    const items = cart.items.map((i: SummaryItem) => ({
      product_variant_id: i.variant.id,
      quantity: i.quantity,
      unit_price: i.unit_price,
    }));

    // Step 1: Create the order (do NOT auto-initiate payment here)
    const createOrderPayload: any = {
      customer_id: (page.props as any)?.auth?.customer_id || undefined,
      items,
      billing_address_id: (selectedAddressId.value as number | undefined) || (props?.billing_address_id as number | undefined) || undefined,
      shipping_address_id: (selectedAddressId.value as number | undefined) || (props?.shipping_address_id as number | undefined) || undefined,
      currency: 'KES',
      tax_amount: cart.totals.tax,
      shipping_amount: cart.totals.shipping,
      notes: 'Customer requested express delivery',
      coupon_code: cart.coupon_code || undefined,
    };

    const axios = (window as any).axios || (await import('axios')).default;
    let orderResp;
    try {
      orderResp = await axios.post(route('orders.store'), createOrderPayload, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        withCredentials: true,
      });
    } catch (e: any) {
      const resp = e?.response;
      if (resp?.status === 409 && resp?.data?.items?.length) {
        status.value = 'failed';
        initiating.value = false;
        insufficientItems.value = resp.data.items;
        message.value = resp.data.message || 'Some items are out of stock. Remove them and try again.';
        return;
      }
      status.value = 'failed';
      initiating.value = false;
      message.value = resp?.data?.message || 'Failed to create order.';
      return;
    }

    const data = orderResp.data;
    const httpStatus = orderResp.status;

    if (httpStatus < 200 || httpStatus >= 300 || !data) {
      const errMsg = (data && (data.message || data.error)) || 'Failed to create order.';
      status.value = 'failed';
      message.value = errMsg;
      initiating.value = false;
      return;
    }

    const order = data?.data;
    if (!order || !order.id) {
      status.value = 'failed';
      message.value = 'Order was not returned by server.';
      initiating.value = false;
      return;
    }

    // remember created order so we can retry payment later if needed
    currentOrder.value = { id: order.id, ulid: order.ulid, total_amount: order.total_amount, currency: order.currency };

    // Step 2: Initiate payment for the created order
    await initiateForOrder(currentOrder.value);
  } catch (e: any) {
    status.value = 'failed';
    initiating.value = false;
    polling.value = false;
    message.value = 'Unexpected error while starting payment. Please try again.';
    console.error(e);
  }
}

async function retryPayment() {
  if (!currentOrder.value) return;
  status.value = 'initiating';
  initiating.value = true;
  message.value = 'Retrying payment... please check your phone for a new prompt.';
  await initiateForOrder(currentOrder.value);
}

function startProgressBar() {
  progress.value = 10;
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(() => {
    if (progress.value < 95) {
      progress.value += Math.random() * 5; // smooth progress while we poll
    }
  }, 800);
}

async function pollVerifyUntilComplete(id: string) {
  const maxSeconds = 90; // timeout window
  const intervalMs = 4000;
  let elapsed = 0;

  return new Promise<void>((resolve) => {
    const iv = setInterval(async () => {
      try {
        elapsed += intervalMs / 1000;
        const axios = (window as any).axios || (await import('axios')).default;
        const { data, status: httpStatus } = await axios.get(route('payments.status', id), {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          withCredentials: true,
        });

        const verified = !!data?.verification?.success;
        if (httpStatus >= 200 && httpStatus < 300 && verified) {
          clearInterval(iv);
          if (pollTimer) clearInterval(pollTimer);
          progress.value = 100;
          polling.value = false;
          status.value = 'success';
          message.value = 'Payment successful! We are confirming your order.';
          resolve();
        } else if (httpStatus >= 200 && httpStatus < 300 && data?.verification && data.verification.success === false) {
          clearInterval(iv);
          if (pollTimer) clearInterval(pollTimer);
          polling.value = false;
          status.value = 'failed';
          message.value = data.verification.message || 'Payment failed or was declined.';
          resolve();
        } else if (elapsed >= maxSeconds) {
          clearInterval(iv);
          if (pollTimer) clearInterval(pollTimer);
          polling.value = false;
          status.value = 'failed';
          message.value = 'Payment verification timed out. If you approved on your phone, your order will update shortly.';
          resolve();
        }
      } catch {
        clearInterval(iv);
        if (pollTimer) clearInterval(pollTimer);
        polling.value = false;
        status.value = 'failed';
        message.value = 'Could not verify payment. Please try again.';
        resolve();
      }
    }, intervalMs);
  });
}

function goBack() {
  router.visit(route('checkout.summary'), { preserveScroll: true, preserveState: true });
}

async function removeItemFromCart(variantId: number) {
  try {
    initiating.value = true;
    await router.post(route('cart.store'), { product_variant_id: variantId, quantity: 0 }, {
      onSuccess: () => {
        // Refresh page props and cart summary
        router.visit(route('checkout.payment'), { preserveScroll: true, preserveState: false });
      },
      onError: () => {
        initiating.value = false;
      }
    });
  } catch {
    initiating.value = false;
  }
}
</script>

<template>
  <MainLayout>
    <section class="mx-auto p-4" style="max-width: 1000px">
      <div class="flex flex-col gap-4 lg:flex-row">
        <!-- LEFT: Payment form and messaging -->
        <div class="w-full lg:w-7/12">
          <div class="space-y-4 rounded-lg bg-white p-4 shadow">
            <h1 class="text-lg font-semibold text-gray-800">Payment</h1>

            <div class="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900" v-if="status === 'idle'">
              After you click Pay, you will receive an M-Pesa STK Push on your phone to authorize the payment.
            </div>

            <!-- Shipping Address -->
            <div>
              <h2 class="mb-2 text-sm font-medium text-gray-700">Shipping Address</h2>
              <div v-if="addresses.length > 0" class="space-y-2">
                <div class="flex items-center gap-2">
                  <select v-model.number="selectedAddressId" class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none">
                    <option v-for="addr in addresses" :key="addr.id" :value="addr.id">
                      {{ (addr.label || 'Address') + ' — ' + [addr.address_line_1, addr.city].filter(Boolean).join(', ') }}
                      {{ addr.is_default ? '(Default)' : '' }}
                    </option>
                  </select>
                  <button v-if="selectedAddressId" @click="makeDefaultAddress(selectedAddressId as number)" class="rounded border px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">Make default</button>
                  <button @click="showAddressForm = !showAddressForm" class="rounded bg-gray-800 px-3 py-2 text-xs text-white hover:bg-gray-900">{{ showAddressForm ? 'Close' : 'Add new' }}</button>
                </div>
                <div v-if="showAddressForm" class="rounded border p-3">
                  <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <input v-model="(newAddress as any).first_name" placeholder="First name" class="rounded border p-2" />
                    <input v-model="(newAddress as any).last_name" placeholder="Last name" class="rounded border p-2" />
                    <input v-model="(newAddress as any).phone" placeholder="Phone" class="rounded border p-2" />
                    <input v-model="(newAddress as any).label" placeholder="Label (e.g., Home)" class="rounded border p-2" />
                    <input v-model="(newAddress as any).address_line_1" placeholder="Address line 1" class="rounded border p-2 sm:col-span-2" />
                    <input v-model="(newAddress as any).address_line_2" placeholder="Address line 2 (optional)" class="rounded border p-2 sm:col-span-2" />
                    <input v-model="(newAddress as any).city" placeholder="City" class="rounded border p-2" />
                    <input v-model="(newAddress as any).state" placeholder="State/Region" class="rounded border p-2" />
                    <input v-model="(newAddress as any).postal_code" placeholder="Postal code" class="rounded border p-2" />
                    <textarea v-model="(newAddress as any).delivery_instructions" placeholder="Delivery instructions (optional)" class="rounded border p-2 sm:col-span-2"></textarea>
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <button @click="useCurrentLocation" class="rounded border px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">Use current location</button>
                    <button @click="saveNewAddress" class="rounded bg-primary px-3 py-2 text-xs text-white hover:bg-primary/90">Save Address</button>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">We will store your pinned coordinates with this address to help our rider deliver accurately.</p>
                </div>
              </div>
              <div v-else class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                You don’t have any saved addresses. Please add one to proceed with delivery.
                <div class="mt-2 rounded border p-3">
                  <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <input v-model="(newAddress as any).first_name" placeholder="First name" class="rounded border p-2" />
                    <input v-model="(newAddress as any).last_name" placeholder="Last name" class="rounded border p-2" />
                    <input v-model="(newAddress as any).phone" placeholder="Phone" class="rounded border p-2" />
                    <input v-model="(newAddress as any).label" placeholder="Label (e.g., Home)" class="rounded border p-2" />
                    <input v-model="(newAddress as any).address_line_1" placeholder="Address line 1" class="rounded border p-2 sm:col-span-2" />
                    <input v-model="(newAddress as any).address_line_2" placeholder="Address line 2 (optional)" class="rounded border p-2 sm:col-span-2" />
                    <input v-model="(newAddress as any).city" placeholder="City" class="rounded border p-2" />
                    <input v-model="(newAddress as any).state" placeholder="State/Region" class="rounded border p-2" />
                    <input v-model="(newAddress as any).postal_code" placeholder="Postal code" class="rounded border p-2" />
                    <textarea v-model="(newAddress as any).delivery_instructions" placeholder="Delivery instructions (optional)" class="rounded border p-2 sm:col-span-2"></textarea>
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <button @click="useCurrentLocation" class="rounded border px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">Use current location</button>
                    <button @click="saveNewAddress" class="rounded bg-primary px-3 py-2 text-xs text-white hover:bg-primary/90">Save and Select</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Phone input -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number (M-Pesa)</label>
              <input
                v-model="phone"
                type="tel"
                placeholder="e.g. 07xxxxxxxx or 2547xxxxxxxx"
                class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
              />
              <p class="mt-1 text-xs text-gray-500">We prefilled your phone number. You can change it before paying.</p>
            </div>

            <!-- Progress and status messages -->
            <div v-if="status === 'initiating' || status === 'polling'" class="space-y-2">
              <div class="h-2 w-full overflow-hidden rounded bg-gray-200">
                <div class="h-2 bg-primary transition-all" :style="{ width: `${Math.min(100, Math.round(progress))}%` }"></div>
              </div>
              <div class="text-sm text-gray-700">{{ message }}</div>
              <ul class="list-inside list-disc text-xs text-gray-600">
                <li>Ensure your phone is on and has network coverage.</li>
                <li>Check for the M-Pesa prompt and enter your PIN to complete.</li>
                <li>Do not close this page while we confirm your payment.</li>
              </ul>
            </div>

            <div v-if="status === 'failed'" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
              {{ message }}
              <div v-if="insufficientItems.length" class="mt-3 text-red-900">
                <div class="mb-2 text-sm font-semibold">Items causing the issue:</div>
                <ul class="mb-3 list-inside list-disc text-sm">
                  <li v-for="it in insufficientItems" :key="it.product_variant_id">
                    {{ it.product_name }} — requested: {{ it.requested }}, available: {{ it.available }}
                    <button @click="removeItemFromCart(it.product_variant_id)" class="ml-2 rounded bg-red-600 px-2 py-0.5 text-xs text-white hover:bg-red-700">Remove</button>
                  </li>
                </ul>
                <div class="flex gap-2">
                  <button
                    @click="(async () => { for (const it of insufficientItems) { await removeItemFromCart(it.product_variant_id); } })()"
                    class="rounded border px-3 py-1 text-xs text-red-700 hover:bg-red-100"
                  >Remove all out-of-stock</button>
                  <button v-if="currentOrder" @click="retryPayment" class="rounded bg-primary px-3 py-1 text-xs text-white hover:bg-primary/90">Retry Payment</button>
                  <a :href="route('orders.my-orders')" class="rounded border px-3 py-1 text-xs text-gray-700 hover:bg-gray-50">Pay from My Orders</a>
                </div>
              </div>
              <div v-else class="mt-3 flex gap-2">
                <button v-if="currentOrder" @click="retryPayment" class="rounded bg-primary px-3 py-1 text-xs text-white hover:bg-primary/90">Retry Payment</button>
                <a :href="route('orders.my-orders')" class="rounded border px-3 py-1 text-xs text-gray-700 hover:bg-gray-50">Pay from My Orders</a>
              </div>
            </div>
            <div v-if="status === 'success'" class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800">
              {{ message }}
            </div>

            <div class="flex items-center gap-2">
              <button @click="startPayment" :disabled="!canPay" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                Pay Now
              </button>
              <button @click="goBack" class="rounded border px-4 py-2 text-gray-700 hover:bg-gray-50">Back to Summary</button>
            </div>

            <p class="mt-2 text-xs text-gray-600">
              By proceeding, you are automatically accepting the
              <a :href="route('terms')" class="underline text-primary hover:text-primary/80">Terms &amp; Conditions</a>
            </p>
          </div>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="w-full lg:w-5/12">
          <div class="space-y-4 rounded-lg bg-white p-4 shadow">
            <h2 class="text-lg font-semibold text-gray-800">Order Summary</h2>
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
          </div>
        </div>
      </div>
    </section>
  </MainLayout>
</template>
