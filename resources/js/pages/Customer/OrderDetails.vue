<script setup lang="ts">
import AppLayout from '@/layouts/CustomerAppSidebarLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { Package, CheckCircle, Cog, Truck, Home, XCircle } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const page = usePage();
const order = computed<any>(() => (page.props as any).order || {});
const googleMapsApiKey = computed(() => (page.props as any).googleMapsApiKey || '');
const declineReasons = computed(() => (page.props as any).declineReasons || []);
const dispatchCenters = computed<any[]>(() => (page.props as any).dispatch_centers || []);

const selectedItem = ref<any>(null);
// The dispatch center shown in the modal (before or after dispatch)
const activeDispatchCenter = ref<any>(null);
const selectedDispatchCenterId = ref<number | null>(null);
const showDispatchModal = ref(false);
const showDeclineModal = ref(false);
const reason = ref('');
const otherReason = ref('');

const mapContainer = ref<HTMLElement | null>(null);
let map: google.maps.Map | null = null;
let marker: google.maps.Marker | null = null;

function loadGoogleMapsScript(): Promise<void> {
  if (window.google?.maps) return Promise.resolve();
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey.value}`;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load Google Maps'));
    document.head.appendChild(script);
  });
}

function initMap(center: { lat: number; lng: number } | null, title: string) {
  if (!mapContainer.value || !center) return;
  if (map) {
    map.setCenter(center);
    if (marker) {
      marker.setPosition(center);
      marker.setTitle(title);
    }
    return;
  }
  map = new google.maps.Map(mapContainer.value, { center, zoom: 15 });
  marker = new google.maps.Marker({ position: center, map, title });
}

function getMapCoords(dc: any): { lat: number; lng: number } | null {
  if (!dc?.latitude || !dc?.longitude) return null;
  return { lat: Number(dc.latitude), lng: Number(dc.longitude) };
}

function openDispatchMap(dc: any) {
  if (!googleMapsApiKey.value || !dc) return;
  loadGoogleMapsScript().then(() => {
    setTimeout(() => {
      const coords = getMapCoords(dc);
      if (coords) initMap(coords, dc.name);
    }, 200);
  });
}

const dispatchItem = (itemId: number) => {
  selectedItem.value = order.value.order_items.find((i: any) => i.id === itemId) || null;
  map = null;
  marker = null;

  // If already dispatched, show the recorded dispatch center
  if (selectedItem.value?.dispatch_center?.latitude) {
    activeDispatchCenter.value = selectedItem.value.dispatch_center;
    selectedDispatchCenterId.value = selectedItem.value.dispatch_center.id;
  } else {
    // Show first available dispatch center so vendor knows where to bring the item
    const first = dispatchCenters.value[0] ?? null;
    activeDispatchCenter.value = first;
    selectedDispatchCenterId.value = first?.id ?? null;
  }

  showDispatchModal.value = true;

  if (activeDispatchCenter.value) {
    openDispatchMap(activeDispatchCenter.value);
  }
};

// When vendor picks a different dispatch center from the dropdown
function onDispatchCenterChange(id: number) {
  selectedDispatchCenterId.value = id;
  const dc = dispatchCenters.value.find((c: any) => c.id === id) ?? null;
  activeDispatchCenter.value = dc;
  if (dc) openDispatchMap(dc);
}

const confirmDispatch = () => {
  if (!selectedItem.value) return;
  router.post(
    route('admin.orders.items.dispatch', { order: order.value.ulid ?? order.value.id, item: selectedItem.value.id }),
    { dispatch_center_id: selectedDispatchCenterId.value },
    { onSuccess: () => { showDispatchModal.value = false; selectedItem.value = null; } }
  );
};

const declineDispatch = (itemId: number) => {
  selectedItem.value = order.value.order_items.find((i: any) => i.id === itemId) || null;
  reason.value = '';
  otherReason.value = '';
  showDeclineModal.value = true;
};

const confirmDecline = () => {
  if (!selectedItem.value || !reason.value) return;
  if (reason.value === 'other' && !otherReason.value) return;
  router.post(route('admin.orders.items.decline', { order: order.value.ulid ?? order.value.id, item: selectedItem.value.id }), {
    reason: reason.value,
    other_reason: otherReason.value
  }, {
    onSuccess: () => { showDeclineModal.value = false; selectedItem.value = null; reason.value = ''; otherReason.value = ''; }
  });
};

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
                <div v-if="item.dispatch_status" class="mt-1">
                  <span
                    class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                    :class="{
                      'bg-gray-100 text-gray-600': item.dispatch_status === 'pending',
                      'bg-blue-100 text-blue-600': item.dispatch_status === 'dispatched',
                      'bg-green-100 text-green-600': item.dispatch_status === 'received',
                      'bg-red-100 text-red-600': ['declined', 'rejected'].includes(item.dispatch_status)
                    }"
                  >
                    {{ item.dispatch_status }}
                  </span>
                  <div v-if="item.dispatch_decline_reason" class="mt-0.5 text-[10px] text-red-500">
                    Declined: {{ item.dispatch_decline_reason }}
                  </div>
                  <div v-if="item.rejection_reason" class="mt-0.5 text-[10px] text-red-500">
                    Rejected by Admin: {{ item.rejection_reason }}
                  </div>
                  <div v-if="item.dispatch_center" class="mt-0.5 text-[10px] text-gray-500">
                    To: {{ item.dispatch_center.name }}
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-700">{{ formatMoney(item.unit_price) }} <span class="text-xs text-gray-500">each</span></div>
                <div class="text-sm font-semibold text-gray-900">{{ formatMoney(item.total_price) }}</div>
                <div v-if="order.is_seller" class="mt-2 flex flex-col gap-1">
                  <Button
                    v-if="['pending', 'declined'].includes(item.dispatch_status)"
                    size="sm"
                    class="bg-blue-600 text-white hover:bg-blue-700"
                    @click="dispatchItem(item.id)"
                  >
                    Dispatch
                  </Button>
                  <Button
                    v-if="item.dispatch_status === 'pending'"
                    size="sm"
                    variant="destructive"
                    @click="declineDispatch(item.id)"
                  >
                    Decline
                  </Button>
                </div>
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

          <div v-if="order?.shipping_address && !order?.is_seller" class="rounded-lg bg-white p-4 shadow">
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

          <div v-if="order?.billing_address && !order?.is_seller" class="rounded-lg bg-white p-4 shadow">
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

      <!-- Dispatch Modal -->
      <Dialog v-model:open="showDispatchModal">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Dispatch Item</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <p>
              You are about to dispatch
              <strong>{{ selectedItem?.product?.name || selectedItem?.product_variant?.product?.name || 'Item' }}</strong>
              to the dispatch center. Please bring the item to the location shown below.
            </p>

            <!-- Dispatch center selector (when multiple centers available and item not yet dispatched) -->
            <div v-if="dispatchCenters.length > 1 && selectedItem?.dispatch_status !== 'dispatched'" class="space-y-1">
              <Label for="dispatch-center-select" class="text-sm font-medium">Select dispatch center</Label>
              <select
                id="dispatch-center-select"
                :value="selectedDispatchCenterId"
                @change="onDispatchCenterChange(Number(($event.target as HTMLSelectElement).value))"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option v-for="dc in dispatchCenters" :key="dc.id" :value="dc.id">
                  {{ dc.name }}{{ dc.address ? ' — ' + dc.address : '' }}
                </option>
              </select>
            </div>

            <!-- Dispatch center info card -->
            <div v-if="activeDispatchCenter" class="rounded-md border border-blue-200 bg-blue-50 p-3">
              <p class="font-semibold text-blue-900">{{ activeDispatchCenter.name }}</p>
              <p v-if="activeDispatchCenter.address" class="text-sm text-blue-700">{{ activeDispatchCenter.address }}</p>
              <p v-if="activeDispatchCenter.location" class="text-xs text-blue-600">{{ activeDispatchCenter.location }}</p>
            </div>
            <div v-else-if="dispatchCenters.length === 0" class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
              No dispatch centers are currently available. Contact the administrator.
            </div>

            <!-- Map showing dispatch center location -->
            <div
              v-if="activeDispatchCenter?.latitude"
              ref="mapContainer"
              class="h-[300px] w-full rounded border bg-muted"
            ></div>
            <p v-else-if="activeDispatchCenter && !activeDispatchCenter.latitude" class="text-xs text-muted-foreground">
              Map not available for this dispatch center.
            </p>

            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="showDispatchModal = false">Cancel</Button>
              <Button
                :disabled="!activeDispatchCenter"
                @click="confirmDispatch"
              >
                Confirm Dispatch
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Decline Dispatch Modal -->
      <Dialog v-model:open="showDeclineModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Decline Dispatch</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="customer-decline-reason">Reason for declining</Label>
              <select
                id="customer-decline-reason"
                v-model="reason"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="" disabled>Select a reason...</option>
                <option v-for="r in declineReasons" :key="r.id" :value="r.id">
                  {{ r.name }}
                </option>
              </select>
            </div>
            <div v-if="reason === 'other'" class="space-y-2">
              <Label for="customer-decline-other-reason">Please specify</Label>
              <textarea
                id="customer-decline-other-reason"
                v-model="otherReason"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                placeholder="Enter other reason..."
              ></textarea>
            </div>
            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="showDeclineModal = false">Cancel</Button>
              <Button variant="destructive" :disabled="!reason || (reason === 'other' && !otherReason)" @click="confirmDecline">Decline Dispatch</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
