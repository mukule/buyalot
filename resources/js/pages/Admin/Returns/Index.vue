<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
  returns: {
    id: number;
    order_code: string;
    order_ulid?: string | null;
    order_status?: string | null;
    status: string;
    reason: string;
    reason_notes?: string | null;
    is_full_return: boolean;
    created_at: string | null;
  }[];
  pagination: { links: { url: string | null; label: string; active: boolean }[]; meta?: any };
  filters: { per_page: number };
}>();

const perPage = ref(props.filters.per_page);
const receivingId = ref<number | null>(null);
const breadcrumbs = [
  { title: 'Dashboard', href: '/admin/dashboard' },
  { title: 'Returns', href: '#' },
];

function applyPerPage() {
  router.get(route('admin.returns.index'), { per_page: perPage.value }, { preserveState: true, replace: true });
}

function receiveReturn(id: number) {
  if (receivingId.value) return;
  receivingId.value = id;
  router.post(route('admin.returns.receive', id), {}, {
    preserveState: true,
    onFinish: () => { receivingId.value = null; },
    onSuccess: () => router.reload(),
  });
}
</script>

<template>
  <Head title="Returns" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold">Returns</h1>
        <div class="flex items-center gap-2">
          <select v-model.number="perPage" @change="applyPerPage" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option :value="10">10 per page</option>
            <option :value="20">20 per page</option>
            <option :value="50">50 per page</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Order</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Order status</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Return status</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Reason</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Date</th>
              <th class="px-4 py-2 text-right font-medium text-gray-500 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="r in props.returns" :key="r.id">
              <td class="px-4 py-2 font-medium">{{ r.order_code || '—' }}</td>
              <td class="px-4 py-2 capitalize">{{ (r.order_status || '—').replace(/_/g, ' ') }}</td>
              <td class="px-4 py-2 capitalize">{{ (r.status || '—').replace(/_/g, ' ') }}</td>
              <td class="px-4 py-2">{{ r.reason || '—' }}</td>
              <td class="px-4 py-2">{{ r.is_full_return ? 'Full' : 'Partial' }}</td>
              <td class="px-4 py-2 text-gray-600">{{ r.created_at || '—' }}</td>
              <td class="px-4 py-2 text-right">
                <div class="flex items-center justify-end gap-3">
                  <Link
                    :href="route('admin.returns.show', r.id)"
                    class="text-primary hover:underline"
                  >
                    View
                  </Link>
                  <button
                    v-if="r.status === 'pending_receive'"
                    @click="receiveReturn(r.id)"
                    :disabled="receivingId === r.id"
                    class="text-green-600 hover:underline disabled:opacity-50"
                  >
                    {{ receivingId === r.id ? 'Receiving...' : 'Receive' }}
                  </button>
                  <Link
                    v-if="r.order_ulid"
                    :href="route('admin.orders.show', r.order_ulid)"
                    class="text-green-600 hover:underline"
                  >
                    View order
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!props.returns?.length">
              <td colspan="7" class="px-4 py-6 text-center text-gray-500">No returns found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="props.pagination?.links?.length > 1" class="mt-4 flex justify-center gap-1">
        <template v-for="(link, idx) in props.pagination.links" :key="idx">
          <Link
            v-if="link.url"
            :href="link.url"
            class="rounded border px-3 py-1 text-sm"
            :class="link.active ? 'border-primary bg-primary text-white' : 'border-gray-300 hover:bg-gray-100'"
            v-html="link.label"
          />
          <span v-else class="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400" v-html="link.label" />
        </template>
      </div>
    </div>
  </AppLayout>
</template>
