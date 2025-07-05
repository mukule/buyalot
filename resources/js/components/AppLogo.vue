<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import logo from '@/assets/images/buyalot-02.svg';

const page = usePage();
const appName = page.props.name as string;
</script>

<template>
  <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
    <img :src="logo" alt="App Logo" class="size-8 object-contain" />
  </div>
  <div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{{ appName }}</span>
  </div>
</template>
