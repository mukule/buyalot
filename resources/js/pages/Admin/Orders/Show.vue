<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
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
}

const props = defineProps<{
  order: OrderPayload;
  delivery_users: DeliveryUser[];
  warehouses: Warehouse[];
}>();

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
        <div class="rounded bg-white p-4 shadow">
          <h2 class="font-semibold">Shipping Address</h2>
          <p>{{ props.order.shipping_address?.address_line_1 }}</p>
          <p>{{ props.order.shipping_address?.city }}<span v-if="props.order.shipping_address?.country">, {{ props.order.shipping_address?.country }}</span></p>
        </div>
      </div>

      <!-- Delivery assignment -->
      <div class="mb-6 rounded bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold">Delivery</h2>
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
              <Button size="sm" :disabled="!canAssign() || assignForm.processing" @click="assignDelivery">
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

      <!-- Delivery note modal -->
      <Dialog v-model:open="showNoteModal">
        <DialogContent class="max-w-lg">
          <DialogHeader>
            <DialogTitle>Delivery note – Order #{{ props.order.order_code }}</DialogTitle>
          </DialogHeader>
          <pre class="whitespace-pre-wrap rounded bg-muted p-4 text-sm">{{ props.order.delivery_note_summary || 'No note generated.' }}</pre>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
