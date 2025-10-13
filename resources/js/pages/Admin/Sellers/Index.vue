<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Seller {
  id: number;
  name: string;
  slug: string;
  email?: string | null;
  phone?: string | null;
  is_active?: boolean;
  created_at?: string;
}

const page = usePage();
const sellers = (page.props as any).sellers as {
  data: Seller[];
  current_page: number;
  last_page: number;
  links: { url: string | null; label: string; active: boolean }[];
};
const filters = (page.props as any).filters as { search?: string };
const search = ref(filters?.search || '');

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Sellers', href: route('admin.sellers.index') },
];
const onSearch = () => {
  router.get(route('admin.sellers.index'), { search: search.value }, { preserveState: true, preserveScroll: true });
};

const viewItem = (s: Seller) => router.visit(route('admin.sellers.show', s.id));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Sellers</h1>
      </div>

      <div class="mb-4 flex gap-2">
        <input v-model="search" type="text" placeholder="Search name, email, phone" class="w-full rounded border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none" />
        <button @click="onSearch" class="rounded bg-gray-800 px-4 py-2 text-white hover:bg-black">Search</button>
      </div>

      <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
              <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
              <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Phone</th>
              <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Active</th>
              <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="s in sellers.data" :key="s.id">
              <td class="px-4 py-2">{{ s.name }}</td>
              <td class="px-4 py-2">{{ s.email || '-' }}</td>
              <td class="px-4 py-2">{{ s.phone || '-' }}</td>
              <td class="px-4 py-2">
                <span :class="s.is_active ? 'text-green-700' : 'text-gray-500'">{{ s.is_active ? 'Yes' : 'No' }}</span>
              </td>
              <td class="px-4 py-2 text-right">
                <button @click="viewItem(s)" class="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex flex-wrap items-center gap-2">
        <template v-for="(lnk, idx) in sellers.links" :key="idx">
          <button
            v-if="lnk.url"
            @click="router.visit(lnk.url, { preserveState: true, preserveScroll: true })"
            class="rounded px-3 py-1 text-sm"
            :class="lnk.active ? 'bg-primary text-white' : 'bg-white text-gray-700 border'"
            v-html="lnk.label"
          />
          <span v-else class="rounded border bg-gray-100 px-3 py-1 text-sm text-gray-400" v-html="lnk.label" />
        </template>
      </div>
            </div>
        </div>
    </AppLayout>
</template>
