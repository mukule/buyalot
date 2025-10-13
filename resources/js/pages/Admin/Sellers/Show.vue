<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const seller = (page.props as any).seller as {
  id: number;
  name: string;
  slug: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  country?: string | null;
  currency?: string | null;
  is_active?: boolean;
  created_at?: string;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto p-4" style="max-width: 900px">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Seller: {{ seller.name }}</h1>
        <a :href="route('admin.sellers.index')" class="text-primary underline">&larr; Back</a>
      </div>

      <div class="rounded-lg bg-white p-4 shadow">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <div class="text-sm text-gray-500">Name</div>
            <div class="font-medium">{{ seller.name }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Email</div>
            <div class="font-medium">{{ seller.email || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Phone</div>
            <div class="font-medium">{{ seller.phone || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Active</div>
            <div class="font-medium" :class="seller.is_active ? 'text-green-700' : 'text-gray-500'">{{ seller.is_active ? 'Yes' : 'No' }}</div>
          </div>
          <div class="md:col-span-2">
            <div class="text-sm text-gray-500">Address</div>
            <div class="font-medium">{{ seller.address || '-' }}</div>
          </div>
        </div>
      </div>
    </section>
    </AppLayout>
</template>
