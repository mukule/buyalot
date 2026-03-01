<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, AppPageProps as InertiaPageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<
    InertiaPageProps & {
        title: string;
        zones?: { id: number; name: string; tier: number }[];
        rate: {
            id: number;
            hashid?: string;
            zone_id?: number | null;
            package_size: 'small' | 'medium' | 'large';
            base_price: number;
            door_price?: number;
            door_fallback_price?: number;
            door_fallback_min_km?: number;
            door_extra_km_cost?: number;
            cod_min_amount?: number | null;
            free_shipping_min_amount?: number | null;
            express_price?: number;
        };
    }
>();

const rate = page.props.rate;
const zones = (page.props.zones ?? []) as { id: number; name: string; tier: number }[];
const title = page.props.title || 'Edit Shipping Rate';
const basePath = '/admin/shipping-rates';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Shipping Rates', href: basePath },
    { title, href: '' },
];

// Shipping rate form (use new door/km fields, fallback to door_price for older records)
const form = useForm({
    zone_id: rate.zone_id ?? null,
    package_size: rate.package_size,
    base_price: rate.base_price,
    door_fallback_price: rate.door_fallback_price ?? rate.door_price ?? 250,
    door_fallback_min_km: rate.door_fallback_min_km ?? 10,
    door_extra_km_cost: rate.door_extra_km_cost ?? 20,
    cod_min_amount: rate.cod_min_amount ?? null,
    free_shipping_min_amount: rate.free_shipping_min_amount ?? null,
});

function updateRate() {
    form.put(`${basePath}/${rate.id}`, {
        onSuccess: () => router.get(basePath),
    });
}
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)] shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <router-link href="/admin/shipping-rates" class="text-sm text-[color:var(--primary)] hover:underline"> ← Back </router-link>
                </div>

                <hr class="border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="updateRate" class="space-y-4">
                    <!-- Zone (optional) -->
                    <div v-if="zones.length">
                        <label for="zone_id" class="mb-1 block text-sm font-medium text-gray-700">Zone</label>
                        <select
                            v-model="form.zone_id"
                            id="zone_id"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option :value="null">Default (all zones)</option>
                            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }} (Tier {{ z.tier }})</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Assign to a zone so regions in that zone use this rate's policy rules.</p>
                        <div v-if="form.errors.zone_id" class="mt-1 text-sm text-red-600">{{ form.errors.zone_id }}</div>
                    </div>

                    <!-- Package Size -->
                    <div>
                        <label for="package_size" class="mb-1 block text-sm font-medium text-gray-700"> Package Size </label>
                        <select
                            v-model="form.package_size"
                            id="package_size"
                            required
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                        </select>
                        <div v-if="form.errors.package_size" class="mt-1 text-sm text-red-600">
                            {{ form.errors.package_size }}
                        </div>
                    </div>

                    <!-- Base Price -->
                    <div>
                        <label for="base_price" class="mb-1 block text-sm font-medium text-gray-700"> Standard/Base Price (Ksh) </label>
                        <input
                            v-model.number="form.base_price"
                            id="base_price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="e.g. 50.00"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.base_price" class="mt-1 text-sm text-red-600">
                            {{ form.errors.base_price }}
                        </div>
                    </div>

                    <!-- Door Delivery/KM -->
                    <div class="rounded border border-gray-200 bg-gray-50 p-3">
                        <h4 class="mb-3 text-sm font-semibold text-gray-700">Door Delivery/KM</h4>
                        <div class="space-y-3">
                            <div>
                                <label for="door_fallback_price" class="mb-1 block text-sm font-medium text-gray-700">Standard Fallback Price (Ksh)</label>
                                <input
                                    v-model.number="form.door_fallback_price"
                                    id="door_fallback_price"
                                    type="number"
                                    step="1"
                                    min="0"
                                    required
                                    placeholder="e.g. 250"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">Flat price for first X km</p>
                                <div v-if="form.errors.door_fallback_price" class="mt-1 text-sm text-red-600">{{ form.errors.door_fallback_price }}</div>
                            </div>
                            <div>
                                <label for="door_fallback_min_km" class="mb-1 block text-sm font-medium text-gray-700">Min KM for Fallback Price</label>
                                <input
                                    v-model.number="form.door_fallback_min_km"
                                    id="door_fallback_min_km"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    required
                                    placeholder="e.g. 10"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">First X km charged at fallback price</p>
                                <div v-if="form.errors.door_fallback_min_km" class="mt-1 text-sm text-red-600">{{ form.errors.door_fallback_min_km }}</div>
                            </div>
                            <div>
                                <label for="door_extra_km_cost" class="mb-1 block text-sm font-medium text-gray-700">Extra KM Cost (Ksh/km)</label>
                                <input
                                    v-model.number="form.door_extra_km_cost"
                                    id="door_extra_km_cost"
                                    type="number"
                                    step="1"
                                    min="0"
                                    required
                                    placeholder="e.g. 20"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">Cost per km beyond fallback threshold</p>
                                <div v-if="form.errors.door_extra_km_cost" class="mt-1 text-sm text-red-600">{{ form.errors.door_extra_km_cost }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Policy Rules -->
                    <div class="rounded border border-amber-200 bg-amber-50/50 p-3">
                        <h4 class="mb-3 text-sm font-semibold text-amber-800">Policy Rules</h4>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="cod_min_amount" class="mb-1 block text-sm font-medium text-gray-700">Pay on Delivery min (KSh)</label>
                                <input
                                    v-model.number="form.cod_min_amount"
                                    id="cod_min_amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="e.g. 50000"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">Min order total for Pay on Delivery.</p>
                                <div v-if="form.errors.cod_min_amount" class="mt-1 text-sm text-red-600">{{ form.errors.cod_min_amount }}</div>
                            </div>
                            <div>
                                <label for="free_shipping_min_amount" class="mb-1 block text-sm font-medium text-gray-700">Free shipping min (KSh)</label>
                                <input
                                    v-model.number="form.free_shipping_min_amount"
                                    id="free_shipping_min_amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="e.g. 50000"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <p class="mt-1 text-xs text-gray-500">Min order total for free shipping.</p>
                                <div v-if="form.errors.free_shipping_min_amount" class="mt-1 text-sm text-red-600">{{ form.errors.free_shipping_min_amount }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)]"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Shipping Rate' }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
