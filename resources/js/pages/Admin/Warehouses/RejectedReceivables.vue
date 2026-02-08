<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  warehouse: { id: number; hashid: string; name: string; code: string };
  items: { id: number; product_name: string; variant_display?: string|null; quantity: number; from_warehouse?: string|null; rejected_reason?: string|null; rejected_at?: string|null; note?: string|null; order_return_id?: number|null; order_code?: string|null; order_ulid?: string|null }[];
  pagination: any;
  filters: { search: string; per_page: number };
}>();

const search = ref(props.filters.search);
const perPage = ref(props.filters.per_page);

function filterList() {
  router.get(route('admin.receivables.rejected', { warehouse: props.warehouse.hashid }), {
    search: search.value,
    per_page: perPage.value,
  }, { preserveState: true, replace: true });
}

function goToPending() {
  router.get(route('admin.receivables.index', { warehouse: props.warehouse.hashid }), {}, { replace: true });
}

function goBackInventory() {
  router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
}
</script>

<template>
  <Head :title="`Rejected Transfers — ${props.warehouse.name}`" />
  <AppLayout :breadcrumbs="[
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Warehouses', href: route('admin.warehouses.index') },
    { title: props.warehouse.name, href: route('admin.inventory', { warehouse: props.warehouse.hashid }) },
    { title: 'Rejected Transfers', href: '#' }
  ]">
    <div class="p-4">
      <div class="mb-3 flex justify-between items-center">
        <h1 class="text-2xl font-semibold">Rejected Transfers</h1>
        <div class="flex items-center gap-2">
          <button @click="goToPending" class="inline-flex items-center gap-2 text-sm bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-500">View Pending Receivables</button>
          <button @click="goBackInventory" class="inline-flex items-center gap-2 text-sm bg-orange-600 text-white px-3 py-2 rounded-md hover:bg-orange-500">Back to Inventory</button>
        </div>
      </div>

      <div class="flex items-center gap-3 mb-4">
        <input type="text" v-model="search" @keyup.enter="filterList" placeholder="Search product, source warehouse, or reason..." class="border rounded-md px-3 py-2 w-80" />
        <select v-model.number="perPage" @change="filterList" class="border rounded-md px-3 py-2">
          <option value="10">10 per page</option>
          <option value="20">20 per page</option>
          <option value="50">50 per page</option>
        </select>
        <button @click="filterList" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90">Filter</button>
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Product</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Variant</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Quantity</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">From Warehouse</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Rejected Reason</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Rejected At</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Note</th>
              <th class="px-4 py-2 text-right font-medium text-gray-500 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="it in props.items" :key="it.id">
              <td class="px-4 py-2">{{ it.product_name }}</td>
              <td class="px-4 py-2">{{ it.variant_display || '—' }}</td>
              <td class="px-4 py-2">{{ it.quantity }}</td>
              <td class="px-4 py-2">{{ it.from_warehouse || '—' }}</td>
              <td class="px-4 py-2">{{ it.rejected_reason || '—' }}</td>
              <td class="px-4 py-2">{{ it.rejected_at || '—' }}</td>
              <td class="px-4 py-2">{{ it.note || '—' }}</td>
              <td class="px-4 py-2 text-right">
                <a v-if="it.order_ulid" :href="route('admin.orders.show', it.order_ulid)" class="text-green-600 hover:underline">View order</a>
                <span v-else class="text-gray-400">—</span>
              </td>
            </tr>
            <tr v-if="!props.items || props.items.length === 0">
              <td colspan="7" class="px-4 py-6 text-center text-gray-500">No rejected transfers.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="props.pagination.meta.total > perPage" class="mt-4 flex justify-center items-center gap-1">
        <template v-for="link in props.pagination.links" :key="link.label">
          <button
            v-if="link.url"
            @click.prevent="router.get(link.url, {}, { preserveState: true, replace: true })"
            :class="['px-3 py-1 border rounded-md', { 'bg-primary text-white': link.active, 'hover:bg-gray-100': !link.active }]"
            v-html="link.label"
          ></button>
          <span v-else class="px-3 py-1 border rounded-md text-gray-400" v-html="link.label"></span>
        </template>
      </div>
    </div>
  </AppLayout>

</template>
