<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed } from 'vue';
import { CalendarDays, Mail, Phone, MapPin, CheckCircle2, XCircle, Users as UsersIcon, Globe } from 'lucide-vue-next';

const page = usePage();

interface Seller {
  id: number;
  display_name?: string | null;
  is_active?: boolean;
  created_at?: string;
}

interface Business {
  company_legal_name?: string | null;
  business_type?: string | null;
  primary_product_category?: string | null;
  contact_email?: string | null;
  contact_phone?: string | null;
  country?: string | null;
  nationality?: string | null;
  website?: string | null;
  owned_brands?: string | null;
  licensed_brands?: string | null;
  business_summary?: string | null;
  status?: number | null;
  is_active?: boolean;
}

interface OwnerUser { id: number; name: string; email: string | null; phone: string | null }
interface UserRow { id: number; name: string; email: string | null; phone: string | null; role?: string | null }

const seller = (page.props as any).seller as Seller;
const business = (page.props as any).business as Business | null;
const owner_user = (page.props as any).owner_user as OwnerUser | null;
const role = (page.props as any).role as string | null;
const users = ((page.props as any).users || []) as UserRow[];

const breadcrumbs = [
  { title: 'Dashboard', href: route('admin.dashboard') },
  { title: 'Vendors', href: route('admin.sellers.index') },
  { title: seller?.display_name ?? 'Vendor', href: route('admin.sellers.show', seller?.id) },
];

const statusBadge = computed(() => ({
  text: (seller?.is_active || business?.is_active) ? 'Approved' : 'Pending',
  class: (seller?.is_active || business?.is_active)
    ? 'inline-flex items-center gap-1 rounded bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700'
    : 'inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600',
  icon: (seller?.is_active || business?.is_active) ? CheckCircle2 : XCircle,
}));
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto p-4" style="max-width: 1100px">
      <!-- Header -->
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Vendor Details</h1>
        <a :href="route('admin.sellers.index')" class="text-primary underline">&larr; Back to list</a>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Overview Card -->
        <div class="rounded-lg bg-white p-4 shadow lg:col-span-2">
          <div class="mb-4 flex items-start justify-between gap-4">
            <div>
              <div class="text-sm text-gray-500">Company Legal Name</div>
              <div class="text-lg font-semibold text-gray-900">{{ business?.company_legal_name || seller.display_name || ('Vendor #' + seller.id) }}</div>
              <div class="mt-1 text-xs text-gray-500" v-if="business?.business_type">Type: {{ business?.business_type }}</div>
            </div>
            <component :is="statusBadge.icon" :class="statusBadge.class" class="h-5 w-5">
              <span class="ml-1">{{ statusBadge.text }}</span>
            </component>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex items-start gap-2">
              <Mail class="mt-0.5 h-4 w-4 text-gray-500" />
              <div>
                <div class="text-xs text-gray-500">Email</div>
                <div class="font-medium">{{ business?.contact_email || '-' }}</div>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <Phone class="mt-0.5 h-4 w-4 text-gray-500" />
              <div>
                <div class="text-xs text-gray-500">Phone</div>
                <div class="font-medium">{{ business?.contact_phone || '-' }}</div>
              </div>
            </div>
            <div class="flex items-start gap-2 md:col-span-2">
              <MapPin class="mt-0.5 h-4 w-4 text-gray-500" />
              <div>
                <div class="text-xs text-gray-500">Location</div>
                <div class="font-medium">{{ business?.country || '-' }}</div>
                <div class="text-xs text-gray-500" v-if="business?.nationality">Nationality: {{ business?.nationality }}</div>
              </div>
            </div>
            <div class="flex items-start gap-2 md:col-span-2" v-if="business?.website">
              <Globe class="mt-0.5 h-4 w-4 text-gray-500" />
              <div>
                <div class="text-xs text-gray-500">Website</div>
                <a :href="business?.website" target="_blank" class="font-medium text-primary hover:underline">{{ business?.website }}</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Meta Card -->
        <div class="rounded-lg bg-white p-4 shadow">
          <div class="mb-2 text-sm font-semibold text-gray-700">Meta</div>
          <div class="flex items-center gap-2 text-sm text-gray-700">
            <CalendarDays class="h-4 w-4 text-gray-500" />
            <div>
              <div class="text-xs text-gray-500">Created</div>
              <div>{{ seller.created_at }}</div>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-700" v-if="business?.primary_product_category">
            <div class="text-xs text-gray-500">Primary Products</div>
            <div class="font-medium">{{ business?.primary_product_category }}</div>
          </div>
        </div>
      </div>

      <!-- Second row: Business details + Owner/Users -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow lg:col-span-2">
          <div class="mb-2 text-sm font-semibold text-gray-700">Business Details</div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <div class="text-xs text-gray-500">Company</div>
              <div class="font-medium">{{ business?.company_legal_name || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Business Type</div>
              <div class="font-medium">{{ business?.business_type || '-' }}</div>
            </div>
            <div v-if="business?.owned_brands">
              <div class="text-xs text-gray-500">Owned Brands</div>
              <div class="font-medium">{{ business?.owned_brands }}</div>
            </div>
            <div v-if="business?.licensed_brands">
              <div class="text-xs text-gray-500">Licensed Brands</div>
              <div class="font-medium">{{ business?.licensed_brands }}</div>
            </div>
            <div class="md:col-span-2" v-if="business?.business_summary">
              <div class="text-xs text-gray-500">Summary</div>
              <div class="whitespace-pre-line">{{ business?.business_summary }}</div>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow">
          <div class="mb-2 flex items-center justify-between">
            <div class="text-sm font-semibold text-gray-700">Owner & Role</div>
          </div>
          <div v-if="owner_user" class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <div class="font-medium">{{ owner_user.name }}</div>
              <span class="rounded bg-gray-100 px-2 py-0.5 text-xs" v-if="role">{{ role }}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-700">
              <Mail class="h-4 w-4 text-gray-500" />
              <span>{{ owner_user.email || '-' }}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-700">
              <Phone class="h-4 w-4 text-gray-500" />
              <span>{{ owner_user.phone || '-' }}</span>
            </div>
          </div>
          <div v-else class="text-sm text-gray-500">No users assigned to this vendor.</div>
        </div>
      </div>

      <div class="mt-4 rounded-lg bg-white p-4 shadow" v-if="users && users.length">
        <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700">
          <UsersIcon class="h-4 w-4 text-gray-500" />
          <div>Users</div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Name</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Email</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Phone</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500">Role</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="u in users" :key="u.id">
                <td class="px-3 py-2">{{ u.name }}</td>
                <td class="px-3 py-2">{{ u.email || '-' }}</td>
                <td class="px-3 py-2">{{ u.phone || '-' }}</td>
                <td class="px-3 py-2">
                  <span class="rounded bg-gray-100 px-2 py-0.5 text-xs" v-if="u.role">{{ u.role }}</span>
                  <span v-else class="text-gray-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </AppLayout>
</template>
