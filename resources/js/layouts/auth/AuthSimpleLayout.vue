<script setup lang="ts">
import logo from '@/assets/images/logo.png';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-primary p-6 md:p-10">
        <div class="w-full max-w-sm rounded-xl bg-background px-6 py-8 shadow-md sm:px-8 sm:py-10">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex items-center justify-center rounded-md">
                            <img :src="logo" alt="App Logo" class="h-10 w-auto" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
