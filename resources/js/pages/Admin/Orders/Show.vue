<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { CheckCircle, Clock, XCircle } from 'lucide-vue-next';
import { watch } from 'vue';

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
  dispatch_status: string;
  dispatch_center?: {
    id: number;
    name: string;
    latitude?: number | null;
    longitude?: number | null;
    address?: string | null;
  } | null;
  dispatch_decline_reason?: string | null;
  rejection_reason?: string | null;
}
interface DeliveryUser { id: number; name: string; email: string }
interface Warehouse { id: number; name: string; address?: string | null; location?: string | null }
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
  delivery_id?: number | null;
  delivery_type?: string | null;
  pickup_warehouse_id?: number | null;
  dispatching_warehouse_id?: number | null;
  pickup_warehouse?: { id: number; name: string; address?: string | null; location?: string | null } | null;
  /** Pickup point the customer selected at checkout (from shipping address) */
  customer_selected_pickup_warehouse?: { id: number; name: string; address?: string | null; location?: string | null } | null;
  delivery_assignment_status?: string | null;
  allocated_for_pickup_at?: string | null;
  picked_at?: string | null;
  payment_method?: string | null;
  delivery_note_summary?: string;
  assigned_rider?: { id: number; name: string; email: string } | null;
  is_seller?: boolean;
  all_items_received?: boolean;
}

interface DispatchCenter { id: number; name: string; address?: string | null; location?: string | null; latitude?: number | null; longitude?: number | null }

const props = defineProps<{
  order: OrderPayload;
  delivery_users: DeliveryUser[];
  warehouses: Warehouse[];
  googleMapsApiKey?: string;
  rejectionReasons: { id: string; name: string }[];
  declineReasons: { id: string; name: string }[];
  dispatch_centers?: DispatchCenter[];
}>();

const selectedItem = ref<OrderItemPayload | null>(null);
const activeDispatchCenter = ref<DispatchCenter | null>(null);
const selectedDispatchCenterId = ref<number | null>(null);
const showDispatchModal = ref(false);
const showDeclineModal = ref(false);
const showReceiveModal = ref(false);
const showRejectModal = ref(false);
const showConfirmAvailableModal = ref(false);

const reason = ref('');
const otherReason = ref('');


// Standalone map modal — visible at any time from the Actions column
const showMapModal = ref(false);
const mapModalCenter = ref<DispatchCenter | null>(null);
const mapModalContainer = ref<HTMLElement | null>(null);
let mapModal: google.maps.Map | null = null;
let markerModal: google.maps.Marker | null = null;

function loadGoogleMapsScript(): Promise<void> {
  if (window.google?.maps) return Promise.resolve();
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapsApiKey}`;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load Google Maps'));
    document.head.appendChild(script);
  });
}


function onDispatchCenterChange(id: number) {
  selectedDispatchCenterId.value = id;
  const dc = (props.dispatch_centers ?? []).find(c => c.id === id) ?? null;
  activeDispatchCenter.value = dc;
}

function mountModalMap(el: HTMLElement, center: { lat: number; lng: number }, title: string) {
  if (mapModal) {
    mapModal.setCenter(center);
    if (markerModal) { markerModal.setPosition(center); markerModal.setTitle(title); }
  } else {
    mapModal = new google.maps.Map(el, { center, zoom: 15 });
    markerModal = new google.maps.Marker({ position: center, map: mapModal, title });
  }
}

function waitForModalContainer(center: { lat: number; lng: number }, title: string, attempts = 0) {
  if (attempts > 20) return;
  const el = mapModalContainer.value;
  if (!el) { setTimeout(() => waitForModalContainer(center, title, attempts + 1), 100); return; }
  mountModalMap(el, center, title);
}

function geocodeAndMount(query: string, title: string) {
  const geocoder = new google.maps.Geocoder();
  geocoder.geocode({ address: query }, (results, status) => {
    if (status === 'OK' && results && results[0]) {
      const loc = results[0].geometry.location;
      waitForModalContainer({ lat: loc.lat(), lng: loc.lng() }, title);
    }
  });
}

function openMapModal(item: OrderItemPayload) {
  const dc: DispatchCenter | null = item.dispatch_center?.latitude
    ? (item.dispatch_center as DispatchCenter)
    : ((props.dispatch_centers ?? [])[0] ?? null);
  if (!dc) return;
  mapModalCenter.value = dc;
  mapModal = null;
  markerModal = null;
  showMapModal.value = true;
  if (!props.googleMapsApiKey) return;
  loadGoogleMapsScript().then(() => {
    if (dc.latitude && dc.longitude) {
      waitForModalContainer({ lat: Number(dc.latitude), lng: Number(dc.longitude) }, dc.name);
    } else {
      const query = [dc.name, dc.address, dc.location].filter(Boolean).join(', ');
      geocodeAndMount(query, dc.name);
    }
  });
}

const receiveItem = (itemId: number) => {
  selectedItem.value = props.order.order_items.find(i => i.id === itemId) || null;
  showReceiveModal.value = true;
};

const confirmReceive = () => {
  if (!selectedItem.value) return;
  router.post(route('admin.orders.items.receive', { order: props.order.ulid ?? props.order.id, item: selectedItem.value.id }), {}, {
    onSuccess: () => { showReceiveModal.value = false; selectedItem.value = null; }
  });
};

const rejectItem = (itemId: number) => {
  selectedItem.value = props.order.order_items.find(i => i.id === itemId) || null;
  reason.value = '';
  otherReason.value = '';
  showRejectModal.value = true;
};

const confirmReject = () => {
  if (!selectedItem.value || !reason.value) return;
  if (reason.value === 'other' && !otherReason.value) return;
  router.post(route('admin.orders.items.reject', { order: props.order.ulid ?? props.order.id, item: selectedItem.value.id }), {
    reason: reason.value,
    other_reason: otherReason.value
  }, {
    onSuccess: () => { showRejectModal.value = false; selectedItem.value = null; reason.value = ''; otherReason.value = ''; }
  });
};

const confirmAvailable = (itemId: number) => {
  selectedItem.value = props.order.order_items.find(i => i.id === itemId) || null;
  showConfirmAvailableModal.value = true;
};

const executeConfirmAvailable = () => {
  if (!selectedItem.value) return;
  router.post(route('admin.orders.items.confirm-available', { order: props.order.ulid ?? props.order.id, item: selectedItem.value.id }), {}, {
    onSuccess: () => { showConfirmAvailableModal.value = false; selectedItem.value = null; }
  });
};

const dispatchItem = (itemId: number) => {
  selectedItem.value = props.order.order_items.find(i => i.id === itemId) || null;

  if (selectedItem.value?.dispatch_center?.latitude) {
    activeDispatchCenter.value = selectedItem.value.dispatch_center as DispatchCenter;
    selectedDispatchCenterId.value = selectedItem.value.dispatch_center.id;
  } else {
    const first = (props.dispatch_centers ?? [])[0] ?? null;
    activeDispatchCenter.value = first;
    selectedDispatchCenterId.value = first?.id ?? null;
  }

  showDispatchModal.value = true;
};

const confirmDispatch = () => {
  if (!selectedItem.value) return;
  router.post(
    route('admin.orders.items.dispatch', { order: props.order.ulid ?? props.order.id, item: selectedItem.value.id }),
    { dispatch_center_id: selectedDispatchCenterId.value },
    { onSuccess: () => { showDispatchModal.value = false; selectedItem.value = null; } }
  );
};

const declineDispatch = (itemId: number) => {
  selectedItem.value = props.order.order_items.find(i => i.id === itemId) || null;
  reason.value = '';
  otherReason.value = '';
  showDeclineModal.value = true;
};

const confirmDecline = () => {
  if (!selectedItem.value || !reason.value) return;
  if (reason.value === 'other' && !otherReason.value) return;
  router.post(route('admin.orders.items.decline', { order: props.order.ulid ?? props.order.id, item: selectedItem.value.id }), {
    reason: reason.value,
    other_reason: otherReason.value
  }, {
    onSuccess: () => { showDeclineModal.value = false; selectedItem.value = null; reason.value = ''; otherReason.value = ''; }
  });
};

function money(val: number, currency: string) {
  const num = Number(val ?? 0);
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(num);
  } catch {
    return `${currency} ${num.toFixed(2)}`;
  }
}

const assignForm = useForm({
  delivery_id: '',
  delivery_type: 'customer_address',
  pickup_warehouse_id: '',
  dispatching_warehouse_id: '',
});
const changeForm = useForm({
  delivery_id: '',
  delivery_type: 'customer_address',
  pickup_warehouse_id: '',
  dispatching_warehouse_id: '',
});
const showNoteModal = ref(false);

function assignDelivery() {
  if (!assignForm.delivery_id) return;
  if (assignForm.delivery_type === 'pickup_point' && !assignForm.pickup_warehouse_id) return;
  assignForm.post(route('admin.orders.assign-delivery', props.order.ulid ?? props.order.id), {
    onSuccess: () => assignForm.reset(),
  });
}

function changeDelivery() {
  if (!changeForm.delivery_id) return;
  if (changeForm.delivery_type === 'pickup_point' && !changeForm.pickup_warehouse_id) return;
  changeForm.post(route('admin.orders.change-delivery', props.order.ulid ?? props.order.id), {
    onSuccess: () => changeForm.reset(),
  });
}

const canAssign = () => {
  if (!props.order.all_items_received) return false;
  if (!assignForm.delivery_id) return false;
  if (assignForm.delivery_type === 'pickup_point') return !!assignForm.pickup_warehouse_id;
  return true;
};
const canChange = () => {
  if (!changeForm.delivery_id) return false;
  if (changeForm.delivery_type === 'pickup_point') return !!changeForm.pickup_warehouse_id;
  return true;
};

// When order has delivery, prefill change form with current type/warehouse (or customer-selected pickup)
watch(() => props.order.delivery_type, (t) => { if (t) changeForm.delivery_type = t; }, { immediate: true });
watch(() => props.order.pickup_warehouse_id, (id) => { if (id) changeForm.pickup_warehouse_id = String(id); }, { immediate: true });
watch(() => props.order.dispatching_warehouse_id, (id) => { changeForm.dispatching_warehouse_id = id ? String(id) : ''; }, { immediate: true });
watch(
  () => props.order.customer_selected_pickup_warehouse,
  (cw) => {
    if (cw) {
      changeForm.delivery_type = 'pickup_point';
      changeForm.pickup_warehouse_id = String(cw.id);
    }
  },
  { immediate: true },
);

// When no delivery assigned yet but customer selected a pickup at checkout, prefill assign form
watch(
  () => props.order.customer_selected_pickup_warehouse,
  (cw) => {
    if (cw && !props.order.assigned_rider) {
      assignForm.delivery_type = 'pickup_point';
      assignForm.pickup_warehouse_id = String(cw.id);
    }
  },
  { immediate: true },
);

function viewDeliveryNote() {
  showNoteModal.value = true;
}

const allocateForm = useForm({});
function allocateForPickup() {
  allocateForm.post(route('admin.orders.allocate-for-pickup', props.order.ulid ?? props.order.id));
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
        <div v-if="props.order.shipping_address" class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Shipping Address</h2>
          <p>{{ props.order.shipping_address.address_line_1 }}</p>
          <p>{{ props.order.shipping_address.city }}<span v-if="props.order.shipping_address.country">, {{ props.order.shipping_address.country }}</span></p>
        </div>
      </div>

      <!-- Delivery assignment (hidden when order is already delivered or for sellers) -->
      <div v-if="(props.order.status || '').toLowerCase() !== 'delivered' && !props.order.is_seller" class="mb-6 rounded bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold">Delivery</h2>
        <div v-if="!props.order.all_items_received" class="mb-4 rounded-md bg-amber-50 p-4 text-amber-800">
          <p class="flex items-center text-sm font-medium">
            <Clock class="mr-2 h-4 w-4" />
            Cannot assign delivery until all items are confirmed received from sellers.
          </p>
        </div>
<!--        <p v-if="props.order.customer_selected_pickup_warehouse" class="mb-3 rounded-md border border-primary/30 bg-primary/5 px-3 py-2 text-sm font-medium text-primary">-->
<!--          Customer selected pickup point: <strong>{{ props.order.customer_selected_pickup_warehouse.name }}</strong>-->
<!--          <span v-if="props.order.customer_selected_pickup_warehouse.address" class="block mt-1 font-normal text-muted-foreground">{{ props.order.customer_selected_pickup_warehouse.address }}</span>-->
<!--        </p>-->
        <div v-if="props.order.assigned_rider" class="space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm">
              Assigned to: <strong>{{ props.order.assigned_rider.name }}</strong>
              <span class="text-muted-foreground"> ({{ props.order.assigned_rider.email }})</span>
            </p>
            <span
              v-if="props.order.delivery_assignment_status"
              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold uppercase"
              :class="{
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': props.order.delivery_assignment_status === 'accepted',
                'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400': props.order.delivery_assignment_status === 'pending',
                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': props.order.delivery_assignment_status === 'rejected',
              }"
            >
              <CheckCircle v-if="props.order.delivery_assignment_status === 'accepted'" class="h-4 w-4 shrink-0" />
              <Clock v-else-if="props.order.delivery_assignment_status === 'pending'" class="h-4 w-4 shrink-0" />
              <XCircle v-else-if="props.order.delivery_assignment_status === 'rejected'" class="h-4 w-4 shrink-0" />
              {{ props.order.delivery_assignment_status }}
            </span>
            <p v-if="props.order.delivery_type || props.order.pickup_warehouse || props.order.customer_selected_pickup_warehouse" class="mt-1 text-sm text-muted-foreground">
              <template v-if="props.order.delivery_type === 'pickup_point' && (props.order.pickup_warehouse || props.order.customer_selected_pickup_warehouse)">
                Pick up point: {{ (props.order.pickup_warehouse || props.order.customer_selected_pickup_warehouse)?.name }}
              </template>
              <template v-else>
                Delivery to customer address
              </template>
            </p>
          </div>
          <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
              <Label for="change-delivery" class="sr-only">Change delivery person</Label>
              <select
                id="change-delivery"
                v-model="changeForm.delivery_id"
                class="w-[220px] rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="">Select new delivery person</option>
                <option
                  v-for="u in props.delivery_users.filter((u) => u.id !== props.order.delivery_id)"
                  :key="u.id"
                  :value="String(u.id)"
                >
                  {{ u.name }} ({{ u.email }})
                </option>
              </select>
              <Button size="sm" :disabled="!canChange() || changeForm.processing" @click="changeDelivery">
                Change delivery person
              </Button>
              <Button size="sm" variant="outline" @click="viewDeliveryNote">View delivery note</Button>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-sm">
              <span class="font-medium">Delivery type:</span>
              <template v-if="props.order.customer_selected_pickup_warehouse">
                <span class="text-muted-foreground">Pick up point (customer selected): <strong>{{ props.order.customer_selected_pickup_warehouse.name }}</strong></span>
              </template>
              <template v-else>
                <label class="flex items-center gap-2">
                  <input v-model="changeForm.delivery_type" type="radio" value="customer_address" class="rounded border-input" />
                  Customer address
                </label>
                <label class="flex items-center gap-2">
                  <input v-model="changeForm.delivery_type" type="radio" value="pickup_point" class="rounded border-input" />
                  Pick up point
                </label>
              </template>
              <template v-if="changeForm.delivery_type === 'pickup_point' && !props.order.customer_selected_pickup_warehouse">
                <Label for="change-warehouse" class="sr-only">Pick up point warehouse</Label>
                <select
                  id="change-warehouse"
                  v-model="changeForm.pickup_warehouse_id"
                  class="min-w-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="">Select warehouse</option>
                  <option v-for="w in props.warehouses" :key="w.id" :value="String(w.id)">
                    {{ w.name }}{{ w.address ? ` — ${w.address}` : '' }}
                  </option>
                </select>
                <Label for="change-dispatch" class="sr-only">Dispatching warehouse (for returns)</Label>
                <select
                  id="change-dispatch"
                  v-model="changeForm.dispatching_warehouse_id"
                  class="min-w-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="">Dispatching warehouse (optional)</option>
                  <option v-for="w in props.warehouses" :key="w.id" :value="String(w.id)">
                    {{ w.name }}
                  </option>
                </select>
              </template>
            </div>
          </div>
          <!-- Allocate for pickup (when delivery accepted and order processing/confirmed) -->
          <div v-if="props.order.delivery_assignment_status === 'accepted' && (props.order.status === 'processing' || props.order.status === 'confirmed')" class="mt-3 border-t pt-3">
            <div v-if="props.order.allocated_for_pickup_at" class="text-right">
              <p class="inline-block rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                Allocated for delivery pickup at {{ props.order.allocated_for_pickup_at }}
                <span v-if="props.order.picked_at" class="block mt-1 text-emerald-700 dark:text-emerald-300">Picked at {{ props.order.picked_at }}</span>
              </p>
            </div>
            <Button
              v-else
              size="sm"
              variant="secondary"
              :disabled="allocateForm.processing"
              @click="allocateForPickup"
            >
              Allocate for  delivery pickup
            </Button>
          </div>
        </div>
        <div v-else class="space-y-3">
          <p class="text-sm text-muted-foreground">No delivery person assigned.</p>
          <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
              <Label for="assign-delivery" class="sr-only">Assign delivery person</Label>
              <select
                id="assign-delivery"
                v-model="assignForm.delivery_id"
                class="w-[220px] rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="">Select delivery person</option>
                <option v-for="u in props.delivery_users" :key="u.id" :value="String(u.id)">
                  {{ u.name }} ({{ u.email }})
                </option>
              </select>
              <Button class="text-white" size="sm" :disabled="!canAssign() || assignForm.processing" @click="assignDelivery">
                Assign & send email
              </Button>
              <Button size="sm" variant="outline" @click="viewDeliveryNote">View delivery note</Button>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-sm">
              <span class="font-medium">Delivery type:</span>
              <template v-if="props.order.customer_selected_pickup_warehouse">
                <span class="text-muted-foreground">Pick up point (customer selected): <strong>{{ props.order.customer_selected_pickup_warehouse.name }}</strong></span>
              </template>
              <template v-else>
                <label class="flex items-center gap-2">
                  <input v-model="assignForm.delivery_type" type="radio" value="customer_address" class="rounded border-input" />
                  Customer address
                </label>
                <label class="flex items-center gap-2">
                  <input v-model="assignForm.delivery_type" type="radio" value="pickup_point" class="rounded border-input" />
                  Pick up point
                </label>
              </template>
              <template v-if="assignForm.delivery_type === 'pickup_point' && !props.order.customer_selected_pickup_warehouse">
                <Label for="assign-warehouse" class="sr-only">Pick up point warehouse</Label>
                <select
                  id="assign-warehouse"
                  v-model="assignForm.pickup_warehouse_id"
                  class="min-w-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="">Select warehouse</option>
                  <option v-for="w in props.warehouses" :key="w.id" :value="String(w.id)">
                    {{ w.name }}{{ w.address ? ` — ${w.address}` : '' }}
                  </option>
                </select>
                <Label for="assign-dispatch" class="sr-only">Dispatching warehouse (for returns)</Label>
                <select
                  id="assign-dispatch"
                  v-model="assignForm.dispatching_warehouse_id"
                  class="min-w-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="">Dispatching warehouse (optional)</option>
                  <option v-for="w in props.warehouses" :key="w.id" :value="String(w.id)">
                    {{ w.name }}
                  </option>
                </select>
              </template>
            </div>
          </div>
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
                <th v-if="!props.order.is_seller" class="px-4 py-2">Seller</th>
                <th class="px-4 py-2">Dispatch Status</th>
                <th class="px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="item in props.order.order_items" :key="item.id">
                <td class="px-4 py-2">{{ item.product?.name ?? '—' }}</td>
                <td class="px-4 py-2">{{ item.variant?.label ?? item.variant?.sku ?? '—' }}</td>
                <td class="px-4 py-2">{{ item.quantity }}</td>
                <td class="px-4 py-2">{{ money(item.unit_price, props.order.currency) }}</td>
                <td class="px-4 py-2">{{ money(item.total_price, props.order.currency) }}</td>
                <td v-if="!props.order.is_seller" class="px-4 py-2">{{ item.seller?.name ?? '—' }}</td>
                <td class="px-4 py-2">
                  <span
                    class="rounded-full px-2 py-1 text-xs font-semibold"
                    :class="{
                      'bg-gray-100 text-gray-800': item.dispatch_status === 'pending',
                      'bg-blue-100 text-blue-800': item.dispatch_status === 'dispatched',
                      'bg-green-100 text-green-800': item.dispatch_status === 'received',
                      'bg-red-100 text-red-800': ['declined', 'rejected'].includes(item.dispatch_status)
                    }"
                  >
                    {{ item.dispatch_status }}
                  </span>
                  <div v-if="item.dispatch_decline_reason" class="mt-1 text-xs text-red-500">
                    Reason: {{ item.dispatch_decline_reason }}
                  </div>
                  <div v-if="item.rejection_reason" class="mt-1 text-xs text-red-500">
                    Rejected: {{ item.rejection_reason }}
                  </div>
                </td>
                <td class="px-4 py-2">
                  <div class="flex flex-wrap gap-2">
                    <!-- Seller Actions -->
                    <template v-if="props.order.is_seller">
                      <Button
                        v-if="['pending', 'declined'].includes(item.dispatch_status)"
                        size="sm"
                        variant="default"
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
                      <Button
                        v-if="!['declined', 'rejected'].includes(item.dispatch_status) && ((item.dispatch_center?.latitude) || (props.dispatch_centers ?? []).length > 0)"
                        size="sm"
                        variant="outline"
                        class="border-green-500 text-green-700 hover:bg-green-50"
                        @click="openMapModal(item)"
                      >
                        📍 View on Map
                      </Button>
                    </template>

                    <!-- Admin Actions -->
                    <template v-else>
                      <Button
                        v-if="(!item.seller || item.seller.id === 0) && item.dispatch_status === 'pending'"
                        size="sm"
                        variant="default"
                        class="bg-indigo-600 text-white hover:bg-indigo-700"
                        @click="confirmAvailable(item.id)"
                      >
                        Confirm Available
                      </Button>
                      <Button
                        v-if="['dispatched', 'rejected'].includes(item.dispatch_status)"
                        size="sm"
                        variant="default"
                        class="bg-green-600 text-white hover:bg-green-700"
                        @click="receiveItem(item.id)"
                      >
                        Confirm Receipt
                      </Button>
                      <Button
                        v-if="item.dispatch_status === 'dispatched'"
                        size="sm"
                        variant="destructive"
                        @click="rejectItem(item.id)"
                      >
                        Reject
                      </Button>
                    </template>
                  </div>
                </td>
              </tr>
              <tr v-if="props.order.order_items.length === 0">
                <td :colspan="props.order.is_seller ? 7 : 8" class="px-4 py-6 text-center text-gray-500">No items</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Delivery note modal -->
      <Dialog v-model:open="showNoteModal">
        <DialogContent class="max-w-lg">
          <DialogHeader>
            <DialogTitle>Delivery note – Order #{{ props.order.order_code }}</DialogTitle>
            <DialogDescription>Summary of the delivery note for this order.</DialogDescription>
          </DialogHeader>
          <pre class="whitespace-pre-wrap rounded bg-muted p-4 text-sm">{{ props.order.delivery_note_summary || 'No note generated.' }}</pre>
        </DialogContent>
      </Dialog>

      <!-- Dispatch Modal -->
      <Dialog v-model:open="showDispatchModal">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Dispatch Item</DialogTitle>
            <DialogDescription>Bring this item to the dispatch center shown below, then confirm.</DialogDescription>
          </DialogHeader>
          <div class="space-y-4">
            <p>
              Dispatching <strong>{{ selectedItem?.product?.name ?? '—' }}</strong>.
              Please physically bring the item to the dispatch center location.
            </p>

            <!-- Center selector when multiple are available and item not yet dispatched -->
            <div v-if="(dispatch_centers ?? []).length > 1 && selectedItem?.dispatch_status !== 'dispatched'" class="space-y-1">
              <Label for="admin-dispatch-center-select" class="text-sm font-medium">Select dispatch center</Label>
              <select
                id="admin-dispatch-center-select"
                :value="selectedDispatchCenterId"
                @change="onDispatchCenterChange(Number(($event.target as HTMLSelectElement).value))"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option v-for="dc in dispatch_centers" :key="dc.id" :value="dc.id">
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
            <div v-else-if="!(dispatch_centers ?? []).length" class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
              No dispatch centers are currently available. Contact the administrator.
            </div>

            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="showDispatchModal = false">Cancel</Button>
              <Button :disabled="!activeDispatchCenter" @click="confirmDispatch">Confirm Dispatch</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Decline Dispatch Modal -->
      <Dialog v-model:open="showDeclineModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Decline Dispatch</DialogTitle>
            <DialogDescription>Please provide a reason for declining this dispatch.</DialogDescription>
          </DialogHeader>
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="decline-reason">Reason for declining</Label>
              <select
                id="decline-reason"
                v-model="reason"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="" disabled>Select a reason...</option>
                <option v-for="r in props.declineReasons" :key="r.id" :value="r.id">
                  {{ r.name }}
                </option>
              </select>
            </div>
            <div v-if="reason === 'other'" class="space-y-2">
              <Label for="decline-other-reason">Please specify</Label>
              <textarea
                id="decline-other-reason"
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

      <!-- Receive Item Modal -->
      <Dialog v-model:open="showReceiveModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirm Receipt</DialogTitle>
            <DialogDescription>Verify that the item has arrived at the dispatch center.</DialogDescription>
          </DialogHeader>
          <div class="space-y-4">
            <p>Confirm that you have received <strong>{{ selectedItem?.product?.name }}</strong> from the seller?</p>
            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="showReceiveModal = false">Cancel</Button>
              <Button @click="confirmReceive">Confirm Receipt</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Reject Item Modal -->
      <Dialog v-model:open="showRejectModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reject Item</DialogTitle>
            <DialogDescription>Explain why this item is being rejected.</DialogDescription>
          </DialogHeader>
          <div class="space-y-4">
            <div class="space-y-2">
              <Label for="reject-reason">Reason for rejection</Label>
              <select
                id="reject-reason"
                v-model="reason"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="" disabled>Select a reason...</option>
                <option v-for="r in props.rejectionReasons" :key="r.id" :value="r.id">
                  {{ r.name }}
                </option>
              </select>
            </div>
            <div v-if="reason === 'other'" class="space-y-2">
              <Label for="reject-other-reason">Please specify</Label>
              <textarea
                id="reject-other-reason"
                v-model="otherReason"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                placeholder="Enter other reason..."
              ></textarea>
            </div>
            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="showRejectModal = false">Cancel</Button>
              <Button variant="destructive" :disabled="!reason || (reason === 'other' && !otherReason)" @click="confirmReject">Reject Item</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Confirm Available Modal (System Items) -->
      <Dialog v-model:open="showConfirmAvailableModal">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirm Item Available</DialogTitle>
            <DialogDescription>Mark this platform item as available for delivery.</DialogDescription>
          </DialogHeader>
          <div class="space-y-4">
            <p>Are you sure this item <strong>{{ selectedItem?.product?.name }}</strong> is available at the dispatch center?</p>
            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="showConfirmAvailableModal = false">Cancel</Button>
              <Button @click="executeConfirmAvailable">Confirm Available</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Standalone dispatch center map modal -->
      <Dialog v-model:open="showMapModal">
        <DialogContent class="max-w-4xl w-full">
          <DialogHeader>
            <DialogTitle>Dispatch Center Location</DialogTitle>
            <DialogDescription>Location where you should bring the item.</DialogDescription>
          </DialogHeader>
          <div class="space-y-3">
            <div v-if="mapModalCenter" class="rounded-md border border-green-200 bg-green-50 p-3">
              <p class="font-semibold text-green-900">{{ mapModalCenter.name }}</p>
              <p v-if="mapModalCenter.address" class="text-sm text-green-700">{{ mapModalCenter.address }}</p>
              <p v-if="mapModalCenter.location" class="text-xs text-green-600">{{ mapModalCenter.location }}</p>
            </div>
            <!-- Map container always present so geocoder can render into it even without stored coords -->
            <div
              ref="mapModalContainer"
              class="h-[480px] w-full rounded border bg-muted"
            ></div>
            <div class="flex justify-end">
              <Button variant="outline" @click="showMapModal = false">Close</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
