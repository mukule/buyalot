<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AppPageProps, Role, Permission } from '@/types';

const page = usePage<AppPageProps<{
  roles: (Role & { hashid: string })[];
  permissions: Permission[];
}>>();

const roles = computed(() => page.props.roles);
const permissions = computed(() => page.props.permissions);

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Roles', href: '/admin/roles' },
];

function goToCreatePage() {
  router.get(route('admin.roles.create'));
}

function goToRoleShow(hashid: string) {
  router.get(route('admin.roles.show', hashid));
}

function deleteRole(hashid: string) {
  if (confirm('Are you sure you want to delete this role?')) {
    router.delete(route('admin.roles.destroy', hashid));
  }
}
</script>

<template>
  <Head title="Roles" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Roles</h1>
        <button
          @click="goToCreatePage"
          class="rounded-xl bg-primary px-4 py-2 text-white hover:bg-primary-dark"
        >
          + New Role
        </button>
      </div>

      <!-- Roles Table -->
      <div class="overflow-x-auto rounded-xl border border-primary">
        <table class="min-w-full table-auto border-collapse text-left text-sm">
          <thead class="bg-primary text-white">
            <tr>
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">Role</th>
              <th class="px-4 py-3">Permissions</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(role, index) in roles"
              :key="role.id"
              class="border-t border-primary/30"
            >
              <td class="px-4 py-3">{{ index + 1 }}</td>
              <td class="px-4 py-3 font-medium">{{ role.name }}</td>
             
              <td class="px-4 py-3 space-x-4">
                <button
                  @click="goToRoleShow(role.hashid)"
                  class="text-sm text-primary hover:underline"
                >
                  Permissions
                </button>
              </td>
              <td>
                <button
                  @click="deleteRole(role.hashid)"
                  class="text-sm text-red-600 hover:underline"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
