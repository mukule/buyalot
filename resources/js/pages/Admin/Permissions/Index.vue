<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AppPageProps, Permission } from '@/types';

const page = usePage<AppPageProps<{
  permissions: (Permission & { hashid: string })[];
}>>();

const permissions = computed(() => page.props.permissions);

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Permissions', href: '/admin/permissions' },
];

function goToCreatePage() {
  router.get(route('admin.permissions.create'));
}

function editPermission(hashid: string) {
  router.get(route('admin.permissions.edit', hashid));
}

function deletePermission(hashid: string) {
  if (confirm('Are you sure you want to delete this permission?')) {
    router.delete(route('admin.permissions.destroy', hashid));
  }
}
</script>

<template>
  <Head title="Permissions" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Permissions</h1>
        <button
          @click="goToCreatePage"
          class="rounded-xl bg-primary px-4 py-2 text-white hover:bg-primary-dark"
        >
          + New Permission
        </button>
      </div>

      <!-- Permissions List -->
      <ul class="divide-y divide-gray-200 list-none">
        <li
          v-for="(permission, index) in permissions"
          :key="permission.hashid"
          class="flex items-center justify-between px-4 py-3"
        >
          <div class="flex items-center space-x-3">
            <span class="font-semibold text-gray-600 w-6 text-center">{{ index + 1 }}.</span>
            <span class="font-medium text-gray-800">{{ permission.name }}</span>
          </div>

          <div class="space-x-4">
            <button
              @click="editPermission(permission.hashid)"
              class="text-sm text-primary hover:underline"
            >
              Edit
            </button>
            <button
              @click="deletePermission(permission.hashid)"
              class="text-sm text-red-600 hover:underline"
            >
              Delete
            </button>
          </div>
        </li>
      </ul>
    </div>
  </AppLayout>
</template>
