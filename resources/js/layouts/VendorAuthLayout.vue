<template>
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
        <div class="flex flex-col justify-center px-8 py-12 sm:px-12 lg:px-20 xl:px-32">
            <div class="mx-auto w-full max-w-md space-y-8">
                <div class="flex flex-col gap-2">
                    <Link href="/" class="mb-4 flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <Store class="h-6 w-6" />
                        </div>
                        <span class="text-xl font-bold tracking-tight"
                            >Marketplace <span class="text-sm font-medium text-primary">Merchant</span></span
                        >
                    </Link>

                    <h1 v-if="title" class="text-3xl font-bold tracking-tight text-foreground">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-lg text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />

                <footer class="text-center text-sm text-muted-foreground">
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved. Merchant Support.
                </footer>
            </div>
        </div>

        <div class="relative hidden flex-col justify-center overflow-hidden bg-muted p-12 lg:flex">
            <div class="pointer-events-none absolute inset-0 opacity-5">
                <svg width="100%" height="100%"><rect width="100%" height="100%" fill="url(#grid-pattern)" /></svg>
                <defs>
                    <pattern id="grid-pattern" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1" />
                    </pattern>
                </defs>
            </div>

            <div class="relative z-10 mx-auto max-w-lg">
                <h2 class="mb-8 text-4xl leading-tight font-extrabold">Built for scale. Designed for sellers.</h2>

                <div class="space-y-8">
                    <div class="flex gap-4">
                        <div class="mt-1 rounded-md bg-background p-2 shadow-sm">
                            <BarChart3 class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Real-time Analytics</h3>
                            <p class="text-muted-foreground">Track your store's performance and customer behavior as it happens.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="mt-1 rounded-md bg-background p-2 shadow-sm">
                            <ShieldCheck class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Secure Payments</h3>
                            <p class="text-muted-foreground">Enterprise-grade encryption for all your merchant transactions and payouts.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-12 rounded-2xl border border-primary/10 bg-primary/5 p-6">
                    <p class="text-lg text-foreground/80 italic">
                        "Switching to this portal helped us grow our quarterly sales by 40% thanks to the streamlined inventory tools."
                    </p>
                    <p class="mt-4 text-sm font-bold tracking-widest text-primary uppercase">— Artisan Collective</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Store, ShieldCheck, BarChart3 } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const appName = computed(() => page.props.appName || 'Buyalot');
defineProps<{
    title?: string;
    description?: string;
}>();
</script>
