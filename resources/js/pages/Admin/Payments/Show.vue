<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

type Nullable<T> = T | null | undefined;

interface PayableSummary {
  id?: number | string;
  ulid?: string;
  type?: string;
  title?: string;
}

interface Transaction {
  id: number | string;
  type?: string;
  amount?: string | number;
  status?: string;
  reference?: string | null;
  created_at?: string;
}

interface Payment {
  id: number;
  ulid: string;
  amount: string | number;
  currency: string;
  provider: string;
  method: string;
  status: string;
  reference: Nullable<string>;
  provider_reference?: Nullable<string>;
  failure_reason?: Nullable<string>;
  metadata?: Record<string, any> | null;
  expires_at?: Nullable<string>;
  completed_at?: Nullable<string>;
  created_at: string;
  payable?: PayableSummary | null;
  transactions?: Transaction[] | null;
}

const page = usePage<{ payment: Payment }>();
const payment = computed(() => page.props.payment);

function statusClass(status: string) {
  switch (status) {
    case 'pending':
      return 'text-yellow-600';
    case 'completed':
      return 'text-green-600';
    case 'failed':
      return 'text-red-600';
    default:
      return 'text-gray-700';
  }
}

function prettyDate(val?: string | null) {
  if (!val) return '-';
  try {
    return new Date(val).toLocaleString();
  } catch {
    return String(val);
  }
}

function goBack() {
  router.get(route('admin.payments.index'));
}

const mpesaPhoneNumber = computed(() => {
    if (payment.value.provider !== 'mpesa') return null;

    const metadata =
        typeof payment.value.metadata === 'string'
            ? JSON.parse(payment.value.metadata)
            : payment.value.metadata;

    const items =
        metadata?.mpesa_callback?.Body?.stkCallback?.CallbackMetadata?.Item;

    const phone = items?.find((i: any) => i.Name === 'PhoneNumber');
    return phone?.Value ?? null;
});


</script>

<template>
  <Head :title="`Payment ${payment.reference || payment.ulid}`" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Dashboard', href: route('admin.dashboard') },
      { title: 'Payments', href: route('admin.payments.index') },
      { title: payment.reference || payment.ulid, href: route('admin.payments.show', { payment: payment.ulid }) },
    ]"
  >
    <div class="p-4">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">
          Payment Details
        </h1>
        <button @click="goBack" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
          Back to Payments
        </button>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <!-- Left: Main details -->
        <div class="lg:col-span-2">
          <div class="rounded-lg bg-white p-4 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
              <div>
                <div class="text-sm text-gray-500">Account Transacted</div>
                  <div
                      v-if="payment.provider === 'mpesa'"
                      class="text-lg font-medium text-gray-900"
                  >
                      {{ mpesaPhoneNumber || '—' }}
                  </div>


              </div>
              <div class="text-right">
                <div class="text-sm text-gray-500">Status</div>
                <div class="text-lg font-semibold capitalize" :class="statusClass(payment.status)">{{ payment.status }}</div>
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <div class="text-sm text-gray-500">Amount</div>
                <div class="text-gray-900">{{ payment.amount }} {{ payment.currency }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Provider / Method</div>
                <div class="text-gray-900">{{ payment.provider }} / {{ payment.method }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Created</div>
                <div class="text-gray-900">{{ prettyDate(payment.created_at) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Completed</div>
                <div class="text-gray-900">{{ prettyDate(payment.completed_at) }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Expires</div>
                <div class="text-gray-900">{{ prettyDate(payment.expires_at) }}</div>
              </div>
              <div v-if="payment.failure_reason">
                <div class="text-sm text-gray-500">Failure Reason</div>
                <div class="text-red-700">{{ payment.failure_reason }}</div>
              </div>
            </div>
          </div>

          <!-- Transactions (optional) -->
          <div v-if="payment.transactions && payment.transactions.length" class="mt-4 rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-lg font-semibold text-gray-800">Transactions</h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-for="tx in payment.transactions" :key="tx.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-700">{{ tx.id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ tx.type || '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ tx.amount ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm capitalize text-gray-700">{{ tx.status || '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ tx.reference || '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ prettyDate(tx.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right: Payable and Metadata -->
        <div class="lg:col-span-1">
          <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-lg font-semibold text-gray-800">Payable</h2>
            <div v-if="payment.payable" class="space-y-1 text-sm">
              <div><span class="text-gray-500">Type:</span> <span class="text-gray-900">{{ payment.payable.model || 'Order' }}</span></div>
              <div><span class="text-gray-500">ID:</span> <span class="font-mono text-gray-900">{{ payment.payable.order_code|| payment.payable.id || '—' }}</span></div>
              <div v-if="payment.payable.title"><span class="text-gray-500">Title:</span> <span class="text-gray-900">{{ payment.payable.title }}</span></div>
            </div>
            <div v-else class="text-sm text-gray-500">No payable relation.</div>
          </div>

<!--          <div class="mt-4 rounded-lg bg-white p-4 shadow-sm">-->
<!--            <h2 class="mb-3 text-lg font-semibold text-gray-800">Metadata</h2>-->
<!--            <div v-if="payment.metadata" class="overflow-auto rounded border border-gray-200 bg-gray-50 p-3">-->
<!--              <pre class="whitespace-pre-wrap text-xs text-gray-800">{{ JSON.stringify(payment.metadata, null, 2) }}</pre>-->
<!--            </div>-->
<!--            <div v-else class="text-sm text-gray-500">No metadata.</div>-->
<!--          </div>-->
        </div>
      </div>
    </div>
  </AppLayout>

</template>
