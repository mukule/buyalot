<script setup lang="ts">
import DistributorLayout from '@/layouts/DistributorLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Package } from 'lucide-vue-next';
</script>

<template>
    <DistributorLayout>
        <Head title="Distributor Dashboard" />
        <div class="p-6">
            <h1 class="text-2xl font-semibold mb-2">Distributor Dashboard</h1>
            <p class="text-muted-foreground mb-6">
                Welcome to your distributor portal. You can switch to your Customer or Seller account using the menu below.
            </p>
            <div class="rounded-lg border bg-card p-6 flex items-center gap-4">
                <Package class="h-10 w-10 text-muted-foreground" />
                <div>
                    <h2 class="font-medium">Portal overview</h2>
                    <p class="text-sm text-muted-foreground">
                        Manage your distributor account from here. Use the sidebar to switch to Customer or Seller portal when needed.
                    </p>
                </div>
            </div>
        </div>
    </DistributorLayout>
</template>
