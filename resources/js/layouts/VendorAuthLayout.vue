<template>
    <AuthBase title="Log in to your Vendor account" description="Access your vendor dashboard to manage inventory and sales.">
        <Head title="Log in" />
                <div class="w-full">
                    <slot />
                </div>

                <footer class="text-center text-sm text-muted-foreground">
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved. Vendor Support.
                </footer>
    </AuthBase>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthBase from '@/layouts/AuthLayout.vue';

const page = usePage();
const appName = computed(() => page.props.appName || 'Bianlina');

defineProps<{
    title?: string;
    description?: string;
}>();
</script>
