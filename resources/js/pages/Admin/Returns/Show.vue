<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
  return: {
    id: number;
    order_id: number;
    order_code: string;
    order_ulid?: string | null;
    order_status?: string | null;
    status: string;
    reason: string;
    reason_notes?: string | null;
    is_full_return: boolean;
    raised_by_type: string;
    created_at: string | null;
    received_at_dispatch_at?: string | null;
    items: { id: number; product_name: string; variant_display?: string | null; quantity_returned: number }[];
    dispatching_warehouse: { id: number; hashid: string; name: string } | null;
    can_receive: boolean;
    pending_receivables_count: number;
  };
}>();

const receiving = ref(false);

function receiveReturn() {
  if (!props.return.can_receive || receiving.value) return;
  receiving.value = true;
  router.post(route('admin.returns.receive', props.return.id), {}, {
    preserveState: true,
    onFinish: () => { receiving.value = false; },
    onSuccess: () => {
      router.reload();
    },
  });
}
</script>

<template>
  <Head :title="`Return #${props.return.id} — Order ${props.return.order_code}`" />
  <AppLayout :breadcrumbs="[
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Returns', href: route('admin.returns.index') },
    { title: `Return #${props.return.id}`, href: '#' },
  ]">
    <div class="p-4">
      <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Return #{{ props.return.id }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            Order {{ props.return.order_code }}
            <Link
              v-if="props.return.order_ulid"
              :href="route('admin.orders.show', props.return.order_ulid)"
              class="ml-2 text-primary hover:underline"
            >
              View order
            </Link>
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.returns.index')"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Back to Returns
          </Link>
          <button
            v-if="props.return.can_receive"
            @click="receiveReturn"
            :disabled="receiving"
            class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50"
          >
            <span v-if="!receiving">Receive Return</span>
            <span v-else>Receiving...</span>
          </button>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
          <h2 class="text-sm font-medium text-gray-700">Return Details</h2>
        </div>
        <dl class="divide-y divide-gray-200 px-4">
          <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Status</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 capitalize">{{ (props.return.status || '—').replace(/_/g, ' ') }}</dd>
          </div>
          <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Reason</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ props.return.reason || '—' }}</dd>
          </div>
          <div v-if="props.return.reason_notes" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Notes</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ props.return.reason_notes }}</dd>
          </div>
          <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Type</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ props.return.is_full_return ? 'Full return' : 'Partial return' }}</dd>
          </div>
          <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Raised by</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 capitalize">{{ (props.return.raised_by_type || '—').replace(/_/g, ' ') }}</dd>
          </div>
          <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Dispatching warehouse</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">
              <template v-if="props.return.dispatching_warehouse">
                <Link
                  :href="route('admin.inventory', props.return.dispatching_warehouse.hashid)"
                  class="text-primary hover:underline"
                >
                  {{ props.return.dispatching_warehouse.name }}
                </Link>
              </template>
              <span v-else>—</span>
            </dd>
          </div>
          <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Created</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ props.return.created_at || '—' }}</dd>
          </div>
          <div v-if="props.return.received_at_dispatch_at" class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-gray-500">Received at dispatch</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2">{{ props.return.received_at_dispatch_at }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
          <h2 class="text-sm font-medium text-gray-700">Returned Items</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Variant</th>
                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Quantity</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="item in props.return.items" :key="item.id">
                <td class="px-4 py-2">{{ item.product_name || '—' }}</td>
                <td class="px-4 py-2">{{ item.variant_display || '—' }}</td>
                <td class="px-4 py-2">{{ item.quantity_returned }}</td>
              </tr>
              <tr v-if="!props.return.items?.length">
                <td colspan="3" class="px-4 py-6 text-center text-gray-500">No items.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <p v-if="props.return.can_receive" class="mt-4 text-sm text-gray-600">
        {{ props.return.pending_receivables_count }} item(s) are pending receipt at the dispatching warehouse.
        Click <strong>Receive Return</strong> to add them to inventory and mark this return as received.
      </p>
    </div>
  </AppLayout>
</template>
