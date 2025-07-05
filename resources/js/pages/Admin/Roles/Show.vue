<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { Role, Permission, BreadcrumbItem } from '@/types';

// Role now includes hashid string for routing
const props = defineProps<{ role: Role & { permissions: Permission[]; hashid: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Roles', href: '/admin/roles' },
  { title: props.role.name, href: '' },
];

function goToEditRole() {
  router.get(route('admin.roles.edit', props.role.hashid));
}
</script>

<template>
  <Head :title="`Role: ${props.role.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-6 bg-white">
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-semibold">{{ props.role.name }}</h2>
       <button
          @click="goToEditRole"
          class="rounded bg-primary px-4 py-2 text-white hover:bg-secondary transition-colors duration-200 focus:outline-none cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Edit Role
        </button>

      </div>
      <hr />

      <section>
        <h3 class="text-lg font-medium mb-3">Permissions</h3>
        <ul class="space-y-2">
          <li v-if="props.role.permissions.length === 0" class="text-gray-500">
            No permissions assigned.
          </li>
          <li 
            v-for="permission in props.role.permissions" 
            :key="permission.id"
            class="flex items-center"
          >
            <span class="w-2 h-2 rounded-full bg-primary mr-2"></span>
            <span>{{ permission.name }}</span>
          </li>
        </ul>
      </section>
    </div>
  </AppLayout>
</template>
