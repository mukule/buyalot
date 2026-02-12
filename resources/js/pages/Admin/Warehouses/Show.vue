<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowLeftIcon, BoxesIcon, TruckIcon, ClipboardListIcon, PencilIcon, PowerIcon, UsersIcon, PackageCheckIcon } from 'lucide-vue-next';

type Manager = { name: string; role?: string | null; phone?: string | null };
type Region = { id: number; name: string } | null;

interface WarehouseView {
  id: number;
  hashid: string;
  name: string;
  code: string;
  type?: string;
  capacity?: number | null;
  active: boolean;
  region: Region;
  managers?: Manager[];
}

interface Summary {
  total_units: number;
  total_variants: number;
  capacity: number;
  remaining_capacity: number;
}

const page = usePage<AppPageProps<{ warehouse: WarehouseView; summary: Summary }>>();
const warehouse = page.props.warehouse;
const summary = page.props.summary;

const breadcrumbs = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Warehouses', href: route('admin.warehouses.index') },
  { title: warehouse.name, href: route('admin.warehouses.show', { warehouse: warehouse.hashid }) },
];

function goBack() {
  router.get(route('admin.warehouses.index'));
}

function editWarehouse() {
  router.get(route('admin.warehouses.edit', { warehouse: warehouse.hashid }));
}

function toggleStatus() {
  router.patch(route('admin.warehouses.toggle-status', { warehouse: warehouse.hashid }), {}, {
    onSuccess: () => router.reload({ only: ['warehouse'] }),
  });
}

function openInventory() {
  router.get(route('admin.inventory', { warehouse: warehouse.hashid }));
}
function openReceivables() {
  router.get(route('admin.receivables.index', { warehouse: warehouse.hashid }));
}
function openDispatches() {
  router.get(route('admin.dispatches.index', { warehouse: warehouse.hashid }));
}
function openOrdersReadyForPickup() {
  router.get(route('admin.orders-ready-for-pickup', { warehouse: warehouse.hashid }));
}
</script>

<template>
  <Head :title="`Warehouse — ${warehouse.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button @click="goBack" class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-gray-700 hover:bg-gray-50">
            <ArrowLeftIcon class="h-4 w-4" />
            Back
          </button>
          <h1 class="text-2xl font-semibold text-gray-800">{{ warehouse.name }}</h1>
          <span :class="warehouse.active ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'" class="rounded px-2 py-0.5 text-xs">
            {{ warehouse.active ? 'Active' : 'Inactive' }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="editWarehouse" class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
            <PencilIcon class="h-4 w-4" /> Edit
          </button>
          <button @click="toggleStatus" class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
            <PowerIcon class="h-4 w-4" /> {{ warehouse.active ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Overview -->
        <div class="col-span-2 rounded-lg bg-white p-4 shadow">
          <h2 class="mb-3 text-lg font-semibold text-gray-800">Overview</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <div class="text-xs uppercase text-gray-500">Code</div>
              <div class="font-mono text-sm text-gray-800">{{ warehouse.code }}</div>
            </div>
            <div>
              <div class="text-xs uppercase text-gray-500">Type</div>
              <div class="text-sm text-gray-800 capitalize">{{ warehouse.type || '—' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase text-gray-500">Region</div>
              <div class="text-sm text-gray-800">{{ warehouse.region?.name || '—' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase text-gray-500">Capacity</div>
              <div class="text-sm text-gray-800">{{ warehouse.capacity ?? '—' }}</div>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-md border bg-gray-50 p-3">
              <div class="text-xs text-gray-500">Total Units</div>
              <div class="text-xl font-semibold text-gray-800">{{ summary.total_units }}</div>
            </div>
            <div class="rounded-md border bg-gray-50 p-3">
              <div class="text-xs text-gray-500">Variants</div>
              <div class="text-xl font-semibold text-gray-800">{{ summary.total_variants }}</div>
            </div>
            <div class="rounded-md border bg-gray-50 p-3">
              <div class="text-xs text-gray-500">Remaining Capacity</div>
              <div class="text-xl font-semibold text-gray-800">{{ summary.remaining_capacity }}</div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-lg bg-white p-4 shadow">
          <h2 class="mb-3 text-lg font-semibold text-gray-800">Quick Actions</h2>
          <div class="flex flex-col gap-2">
            <button @click="openInventory" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-gray-700 hover:bg-gray-50">
              <BoxesIcon class="h-4 w-4" /> Inventory
            </button>
            <button @click="openReceivables" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-gray-700 hover:bg-gray-50">
              <ClipboardListIcon class="h-4 w-4" /> Receivables
            </button>
            <button @click="openDispatches" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-gray-700 hover:bg-gray-50">
              <TruckIcon class="h-4 w-4" /> Dispatches
            </button>
            <button @click="openOrdersReadyForPickup" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-gray-700 hover:bg-gray-50">
              <PackageCheckIcon class="h-4 w-4" /> Orders ready for pickup
            </button>
          </div>
        </div>
      </div>

      <!-- Managers -->
      <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <div class="mb-2 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">Staff</h2>
          <div class="text-sm text-gray-500">{{ (warehouse.managers?.length || 0) }} total</div>
        </div>
        <div v-if="warehouse.managers?.length" class="divide-y">
          <div v-for="(m, i) in warehouse.managers" :key="i" class="flex items-center justify-between py-2">
            <div class="flex items-center gap-3">
              <UsersIcon class="h-4 w-4 text-gray-400" />
              <div>
                <div class="font-medium text-gray-800">{{ m.name }}</div>
                <div class="text-xs text-gray-500">{{ m.role?.replace('_',' ') || '—' }}<span v-if="m.phone"> • {{ m.phone }}</span></div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-gray-500">No staff assigned.</div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
</style>
