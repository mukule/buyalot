<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface InvoiceItem {
  id: number;
  number: string;
  seller_id: number;
  buyer_id?: number | null;
  status: string;
  type: string;
  currency: string;
  subtotal_minor: number;
  discount_minor: number;
  tax_minor: number;
  total_minor: number;
  balance_minor: number;
  issue_date?: string | null;
  due_date?: string | null;
  created_at: string;
}

const page = usePage();
const invoices = computed<InvoiceItem[]>(() => (page.props.invoices?.data ?? []) as InvoiceItem[]);
const pagination = computed(() => page.props.invoices ?? {});
const filters = ref<{ seller_id?: number | null; status?: string | null; type?: string | null; search?: string | null }>(
  (page.props.filters as any) ?? {}
);

const breadcrumbs = [
  { title: 'Dashboard', href: '/admin/dashboard' },
  { title: 'Invoices', href: '/admin/invoices' },
];

function money(minor: number, currency = 'KES') {
  const val = (minor ?? 0) / 100;
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(val);
}

function applyFilters() {
  const params: Record<string, any> = {};
  if (filters.value.seller_id) params.seller_id = filters.value.seller_id;
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.type) params.type = filters.value.type;
  if (filters.value.search) params.search = filters.value.search;
  router.get('/admin/invoices', params, { preserveState: true, replace: true });
}

let searchTimeout: number | undefined;
watch(
  () => filters.value.search,
  () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => applyFilters(), 400);
  }
);

function goPrev() {
  const url = (pagination.value?.prev_page_url as string) ?? null;
  if (url) router.visit(url, { preserveState: true, replace: true });
}
function goNext() {
  const url = (pagination.value?.next_page_url as string) ?? null;
  if (url) router.visit(url, { preserveState: true, replace: true });
}
</script>

<template>
  <Head title="Invoices" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 class="text-2xl font-semibold">Invoices</h1>
            <p class="text-sm text-gray-600">Manage billing invoices and their statuses.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <input
              v-model.number="filters.seller_id"
              type="number"
              placeholder="Seller ID"
              class="w-32 rounded border border-gray-300 p-2 text-sm"
            />
            <select v-model="filters.status" class="rounded border border-gray-300 p-2 text-sm">
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="issued">Issued</option>
              <option value="sent">Sent</option>
              <option value="partially_paid">Partially paid</option>
              <option value="paid">Paid</option>
            </select>
            <select v-model="filters.type" class="rounded border border-gray-300 p-2 text-sm">
              <option value="">All Types</option>
              <option value="tax_invoice">Tax invoice</option>
              <option value="credit_note">Credit note</option>
              <option value="debit_note">Debit note</option>
              <option value="proforma">Proforma</option>
              <option value="commercial">Commercial</option>
            </select>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search number/ref/PO"
              class="w-56 rounded border border-gray-300 p-2 text-sm"
            />
            <button class="rounded bg-primary px-3 py-2 text-sm text-white" @click="applyFilters">Apply</button>
          </div>
        </div>
        <hr />

        <div class="overflow-x-auto">
          <table class="min-w-full table-auto text-left text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Number</th>
                <th class="px-4 py-2">Seller</th>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Total</th>
                <th class="px-4 py-2">Balance</th>
                <th class="px-4 py-2">Issued</th>
                <th class="px-4 py-2">Due</th>
                <th class="px-4 py-2">Created</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(inv, idx) in invoices" :key="inv.id" class="border-b">
                <td class="px-4 py-2">{{ idx + 1 }}</td>
                <td class="px-4 py-2 font-medium">{{ inv.number }}</td>
                <td class="px-4 py-2">{{ inv.seller_id }}</td>
                <td class="px-4 py-2">{{ inv.type }}</td>
                <td class="px-4 py-2">{{ inv.status }}</td>
                <td class="px-4 py-2">{{ money(inv.total_minor, inv.currency) }}</td>
                <td class="px-4 py-2">{{ money(inv.balance_minor, inv.currency) }}</td>
                <td class="px-4 py-2">{{ inv.issue_date ?? '-' }}</td>
                <td class="px-4 py-2">{{ inv.due_date ?? '-' }}</td>
                <td class="px-4 py-2">{{ new Date(inv.created_at).toLocaleDateString() }}</td>
              </tr>
              <tr v-if="invoices.length === 0">
                <td class="px-4 py-6 text-center text-gray-500" colspan="10">No invoices found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <button class="rounded border px-3 py-2 text-sm" :disabled="!pagination.prev_page_url" @click="goPrev">Previous</button>
          <div class="text-xs text-gray-600">
            Page {{ pagination.current_page ?? 1 }} of {{ pagination.last_page ?? 1 }} — Total {{ pagination.total ?? 0 }}
          </div>
          <button class="rounded border px-3 py-2 text-sm" :disabled="!pagination.next_page_url" @click="goNext">Next</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
