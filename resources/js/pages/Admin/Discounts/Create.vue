<script setup lang="ts">
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const form = reactive({
  name: '',
  slug: '',
  description: '',
  code: '',
  type: 'percentage',
  value: 0,
  minimum_amount: 0,
  maximum_discount: 0,
  usage_limit: 0,
  usage_limit_per_customer: 0,
  is_active: true as any,
  starts_at: '',
  expires_at: '',
  applicable_to: '',
  conditions: '',
  metadata: '',
});
const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Discounts', href: route('admin.discounts.index') },
    { title: 'Create', href: route('admin.discounts.create') },
];

const submit = () => {
  router.post(route('admin.discounts.store'), form);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
      <div class="p-4">
          <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
              <!-- Header -->
              <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Create Discount</h1>
        <a :href="route('admin.discounts.index')" class="text-primary underline">&larr; Back</a>
      </div>

      <div class="rounded-lg bg-white p-4 shadow">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
            <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Slug (optional)</label>
            <input v-model="form.slug" type="text" class="w-full rounded border px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Code (optional)</label>
            <input v-model="form.code" type="text" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
            <select v-model="form.type" class="w-full rounded border px-3 py-2">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed_amount">Fixed Amount</option>
              <option value="free_shipping">Free Shipping</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Value</label>
            <input v-model.number="form.value" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Active</label>
            <select v-model="form.is_active" class="w-full rounded border px-3 py-2">
              <option :value="true">Yes</option>
              <option :value="false">No</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Starts At</label>
            <input v-model="form.starts_at" type="datetime-local" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Expires At</label>
            <input v-model="form.expires_at" type="datetime-local" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Minimum Order Amount</label>
            <input v-model.number="form.minimum_amount" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Maximum Discount</label>
            <input v-model.number="form.maximum_discount" type="number" step="0.01" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Usage Limit (Global)</label>
            <input v-model.number="form.usage_limit" type="number" step="1" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Usage Limit Per Customer</label>
            <input v-model.number="form.usage_limit_per_customer" type="number" step="1" min="0" class="w-full rounded border px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Applicable To (optional)</label>
            <input v-model="form.applicable_to" type="text" placeholder="e.g., all, specific_products, specific_categories" class="w-full rounded border px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Conditions (JSON)</label>
            <textarea v-model="form.conditions" rows="5" placeholder='{"applies_to":"items","product_ids":[1,2]}' class="w-full rounded border px-3 py-2 font-mono text-sm" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Metadata (JSON)</label>
            <textarea v-model="form.metadata" rows="3" class="w-full rounded border px-3 py-2 font-mono text-sm" />
          </div>
        </div>

        <div class="mt-4">
          <button @click="submit" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">Save</button>
        </div>
      </div>
          </div>
      </div>
  </AppLayout>
</template>
