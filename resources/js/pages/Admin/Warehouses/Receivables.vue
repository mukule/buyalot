<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  warehouse: { id: number; hashid: string; name: string; code: string };
  receivables: { id: number; product_name: string; variant_display?: string|null; quantity: number; note?: string|null; created_at?: string|null }[];
  rejection_reasons: { id: number; name: string; description?: string|null }[];
  pagination: any;
  filters: { search: string; per_page: number };
}>();

const search = ref(props.filters.search);
const perPage = ref(props.filters.per_page);

function filterList() {
  router.get(route('admin.receivables.index', { warehouse: props.warehouse.hashid }), {
    search: search.value,
    per_page: perPage.value,
  }, { preserveState: true, replace: true });
}

function goBack() {
  router.get(route('admin.inventory', { warehouse: props.warehouse.hashid }), {}, { replace: true });
}

function goToRejected() {
  router.get(route('admin.receivables.rejected', { warehouse: props.warehouse.hashid }), {}, { replace: true });
}

function accept(id: number) {
  router.post(route('admin.receivables.accept', { warehouse: props.warehouse.hashid }), { receivable_id: id }, {
    preserveState: true,
    onSuccess: () => {
      // Refresh list after accepting and also refresh inventory page to reflect counts when user goes back
      router.get(route('admin.receivables.index', { warehouse: props.warehouse.hashid }), {}, { replace: true });
    }
  });
}

// Reject modal state
const showReject = ref(false);
const rejectingId = ref<number|null>(null);
const selectedReasonId = ref<number|null>(null);
const extraNote = ref('');
const submitting = ref(false);

function openRejectModal(id: number) {
  rejectingId.value = id;
  // Preselect first active reason if available
  selectedReasonId.value = props.rejection_reasons?.[0]?.id ?? null;
  extraNote.value = '';
  showReject.value = true;
}

function closeRejectModal() {
  showReject.value = false;
  rejectingId.value = null;
  selectedReasonId.value = null;
  extraNote.value = '';
}

function submitReject() {
  if (!rejectingId.value) return;
  if (!selectedReasonId.value) {
    window.alert('Please select a rejection reason.');
    return;
  }
  submitting.value = true;
  router.post(
    route('admin.receivables.reject', { warehouse: props.warehouse.hashid }),
    { receivable_id: rejectingId.value, rejection_reason_id: selectedReasonId.value, note: extraNote.value || undefined },
    {
      preserveState: true,
      onFinish: () => { submitting.value = false; },
      onSuccess: () => {
        closeRejectModal();
        router.get(route('admin.receivables.index', { warehouse: props.warehouse.hashid }), {}, { replace: true });
      }
    }
  );
}
</script>

<template>
  <Head :title="`Receivables — ${props.warehouse.name}`" />
  <AppLayout :breadcrumbs="[
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Warehouses', href: route('admin.warehouses.index') },
    { title: props.warehouse.name, href: route('admin.inventory', { warehouse: props.warehouse.hashid }) },
    { title: 'Receivables', href: '#' }
  ]">
    <div class="p-4">
      <div class="mb-3 flex justify-between items-center">
        <h1 class="text-2xl font-semibold">Receivables</h1>
        <div class="flex items-center gap-2">
          <button @click="goToRejected" class="inline-flex items-center gap-2 text-sm bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-500">View Rejected Transfers</button>
          <button @click="goBack" class="inline-flex items-center gap-2 text-sm bg-orange-600 text-white px-3 py-2 rounded-md hover:bg-primary/80">Back to Inventory</button>
        </div>
      </div>

      <div class="flex items-center gap-3 mb-4">
        <input type="text" v-model="search" @keyup.enter="filterList" placeholder="Search product..." class="border rounded-md px-3 py-2 w-64" />
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
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Note</th>
              <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase">Created</th>
              <th class="px-4 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="r in props.receivables" :key="r.id">
              <td class="px-4 py-2">{{ r.product_name }}</td>
              <td class="px-4 py-2">{{ r.variant_display || '—' }}</td>
              <td class="px-4 py-2">{{ r.quantity }}</td>
              <td class="px-4 py-2">{{ r.note || '—' }}</td>
              <td class="px-4 py-2">{{ r.created_at || '—' }}</td>
              <td class="px-4 py-2 text-right">
                <div class="flex items-center gap-3 justify-end">
                  <button @click="accept(r.id)" class="text-green-700 hover:underline text-xs">Receive</button>
                  <button @click="openRejectModal(r.id)" class="text-red-600 hover:underline text-xs">Reject</button>
                </div>
              </td>
            </tr>
            <tr v-if="!props.receivables || props.receivables.length === 0">
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">No pending receivables.</td>
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

      <!-- Reject Modal -->
      <div v-if="showReject" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeRejectModal"></div>
        <div class="relative bg-white rounded-md shadow-lg w-full max-w-md p-5">
          <h3 class="text-lg font-semibold mb-3">Reject Receivable</h3>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Reason</label>
            <select v-model.number="selectedReasonId" class="w-full border rounded-md px-3 py-2">
              <option v-for="rr in props.rejection_reasons" :key="rr.id" :value="rr.id">
                {{ rr.name }}
              </option>
            </select>
            <p v-if="props.rejection_reasons && selectedReasonId" class="text-xs text-gray-500 mt-1">
              {{ props.rejection_reasons.find(r=>r.id===selectedReasonId)?.description || '' }}
            </p>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Additional details (optional)</label>
            <textarea v-model="extraNote" rows="3" class="w-full border rounded-md px-3 py-2" placeholder="Add any details..."></textarea>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="closeRejectModal" class="px-4 py-2 rounded-md border">Cancel</button>
            <button :disabled="submitting" @click="submitReject" class="px-4 py-2 rounded-md bg-red-600 text-white disabled:opacity-50">
              <span v-if="!submitting">Reject</span>
              <span v-else>Submitting...</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
